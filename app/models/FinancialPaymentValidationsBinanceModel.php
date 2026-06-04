<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS (BINANCE)
 * ARCHIVO: app/models/FinancialPaymentValidationsBinanceModel.php
 * PROPÓSITO: Persistencia corregida sincronizada con la integridad de FK_ledger_enroll_v4.
 * VERSIÓN: 1.0.7 - Fix: Sincronización de estatus 'PENDIENTE' y limpieza de caracteres.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class FinancialPaymentValidationsBinanceModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene pagos de Binance PENDING.
     */
    public function getPendingBinancePayments(array $filters = []): array
    {
        $params = [];
        $sql = "SELECT 
                    p.id,
                    p.student_id,
                    CONCAT(u.first_name, ' ', u.last_name) as estudiante,
                    p.reference_id as referencia,
                    p.amount as monto_usd,
                    p.screenshot_path,
                    COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p.payment_metadata, '$.binance_nick')), 'null'), 'No reportado') as titular,
                    COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p.payment_metadata, '$.fecha_pago')), 'null'), DATE_FORMAT(p.created_at, '%Y-%m-%d')) as fecha_pago
                FROM tbl_financial_payments p
                INNER JOIN tbl_students s ON p.student_id = s.id
                INNER JOIN tbl_users u ON s.user_id = u.id
                WHERE p.method = 'BINANCE' 
                AND p.status = 'PENDING'";

        if (!empty($filters['text'])) {
            $sql .= " AND (p.reference_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $search = "%{$filters['text']}%";
            array_push($params, $search, $search, $search);
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $sql .= " AND (DATE(p.created_at) BETWEEN ? AND ?)";
            array_push($params, $filters['date_from'], $filters['date_to']);
        }

        $sql .= " ORDER BY p.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Aprobación con búsqueda de enrollment_id activo.
     */
    public function approveBinancePayment(int $paymentId, int $adminId): bool
    {
        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();

            $sql = "SELECT s.user_id, p.amount, s.enrollment_id
                    FROM tbl_financial_payments p
                    INNER JOIN tbl_students s ON p.student_id = s.id
                    WHERE p.id = ? FOR UPDATE";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$paymentId]);
            $payInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payInfo || !$payInfo['enrollment_id']) {
                throw new Exception("El estudiante no posee una inscripción vinculada.");
            }

            // 1. Actualizar estatus del pago
            $this->db->prepare("UPDATE tbl_financial_payments SET status = 'APPROVED' WHERE id = ?")
                     ->execute([$paymentId]);

            // 2. Ejecutar Cascada
            $this->processCascadaUSD((int)$payInfo['enrollment_id'], (int)$payInfo['user_id'], $paymentId, (float)$payInfo['amount']);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw new Exception($e->getMessage()); 
        }
    }

    private function processCascadaUSD(int $enrollId, int $userId, int $paymentId, float $amountAvailable): void
    {
        // IMPORTANTE: Status debe ser 'PENDIENTE' para coincidir con el resto del sistema
        $sqlDeudas = "SELECT id, amount_due, amount_paid 
                      FROM tbl_financial_student_ledger 
                      WHERE user_id = ? AND enrollment_id = ? AND status IN ('PENDIENTE', 'VENCIDO', 'ABONADO') 
                      ORDER BY due_date ASC";
        
        $stmt = $this->db->prepare($sqlDeudas);
        $stmt->execute([$userId, $enrollId]);
        $deudas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $saldo = $amountAvailable;

        foreach ($deudas as $deuda) {
            if ($saldo <= 0) break;

            $montoYaPagado = (float)$deuda['amount_paid'];
            $montoTotalCuota = (float)$deuda['amount_due'];
            $pendiente = $montoTotalCuota - $montoYaPagado;

            if ($saldo >= $pendiente) {
                $pagoAplicado = $pendiente;
                $nuevoStatus = 'PAGADO';
                $saldo -= $pendiente;
            } else {
                $pagoAplicado = $saldo;
                $nuevoStatus = 'ABONADO';
                $saldo = 0;
            }

            $this->db->prepare("UPDATE tbl_financial_student_ledger 
                                SET amount_paid = amount_paid + ?, 
                                    status = ?, 
                                    payment_id = ?, 
                                    processed_at = NOW() 
                                WHERE id = ?")
                     ->execute([$pagoAplicado, $nuevoStatus, $paymentId, $deuda['id']]);
        }

        // 3. Registro de saldo A FAVOR
        if ($saldo > 0) {
            $this->db->prepare("INSERT INTO tbl_financial_student_ledger 
                                (enrollment_id, user_id, payment_id, concept, amount_due, amount_paid, exchange_rate, due_date, status, processed_at) 
                                VALUES (?, ?, ?, 'SALDO A FAVOR (Binance USDT)', 0.00, ?, 1.0000, CURDATE(), 'A FAVOR', NOW())")
                     ->execute([$enrollId, $userId, $paymentId, $saldo]);
        }
    }

    public function rejectBinancePayment(int $paymentId): bool
    {
        return $this->db->prepare("UPDATE tbl_financial_payments SET status = 'REJECTED' WHERE id = ?")
                        ->execute([$paymentId]);
    }
}