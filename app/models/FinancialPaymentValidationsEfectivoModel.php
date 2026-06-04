<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS (EFECTIVO)
 * ARCHIVO: app/models/FinancialPaymentValidationsEfectivoModel.php
 * PROPÓSITO: Modelo para validar reportes de pago en efectivo existentes en tbl_financial_payments.
 * VERSIÓN: 1.1.0 - Filtrado por pagos PENDING y aplicación de cascada.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class FinancialPaymentValidationsEfectivoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * CORRECCIÓN: Busca registros PENDIENTES en la tabla de pagos, no deudas en el ledger.
     */
    public function getStudentsWithPendingLedger(array $filters = []): array
    {
        $params = [];
        // Consultamos la tabla de pagos financieros
        $sql = "SELECT 
                    p.id as payment_id,
                    s.id as student_id,
                    u.id as user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as estudiante,
                    u.document_id as cedula,
                    p.amount as monto_reportado,
                    p.created_at as fecha_reporte
                FROM tbl_financial_payments p
                INNER JOIN tbl_students s ON p.student_id = s.id
                INNER JOIN tbl_users u ON s.user_id = u.id
                WHERE p.method IN ('CASH', 'EFECTIVO') 
                  AND p.status IN ('PENDING', 'PENDIENTE')";

        if (!empty($filters['text'])) {
            $sql .= " AND (u.document_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $search = "%{$filters['text']}%";
            array_push($params, $search, $search, $search);
        }

        $sql .= " ORDER BY p.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Procesa la VALIDACIÓN de un pago ya registrado en la tabla de pagos.
     */

    public function processCashPayment(int $paymentId, float $amount, string $currency, ?array $breakdown, int $adminId): bool
    {
        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();

            // 1. Obtenemos solo las columnas que REALMENTE existen en tu SQL
            $stmt = $this->db->prepare("SELECT student_id, amount, payment_metadata FROM tbl_financial_payments WHERE id = ? FOR UPDATE");
            $stmt->execute([$paymentId]);
            $paymentData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$paymentData) throw new Exception("El registro de pago no existe.");

            // 2. Extraemos el student_id para buscar su inscripción
            $studentId = (int)$paymentData['student_id'];
            $stmtS = $this->db->prepare("SELECT user_id, enrollment_id FROM tbl_students WHERE id = ?");
            $stmtS->execute([$studentId]);
            $student = $stmtS->fetch(PDO::FETCH_ASSOC);

            // 3. Preparamos los nuevos metadatos mezclando lo que ya existía con el arqueo actual
            $oldMetadata = json_decode($paymentData['payment_metadata'] ?? '{}', true);
            $newMetadata = json_encode(array_merge($oldMetadata, [
                'arqueo_caja' => [
                    'desglose' => $breakdown,
                    'monto_fisico' => $amount,
                    'moneda_fisica' => $currency,
                    'validado_por' => $adminId,
                    'fecha_validacion' => date('Y-m-d H:i:s')
                ]
            ]), JSON_UNESCAPED_UNICODE);

            // 4. Actualizamos el status a APPROVED y guardamos el collector_id (tu columna de auditoría)
            $sqlUpd = "UPDATE tbl_financial_payments 
                    SET status = 'APPROVED', 
                        payment_metadata = ?, 
                        collector_id = ? 
                    WHERE id = ?";
            $this->db->prepare($sqlUpd)->execute([$newMetadata, $adminId, $paymentId]);

            // 5. EJECUTAR CASCADA (Usando el amount de la tabla que está en USD)
            $this->applyCascada($paymentId, (int)$student['user_id'], (int)$student['enrollment_id'], (float)$paymentData['amount']);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    private function applyCascada(int $paymentId, int $userId, int $enrollId, float $amountAvailable): void
    {
        // La lógica de la cascada se mantiene igual, ya que busca deudas y las mata en orden
        $sqlDeudas = "SELECT id, amount_due, amount_paid 
                      FROM tbl_financial_student_ledger 
                      WHERE user_id = ? AND enrollment_id = ? AND status IN ('PENDIENTE', 'VENCIDO', 'ABONADO') 
                      ORDER BY due_date ASC";
        
        $stmt = $this->db->prepare($sqlDeudas);
        $stmt->execute([$userId, $enrollId]);
        $deudas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $saldo = $amountAvailable;

        foreach ($deudas as $deuda) {
            if ($saldo <= 0.009) break;

            $pendiente = (float)$deuda['amount_due'] - (float)$deuda['amount_paid'];
            $pago = ($saldo >= $pendiente) ? $pendiente : $saldo;
            $estatus = ($saldo >= $pendiente) ? 'PAGADO' : 'ABONADO';

            $this->db->prepare("UPDATE tbl_financial_student_ledger 
                                SET amount_paid = amount_paid + ?, 
                                    status = ?, 
                                    payment_id = ?, 
                                    processed_at = NOW() 
                                WHERE id = ?")
                     ->execute([$pago, $estatus, $paymentId, $deuda['id']]);
            
            $saldo -= $pago;
        }

        if ($saldo > 0.05) {
            $this->db->prepare("INSERT INTO tbl_financial_student_ledger 
                (enrollment_id, user_id, payment_id, concept, amount_due, amount_paid, exchange_rate, due_date, status, processed_at) 
                VALUES (?, ?, ?, 'SALDO A FAVOR', 0.00, ?, 1.0000, CURDATE(), 'A FAVOR', NOW())")
                     ->execute([$enrollId, $userId, $paymentId, $saldo]);
        }
    }

/**
     * RECHAZO SIMPLE: Cambia el estatus del pago y guarda el motivo.
     * No afecta el Ledger porque el asiento solo se crea al validar.
     */
public function rejectCashPayment(int $paymentId, string $reason, int $adminId): bool
{
    try {
        // SQL ajustado a tu estructura real:
        // Eliminamos updated_at porque no existe en tu tabla
        $sql = "UPDATE tbl_financial_payments 
                SET status = 'REJECTED', 
                    observation = ?, 
                    collector_id = ? 
                WHERE id = ? AND status = 'PENDING'";
        
        $stmt = $this->db->prepare($sql);
        
        // Ejecutamos pasando los parámetros en el orden correcto
        return $stmt->execute([
            $reason,   // va a observation
            $adminId,  // va a collector_id
            $paymentId // para el WHERE
        ]);

    } catch (Exception $e) {
        // Esto te ayudará a ver errores de sintaxis en el log
        error_log("Error en rejectCashPayment: " . $e->getMessage());
        return false;
    }
}


}