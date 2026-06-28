<?php
/**
 * MÓDULO: ACADÉMICO / CIERRE ACADÉMICO
 * ARCHIVO: app/models/AcademicCierreModel.php
 * PROPÓSITO: Cierre formal de ofertas académicas. Verifica solvencia,
 *            3 notas obligatorias (Teórica/Práctica/Virtual), expediente
 *            completo. Profesor por modalidad desde tbl_profesor_modalidad
 *            con contacto desde tbl_personal (fallback a tbl_users).
 * VERSIÓN: 1.2.0 - 3 notas siempre obligatorias. Contacto profesor.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class AcademicCierreModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // OFERTAS DISPONIBLES PARA CIERRE
    // =========================================================================

    public function getOfertas(string $search = ''): array
    {
        $where  = "WHERE ao.status IN ('ABIERTA','CERRADA') AND ao.is_active = 1";
        $params = [];

        if ($search !== '') {
            $where .= " AND (d.name LIKE :search OR c.name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $stmt = $this->db->prepare(
            "SELECT ao.id AS offering_id,
                    d.name AS diplomado_nombre,
                    c.name AS cohorte_nombre,
                    ao.status, ao.enrolled_count, ao.total_cost,
                    (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og2
                     INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                     WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre
             FROM tbl_academic_offerings ao
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes   c ON c.id = ao.cohort_id
             {$where}
             ORDER BY d.name ASC, c.name ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOferta(int $offeringId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ao.id AS offering_id, ao.status, ao.diploma_id, ao.total_cost,
                    d.name AS diplomado_nombre, c.name AS cohorte_nombre,
                    c.cohort_status,
                    (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og2
                     INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                     WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre
             FROM tbl_academic_offerings ao
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes   c ON c.id = ao.cohort_id
             WHERE ao.id = :id"
        );
        $stmt->execute([':id' => $offeringId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // NOTA MÍNIMA
    // =========================================================================

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
    // PROFESORES POR MODALIDAD (para el modal de contacto)
    // =========================================================================

    public function getProfesoresPorModalidad(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT pm.modalidad,
                    prof.full_name,
                    COALESCE(per.email, u.email)            AS email,
                    COALESCE(per.telefono_celular, per.telefono_local) AS telefono
             FROM tbl_profesor_modalidad pm
             INNER JOIN tbl_professors prof ON prof.id = pm.professor_id
             LEFT JOIN tbl_personal per ON per.profesor_id = pm.professor_id
             LEFT JOIN tbl_users u ON u.id = prof.user_id
             WHERE pm.offering_id = :oid
             ORDER BY FIELD(pm.modalidad, 'TEORICA', 'PRACTICA', 'VIRTUAL')"
        );
        $stmt->execute([':oid' => $offeringId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mapa modalidad => datos profesor
        $mapa = ['TEORICA' => null, 'PRACTICA' => null, 'VIRTUAL' => null];
        foreach ($rows as $r) {
            $mapa[$r['modalidad']] = $r;
        }
        return $mapa;
    }

    // =========================================================================
    // ESTUDIANTES CON TODAS SUS CONDICIONES
    // =========================================================================

    public function getEstudiantes(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.id AS enrollment_id,
                    u.first_name, u.last_name, u.document_id, u.phone,
                    e.doc_id_card, e.doc_degree, e.doc_cv,
                    ao.total_cost,
                    IFNULL((SELECT SUM(fl.amount_paid) FROM tbl_financial_student_ledger fl
                            WHERE fl.enrollment_id = e.id AND fl.status != 'ANULADO'), 0) AS total_paid,
                    (SELECT ne.nota FROM tbl_notas_estudiantes ne
                     WHERE ne.enrollment_id = e.id AND ne.offering_id = :oid
                       AND ne.modalidad = 'TEORICA' LIMIT 1) AS nota_teorica,
                    (SELECT ne.nota FROM tbl_notas_estudiantes ne
                     WHERE ne.enrollment_id = e.id AND ne.offering_id = :oid2
                       AND ne.modalidad = 'PRACTICA' LIMIT 1) AS nota_practica,
                    (SELECT ne.nota FROM tbl_notas_estudiantes ne
                     WHERE ne.enrollment_id = e.id AND ne.offering_id = :oid3
                       AND ne.modalidad = 'VIRTUAL' LIMIT 1) AS nota_virtual
             FROM tbl_enrollments e
             INNER JOIN tbl_users u ON e.user_id = u.id
             INNER JOIN tbl_academic_offerings ao ON ao.id = e.offering_id
             WHERE e.offering_id = :oid4
               AND e.status = 'APROBADO'
             ORDER BY u.last_name ASC, u.first_name ASC"
        );
        $stmt->execute([
            ':oid'  => $offeringId,
            ':oid2' => $offeringId,
            ':oid3' => $offeringId,
            ':oid4' => $offeringId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // CERRAR OFERTA
    // =========================================================================

    public function cerrarOferta(int $offeringId, int $userId): void
{
    $this->db->prepare(
        "UPDATE tbl_academic_offerings
         SET status = 'CERRADA', updated_by = :uid WHERE id = :id"
    )->execute([':uid' => $userId, ':id' => $offeringId]);

    // Cambiar cohorte a Finalizada
    $this->db->prepare(
        "UPDATE tbl_cohortes c
         INNER JOIN tbl_academic_offerings ao ON ao.cohort_id = c.id
         SET c.cohort_status = 'Finalizada'
         WHERE ao.id = :id"
    )->execute([':id' => $offeringId]);
}

    // =========================================================================
    // VERIFICAR SI TODOS LOS ESTUDIANTES SON APTOS
    // =========================================================================

    public function todosAptos(int $offeringId, float $notaMinima): bool
    {
        $estudiantes = $this->getEstudiantes($offeringId);
        foreach ($estudiantes as $e) {
            if ((float)$e['total_paid'] < (float)$e['total_cost']) return false;
            if (empty($e['doc_id_card']) || empty($e['doc_degree']) || empty($e['doc_cv'])) return false;
            if ($e['nota_teorica'] === null || $e['nota_practica'] === null || $e['nota_virtual'] === null) return false;
            $promedio = (int) round(((float)$e['nota_teorica'] + (float)$e['nota_practica'] + (float)$e['nota_virtual']) / 3);
            if ($promedio < $notaMinima) return false;
        }
        return true;
    }


    public function reversarCierre(int $offeringId, int $userId, string $motivo): void
{
    // Cambiar cohorte a Reabierta
$this->db->prepare(
    "UPDATE tbl_cohortes c
     INNER JOIN tbl_academic_offerings ao ON ao.cohort_id = c.id
     SET c.cohort_status = 'Reabierta'
     WHERE ao.id = :id"
)->execute([':id' => $offeringId]);

    $this->db->prepare(
        "UPDATE tbl_actas SET estado = 'BORRADOR', aprobada_por = NULL
         WHERE offering_id = :id AND estado = 'APROBADA'"
    )->execute([':id' => $offeringId]);

    $this->db->prepare(
        "INSERT INTO tbl_cierre_reversas (offering_id, motivo, reversado_por)
         VALUES (:oid, :motivo, :uid)"
    )->execute([':oid' => $offeringId, ':motivo' => $motivo, ':uid' => $userId]);
}

public function getHistorialReversas(string $search = ''): array
{
    $where  = "WHERE 1=1";
    $params = [];
    if ($search !== '') {
        $where .= " AND (d.name LIKE :s OR c.name LIKE :s)";
        $params[':s'] = "%{$search}%";
    }
    $stmt = $this->db->prepare(
        "SELECT cr.id, cr.motivo, cr.created_at,
                d.name AS diplomado_nombre, c.name AS cohorte_nombre,
                u.first_name, u.last_name,
                (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                 FROM tbl_academic_offering_groups og2
                 INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                 WHERE og2.offering_id = cr.offering_id AND og2.is_enabled = 1) AS grupos_nombre
         FROM tbl_cierre_reversas cr
         INNER JOIN tbl_academic_offerings ao ON ao.id = cr.offering_id
         INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
         INNER JOIN tbl_cohortes   c ON c.id = ao.cohort_id
         INNER JOIN tbl_users u ON u.id = cr.reversado_por
         {$where}
         ORDER BY cr.created_at DESC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}