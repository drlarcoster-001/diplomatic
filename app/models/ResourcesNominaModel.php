<?php
/**
 * MÓDULO: RECURSOS HUMANOS / NÓMINA
 * ARCHIVO: app/models/ResourcesNominaModel.php
 * PROPÓSITO: Gestión completa de nóminas. Crear, agregar personal con salario
 *            manual (copiable del contrato), aplicar asignaciones/deducciones
 *            del catálogo, calcular con tasa BCV, y procesar generando snapshot.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ResourcesNominaModel
{
    private PDO $db;

    private const PREFIJOS = [
        'QUINCENAL'  => 'QNC',
        'POR_DIA'    => 'DIA',
        'POR_SESION' => 'SES',
    ];

    private const TIPOS_PERSONAL = [
        'QUINCENAL'  => ['Administrativo', 'Coordinador de Práctica Profesional'],
        'POR_DIA'    => ['Vigilancia', 'Mantenimiento', 'Audio Visual'],
        'POR_SESION' => ['Profesor', 'Coordinador de Entornos Virtuales'],
    ];

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // LISTADO DE NÓMINAS (INDEX)
    // =========================================================================

    public function getNominas(string $search = '', int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $where .= " AND n.nombre LIKE :search";
            $params[':search'] = "%{$search}%";
        }

        $sql = "SELECT n.id, n.nombre, n.tipo, n.fecha_pago, n.estado, n.created_at,
                       COUNT(np.id) AS total_personal,
                       COALESCE(SUM(np.total_usd), 0) AS total_usd,
                       COALESCE(SUM(np.total_bs),  0) AS total_bs
                FROM tbl_nominas n
                LEFT JOIN tbl_nomina_personal np ON np.nomina_id = n.id
                {$where}
                GROUP BY n.id, n.nombre, n.tipo, n.fecha_pago, n.estado, n.created_at
                ORDER BY n.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countNominas(string $search = ''): int
    {
        $where  = "WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $where .= " AND nombre LIKE :search";
            $params[':search'] = "%{$search}%";
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_nominas {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getNominaById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_nominas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // CREAR NÓMINA
    // =========================================================================

    public function generarNombre(string $tipo): string
    {
        $prefijo = self::PREFIJOS[$tipo] ?? 'NOM';
        $mes     = strtoupper(date('M'));
        $anio    = date('Y');

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_nominas
             WHERE nombre LIKE :patron"
        );
        $patron = "{$prefijo}-{$mes}-{$anio}-%";
        $stmt->execute([':patron' => $patron]);
        $siguiente = (int) $stmt->fetchColumn() + 1;

        return sprintf('%s-%s-%s-%02d', $prefijo, $mes, $anio, $siguiente);
    }

    public function createNomina(array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_nominas (nombre, tipo, fecha_pago, estado, created_by)
             VALUES (:nombre, :tipo, :fecha, 'BORRADOR', :uid)"
        );
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':tipo'   => $data['tipo'],
            ':fecha'  => $data['fecha_pago'],
            ':uid'    => $userId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    // =========================================================================
    // BÚSQUEDA DE PERSONAL SEGÚN TIPO DE NÓMINA
    // =========================================================================

    public function buscarPersonalPorTipo(string $tipoNomina, string $search, int $nominaId): array
    {
        $tiposPersonal = self::TIPOS_PERSONAL[$tipoNomina] ?? [];
        if (empty($tiposPersonal)) return [];

        $placeholders = implode(',', array_fill(0, count($tiposPersonal), '?'));
        $params = $tiposPersonal;

        $sql = "SELECT p.id, p.first_name, p.last_name, p.document_id, tp.nombre AS tipo_nombre,
                       (SELECT cfv.valor
                        FROM tbl_contracts c
                        INNER JOIN tbl_contract_template_fields ctf
                               ON ctf.template_id = c.template_id AND ctf.nombre_campo = 'monto_usd'
                        INNER JOIN tbl_contract_field_values cfv
                               ON cfv.contract_id = c.id AND cfv.field_id = ctf.id
                        WHERE c.personal_id = p.id AND c.estado = 'Activo' AND c.is_active = 1
                        ORDER BY c.id DESC LIMIT 1) AS monto_contrato
                FROM tbl_personal p
                INNER JOIN tbl_personal_tipos tp ON p.tipo_personal_id = tp.id
                WHERE p.is_active = 1
                  AND tp.nombre IN ({$placeholders})
                  AND p.id NOT IN (
                      SELECT personal_id FROM tbl_nomina_personal WHERE nomina_id = ?
                  )";
        $params[] = $nominaId;

        if ($search !== '') {
            $sql .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.document_id LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY p.last_name ASC LIMIT 20";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // PERSONAL EN LA NÓMINA (GRID)
    // =========================================================================

    public function getPersonalEnNomina(int $nominaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT np.id, np.personal_id, np.salario_base, np.total_asignaciones,
                    np.total_deducciones, np.total_usd, np.tasa_bcv, np.total_bs, np.estado,
                    p.first_name, p.last_name, p.document_id, tp.nombre AS tipo_nombre
             FROM tbl_nomina_personal np
             INNER JOIN tbl_personal p ON np.personal_id = p.id
             INNER JOIN tbl_personal_tipos tp ON p.tipo_personal_id = tp.id
             WHERE np.nomina_id = :nid
             ORDER BY p.last_name ASC"
        );
        $stmt->execute([':nid' => $nominaId]);
        $personal = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($personal as &$p) {
            $p['asignaciones'] = $this->getAsignacionesByNominaPersonal((int) $p['id']);
            $p['deducciones']  = $this->getDeduccionesByNominaPersonal((int) $p['id']);
        }
        return $personal;
    }

    public function getAsignacionesByNominaPersonal(int $nominaPersonalId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tbl_nomina_personal_asignaciones WHERE nomina_personal_id = :id"
        );
        $stmt->execute([':id' => $nominaPersonalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDeduccionesByNominaPersonal(int $nominaPersonalId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tbl_nomina_personal_deducciones WHERE nomina_personal_id = :id"
        );
        $stmt->execute([':id' => $nominaPersonalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // AGREGAR PERSONAL A LA NÓMINA
    // =========================================================================

    public function getMontoContrato(int $personalId): ?float
    {
        $stmt = $this->db->prepare(
            "SELECT cfv.valor
             FROM tbl_contracts c
             INNER JOIN tbl_contract_template_fields ctf
                    ON ctf.template_id = c.template_id AND ctf.nombre_campo = 'monto_usd'
             INNER JOIN tbl_contract_field_values cfv
                    ON cfv.contract_id = c.id AND cfv.field_id = ctf.id
             WHERE c.personal_id = :pid AND c.estado = 'Activo' AND c.is_active = 1
             ORDER BY c.id DESC LIMIT 1"
        );
        $stmt->execute([':pid' => $personalId]);
        $val = $stmt->fetchColumn();
        return $val !== false && $val !== null ? (float) $val : null;
    }

    public function getTasaBcvActual(): float
    {
        $stmt = $this->db->prepare(
            "SELECT dolar_bcv FROM tbl_financial_exchange_rates
             WHERE status = 'ACTIVE'
             ORDER BY rate_date DESC LIMIT 1"
        );
        $stmt->execute();
        $val = $stmt->fetchColumn();
        return $val !== false ? (float) $val : 0;
    }

    // =========================================================================
    // COLA DE PROFESORES CON SESIONES PENDIENTES DE PAGO (POR_SESION)
    // =========================================================================

    public function getProfesoresConSesionesPendientes(int $nominaId): array
    {
        $sql = "SELECT p.id, p.first_name, p.last_name, p.document_id, tp.nombre AS tipo_nombre,
                       COUNT(s.id) AS sesiones_pendientes,
                       (SELECT cfv.valor
                        FROM tbl_contracts c
                        INNER JOIN tbl_contract_template_fields ctf
                               ON ctf.template_id = c.template_id AND ctf.nombre_campo = 'monto_usd'
                        INNER JOIN tbl_contract_field_values cfv
                               ON cfv.contract_id = c.id AND cfv.field_id = ctf.id
                        WHERE c.personal_id = p.id AND c.estado = 'Activo' AND c.is_active = 1
                        ORDER BY c.id DESC LIMIT 1) AS tarifa
                FROM tbl_personal p
                INNER JOIN tbl_personal_tipos tp ON p.tipo_personal_id = tp.id
                INNER JOIN tbl_sesiones s
                       ON s.personal_id = p.id AND s.estado = 'DICTADA' AND s.is_active = 1
                LEFT JOIN tbl_nomina_personal_sesiones nps ON nps.sesion_id = s.id
                WHERE p.is_active = 1
                  AND tp.nombre IN ('Profesor', 'Coordinador de Entornos Virtuales')
                  AND nps.id IS NULL
                  AND p.id NOT IN (SELECT personal_id FROM tbl_nomina_personal WHERE nomina_id = ?)
                GROUP BY p.id, p.first_name, p.last_name, p.document_id, tp.nombre
                HAVING sesiones_pendientes > 0
                ORDER BY sesiones_pendientes DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nominaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countSesionesPendientesGlobal(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM tbl_sesiones s
             LEFT JOIN tbl_nomina_personal_sesiones nps ON nps.sesion_id = s.id
             WHERE s.estado = 'DICTADA' AND s.is_active = 1 AND nps.id IS NULL"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function getSesionesPendientesIds(int $personalId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id
             FROM tbl_sesiones s
             LEFT JOIN tbl_nomina_personal_sesiones nps ON nps.sesion_id = s.id
             WHERE s.personal_id = ? AND s.estado = 'DICTADA' AND s.is_active = 1
               AND nps.id IS NULL"
        );
        $stmt->execute([$personalId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getSesionesPendientesDetalle(int $personalId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id, s.fecha, s.tipo_horario,
                    CASE
                        WHEN s.tipo_horario = 'TEORICO' THEN
                            CONCAT(ht.dia_semana, ' ',
                                   TIME_FORMAT(ht.hora_inicio, '%h:%i %p'), ' – ',
                                   TIME_FORMAT(ht.hora_fin,   '%h:%i %p'))
                        WHEN s.tipo_horario = 'PRACTICA' THEN
                            CONCAT(gp.nombre, ' / ', cm.nombre)
                    END AS horario_desc,
                    d.name AS diplomado_nombre
             FROM tbl_sesiones s
             LEFT JOIN tbl_nomina_personal_sesiones nps ON nps.sesion_id = s.id
             LEFT JOIN tbl_horarios_teoricos ht ON s.tipo_horario = 'TEORICO' AND s.horario_id = ht.id
             LEFT JOIN tbl_horarios_practicas hp ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id
             LEFT JOIN tbl_grupos_practica gp ON hp.grupo_id = gp.id
             LEFT JOIN tbl_centros_medicos cm ON hp.centro_medico_id = cm.id
             LEFT JOIN tbl_academic_offerings ao ON COALESCE(ht.offering_id, hp.offering_id) = ao.id
             LEFT JOIN tbl_diplomados d ON ao.diploma_id = d.id
             WHERE s.personal_id = ? AND s.estado = 'DICTADA' AND s.is_active = 1
               AND nps.id IS NULL
             ORDER BY s.fecha ASC"
        );
        $stmt->execute([$personalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function linkSesionesToNominaPersonal(int $nominaPersonalId, array $sesionIds): void
    {
        if (empty($sesionIds)) return;
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_nomina_personal_sesiones (nomina_personal_id, sesion_id) VALUES (?, ?)"
        );
        foreach ($sesionIds as $sid) {
            $stmt->execute([$nominaPersonalId, (int) $sid]);
        }
    }

    public function addPersonalToNomina(int $nominaId, int $personalId, float $salarioBase, float $tasaBcv, int $userId): int
    {
        $totalUsd = $salarioBase;
        $totalBs  = $totalUsd * $tasaBcv;

        $stmt = $this->db->prepare(
            "INSERT INTO tbl_nomina_personal
                (nomina_id, personal_id, salario_base, total_asignaciones, total_deducciones,
                 total_usd, tasa_bcv, total_bs, estado, created_by)
             VALUES (:nid, :pid, :salario, 0, 0, :tusd, :tasa, :tbs, 'PENDIENTE', :uid)"
        );
        $stmt->execute([
            ':nid'     => $nominaId,
            ':pid'     => $personalId,
            ':salario' => $salarioBase,
            ':tusd'    => $totalUsd,
            ':tasa'    => $tasaBcv,
            ':tbs'     => $totalBs,
            ':uid'     => $userId,
        ]);
        $npId = (int) $this->db->lastInsertId();

        // Asignación SALARIO_BASE automática (snapshot)
        $this->db->prepare(
            "INSERT INTO tbl_nomina_personal_asignaciones (nomina_personal_id, asignacion_id, nombre_concepto, monto)
             VALUES (:npid, NULL, 'Salario Base', :monto)"
        )->execute([':npid' => $npId, ':monto' => $salarioBase]);

        return $npId;
    }

    public function removePersonalFromNomina(int $nominaPersonalId): void
    {
        $this->db->prepare("DELETE FROM tbl_nomina_personal_sesiones WHERE nomina_personal_id = :id")
                 ->execute([':id' => $nominaPersonalId]);
        $this->db->prepare("DELETE FROM tbl_nomina_personal_asignaciones WHERE nomina_personal_id = :id")
                 ->execute([':id' => $nominaPersonalId]);
        $this->db->prepare("DELETE FROM tbl_nomina_personal_deducciones WHERE nomina_personal_id = :id")
                 ->execute([':id' => $nominaPersonalId]);
        $this->db->prepare("DELETE FROM tbl_nomina_personal WHERE id = :id")
                 ->execute([':id' => $nominaPersonalId]);
    }

    // =========================================================================
    // ASIGNACIONES / DEDUCCIONES POR PERSONA
    // =========================================================================

    public function getCatalogoAsignaciones(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_asignaciones WHERE is_active = 1 ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCatalogoDeducciones(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_deducciones WHERE is_active = 1 ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addAsignacionToPersonal(int $nominaPersonalId, ?int $asignacionId, string $nombre, float $monto): void
    {
        $this->db->prepare(
            "INSERT INTO tbl_nomina_personal_asignaciones (nomina_personal_id, asignacion_id, nombre_concepto, monto)
             VALUES (:npid, :aid, :nombre, :monto)"
        )->execute([
            ':npid' => $nominaPersonalId, ':aid' => $asignacionId,
            ':nombre' => $nombre, ':monto' => $monto,
        ]);
        $this->recalcularTotales($nominaPersonalId);
    }

    public function addDeduccionToPersonal(int $nominaPersonalId, ?int $deduccionId, string $nombre, float $monto): void
    {
        $this->db->prepare(
            "INSERT INTO tbl_nomina_personal_deducciones (nomina_personal_id, deduccion_id, nombre_concepto, monto)
             VALUES (:npid, :did, :nombre, :monto)"
        )->execute([
            ':npid' => $nominaPersonalId, ':did' => $deduccionId,
            ':nombre' => $nombre, ':monto' => $monto,
        ]);
        $this->recalcularTotales($nominaPersonalId);
    }

    public function deleteAsignacionConcepto(int $id, int $nominaPersonalId): void
    {
        $this->db->prepare("DELETE FROM tbl_nomina_personal_asignaciones WHERE id = :id")->execute([':id' => $id]);
        $this->recalcularTotales($nominaPersonalId);
    }

    public function deleteDeduccionConcepto(int $id, int $nominaPersonalId): void
    {
        $this->db->prepare("DELETE FROM tbl_nomina_personal_deducciones WHERE id = :id")->execute([':id' => $id]);
        $this->recalcularTotales($nominaPersonalId);
    }

    private function recalcularTotales(int $nominaPersonalId): void
    {
        $stmtA = $this->db->prepare(
            "SELECT COALESCE(SUM(monto),0) FROM tbl_nomina_personal_asignaciones WHERE nomina_personal_id = :id"
        );
        $stmtA->execute([':id' => $nominaPersonalId]);
        $totalAsig = (float) $stmtA->fetchColumn();

        $stmtD = $this->db->prepare(
            "SELECT COALESCE(SUM(monto),0) FROM tbl_nomina_personal_deducciones WHERE nomina_personal_id = :id"
        );
        $stmtD->execute([':id' => $nominaPersonalId]);
        $totalDed = (float) $stmtD->fetchColumn();

        $stmtTasa = $this->db->prepare("SELECT tasa_bcv FROM tbl_nomina_personal WHERE id = :id");
        $stmtTasa->execute([':id' => $nominaPersonalId]);
        $tasa = (float) $stmtTasa->fetchColumn();

        $totalUsd = $totalAsig - $totalDed;
        $totalBs  = $totalUsd * $tasa;

        $this->db->prepare(
            "UPDATE tbl_nomina_personal
             SET total_asignaciones = :ta, total_deducciones = :td,
                 total_usd = :tu, total_bs = :tb
             WHERE id = :id"
        )->execute([
            ':ta' => $totalAsig, ':td' => $totalDed,
            ':tu' => $totalUsd, ':tb' => $totalBs,
            ':id' => $nominaPersonalId,
        ]);
    }

    // =========================================================================
    // PROCESAR NÓMINA
    // =========================================================================

    public function procesarNomina(int $nominaId, int $userId): void
    {
        $this->db->prepare(
            "UPDATE tbl_nominas SET estado = 'PROCESADA', updated_by = :uid WHERE id = :id"
        )->execute([':uid' => $userId, ':id' => $nominaId]);

        $this->db->prepare(
            "UPDATE tbl_nomina_personal SET estado = 'PROCESADO' WHERE nomina_id = :id"
        )->execute([':id' => $nominaId]);
    }

    public function countPersonalEnNomina(int $nominaId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_nomina_personal WHERE nomina_id = :id");
        $stmt->execute([':id' => $nominaId]);
        return (int) $stmt->fetchColumn();
    }

    // =========================================================================
    // DESCARTAR / REVERSAR NÓMINA
    // =========================================================================

    public function descartarNomina(int $nominaId): void
    {
        $stmt = $this->db->prepare("SELECT id FROM tbl_nomina_personal WHERE nomina_id = :id");
        $stmt->execute([':id' => $nominaId]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($ids as $npId) {
            $this->db->prepare("DELETE FROM tbl_nomina_personal_sesiones WHERE nomina_personal_id = :id")
                     ->execute([':id' => $npId]);
            $this->db->prepare("DELETE FROM tbl_nomina_personal_asignaciones WHERE nomina_personal_id = :id")
                     ->execute([':id' => $npId]);
            $this->db->prepare("DELETE FROM tbl_nomina_personal_deducciones WHERE nomina_personal_id = :id")
                     ->execute([':id' => $npId]);
        }
        $this->db->prepare("DELETE FROM tbl_nomina_personal WHERE nomina_id = :id")->execute([':id' => $nominaId]);
        $this->db->prepare("DELETE FROM tbl_nominas WHERE id = :id")->execute([':id' => $nominaId]);
    }

    public function countOrdenesPagadas(int $nominaId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_ordenes_pago WHERE nomina_id = :id AND estado = 'PAGADA'"
        );
        $stmt->execute([':id' => $nominaId]);
        return (int) $stmt->fetchColumn();
    }

    public function reversarNomina(int $nominaId, int $userId): void
    {
        $nomina = $this->getNominaById($nominaId);

        if ($nomina && $nomina['estado'] === 'APROBADA') {
            // Eliminar órdenes de pago generadas y regresar a PROCESADA
            $this->db->prepare("DELETE FROM tbl_ordenes_pago WHERE nomina_id = :id")
                     ->execute([':id' => $nominaId]);

            $this->db->prepare(
                "UPDATE tbl_nominas SET estado = 'PROCESADA', updated_by = :uid WHERE id = :id"
            )->execute([':uid' => $userId, ':id' => $nominaId]);

            return;
        }

        $this->db->prepare(
            "UPDATE tbl_nominas SET estado = 'BORRADOR', updated_by = :uid WHERE id = :id"
        )->execute([':uid' => $userId, ':id' => $nominaId]);

        $this->db->prepare(
            "UPDATE tbl_nomina_personal SET estado = 'PENDIENTE' WHERE nomina_id = :id"
        )->execute([':id' => $nominaId]);
    }

    // =========================================================================
    // EDITAR SALARIO BASE DE UNA PERSONA YA AGREGADA
    // =========================================================================

    public function updateSalarioPersonal(int $nominaPersonalId, float $nuevoSalario): void
    {
        $this->db->prepare(
            "UPDATE tbl_nomina_personal SET salario_base = :sal WHERE id = :id"
        )->execute([':sal' => $nuevoSalario, ':id' => $nominaPersonalId]);

        $this->db->prepare(
            "UPDATE tbl_nomina_personal_asignaciones
             SET monto = :sal
             WHERE nomina_personal_id = :id AND asignacion_id IS NULL AND nombre_concepto = 'Salario Base'"
        )->execute([':sal' => $nuevoSalario, ':id' => $nominaPersonalId]);

        $this->recalcularTotales($nominaPersonalId);
    }
}