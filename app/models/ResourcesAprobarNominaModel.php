<?php
/**
 * MÓDULO: RECURSOS HUMANOS / APROBAR NÓMINAS
 * ARCHIVO: app/models/ResourcesAprobarNominaModel.php
 * PROPÓSITO: Gestión de aprobación de nóminas. Lista nóminas en estado PROCESADA
 *            pendientes de aprobación, muestra el detalle de solo lectura, y al
 *            aprobar genera una Orden de Pago individual por cada persona incluida.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ResourcesAprobarNominaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // LISTADO DE NÓMINAS PROCESADAS (INDEX)
    // =========================================================================

    public function getNominasProcesadas(string $search = '', int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE n.estado = 'PROCESADA'";
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
                ORDER BY n.created_at ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countNominasProcesadas(string $search = ''): int
    {
        $where  = "WHERE estado = 'PROCESADA'";
        $params = [];
        if ($search !== '') {
            $where .= " AND nombre LIKE :search";
            $params[':search'] = "%{$search}%";
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_nominas {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // =========================================================================
    // LISTADO DE NÓMINAS APROBADAS
    // =========================================================================

    public function getNominasAprobadas(string $search = '', int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE n.estado = 'APROBADA'";
        $params = [];

        if ($search !== '') {
            $where .= " AND n.nombre LIKE :search";
            $params[':search'] = "%{$search}%";
        }

        $sql = "SELECT n.id, n.nombre, n.tipo, n.fecha_pago, n.estado, n.created_at,
                       COUNT(np.id) AS total_personal,
                       COALESCE(SUM(np.total_usd), 0) AS total_usd,
                       (SELECT COUNT(*) FROM tbl_ordenes_pago op WHERE op.nomina_id = n.id AND op.estado = 'PAGADA') AS ordenes_pagadas
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

    public function countNominasAprobadas(string $search = ''): int
    {
        $where  = "WHERE estado = 'APROBADA'";
        $params = [];
        if ($search !== '') {
            $where .= " AND nombre LIKE :search";
            $params[':search'] = "%{$search}%";
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_nominas {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function countOrdenesPagadas(int $nominaId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_ordenes_pago WHERE nomina_id = :id AND estado = 'PAGADA'"
        );
        $stmt->execute([':id' => $nominaId]);
        return (int) $stmt->fetchColumn();
    }

    public function reversarAprobacion(int $nominaId, int $userId): void
    {
        $this->db->prepare("DELETE FROM tbl_ordenes_pago WHERE nomina_id = :id")
                 ->execute([':id' => $nominaId]);

        $this->db->prepare(
            "UPDATE tbl_nominas SET estado = 'PROCESADA', updated_by = :uid WHERE id = :id"
        )->execute([':uid' => $userId, ':id' => $nominaId]);
    }

    public function getNominaById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_nominas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // DETALLE DE PERSONAL (SOLO LECTURA)
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
            $p['asignaciones'] = $this->getConceptos('tbl_nomina_personal_asignaciones', (int) $p['id']);
            $p['deducciones']  = $this->getConceptos('tbl_nomina_personal_deducciones', (int) $p['id']);
            $p['sesiones']     = $this->getSesionesVinculadas((int) $p['id']);
        }
        return $personal;
    }

    private function getConceptos(string $tabla, int $nominaPersonalId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$tabla} WHERE nomina_personal_id = :id");
        $stmt->execute([':id' => $nominaPersonalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getSesionesVinculadas(int $nominaPersonalId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.fecha,
                    CASE
                        WHEN s.tipo_horario = 'TEORICO' THEN
                            CONCAT(ht.dia_semana, ' ',
                                   TIME_FORMAT(ht.hora_inicio, '%h:%i %p'), ' – ',
                                   TIME_FORMAT(ht.hora_fin,   '%h:%i %p'))
                        WHEN s.tipo_horario = 'PRACTICA' THEN
                            CONCAT(gp.nombre, ' / ', cm.nombre)
                    END AS horario_desc,
                    d.name AS diplomado_nombre
             FROM tbl_nomina_personal_sesiones nps
             INNER JOIN tbl_sesiones s ON nps.sesion_id = s.id
             LEFT JOIN tbl_horarios_teoricos ht ON s.tipo_horario = 'TEORICO' AND s.horario_id = ht.id
             LEFT JOIN tbl_horarios_practicas hp ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id
             LEFT JOIN tbl_grupos_practica gp ON hp.grupo_id = gp.id
             LEFT JOIN tbl_centros_medicos cm ON hp.centro_medico_id = cm.id
             LEFT JOIN tbl_academic_offerings ao ON COALESCE(ht.offering_id, hp.offering_id) = ao.id
             LEFT JOIN tbl_diplomados d ON ao.diploma_id = d.id
             WHERE nps.nomina_personal_id = :id
             ORDER BY s.fecha ASC"
        );
        $stmt->execute([':id' => $nominaPersonalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // APROBAR NÓMINA → GENERAR ÓRDENES DE PAGO
    // =========================================================================

    public function generarNumeroOrden(): string
    {
        $anio = date('Y');
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_ordenes_pago WHERE numero_orden LIKE :patron"
        );
        $patron = "OP-{$anio}-%";
        $stmt->execute([':patron' => $patron]);
        $siguiente = (int) $stmt->fetchColumn() + 1;
        return sprintf('OP-%s-%04d', $anio, $siguiente);
    }

    public function aprobarNomina(int $nominaId, int $userId): int
    {
        $nomina = $this->getNominaById($nominaId);
        $personal = $this->getPersonalEnNomina($nominaId);

        $stmtInsert = $this->db->prepare(
            "INSERT INTO tbl_ordenes_pago
                (numero_orden, nomina_id, nomina_personal_id, personal_id,
                 monto_usd, tasa_bcv, monto_bs, fecha_pago, estado, created_by)
             VALUES (:num, :nid, :npid, :pid, :usd, :tasa, :bs, :fecha, 'PENDIENTE', :uid)"
        );

        $generadas = 0;
        foreach ($personal as $p) {
            $numero = $this->generarNumeroOrden();
            $stmtInsert->execute([
                ':num'   => $numero,
                ':nid'   => $nominaId,
                ':npid'  => $p['id'],
                ':pid'   => $p['personal_id'],
                ':usd'   => $p['total_usd'],
                ':tasa'  => $p['tasa_bcv'],
                ':bs'    => $p['total_bs'],
                ':fecha' => $nomina['fecha_pago'],
                ':uid'   => $userId,
            ]);
            $generadas++;
        }

        $this->db->prepare(
            "UPDATE tbl_nominas SET estado = 'APROBADA', updated_by = :uid WHERE id = :id"
        )->execute([':uid' => $userId, ':id' => $nominaId]);

        return $generadas;
    }
}