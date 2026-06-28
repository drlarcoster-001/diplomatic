<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/Models/AdministrativeInscriptionsModel.php
 * PROPÓSITO: Lógica de persistencia para inscripciones manuales.
 * VERSIÓN: 4.2.0 - CLEAN & RESTORED: Se elimina inserción en Ledger y se restaura método de consulta de planes.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class AdministrativeInscriptionsModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene las ofertas académicas abiertas con cálculo de cupos, grupos y sus descripciones (horarios).
     */
    public function getOpenOfferings(): array 
    {
        $sql = "SELECT o.id, o.total_capacity, o.enrolled_count, o.registration_end, 
                       d.name as diploma_name, c.name as cohort_name,
                       
                       -- CIRUGÍA: Extraer nombres de los grupos
                       (SELECT GROUP_CONCAT(g.name SEPARATOR ', ') 
                        FROM tbl_academic_offering_groups og 
                        INNER JOIN tbl_grupos g ON og.group_id = g.id 
                        WHERE og.offering_id = o.id) AS grupos_nombres,
                        
                       -- CIRUGÍA: Extraer descripciones/horarios de los grupos
                       (SELECT GROUP_CONCAT(g.description SEPARATOR ' | ') 
                        FROM tbl_academic_offering_groups og 
                        INNER JOIN tbl_grupos g ON og.group_id = g.id 
                        WHERE og.offering_id = o.id) AS grupos_descripciones

                FROM tbl_academic_offerings o
                INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                INNER JOIN tbl_cohortes c ON o.cohort_id = c.id
                WHERE o.status = 'ABIERTA' 
                    AND o.is_active = 1
                    AND c.cohort_status = 'Planificada'
                    AND EXISTS (
                        SELECT 1 FROM tbl_periodos_cohorte p
                        WHERE p.id = c.periodo_id AND p.estado = 'Activo'
                    )
                    AND d.status = 'ACTIVO'
                    ORDER BY o.registration_end ASC";
        
        $offerings = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($offerings as &$off) {
            $off['available_seats'] = max(0, (int)$off['total_capacity'] - (int)$off['enrolled_count']);
        }
        return $offerings;
    }


    /**
     * Buscador de participantes activos para el paso 1 del Wizard.
     */
    public function searchParticipants(string $term): array 
    {
        $sql = "SELECT id, document_id, first_name, last_name, email, 
                       IFNULL(avatar, 'default.png') as avatar, 
                       undergraduate_degree, provenance 
                FROM tbl_users 
                WHERE (document_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)
                AND user_type = 'PARTICIPANT' AND status = 'ACTIVE'
                LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $t = "%$term%";
        $stmt->execute([$t, $t, $t, $t]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el cronograma de pagos vinculado a una oferta (Necesario para la UI).
     */
    public function getOfferingPaymentPlan(int $offeringId): array 
    {
        $sql = "SELECT name, amount, due_date FROM tbl_academic_offering_payment_plans WHERE offering_id = ? ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificación de duplicidad para evitar re-inscripciones accidentales.
     */
    public function checkExistingEnrollment(int $userId, int $offeringId): bool
    {
        $sql = "SELECT COUNT(*) FROM tbl_enrollments 
                WHERE user_id = ? AND offering_id = ? 
                AND status NOT IN ('RECHAZADO', 'ANULADO')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $offeringId]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    /**
     * RECHAZO DE INSCRIPCIÓN (CASH / PAGO MÓVIL / ZELLE)
     * Limpia datos asociados para permitir re-inscripción.
     */
    public function rejectEnrollmentFull(int $enrollmentId): bool
    {
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            // 1. Borrar Ledger si existiera (por seguridad)
            $this->db->prepare("DELETE FROM tbl_financial_student_ledger WHERE enrollment_id = ?")
                     ->execute([$enrollmentId]);

            // 2. Anular el pago vinculado
            $this->db->prepare("UPDATE tbl_enrollments_payments SET status = 'REJECTED' WHERE enrollment_id = ?")
                     ->execute([$enrollmentId]);

            // 3. Cambiar estatus de la inscripción
            $this->db->prepare("UPDATE tbl_enrollments SET status = 'RECHAZADO' WHERE id = ?")
                     ->execute([$enrollmentId]);

            // 4. Devolver el cupo
            $stmt = $this->db->prepare("SELECT offering_id FROM tbl_enrollments WHERE id = ?");
            $stmt->execute([$enrollmentId]);
            $off = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($off) {
                $this->db->prepare("UPDATE tbl_academic_offerings SET enrolled_count = GREATEST(0, enrolled_count - 1) WHERE id = ?")
                         ->execute([$off['offering_id']]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Error en rejectEnrollmentFull: " . $e->getMessage());
            return false;
        }
    }

    /**
     * PROCESO ATÓMICO DE INSCRIPCIÓN (LIMPIO)
     * Solo registra inscripción y reporte de pago inicial.
     * EL LEDGER NO SE TOCA AQUÍ, SE DEJA PARA EL MÓDULO DE VALIDACIÓN.
     */
    public function executeEnrollment(array $enrollData, array $documents, array $paymentData): int
    {
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            // ALCABALA ANTI-DUPLICADO (FOR UPDATE para concurrencia)
            $checkDuplicate = $this->db->prepare("SELECT id FROM tbl_enrollments WHERE user_id = ? AND offering_id = ? AND status NOT IN ('RECHAZADO', 'ANULADO') LIMIT 1 FOR UPDATE");
            $checkDuplicate->execute([$enrollData['user_id'], $enrollData['offering_id']]);
            if ($checkDuplicate->fetch()) {
                throw new Exception("Bloqueo de seguridad: El participante ya posee una inscripción activa.");
            }


            // --- LÓGICA DE ESTATUS DE DOCUMENTOS ---
            $idFile     = $documents['ID_CARD'] ?? null;
            $degreeFile = $documents['DEGREE'] ?? null;
            $cvFile      = $documents['CV'] ?? null;

            // REGLA: Solo si AMBOS (Cédula y Título) tienen ruta, es COMPLETE.
            $docStatus = ($idFile && $degreeFile) ? 'COMPLETE' : 'INCOMPLETE';

           // 1. Insertar Inscripción (Se incluye la columna document_status)
            $sqlEnroll = "INSERT INTO tbl_enrollments 
                (user_id, offering_id, undergraduate_degree, provenance, doc_id_card, doc_degree, doc_cv, status, type, created_by, document_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'MANUAL', ?, ?)";
            
            $stmt = $this->db->prepare($sqlEnroll);
            $stmt->execute([
                $enrollData['user_id'], 
                $enrollData['offering_id'], 
                $enrollData['undergraduate_degree'], 
                $enrollData['provenance'], 
                $idFile,     // Guardará NULL si el controlador no envió el archivo
                $degreeFile, // Guardará NULL si el controlador no envió el archivo
                $cvFile,     // Guardará NULL si el controlador no envió el archivo
                strtoupper($enrollData['status'] ?? 'REVISION'), 
                $enrollData['created_by'],
                $docStatus    // 'COMPLETE' o 'INCOMPLETE'
            ]);

            $enrollmentId = (int)$this->db->lastInsertId();

            // 2. Insertar Pago Vinculado (PENDIENTE DE VALIDACIÓN)
            $sqlPay = "INSERT INTO tbl_enrollments_payments 
                (enrollment_id, method, amount, currency, reference_id, payment_metadata, screenshot_path, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING')";
            
            $stmtPay = $this->db->prepare($sqlPay);
            $stmtPay->execute([
                $enrollmentId, 
                $paymentData['method'], 
                $paymentData['amount'], 
                $paymentData['currency'], 
                $paymentData['reference'],  
                $paymentData['metadata'], 
                $paymentData['screenshot']  
            ]);

            // 3. Actualización de Cupos (+1)
            $this->db->prepare("UPDATE tbl_academic_offerings SET enrolled_count = enrolled_count + 1 WHERE id = ?")
                     ->execute([$enrollData['offering_id']]);

            $this->db->commit();
            return $enrollmentId;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("FALLO MODELO INSCRIPCIÓN: " . $e->getMessage());
            throw $e;
        }
    }


    public function getEffectiveRate(string $date): array|false
    {
        $sql = "SELECT dolar_bcv, rate_date 
                FROM tbl_financial_exchange_rates 
                WHERE rate_date <= :target_date 
                AND status = 'ACTIVE' 
                ORDER BY rate_date DESC, id DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':target_date' => $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    

}