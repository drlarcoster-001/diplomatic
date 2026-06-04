<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES / INSCRIPCIONES
 * ARCHIVO: app/Models/StudentsInscriptionsModel_s5.php
 * PROPÓSITO: Transacción atómica para matrícula y pagos.
 * VERSIÓN: 2.9.0 - CLEAN: Eliminación de generación prematura de Ledger (Evita Deuda Fantasma).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class StudentsInscriptionsModel_s5
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Guarda la inscripción y el reporte de pago (ESTUDIANTES). 
     * Estatus de documentos: COMPLETE (Cédula + Título) o INCOMPLETE.
     */
    public function saveCompleteInscription(array $data): ?int 
    {
        try {
            // Validación de integridad
            if (!isset($data['user_id'], $data['offering_id'], $data['enrollment_status'], $data['payment_status'])) {
                throw new Exception("Error: Datos incompletos para procesar la transacción.");
            }

            $this->db->beginTransaction();

            // --- LÓGICA BINARIA DE DOCUMENTOS ---
            // Verificamos si el estudiante cargó los dos requisitos mínimos
            $hasId     = !empty($data['doc_id_card']);
            $hasDegree = !empty($data['doc_degree']);
            $docStatus = ($hasId && $hasDegree) ? 'COMPLETE' : 'INCOMPLETE';

            // 1. INSERTAR EN tbl_enrollments (Se agrega la columna document_status)
            $sqlIns = "INSERT INTO tbl_enrollments 
                       (user_id, offering_id, undergraduate_degree, provenance, doc_id_card, doc_degree, doc_cv, status, type, created_by, document_status) 
                       VALUES (:user, :offering, :degree, :prov, :doc_id, :doc_deg, :doc_cv, :status, 'AUTO', :creator, :doc_status)";
            
            $stmt = $this->db->prepare($sqlIns);
            $stmt->execute([
                ':user'       => (int)$data['user_id'],
                ':offering'   => (int)$data['offering_id'],
                ':degree'     => $data['undergraduate_degree'] ?? 'N/A',
                ':prov'       => $data['provenance'] ?? 'N/A',
                ':doc_id'     => $data['doc_id_card'],
                ':doc_deg'    => $data['doc_degree'],
                ':doc_cv'     => $data['doc_cv'],
                ':status'     => $data['enrollment_status'], 
                ':creator'    => (int)$data['user_id'],
                ':doc_status' => $docStatus // <--- La nueva etiqueta
            ]);
            
            $enrollId = (int)$this->db->lastInsertId();

            if ($enrollId <= 0) {
                throw new Exception("Fallo crítico: No se generó el ID de inscripción.");
            }

            // 2. INSERTAR EN tbl_enrollments_payments
            $paymentMethod = strtoupper(trim((string)$data['payment_method']));
            $currency = $data['currency'] ?? (($paymentMethod === 'PAGOMOVIL') ? 'BS' : 'USD');

            $sqlPay = "INSERT INTO tbl_enrollments_payments 
                       (enrollment_id, method, amount, currency, reference_id, payment_metadata, screenshot_path, status) 
                       VALUES (:ins, :meth, :amount, :curr, :ref, :meta, :screen, :pay_status)";
            
            $stmtPay = $this->db->prepare($sqlPay);
            $stmtPay->execute([
                ':ins'        => $enrollId,
                ':meth'       => $paymentMethod, 
                ':amount'     => (float)$data['amount'], 
                ':curr'       => $currency,
                ':ref'        => $data['reference_id'], 
                ':meta'       => $data['payment_metadata'],
                ':screen'     => $data['screenshot_path'],
                ':pay_status' => $data['payment_status']
            ]);

            // 3. ACTUALIZACIÓN MATEMÁTICA DE CUPOS
            $sqlSync = "UPDATE tbl_academic_offerings o 
                        SET o.enrolled_count = (
                            SELECT COUNT(e.id) 
                            FROM tbl_enrollments e 
                            WHERE e.offering_id = o.id 
                            AND e.status NOT IN ('CANCELADO', 'RECHAZADO')
                        )
                        WHERE o.id = ?";
            $this->db->prepare($sqlSync)->execute([(int)$data['offering_id']]);

            $this->db->commit();
            return $enrollId;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("CRITICAL MODEL ERROR (saveCompleteInscription): " . $e->getMessage());
            throw $e; 
        }
    }
    



}