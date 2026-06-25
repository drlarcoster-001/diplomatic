<?php
/**
 * MÓDULO: ACADÉMICO / HISTÓRICO
 * ARCHIVO: app/models/AcademicHistoricoModel.php
 * PROPÓSITO: Obtiene ofertas CERRADAS con sus estudiantes, notas finales
 *            y resultado. Filtros por diplomado, cohorte y año.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class AcademicHistoricoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // OFERTAS CERRADAS
    // =========================================================================

    public function getOfertas(string $search = ''): array
    {
        $where  = "WHERE ao.status = 'CERRADA' AND ao.is_active = 1";
        $params = [];

        if ($search !== '') {
            $where .= " AND (d.name LIKE :s OR c.name LIKE :s)";
            $params[':s'] = "%{$search}%";
        }

        $stmt = $this->db->prepare(
            "SELECT ao.id AS offering_id,
                    d.name AS diplomado_nombre,
                    c.name AS cohorte_nombre,
                    ao.enrolled_count,
                    ao.updated_at AS fecha_cierre,
                    (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og2
                     INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                     WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre,
                    COALESCE(dc.nota_minima, 15.00) AS nota_minima
             FROM tbl_academic_offerings ao
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes   c ON c.id = ao.cohort_id
             LEFT JOIN tbl_diplomados_config dc ON dc.diplomado_id = ao.diploma_id
             {$where}
             ORDER BY ao.updated_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // DETALLE — ESTUDIANTES CON NOTAS Y RESULTADO
    // =========================================================================

    public function getEstudiantes(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.id AS enrollment_id,
                    u.first_name, u.last_name, u.document_id,
                    COALESCE(dc.nota_minima, 15.00) AS nota_minima,
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
             LEFT JOIN tbl_diplomados_config dc ON dc.diplomado_id = ao.diploma_id
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
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$e) {
            $notas = [];
            if ($e['nota_teorica']  !== null) $notas[] = (float)$e['nota_teorica'];
            if ($e['nota_practica'] !== null) $notas[] = (float)$e['nota_practica'];
            if ($e['nota_virtual']  !== null) $notas[] = (float)$e['nota_virtual'];

            $notaTeorica  = $e['nota_teorica']  !== null ? (float)$e['nota_teorica']  : 0;
            $notaPractica = $e['nota_practica'] !== null ? (float)$e['nota_practica'] : 0;
            $notaVirtual  = $e['nota_virtual']  !== null ? (float)$e['nota_virtual']  : 0;
            $e['nota_final'] = (int) round(($notaTeorica + $notaPractica + $notaVirtual) / 3);
            $e['aprobado'] = $e['nota_final'] !== null && $e['nota_final'] >= (float)$e['nota_minima'];
        }
        unset($e);

        return $rows;
    }

    // =========================================================================
    // DATOS DE UNA OFERTA
    // =========================================================================

    public function getOferta(int $offeringId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ao.id AS offering_id, ao.updated_at AS fecha_cierre,
                    d.name AS diplomado_nombre, c.name AS cohorte_nombre,
                    COALESCE(dc.nota_minima, 15.00) AS nota_minima,
                    (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og2
                     INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                     WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre
             FROM tbl_academic_offerings ao
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes   c ON c.id = ao.cohort_id
             LEFT JOIN tbl_diplomados_config dc ON dc.diplomado_id = ao.diploma_id
             WHERE ao.id = :id AND ao.status = 'CERRADA'"
        );
        $stmt->execute([':id' => $offeringId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}