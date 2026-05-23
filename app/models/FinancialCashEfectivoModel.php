<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / EFECTIVO (CASH)
 * ARCHIVO: app/models/FinancialCashEfectivoModel.php
 * PROPÓSITO: Conciliación y gestión de rechazos de pagos en efectivo respetando estatus de inscripción.
 * VERSIÓN: 1.2.2 - FIX: Se elimina actualización de tbl_enrollments para mantener estatus COMPROMISO original.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class FinancialCashEfectivoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getPendingCashCommitments(array $filters = []): array
    {
        $params = [];
        $sql = "SELECT 
                    ep.id as payment_id,
                    ep.enrollment_id,
                    u.id as user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as estudiante,
                    u.document_id as cedula,
                    ep.amount as monto_pactado,
                    e.created_at as fecha_inscripcion,
                    d.name as diplomado
                FROM tbl_enrollments_payments ep
                JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                JOIN tbl_users u ON e.user_id = u.id
                JOIN tbl_academic_offerings o ON e.offering_id = o.id
                JOIN tbl_diplomados d ON o.diploma_id = d.id
                WHERE e.status = 'COMPROMISO' 
                AND ep.method = 'CASH' 
                AND ep.status = 'PENDING'";

        if (!empty($filters['text'])) {
            $sql .= " AND (u.document_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $search = "%{$filters['text']}%";
            array_push($params, $search, $search, $search);
        }

        $sql .= " ORDER BY e.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function approveCashPayment(int $paymentId, array $breakdown, int $adminId): bool
    {
        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();

            $sql = "SELECT ep.enrollment_id, ep.amount, e.user_id, e.offering_id 
                    FROM tbl_enrollments_payments ep
                    JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                    WHERE ep.id = ? FOR UPDATE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$paymentId]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$info) throw new Exception("Compromiso no encontrado.");

            $metadata = json_encode([
                'tipo_validacion' => 'ARQUEO_FISICO',
                'desglose_billetes' => $breakdown,
                'auditoria' => [
                    'agente_receptor' => $adminId,
                    'fecha_recepcion' => date('Y-m-d H:i:s')
                ]
            ], JSON_UNESCAPED_UNICODE);

            // 1. APROBAR EL PAGO (Esta es la tabla que debe pasar a APPROVED)
            $this->db->prepare("UPDATE tbl_enrollments_payments SET status = 'APPROVED', observation = ?, validated_by = ?, validation_date = NOW() WHERE id = ?")
                    ->execute([$metadata, $adminId, $paymentId]);

            // NOTA: Se omite intencionalmente el UPDATE de tbl_enrollments para mantener al estudiante en COMPROMISO.

            // 2. Generar explosión del Ledger para control financiero
            $this->generateLedgerUSD((int)$info['enrollment_id'], (int)$info['user_id'], (int)$info['offering_id'], $paymentId, (float)$info['amount']);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("APPROVE CASH MODEL ERROR: " . $e->getMessage());
            return false;
        }
    }

public function rejectCashPayment(int $paymentId, string $reason, int $adminId): bool
{
    try {
        if (!$this->db->inTransaction()) $this->db->beginTransaction();

        // 1. Buscamos el enrollment_id asociado
        $stmt = $this->db->prepare("SELECT enrollment_id FROM tbl_enrollments_payments WHERE id = ? AND status = 'PENDING' FOR UPDATE");
        $stmt->execute([$paymentId]);
        $enrollmentId = $stmt->fetchColumn();
        
        if (!$enrollmentId) throw new Exception("Pago no encontrado.");

        // 2. ACTUALIZAMOS EL PAGO: Solo estatus y razón, NO tocamos payment_metadata
        $sqlPayment = "UPDATE tbl_enrollments_payments 
                       SET status = 'REJECTED', 
                           rejection_reason = ?, 
                           validated_by = ?, 
                           validation_date = NOW() 
                       WHERE id = ?";
        $this->db->prepare($sqlPayment)->execute([$reason, $adminId, $paymentId]);

        // 3. ACTUALIZAMOS LA INSCRIPCIÓN
        $sqlEnroll = "UPDATE tbl_enrollments 
                      SET status = 'RECHAZADO', 
                          updated_at = NOW() 
                      WHERE id = ?";
        $this->db->prepare($sqlEnroll)->execute([$enrollmentId]);

        $this->db->commit();
        return true;
    } catch (Exception $e) {
        if ($this->db->inTransaction()) $this->db->rollBack();
        return false;
    }
}

    private function generateLedgerUSD(int $enrollId, int $userId, int $offeringId, int $paymentId, float $monto): void
    {
        $sqlPlan = "SELECT name, amount, due_date FROM tbl_academic_offering_payment_plans WHERE offering_id = ? ORDER BY id ASC";
        $stmtPlan = $this->db->prepare($sqlPlan);
        $stmtPlan->execute([$offeringId]);
        $plans = $stmtPlan->fetchAll(PDO::FETCH_ASSOC);

        $sqlLedger = "INSERT INTO tbl_financial_student_ledger 
                      (enrollment_id, user_id, payment_id, concept, amount_due, amount_paid, exchange_rate, due_date, status, processed_at) 
                      VALUES (?, ?, ?, ?, ?, ?, 1.0000, ?, ?, NOW())";
        $stmtLedger = $this->db->prepare($sqlLedger);

        $restante = $monto;
        foreach ($plans as $plan) {
            $costo = (float)$plan['amount'];
            if ($restante >= $costo) {
                $abono = $costo; $status = 'PAGADO'; $restante -= $costo;
            } elseif ($restante > 0) {
                $abono = $restante; $status = 'ABONADO'; $restante = 0;
            } else {
                $abono = 0.00; $status = 'PENDIENTE';
            }

            $stmtLedger->execute([
                $enrollId, $userId, ($abono > 0) ? $paymentId : null, 
                $plan['name'], $costo, $abono, 
                $plan['due_date'] ?? date('Y-m-d'), $status
            ]);
        }
    }
}