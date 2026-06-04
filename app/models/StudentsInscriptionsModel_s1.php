<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: app/Models/StudentsInscriptionsModel_s1.php
 * PROPÓSITO: Lógica de validación de pre-requisitos y existencia de registros para el Paso 1.
 * VERSIÓN: 1.0.1 - Sincronización con tbl_enrollments (INT UNSIGNED para user_id) y validación de estatus activos.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class StudentsInscriptionsModel_s1
{
    private PDO $db;

    public function __construct()
    {
        // Inicializamos la conexión centralizada del núcleo
        $this->db = (new Database())->getConnection();
    }

    /**
     * Verifica si el estudiante ya posee una inscripción activa o en proceso
     * para la oferta académica seleccionada.
     * * @param int $userId ID del usuario (INT UNSIGNED)
     * @param int $offeringId ID de la oferta académica
     * @return bool True si ya existe un registro, False de lo contrario.
     */
    public function checkExistingEnrollment(int $userId, int $offeringId): bool
    {
        // Solo consideramos inscripciones que no hayan sido anuladas
        $sql = "SELECT COUNT(*) FROM tbl_enrollments 
                WHERE user_id = ? 
                AND offering_id = ? 
                AND status != 'ANULADA'";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $offeringId]);
            
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (\PDOException $e) {
            // Error log para auditoría técnica en caso de fallo en BD
            error_log("Error en StudentsInscriptionsModel_s1::checkExistingEnrollment -> " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene datos adicionales de seguridad del estudiante para doble validación en S1.
     */
    public function getStudentValidationData(int $userId): array
    {
        $sql = "SELECT id, document_id, email, status 
                FROM tbl_users 
                WHERE id = ? AND user_type = 'PARTICIPANT' LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}