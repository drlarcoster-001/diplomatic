<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES / CORREOS
 * ARCHIVO: app/Models/StudentsInscriptionsModel_s6.php
 * PROPÓSITO: Obtener datos de inscripción y credenciales SMTP para el envío de correo.
 * VERSIÓN: 1.1.1 - FIX: Uso de LEFT JOIN en pagos para evitar fallos de envío por concurrencia.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class StudentsInscriptionsModel_s6
{
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene los datos necesarios para el cuerpo del correo.
     * Se cambia a LEFT JOIN en la tabla de pagos para garantizar que el correo salga 
     * aunque la relación con el pago esté procesándose.
     */
    public function getEnrollmentData(int $enrollmentId): ?array {
        $sql = "SELECT 
                    u.first_name, 
                    u.last_name, 
                    u.email, 
                    d.name AS diplomado_name, 
                    IFNULL(p.method, 'PENDIENTE') AS payment_method
                FROM tbl_enrollments e
                INNER JOIN tbl_users u ON e.user_id = u.id
                INNER JOIN tbl_academic_offerings o ON e.offering_id = o.id
                INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                LEFT JOIN tbl_enrollments_payments p ON e.id = p.enrollment_id
                WHERE e.id = :id 
                LIMIT 1";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $enrollmentId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $res ?: null;
    }

    /**
     * Obtiene la configuración SMTP activa.
     */
    public function getSmtpSettings(): ?array {
        // Aseguramos que la consulta sea explícita
        $sql = "SELECT smtp_host, smtp_user, smtp_password, smtp_port, smtp_security, from_email, from_name, validate_cert 
                FROM tbl_email_settings 
                WHERE tipo_correo = 'INSCRIPCION' 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}