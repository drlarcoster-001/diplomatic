<?php
/**
 * MÓDULO: RECURSOS HUMANOS / PROCESAR SESIONES
 * ARCHIVO: app/models/ResourcesProcesarSesionesModel.php
 * PROPÓSITO: Gestión del procesamiento de sesiones programadas. Obtiene ofertas con
 *            sesiones pendientes, sesiones por oferta, estudiantes matriculados con
 *            su asistencia, y procesa el marcado de asistencia generando el cambio
 *            de estado PROGRAMADA → DICTADA en tbl_sesiones.
* VERSIÓN: 2.2.0 - Agrega filtro por período, columna grupos y método getPeriodos.
 *          reiniciarAsistencia() para limpiar tbl_sesion_asistencia desde el admin.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ResourcesProcesarSesionesModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // OFERTAS CON SESIONES PROGRAMADAS (INDEX)
    // =========================================================================

    public function getOfertasConSesiones(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE ao.is_active = 1
                    AND ao.status IN ('ABIERTA', 'EN CURSO')
                    AND s.is_active = 1";
        $params = [];

        $search = $filters['search'] ?? '';
        $periodoId = $filters['periodo_id'] ?? null;

        if ($periodoId) {
            $where .= " AND c.periodo_id = :periodo_id";
            $params[':periodo_id'] = $periodoId;
        }

        if ($search !== '') {
            $where .= " AND (d.name LIKE :search1 OR c.name LIKE :search2)";
            $params[':search1'] = "%{$search}%";
            $params[':search2'] = "%{$search}%";
        }
        $diplomaId = $filters['diploma_id'] ?? null;
        if ($diplomaId) {
            $where .= " AND ao.diploma_id = :diploma_id";
            $params[':diploma_id'] = $diplomaId;
        }
        $sql = "SELECT ao.id AS offering_id,
               d.name AS diplomado_nombre,
               c.name AS cohorte_nombre,
               ao.general_modality,
               ao.status,
               (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                FROM tbl_academic_offering_groups og2
                INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre,
               SUM(CASE WHEN s.estado = 'PROGRAMADA' THEN 1 ELSE 0 END) AS sesiones_pendientes,
               SUM(CASE WHEN s.estado = 'DICTADA'    THEN 1 ELSE 0 END) AS sesiones_dictadas
                FROM tbl_academic_offerings ao
                INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                INNER JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
                INNER JOIN tbl_sesiones s ON s.is_active = 1
                LEFT JOIN tbl_horarios_teoricos ht
                       ON s.tipo_horario = 'TEORICO' AND s.horario_id = ht.id AND ht.offering_id = ao.id
                LEFT JOIN tbl_horarios_practicas hp
                       ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id AND hp.offering_id = ao.id
                {$where}
                AND (ht.id IS NOT NULL OR hp.id IS NOT NULL)
                GROUP BY ao.id, d.name, c.name, ao.general_modality, ao.status
                ORDER BY d.name ASC, c.name ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countOfertas(array $filters = []): int
    {
        $where  = "WHERE ao.is_active = 1
             AND ao.status IN ('ABIERTA', 'EN CURSO')
             AND s.is_active = 1";
            $params = [];

            $search = $filters['search'] ?? '';
            $periodoId = $filters['periodo_id'] ?? null;

            if ($periodoId) {
                $where .= " AND c.periodo_id = :periodo_id";
                $params[':periodo_id'] = $periodoId;
            }

            if ($search !== '') {
                $where .= " AND (d.name LIKE :search1 OR c.name LIKE :search2)";
                $params[':search1'] = "%{$search}%";
                $params[':search2'] = "%{$search}%";
            }
            $diplomaId = $filters['diploma_id'] ?? null;
            if ($diplomaId) {
                $where .= " AND ao.diploma_id = :diploma_id";
                $params[':diploma_id'] = $diplomaId;
            }
        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT ao.id)
             FROM tbl_academic_offerings ao
             INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
             INNER JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
             INNER JOIN tbl_sesiones s ON s.is_active = 1
             LEFT JOIN tbl_horarios_teoricos ht
                    ON s.tipo_horario = 'TEORICO' AND s.horario_id = ht.id AND ht.offering_id = ao.id
             LEFT JOIN tbl_horarios_practicas hp
                    ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id AND hp.offering_id = ao.id
             {$where}
             AND (ht.id IS NOT NULL OR hp.id IS NOT NULL)"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getOferta(int $offeringId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ao.id, d.name AS diplomado_nombre, c.name AS cohorte_nombre,
                    ao.general_modality, ao.status
             FROM tbl_academic_offerings ao
             INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
             INNER JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
             WHERE ao.id = :id"
        );
        $stmt->execute([':id' => $offeringId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // SESIONES DE LA OFERTA — incluye tiene_asistencia para el indicador visual
    // =========================================================================

    public function getSesionesByOffering(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id, s.tipo_horario, s.horario_id, s.fecha, s.estado,
                    p.first_name, p.last_name, p.document_id AS personal_doc,
                    CASE
                        WHEN s.tipo_horario = 'TEORICO' THEN
                            CONCAT(ht.dia_semana, ' ',
                                   TIME_FORMAT(ht.hora_inicio, '%h:%i %p'), ' – ',
                                   TIME_FORMAT(ht.hora_fin,   '%h:%i %p'))
                        WHEN s.tipo_horario = 'PRACTICA' THEN
                            CONCAT(gp.nombre, ' / ', cm.nombre)
                    END AS horario_desc,
                    (SELECT COUNT(*) FROM tbl_sesion_asistencia
                     WHERE sesion_id = s.id) AS tiene_asistencia
             FROM tbl_sesiones s
             INNER JOIN tbl_personal p ON s.personal_id = p.id
             LEFT JOIN tbl_horarios_teoricos ht
                    ON s.tipo_horario = 'TEORICO' AND s.horario_id = ht.id
             LEFT JOIN tbl_horarios_practicas hp
                    ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id
             LEFT JOIN tbl_grupos_practica gp ON hp.grupo_id = gp.id
             LEFT JOIN tbl_centros_medicos cm ON hp.centro_medico_id = cm.id
             WHERE COALESCE(ht.offering_id, hp.offering_id) = :oid
               AND s.estado = 'PROGRAMADA'
               AND s.is_active = 1
             ORDER BY s.fecha ASC, p.last_name ASC"
        );
        $stmt->execute([':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSesionById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, p.first_name, p.last_name, p.document_id AS personal_doc,
                    CASE
                        WHEN s.tipo_horario = 'TEORICO' THEN
                            CONCAT(ht.dia_semana, ' ',
                                   TIME_FORMAT(ht.hora_inicio, '%h:%i %p'), ' – ',
                                   TIME_FORMAT(ht.hora_fin,   '%h:%i %p'))
                        WHEN s.tipo_horario = 'PRACTICA' THEN
                            CONCAT(gp.nombre, ' / ', cm.nombre)
                    END AS horario_desc,
                    d.name AS diplomado_nombre,
                    c.name AS cohorte_nombre,
                    ao.id  AS offering_id
             FROM tbl_sesiones s
             INNER JOIN tbl_personal p ON s.personal_id = p.id
             LEFT JOIN tbl_horarios_teoricos ht
                    ON s.tipo_horario = 'TEORICO' AND s.horario_id = ht.id
             LEFT JOIN tbl_horarios_practicas hp
                    ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id
             LEFT JOIN tbl_grupos_practica gp ON hp.grupo_id = gp.id
             LEFT JOIN tbl_centros_medicos cm ON hp.centro_medico_id = cm.id
             LEFT JOIN tbl_academic_offerings ao
                    ON COALESCE(ht.offering_id, hp.offering_id) = ao.id
             LEFT JOIN tbl_diplomados d ON ao.diploma_id = d.id
             LEFT JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
             WHERE s.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // SESIONES DICTADAS
    // =========================================================================

    public function getSesionesDictadasByOffering(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id, s.tipo_horario, s.horario_id, s.fecha, s.estado,
                    p.first_name, p.last_name, p.document_id AS personal_doc,
                    CASE
                        WHEN s.tipo_horario = 'TEORICO' THEN
                            CONCAT(ht.dia_semana, ' ',
                                   TIME_FORMAT(ht.hora_inicio, '%h:%i %p'), ' – ',
                                   TIME_FORMAT(ht.hora_fin,   '%h:%i %p'))
                        WHEN s.tipo_horario = 'PRACTICA' THEN
                            CONCAT(gp.nombre, ' / ', cm.nombre)
                    END AS horario_desc,
                    (CASE WHEN nps.id IS NULL THEN 0 ELSE 1 END) AS en_nomina
             FROM tbl_sesiones s
             INNER JOIN tbl_personal p ON s.personal_id = p.id
             LEFT JOIN tbl_horarios_teoricos ht
                    ON s.tipo_horario = 'TEORICO' AND s.horario_id = ht.id
             LEFT JOIN tbl_horarios_practicas hp
                    ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id
             LEFT JOIN tbl_grupos_practica gp ON hp.grupo_id = gp.id
             LEFT JOIN tbl_centros_medicos cm ON hp.centro_medico_id = cm.id
             LEFT JOIN tbl_nomina_personal_sesiones nps ON nps.sesion_id = s.id
             WHERE COALESCE(ht.offering_id, hp.offering_id) = :oid
               AND s.estado = 'DICTADA'
               AND s.is_active = 1
             ORDER BY s.fecha DESC, p.last_name ASC"
        );
        $stmt->execute([':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function estaEnNomina(int $sesionId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_nomina_personal_sesiones WHERE sesion_id = :id"
        );
        $stmt->execute([':id' => $sesionId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function reversarSesion(int $sesionId, int $userId): void
    {
        $this->db->prepare(
            "UPDATE tbl_sesiones SET estado = 'PROGRAMADA', updated_by = :uid WHERE id = :id"
        )->execute([':uid' => $userId, ':id' => $sesionId]);

        $this->db->prepare(
            "DELETE FROM tbl_sesion_asistencia WHERE sesion_id = :id"
        )->execute([':id' => $sesionId]);
    }

    // =========================================================================
    // REINICIAR ASISTENCIA — borra tbl_sesion_asistencia sin cambiar estado
    // =========================================================================

    public function reiniciarAsistencia(int $sesionId, int $userId): void
    {
        $this->db->prepare(
            "DELETE FROM tbl_sesion_asistencia WHERE sesion_id = :id"
        )->execute([':id' => $sesionId]);

        $this->db->prepare(
            "UPDATE tbl_sesiones SET updated_by = :uid WHERE id = :id"
        )->execute([':uid' => $userId, ':id' => $sesionId]);
    }

    // =========================================================================
    // ESTUDIANTES MATRICULADOS CON ASISTENCIA
    // =========================================================================

    public function getEstudiantesConAsistencia(int $sesionId, int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.id AS enrollment_id,
                    u.first_name, u.last_name, u.document_id,
                    COALESCE(sa.asistio, 1) AS asistio,
                    sa.id AS asistencia_id
             FROM tbl_enrollments e
             INNER JOIN tbl_users u ON e.user_id = u.id
             LEFT JOIN tbl_sesion_asistencia sa
                    ON sa.sesion_id = :sid AND sa.enrollment_id = e.id
             WHERE e.offering_id = :oid AND e.status = 'APROBADO'
             ORDER BY u.last_name ASC, u.first_name ASC"
        );
        $stmt->execute([':sid' => $sesionId, ':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // PROCESAR SESIÓN
    // =========================================================================

    public function procesarSesion(int $sesionId, array $asistencia, int $userId): void
    {
        $this->db->prepare(
            "UPDATE tbl_sesiones SET estado = 'DICTADA', updated_by = :uid WHERE id = :id"
        )->execute([':uid' => $userId, ':id' => $sesionId]);

        $stmt = $this->db->prepare(
            "INSERT INTO tbl_sesion_asistencia (sesion_id, enrollment_id, asistio, created_by)
             VALUES (:sid, :eid, :asistio, :uid)
             ON DUPLICATE KEY UPDATE asistio = VALUES(asistio)"
        );
        foreach ($asistencia as $enrollmentId => $asistio) {
            $stmt->execute([
                ':sid'     => $sesionId,
                ':eid'     => (int) $enrollmentId,
                ':asistio' => $asistio ? 1 : 0,
                ':uid'     => $userId,
            ]);
        }
    }

    // =========================================================================
    // DATOS PARA PDF
    // =========================================================================

    public function getDatosAsistenciaPdf(int $sesionId): ?array
    {
        $sesion = $this->getSesionById($sesionId);
        if (!$sesion) return null;

        $estudiantes = $this->getEstudiantesConAsistencia($sesionId, (int) $sesion['offering_id']);
        return ['sesion' => $sesion, 'estudiantes' => $estudiantes];
    }

    public function getDiplomadosPorPeriodo(int $periodoId = 0): array
    {
        $where = $periodoId ? "AND c.periodo_id = :periodo_id" : "";
        $stmt = $this->db->prepare(
            "SELECT DISTINCT d.id, d.name
             FROM tbl_diplomados d
             INNER JOIN tbl_academic_offerings ao ON ao.diploma_id = d.id
             INNER JOIN tbl_cohortes c ON c.id = ao.cohort_id
             WHERE ao.is_active = 1
               AND ao.status IN ('ABIERTA','EN CURSO')
               {$where}
             ORDER BY d.name ASC"
        );
        $params = [];
        if ($periodoId) $params[':periodo_id'] = $periodoId;
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPeriodos(): array
{
    $stmt = $this->db->query(
        "SELECT id, nombre, estado FROM tbl_periodos_cohorte
         WHERE is_active = 1 ORDER BY id DESC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}