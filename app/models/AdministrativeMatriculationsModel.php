<?php
/**
 * MÓDULO: ADMINISTRATIVO / MATRÍCULAS
 * ARCHIVO: app/models/AdministrativeMatriculationsModel.php
 * PROPÓSITO: Gestión de persistencia para el historial académico, procesamiento de notas y sincronización de estados.
 * VERSIÓN: 1.5.1 - Full Fix: Promoción universal a EGRESADO y restauración de todos los métodos auxiliares.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

class AdministrativeMatriculationsModel 
{
    private PDO $db;

    public function __construct() 
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene los cohortes para las tarjetas del index.
     * Trae cualquier oferta académica que tenga al menos 1 alumno matriculado.
     */
    public function getActiveCohorts(): array 
    {
        try {
            $sql = "SELECT 
                        o.id AS offering_internal_id,
                        c.cohort_code,
                        d.name AS diplomado_name,
                        o.status AS offering_status,
                        COUNT(m.id) AS total_matriculados
                    FROM tbl_academic_offerings o
                    INNER JOIN tbl_cohortes c ON o.cohort_id = c.id
                    INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                    LEFT JOIN tbl_student_matriculations m ON m.offering_id = o.id
                    GROUP BY o.id, c.cohort_code, d.name, o.status
                    HAVING total_matriculados > 0
                    ORDER BY c.cohort_code DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("Error en getActiveCohorts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lista estudiantes vinculados a un cohorte para la carga masiva de notas.
     */
    public function getStudentsByOffering(int $offeringId): array 
    {
        try {
            $sql = "SELECT 
                        m.id AS matricula_id,
                        m.final_grade,
                        m.academic_status,
                        s.id AS student_internal_id,
                        u.first_name,
                        u.last_name,
                        u.document_id AS cedula
                    FROM tbl_student_matriculations m
                    INNER JOIN tbl_students s ON m.student_id = s.id
                    INNER JOIN tbl_users u ON s.user_id = u.id
                    WHERE m.offering_id = :oid
                    ORDER BY u.last_name ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':oid', $offeringId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("Error en getStudentsByOffering: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ACTUALIZACIÓN DE NOTA Y CIERRE DE CICLO.
     * Regla: Tanto APROBADOS como REPROBADOS pasan a estatus maestro EGRESADO.
     */
    public function processStudentGrade(int $matriculaId, float $grade, string $status): bool 
    {
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            // 1. Actualizamos la matrícula con su nota y estado final
            $sql = "UPDATE tbl_student_matriculations 
                    SET final_grade = :grade, 
                        academic_status = :status, 
                        updated_at = NOW() 
                    WHERE id = :mid";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':grade'  => $grade,
                ':status' => $status,
                ':mid'    => $matriculaId
            ]);

            // 2. LÓGICA INSTITUCIONAL: Si el ciclo terminó por acta, el estudiante es EGRESADO.
            if (in_array($status, ['APROBADO', 'REPROBADO'])) {
                $this->syncMasterStatusByMatricula($matriculaId, 'EGRESADO');
            }

            return $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error crítico en processStudentGrade: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sincroniza el estado del estudiante directamente en la tabla maestra.
     * Usado para procesos de RETIRADO, CONGELADO o reactivación manual.
     */
    public function syncMasterStatus(int $studentId, string $status): bool 
    {
        try {
            $sql = "UPDATE tbl_students SET status = :status WHERE id = :sid";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':status' => $status,
                ':sid'    => $studentId
            ]);
        } catch (Throwable $e) {
            error_log("Error en syncMasterStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper interno para sincronizar el estatus del estudiante usando el ID de matrícula.
     */
    private function syncMasterStatusByMatricula(int $matriculaId, string $status): void 
    {
        $sql = "UPDATE tbl_students s
                INNER JOIN tbl_student_matriculations m ON s.id = m.student_id
                SET s.status = :status
                WHERE m.id = :mid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':status' => $status, 
            ':mid'    => $matriculaId
        ]);
    }

    /**
     * Obtiene metadatos del cohorte para encabezados de documentos o impresión.
     */
    /**
     * Obtiene metadatos del cohorte para encabezados de documentos o impresión.
     * Incluye los nombres de los grupos asociados mediante una subconsulta.
     */
    public function getCohortHeaderInfo(int $offeringId): ?array 
    {
        try {
            $sql = "SELECT 
                        c.cohort_code, 
                        d.name AS diplomado_name,
                        -- Subconsulta para obtener los nombres de los grupos vinculados
                        (SELECT GROUP_CONCAT(g.name SEPARATOR ', ') 
                         FROM tbl_academic_offering_groups og 
                         INNER JOIN tbl_grupos g ON og.group_id = g.id 
                         WHERE og.offering_id = o.id) AS grupos_nombres
                    FROM tbl_academic_offerings o
                    INNER JOIN tbl_cohortes c ON o.cohort_id = c.id
                    INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                    WHERE o.id = :oid";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':oid', $offeringId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (Throwable $e) {
            error_log("Error en getCohortHeaderInfo: " . $e->getMessage());
            return null;
        }
    }
}