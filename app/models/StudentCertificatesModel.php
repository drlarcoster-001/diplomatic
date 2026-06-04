<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / CONSTANCIAS
 * ARCHIVO: app/models/StudentCertificatesModel.php
 * PROPÓSITO: Motor de persistencia para el historial de certificados y consultas académicas vinculadas al estudiante en sesión.
 * VERSIÓN: 2.0.0 - Sincronización con tabla central de folios (registerCertificate), soporte para campos extendidos de planilla y normalización de IDs.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

class StudentCertificatesModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * SEGURIDAD: Traduce el ID de usuario de la sesión al ID de la tabla estudiantes.
     * Crucial para mantener la integridad referencial (INT UNSIGNED vs INT).
     */
    public function getStudentIdByUserId(int $userId): int
    {
        try {
            // Relacionamos al estudiante con su inscripción específica
            $sql = "SELECT s.id 
                    FROM tbl_students s
                    INNER JOIN tbl_enrollments e ON s.enrollment_id = e.id
                    WHERE s.user_id = ? AND e.status = 'APROBADO' 
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetchColumn();
            return $result ? (int)$result : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Obtiene los programas académicos (diplomados) donde el estudiante está matriculado.
     */
    public function getStudentPrograms(int $studentId): array
    {
        try {
            $sql = "SELECT 
                        ao.id as offering_id,
                        d.name as diplomado,
                        c.name as cohorte,
                        DATE_FORMAT(ao.class_start, '%d/%m/%Y') as class_start,
                        DATE_FORMAT(ao.class_end, '%d/%m/%Y') as class_end
                    FROM tbl_student_matriculations m
                    INNER JOIN tbl_academic_offerings ao ON m.offering_id = ao.id
                    INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                    INNER JOIN tbl_cohortes c ON ao.cohort_id = c.id
                    WHERE m.student_id = ?
                    ORDER BY ao.id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$studentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("StudentCertificatesModel [getStudentPrograms]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * REGISTRO DE FOLIO ÚNICO (Historial de Auditoría)
     * Inserta el registro de la constancia generada por el alumno en la tabla centralizada.
     * Sincronizado con el campo qr_url del bypass de validación pública.
     */
    public function registerCertificate(array $data): bool
    {
        try {
            $sql = "INSERT INTO tbl_students_certificates 
                    (student_id, offering_id, type, code, qr_url) 
                    VALUES (:sid, :oid, :type, :code, :qr)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':sid'  => $data['student_id'],
                ':oid'  => $data['offering_id'],
                ':type' => $data['type'],
                ':code' => $data['code'],
                ':qr'   => $data['qr_url']
            ]);
        } catch (Throwable $e) {
            error_log("StudentCertificatesModel [registerCertificate]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene la metadata maestra para renderizar el PDF.
     * Incluye datos extendidos de tbl_users (dirección, grado, teléfono) para la Planilla de Inscripción.
     */
    public function getFullDataForCert(int $studentId, int $offeringId): ?array
    {
        try {
            $sql = "SELECT 
                        u.first_name, 
                        u.last_name, 
                        u.document_id,
                        u.email,
                        u.address,
                        u.phone,
                        u.undergraduate_degree,
                        d.name as diplomado_name, 
                        d.total_hours, 
                        c.name as cohorte_name,
                        c.start_date as lapso_inicio, 
                        c.end_date as lapso_fin, 
                        ao.class_start, 
                        ao.general_modality, 
                        comp.nombre_comercial 
                    FROM tbl_students s
                    INNER JOIN tbl_users u ON s.user_id = u.id
                    INNER JOIN tbl_academic_offerings ao ON ao.id = :oid
                    INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                    INNER JOIN tbl_cohortes c ON ao.cohort_id = c.id
                    LEFT JOIN (SELECT nombre_comercial FROM tbl_company_settings LIMIT 1) as comp ON 1=1
                    WHERE s.id = :sid LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':sid' => $studentId, ':oid' => $offeringId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Throwable $e) {
            error_log("StudentCertificatesModel [getFullDataForCert]: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene la configuración SMTP 'DOCUMENTOS' para los envíos automáticos por correo.
     */
    public function getSmtpSettings(): ?array 
    {
        try {
            $sql = "SELECT smtp_host, smtp_user, smtp_password, smtp_port, smtp_security, from_email, from_name 
                    FROM tbl_email_settings 
                    WHERE tipo_correo = 'DOCUMENTOS' 
                    LIMIT 1";
            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log("StudentCertificatesModel [getSmtpSettings]: " . $e->getMessage());
            return null;
        }
    }
}