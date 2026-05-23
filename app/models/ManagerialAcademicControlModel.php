<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / CONTROL ACADÉMICO
 * ARCHIVO: app/models/ManagerialAcademicControlModel.php
 * PROPÓSITO: Modelo dinámico con consolidación de grupos por oferta (1:N) y prevención de Errores 400.
 * VERSIÓN: 2.0.1 - FIX CRÍTICO: Restauración del nombre original de la tabla tbl_students_certificates.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ManagerialAcademicControlModel 
{
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * FUNCIÓN DINÁMICA: Lee los valores permitidos del ENUM 'status' en tbl_students.
     */
    public function getStudentStatuses(): array {
        try {
            $sql = "SHOW COLUMNS FROM tbl_students LIKE 'status'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return [];
            preg_match("/^enum\(\'(.*)\'\)$/", $row['Type'], $matches);
            return isset($matches[1]) ? explode("','", $matches[1]) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Obtiene la lista de ofertas académicas activas.
     */
    public function getOfferingsList(): array {
        $sql = "SELECT o.id, d.name as diploma_name 
                FROM tbl_academic_offerings o
                INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                WHERE o.status = 'ABIERTA' 
                ORDER BY d.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtiene los grupos vinculados a una oferta específica.
     */
    public function getGroupsByOffering(int $offeringId): array {
        $sql = "SELECT g.id, g.name 
                FROM tbl_grupos g
                INNER JOIN tbl_academic_offering_groups aog ON g.id = aog.group_id
                WHERE aog.offering_id = ? AND g.is_active = 1
                ORDER BY g.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * CONSULTA MAESTRA DE TRAZABILIDAD
     */
    public function getEnrollmentTracking(array $f, int $limit = 25, int $offset = 0): array {
        list($whereSql, $params) = $this->prepareFilters($f);

        $sql = "SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) AS participante,
                    u.document_id AS cedula,
                    d.name AS diplomado,
                    
                    /* CONSOLIDACIÓN DE GRUPOS: Lista todos los grupos de la oferta separados por coma */
                    (SELECT GROUP_CONCAT(g_sub.name ORDER BY g_sub.name ASC SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og_sub
                     INNER JOIN tbl_grupos g_sub ON og_sub.group_id = g_sub.id
                     WHERE og_sub.offering_id = e.offering_id) AS nombre_grupo,

                    CASE 
                        WHEN e.status = 'RECHAZADO' THEN '❌ RECHAZADO'
                        WHEN e.status = 'APROBADO' AND ep.status = 'APPROVED' THEN '✅ EXPEDIENTE APROBADO'
                        WHEN ep.status = 'PENDING' THEN '⏳ PAGO EN REVISIÓN'
                        WHEN e.status IN ('REVISION', 'COMPROMISO') AND ep.status = 'APPROVED' THEN '📂 DOCS. EN REVISIÓN'
                        WHEN ep.id IS NULL THEN '⚠️ SIN PAGO'
                        ELSE e.status
                    END AS trazabilidad_adm_fin,
                    COALESCE(s.student_code, 'PTE. ASIGNAR') AS codigo_estudiante,
                    
                    /* TABLA RESTAURADA A SU NOMBRE ORIGINAL: tbl_students_certificates */
                    (SELECT COUNT(*) FROM tbl_students_certificates WHERE student_id = s.id AND type = 'INSCRIPCION') AS nro_const_inscripcion,
                    (SELECT COUNT(*) FROM tbl_students_certificates WHERE student_id = s.id AND type = 'ESTUDIOS') AS nro_const_estudios,
                    
                    COALESCE(s.status, 'SIN FICHA') AS estatus_ficha,
                    COALESCE(sm.academic_status, 'NO MATRICULADO') AS estatus_matricula
                FROM tbl_enrollments e
                INNER JOIN tbl_users u ON e.user_id = u.id
                INNER JOIN tbl_academic_offerings ao ON e.offering_id = ao.id
                INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                LEFT JOIN tbl_enrollments_payments ep ON e.id = ep.enrollment_id
                LEFT JOIN tbl_students s ON e.id = s.enrollment_id
                LEFT JOIN tbl_student_matriculations sm ON e.id = sm.enrollment_id
                $whereSql
                ORDER BY u.last_name ASC";

        if ($limit > 0) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Contador para paginación.
     */
    public function countEnrollmentTracking(array $f): int {
        list($whereSql, $params) = $this->prepareFilters($f);

        $sql = "SELECT COUNT(e.id) 
                FROM tbl_enrollments e 
                INNER JOIN tbl_users u ON e.user_id = u.id
                LEFT JOIN tbl_students s ON e.id = s.enrollment_id
                LEFT JOIN tbl_student_matriculations sm ON e.id = sm.enrollment_id
                $whereSql";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * MÉTODO PRIVADO: UNIFICACIÓN DE FILTROS
     * Versión 2.5.0 - Sincronizada con los nuevos estados del reporte.
     */
    private function prepareFilters(array $f): array {
        $sql = " WHERE 1=1";
        $params = [];

        // 1. Filtro por Participante / Cédula
        if (!empty($f['student'])) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.document_id LIKE ?)";
            $b = "%{$f['student']}%"; 
            array_push($params, $b, $b, $b);
        }

        // 2. Filtro por Diplomado
        if (isset($f['offering_id']) && $f['offering_id'] !== 'ALL') {
            $sql .= " AND e.offering_id = ?";
            $params[] = (int)$f['offering_id'];
        }

        // 3. Filtro por Grupo
        if (!empty($f['group_id']) && $f['group_id'] !== 'ALL') {
            $sql .= " AND EXISTS (
                        SELECT 1 FROM tbl_academic_offering_groups og_filt
                        WHERE og_filt.offering_id = e.offering_id
                        AND og_filt.group_id = ?
                      )";
            $params[] = (int)$f['group_id'];
        }

        // 4. LÓGICA MAESTRA DE ESTATUS (REPARADA)
        if (!empty($f['participant_status']) && $f['participant_status'] !== 'ALL') {
            switch ($f['participant_status']) {
                case 'MAT_CURSANDO':
                    $sql .= " AND sm.academic_status = 'CURSANDO'";
                    break;
                case 'MAT_PENDIENTE':
                    $sql .= " AND sm.id IS NULL";
                    break;
                case 'FICHA_PENDIENTE':
                    $sql .= " AND s.id IS NULL";
                    break;
                case 'FICHA_ACTIVA':
                    $sql .= " AND s.status = 'ACTIVO'";
                    break;
                case 'RECHAZADO':
                    $sql .= " AND e.status = 'RECHAZADO'";
                    break;
                case 'ASPIRANTE':
                    $sql .= " AND s.id IS NULL AND e.status != 'RECHAZADO'";
                    break;
                default:
                    // Captura estados directos como SUSPENDIDO, RETIRADO o CONGELADO
                    $sql .= " AND s.status = ?";
                    $params[] = $f['participant_status'];
                    break;
            }
        }

        return [$sql, $params];
    }
}