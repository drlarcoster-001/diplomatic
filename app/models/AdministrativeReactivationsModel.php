<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / EXCEPCIONES
 * ARCHIVO: app/models/AdministrativeReactivationsModel.php
 * PROPÓSITO: Gestión masiva de reactivación por cohortes/diplomados.
 * VERSIÓN: 2.1.1 - FIX CRÍTICO: Corrección de sintaxis PHP (->) y optimización de UPDATE JOIN para MySQL.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

class AdministrativeReactivationsModel
{
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene los diplomados que tienen estudiantes en estados que requieren reactivación.
     */
    public function getCohortsForReactivation(): array {
        try {
            $sql = "SELECT 
                        ao.id as offering_internal_id,
                        d.name as diplomado_name,
                        c.name as cohort_code,
                        COUNT(m.id) as total_reactivables
                    FROM tbl_student_matriculations m
                    INNER JOIN tbl_academic_offerings ao ON m.offering_id = ao.id
                    INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                    INNER JOIN tbl_cohortes c ON ao.cohort_id = c.id
                    WHERE m.academic_status IN ('APROBADO', 'FINALIZADO', 'RETIRADO', 'CONGELADO', 'INACTIVO')
                    GROUP BY ao.id, d.name, c.name";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) { 
            error_log("Error en getCohortsForReactivation: " . $e->getMessage());
            return []; 
        }
    }

    /**
     * Lista los estudiantes de una cohorte específica para la vista de gestión.
     */
    public function getStudentsByOffering(int $offeringId): array {
        try {
            $sql = "SELECT 
                        u.document_id,
                        u.first_name,
                        u.last_name,
                        m.academic_status,
                        s.status as student_status
                    FROM tbl_student_matriculations m
                    INNER JOIN tbl_students s ON m.student_id = s.id
                    INNER JOIN tbl_users u ON s.user_id = u.id
                    WHERE m.offering_id = :oid";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':oid' => $offeringId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) { 
            error_log("Error en getStudentsByOffering: " . $e->getMessage());
            return []; 
        }
    }

    /**
     * PROCESO MAESTRO: Reactivación masiva de toda la cohorte.
     * Cambia tbl_students a ACTIVO y tbl_student_matriculations a CURSANDO.
     */
    public function reactivateFullCohort(int $offeringId): array {
        try {
            // Verificación de transacción activa
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            // 1. Actualización masiva de Estudiantes vinculados a la cohorte
            // Usamos una subconsulta para identificar los IDs de estudiantes de esta oferta
            $sqlUsers = "UPDATE tbl_students 
                         SET status = 'ACTIVO' 
                         WHERE id IN (
                            SELECT student_id 
                            FROM tbl_student_matriculations 
                            WHERE offering_id = ?
                         )";
            
            $stmtUsers = $this->db->prepare($sqlUsers);
            $stmtUsers->execute([$offeringId]);

            // 2. Actualización masiva de las Matrículas de la cohorte
            $sqlMatric = "UPDATE tbl_student_matriculations 
                          SET academic_status = 'CURSANDO'
                          WHERE offering_id = ?";
            
            $stmtMatric = $this->db->prepare($sqlMatric);
            $stmtMatric->execute([$offeringId]); // <-- CORREGIDO: Uso de -> en lugar de .

            $this->db->commit();
            
            return [
                'success' => true, 
                'message' => 'La cohorte ha sido reabierta exitosamente. Estudiantes y matrículas restaurados.'
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error crítico en reactivateFullCohort: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Fallo en la operación: ' . $e->getMessage()
            ];
        }
    }
}