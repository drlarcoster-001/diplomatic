<?php
/**
 * MÓDULO: ADMINISTRATIVO / VERIFICACIÓN DE DOCUMENTOS
 * ARCHIVO: app/models/DocumentVerificationModel.php
 * PROPÓSITO: Auditoría de recaudos, formalización y persistencia de observaciones técnicas.
 * VERSIÓN: 1.4.2 - Fix: Implementación de observación técnica manteniendo estatus REVISION.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class DocumentVerificationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }


public function getPendingList(array $filters = []): array
{
    $params = [];
    $sql = "SELECT 
                e.id as enrollment_id,
                DATE_FORMAT(e.created_at, '%d/%m/%Y %h:%i %p') as fecha_solicitud,
                e.status as enrollment_status,
                CONCAT(u.first_name, ' ', u.last_name) as participante,
                u.document_id as cedula,
                u.phone as telefono,
                
                -- EL CORREO (Está en tbl_users según tu dump)
                u.email, 
                
                -- LA PROFESIÓN (Está en tbl_enrollments y tbl_users, usamos e. para el trámite)
                e.undergraduate_degree,
                
                -- LOS ARCHIVOS (Están en tbl_enrollments según tu dump)
                e.doc_id_card,
                e.doc_degree,
                e.doc_cv,
                
                d.name as diplomado,
                (SELECT status FROM tbl_enrollments_payments WHERE enrollment_id = e.id ORDER BY id DESC LIMIT 1) as payment_status
            FROM tbl_enrollments e
            JOIN tbl_users u ON e.user_id = u.id
            JOIN tbl_academic_offerings o ON e.offering_id = o.id
            JOIN tbl_diplomados d ON o.diploma_id = d.id
            WHERE e.status IN ('REVISION', 'COMPROMISO')";

    if (!empty($filters['status'])) {
        $sql .= " AND e.status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (u.document_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR d.name LIKE ?)";
        $search = "%{$filters['search']}%";
        array_push($params, $search, $search, $search, $search);
    }

    $sql .= " ORDER BY e.created_at ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    /**
     * Promociona al participante a Estudiante con Expediente Universal.
     */
    public function promoteToStudent(int $enrollmentId, int $adminId): array
    {
        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();

            $sqlData = "SELECT 
                            e.id as enroll_id, e.user_id, e.offering_id,
                            u.first_name, u.last_name, u.document_id, u.email, u.undergraduate_degree,
                            d.name as diploma_name,
                            c.cohort_code
                        FROM tbl_enrollments e
                        JOIN tbl_users u ON e.user_id = u.id
                        JOIN tbl_academic_offerings o ON e.offering_id = o.id
                        JOIN tbl_diplomados d ON o.diploma_id = d.id
                        JOIN tbl_cohortes c ON o.cohort_id = c.id
                        WHERE e.id = ? AND e.status IN ('REVISION', 'COMPROMISO') FOR UPDATE";
            
            $stmt = $this->db->prepare($sqlData);
            $stmt->execute([$enrollmentId]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$info) throw new Exception("La inscripción no es válida o ya fue formalizada.");

            $sqlCheckStudent = "SELECT id, student_code FROM tbl_students WHERE user_id = ?";
            $stmtCheck = $this->db->prepare($sqlCheckStudent);
            $stmtCheck->execute([$info['user_id']]);
            $existingStudent = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            $studentId = null;
            $studentCode = '';

            if ($existingStudent) {
                $studentId = (int)$existingStudent['id'];
                $studentCode = $existingStudent['student_code'];
            } else {
                $studentCode = $this->generateUniversalStudentCode($info['cohort_code']);
                
                $sqlInsertStudent = "INSERT INTO tbl_students 
                                     (user_id, enrollment_id, student_code, first_name, last_name, document_id, email, undergraduate_degree, admission_date, status) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'ACTIVO')";
                $this->db->prepare($sqlInsertStudent)->execute([
                    $info['user_id'], $info['enroll_id'], $studentCode, $info['first_name'], 
                    $info['last_name'], $info['document_id'], $info['email'], $info['undergraduate_degree']
                ]);
                $studentId = (int)$this->db->lastInsertId();
            }

            $sqlCheckMatricula = "SELECT id FROM tbl_student_matriculations WHERE student_id = ? AND offering_id = ?";
            $stmtMatricula = $this->db->prepare($sqlCheckMatricula);
            $stmtMatricula->execute([$studentId, $info['offering_id']]);
            
            if (!$stmtMatricula->fetch()) {
                $sqlInsertMatricula = "INSERT INTO tbl_student_matriculations 
                                       (student_id, offering_id, enrollment_id, academic_status, registered_by, registered_at) 
                                       VALUES (?, ?, ?, 'CURSANDO', ?, NOW())";
                $this->db->prepare($sqlInsertMatricula)->execute([
                    $studentId, $info['offering_id'], $info['enroll_id'], $adminId
                ]);
            }

            $this->db->prepare("UPDATE tbl_enrollments SET status = 'APROBADO', observations = NULL, updated_at = NOW() WHERE id = ?")
                     ->execute([$enrollmentId]);

            $this->db->commit();
            
            return [
                'success'      => true, 
                'student_code' => $studentCode,
                'email'        => $info['email'],
                'first_name'   => $info['first_name'],
                'diploma_name' => $info['diploma_name']
            ];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Error promoteToStudent: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function generateUniversalStudentCode(string $cohortCode): string
    {
        $stmtSiglas = $this->db->query("SELECT siglas_estudiantes FROM tbl_company_settings LIMIT 1");
        $siglas = $stmtSiglas->fetchColumn() ?: 'DCS';
        $prefix = "{$siglas}-{$cohortCode}-";
        $sql = "SELECT COUNT(*) FROM tbl_students WHERE student_code LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$prefix . '%']);
        $count = (int)$stmt->fetchColumn();
        return $prefix . str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);
    }

    public function updateEnrollmentStatus(int $enrollmentId, string $status, string $reason, int $adminId): bool
    {
        try {
            $sql = "UPDATE tbl_enrollments SET status = ?, observations = ?, updated_at = NOW() WHERE id = ?";
            return $this->db->prepare($sql)->execute([$status, (!empty($reason) ? $reason : null), $enrollmentId]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * ACCIÓN OBSERVAR: Guarda la observación y garantiza que el estatus siga en REVISION.
     */
    public function addObservationToEnrollment(int $enrollmentId, string $observation, int $adminId): bool
    {
        try {
            // Actualizamos la observación y forzamos/mantenemos el estatus REVISION
            $sql = "UPDATE tbl_enrollments 
                    SET observations = ?, 
                        status = 'REVISION', 
                        updated_at = NOW() 
                    WHERE id = ?";
            
            return $this->db->prepare($sql)->execute([$observation, $enrollmentId]);
        } catch (Exception $e) {
            error_log("Error addObservationToEnrollment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene y organiza los datos para el reporte de auditoría PDF.
     * Separa automáticamente los expedientes en REVISIÓN y COMPROMISO.
     */
    public function getReportDataForPDF(): array
    {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(e.created_at, '%d/%m/%Y %h:%i %p') as fecha_solicitud,
                        CONCAT(u.first_name, ' ', u.last_name) as participante,
                        u.document_id as cedula,
                        u.phone as telefono, -- <--- CAMBIO: Se agrega el teléfono para el reporte
                        d.name as diplomado,
                        e.status as enrollment_status,
                        (SELECT status FROM tbl_enrollments_payments 
                         WHERE enrollment_id = e.id 
                         ORDER BY id DESC LIMIT 1) as payment_status
                    FROM tbl_enrollments e
                    INNER JOIN tbl_users u ON e.user_id = u.id
                    INNER JOIN tbl_academic_offerings o ON e.offering_id = o.id
                    INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                    WHERE e.status IN ('REVISION', 'COMPROMISO')
                    ORDER BY e.created_at ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Retornamos los datos ya clasificados por estatus
            return [
                'REVISION'   => array_filter($todos, fn($item) => $item['enrollment_status'] === 'REVISION'),
                'COMPROMISO' => array_filter($todos, fn($item) => $item['enrollment_status'] === 'COMPROMISO')
            ];
        } catch (\Throwable $e) {
            error_log("Error en DocumentVerificationModel::getReportDataForPDF: " . $e->getMessage());
            return ['REVISION' => [], 'COMPROMISO' => []];
        }
    }

}