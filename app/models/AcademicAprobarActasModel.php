<?php
/**
 * MÓDULO: ACADÉMICO / APROBAR ACTAS
 * ARCHIVO: app/models/AcademicAprobarActasModel.php
 * PROPÓSITO: Lista actas generadas por profesores con filtro por estado.
 *            Detalle de acta con estudiantes y notas. Aprobar y reversar actas.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class AcademicAprobarActasModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // LISTADO DE ACTAS
    // =========================================================================

    public function getActas(string $estado = '', string $search = '', int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE a.estado IN ('ENVIADA','APROBADA')";
        $params = [];

        if ($estado !== '' && in_array($estado, ['ENVIADA','APROBADA'], true)) {
            $where .= " AND a.estado = :estado";
            $params[':estado'] = $estado;
        }
        if ($search !== '') {
            $where .= " AND (d.name LIKE :search OR c.name LIKE :search OR p.full_name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $stmt = $this->db->prepare(
            "SELECT a.id, a.offering_id, a.modalidad, a.estado, a.created_at, a.updated_at,
                    d.name AS diplomado_nombre,
                    c.name AS cohorte_nombre,
                    p.full_name AS profesor_nombre,
                    (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og2
                     INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                     WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre,
                    (SELECT COUNT(*) FROM tbl_notas_estudiantes ne
                     WHERE ne.offering_id = a.offering_id AND ne.modalidad = a.modalidad) AS total_notas,
                    (SELECT COUNT(*) FROM tbl_notas_estudiantes ne
                     INNER JOIN tbl_diplomados_config dc ON dc.diplomado_id = ao.diploma_id
                     WHERE ne.offering_id = a.offering_id AND ne.modalidad = a.modalidad
                       AND ne.nota >= dc.nota_minima) AS aprobados,
                    u.first_name AS aprobado_first, u.last_name AS aprobado_last
             FROM tbl_actas a
             INNER JOIN tbl_academic_offerings ao ON ao.id = a.offering_id
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes   c ON c.id = ao.cohort_id
             INNER JOIN tbl_users prof_u ON prof_u.id = a.generada_por
             INNER JOIN tbl_professors p ON p.user_id = prof_u.id
             LEFT JOIN tbl_users u ON u.id = a.aprobada_por
             {$where}
             ORDER BY a.estado ASC, a.updated_at DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countActas(string $estado = '', string $search = ''): int
    {
        $where  = "WHERE a.estado IN ('ENVIADA','APROBADA')";
        $params = [];

        if ($estado !== '' && in_array($estado, ['ENVIADA','APROBADA'], true)) {
            $where .= " AND a.estado = :estado";
            $params[':estado'] = $estado;
        }
        if ($search !== '') {
            $where .= " AND (d.name LIKE :search OR c.name LIKE :search OR p.full_name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_actas a
             INNER JOIN tbl_academic_offerings ao ON ao.id = a.offering_id
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes   c ON c.id = ao.cohort_id
             INNER JOIN tbl_users prof_u ON prof_u.id = a.generada_por
             INNER JOIN tbl_professors p ON p.user_id = prof_u.id
             {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // =========================================================================
    // DETALLE DE ACTA
    // =========================================================================

    public function getActaById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.id, a.offering_id, a.modalidad, a.estado, a.created_at, a.updated_at,
                    d.id AS diplomado_id,
                    d.name AS diplomado_nombre,
                    c.name AS cohorte_nombre,
                    ao.general_modality,
                    p.full_name AS profesor_nombre,
                    p.id AS professor_id,
                    (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og2
                     INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                     WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre,
                    u_aprobado.first_name AS aprobado_first,
                    u_aprobado.last_name  AS aprobado_last
             FROM tbl_actas a
             INNER JOIN tbl_academic_offerings ao ON ao.id = a.offering_id
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes   c ON c.id = ao.cohort_id
             INNER JOIN tbl_users prof_u ON prof_u.id = a.generada_por
             INNER JOIN tbl_professors p ON p.user_id = prof_u.id
             LEFT JOIN tbl_users u_aprobado ON u_aprobado.id = a.aprobada_por
             WHERE a.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getEstudiantesConNotas(int $offeringId, string $modalidad): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.id AS enrollment_id,
                    u.first_name, u.last_name, u.document_id,
                    ne.nota,
                    COALESCE(dc.nota_minima, 15.00) AS nota_minima
             FROM tbl_enrollments e
             INNER JOIN tbl_users u ON e.user_id = u.id
             LEFT JOIN tbl_notas_estudiantes ne
                    ON ne.enrollment_id = e.id
                    AND ne.offering_id  = :oid
                    AND ne.modalidad    = :modalidad
             LEFT JOIN tbl_academic_offerings ao ON ao.id = e.offering_id
             LEFT JOIN tbl_diplomados_config dc ON dc.diplomado_id = ao.diploma_id
             WHERE e.offering_id = :oid2
               AND e.status = 'APROBADO'
             ORDER BY u.last_name ASC, u.first_name ASC"
        );
        $stmt->execute([':oid' => $offeringId, ':modalidad' => $modalidad, ':oid2' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNotaMinima(int $offeringId): float
    {
        $stmt = $this->db->prepare(
            "SELECT dc.nota_minima
             FROM tbl_diplomados_config dc
             INNER JOIN tbl_academic_offerings ao ON ao.diploma_id = dc.diplomado_id
             WHERE ao.id = :oid LIMIT 1"
        );
        $stmt->execute([':oid' => $offeringId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (float) $val : 15.00;
    }

    // =========================================================================
    // APROBAR / REVERSAR
    // =========================================================================

    public function aprobarActa(int $id, int $userId): void
    {
        $this->db->prepare(
            "UPDATE tbl_actas
             SET estado = 'APROBADA', aprobada_por = :uid, updated_at = NOW()
             WHERE id = :id AND estado = 'ENVIADA'"
        )->execute([':uid' => $userId, ':id' => $id]);
    }

    public function reversarActa(int $id, int $userId): void
    {
        $this->db->prepare(
            "UPDATE tbl_actas
             SET estado = 'BORRADOR', aprobada_por = NULL, updated_at = NOW()
             WHERE id = :id AND estado IN ('ENVIADA','APROBADA')"
        )->execute([':id' => $id]);
    }
}