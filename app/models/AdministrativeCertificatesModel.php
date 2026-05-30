<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / CONSTANCIAS
 * ARCHIVO: app/models/AdministrativeCertificatesModel.php
 * PROPÓSITO: Motor de persistencia para búsqueda de alumnos, gestión de diplomados y registro oficial de certificados con historial.
 * VERSIÓN: 4.2.0 - FIX: Sincronización de campos de persistencia (qr_url) y Metadata Extendida.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

class AdministrativeCertificatesModel
{
    private PDO $db;

    /**
     * Constructor: Inicializa la conexión mediante el Core de la aplicación.
     */
    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Buscador reactivo para el componente Autocomplete.
     * @param string $term Término de búsqueda (Cédula o Nombre).
     * @return array Listado de alumnos encontrados.
     */
    public function searchStudents(string $term): array
    {
        try {
            $sql = "SELECT DISTINCT
                        u.id as user_id, 
                        u.first_name, 
                        u.last_name, 
                        u.document_id,
                        u.email
                    FROM tbl_users u
                    INNER JOIN tbl_students s ON u.id = s.user_id
                    INNER JOIN tbl_student_matriculations m ON s.id = m.student_id
                    WHERE u.document_id LIKE :t1 
                       OR u.first_name LIKE :t2 
                       OR u.last_name LIKE :t3
                    ORDER BY u.last_name ASC
                    LIMIT 20";
            
            $stmt = $this->db->prepare($sql);
            $searchTerm = "%$term%";
            
            $stmt->execute([
                ':t1' => $searchTerm,
                ':t2' => $searchTerm,
                ':t3' => $searchTerm
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("AdministrativeCertificatesModel [searchStudents]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los diplomados vinculados a un usuario.
     * @param int $userId ID del usuario.
     */
    public function getStudentPrograms(int $userId): array
    {
        try {
            $sql = "SELECT 
                        ao.id as offering_id,
                        d.name as diplomado,
                        c.name as cohorte,
                        DATE_FORMAT(ao.class_start, '%d/%m/%Y') as class_start,
                        DATE_FORMAT(ao.class_end, '%d/%m/%Y') as class_end
                    FROM tbl_student_matriculations m
                    INNER JOIN tbl_students s ON m.student_id = s.id
                    INNER JOIN tbl_academic_offerings ao ON m.offering_id = ao.id
                    INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                    INNER JOIN tbl_cohortes c ON ao.cohort_id = c.id
                    WHERE s.user_id = ?
                    ORDER BY ao.id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("AdministrativeCertificatesModel [getStudentPrograms]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si ya existe un certificado (Opcional para consultas de historial).
     */
    public function checkExistingCertificate(int $userId, int $offeringId, string $type): ?array
    {
        try {
            $sql = "SELECT cert.code, cert.qr_url as qr_validation_url 
                    FROM tbl_students_certificates cert
                    INNER JOIN tbl_students s ON cert.student_id = s.id
                    WHERE s.user_id = ? AND cert.offering_id = ? AND cert.type = ? 
                    ORDER BY cert.created_at DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $offeringId, $type]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * REGISTRO OFICIAL (Bootstrap de Persistencia)
     * FIX: Usa el nombre de columna 'qr_url' para coincidir con la estructura Master.
     * @param array $data Datos enviados desde el Controlador issueNewCertificateRecord.
     */
    public function registerCertificate(array $data): bool
    {
        try {
            // 1. Traducir el UserID del sistema al StudentID académico
            $stmtId = $this->db->prepare("SELECT id FROM tbl_students WHERE user_id = ? LIMIT 1");
            $stmtId->execute([$data['user_id']]);
            $student = $stmtId->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                error_log("Fallo Persistencia: UserID " . $data['user_id'] . " no tiene entrada en tbl_students.");
                return false;
            }

            // 2. Inserción física en la tabla de folios
            $sql = "INSERT INTO tbl_students_certificates 
                    (student_id, offering_id, type, code, qr_url) 
                    VALUES (:sid, :oid, :type, :code, :qr)";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':sid'  => (int)$student['id'],
                ':oid'  => (int)$data['offering_id'],
                ':type' => $data['type'],
                ':code' => $data['code'],
                ':qr'   => $data['qr_url'] // Sincronizado con v7.1.0 del Controlador
            ]);

            if ($success) {
                error_log("Historial: Folio {$data['code']} registrado para StudentID {$student['id']}");
            }

            return $success;
        } catch (Throwable $e) {
            error_log("ERROR CRÍTICO DB [registerCertificate]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene la metadata completa para el PDF (Réplica Word UCLA).
     * Incluye datos extendidos de contacto y grado.
     */
    public function getFullDataForCert(int $userId, int $offeringId): ?array
    {
        try {
            $sql = "SELECT 
                        u.first_name, u.last_name, u.document_id, u.email,
                        u.address, u.phone, u.undergraduate_degree,
                        d.name as diplomado_name, d.total_hours,
                        c.name as cohorte_name, c.start_date as cohorte_inicio, c.end_date as cohorte_fin,
                        ao.class_start, ao.class_end, ao.general_modality,
                        comp.nombre_comercial as company_name
                    FROM tbl_users u
                    INNER JOIN tbl_students s ON u.id = s.user_id
                    INNER JOIN tbl_academic_offerings ao ON ao.id = :oid
                    INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                    INNER JOIN tbl_cohortes c ON ao.cohort_id = c.id
                    LEFT JOIN (SELECT nombre_comercial FROM tbl_company_settings LIMIT 1) as comp ON 1=1
                    WHERE u.id = :uid LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uid' => $userId, ':oid' => $offeringId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Throwable $e) {
            error_log("AdministrativeCertificatesModel [getFullDataForCert]: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene la configuración SMTP para el motor de envíos.
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
            return null;
        }
    }

    public function searchStudentsPaged(string $term, int $limit, int $offset): array
{
    try {
        $t = "%{$term}%";
        $sql = "SELECT DISTINCT u.id as user_id, u.first_name, u.last_name, u.document_id, u.email
                FROM tbl_users u
                INNER JOIN tbl_students s ON u.id = s.user_id
                WHERE u.document_id LIKE '{$t}' OR u.first_name LIKE '{$t}' OR u.last_name LIKE '{$t}'
                ORDER BY u.last_name ASC
                LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

public function countStudents(string $term): int
{
    try {
        $t = "%{$term}%";
        $sql = "SELECT COUNT(DISTINCT u.id)
                FROM tbl_users u
                INNER JOIN tbl_students s ON u.id = s.user_id
                WHERE u.document_id LIKE '{$t}' OR u.first_name LIKE '{$t}' OR u.last_name LIKE '{$t}'";
        $stmt = $this->db->query($sql);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}
}