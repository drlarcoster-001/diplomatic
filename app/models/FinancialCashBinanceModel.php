<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / BINANCE PAY
 * ARCHIVO: app/models/FinancialCashBinanceModel.php
 * PROPÓSITO: Persistencia para pagos Binance (USDT), validación de referencias y Ledger 1:1 USD.
 * VERSIÓN: 1.0.0 - Clonación de arquitectura Zelle adaptada a Binance Pay.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class FinancialCashBinanceModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene pagos de Binance pendientes con filtros.
     */
    public function getPendingBinancePayments(array $filters = []): array
    {
        $params = [];
        $sql = "SELECT 
                    ep.id,
                    ep.enrollment_id,
                    u.id as user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as estudiante,
                    ep.reference_id as referencia,
                    ep.amount as monto_usd,
                    ep.screenshot_path,
                    
                    -- Extracción segura del titular desde el JSON de Binance
                    COALESCE(
                        JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_origen.nombre_titular')), 
                        'No reportado'
                    ) as titular,
                    
                    -- Extracción del correo/ID de Binance
                    COALESCE(
                        JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_origen.cuenta_correo_telf')), 
                        'No reportado'
                    ) as correo,
                    
                    JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.fecha_comprobante')) as fecha_pago
                FROM tbl_enrollments_payments ep
                JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                JOIN tbl_users u ON e.user_id = u.id
                WHERE UPPER(TRIM(ep.method)) = 'BINANCE' AND ep.status = 'PENDING'";

        if (!empty($filters['text'])) {
            $sql .= " AND (ep.reference_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $search = "%{$filters['text']}%";
            array_push($params, $search, $search, $search);
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.fecha_comprobante')) BETWEEN ? AND ?";
            array_push($params, $filters['date_from'], $filters['date_to']);
        }

        $sql .= " ORDER BY ep.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Rechaza un pago Binance.
     */
    public function rejectBinancePayment(int $paymentId, int $adminId): bool
    {
        try {
            $sql = "UPDATE tbl_enrollments_payments SET status = 'REJECTED', validated_by = ?, validation_date = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$adminId, $paymentId]);
        } catch (Exception $e) {
            error_log("Error rejectBinancePayment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aprobación con Cascada (USDT 1:1 USD).
     */
    public function approveBinancePayment(int $paymentId, int $adminId): bool
    {
        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();

            $sqlData = "SELECT ep.enrollment_id, ep.amount as monto_pagado, e.user_id, e.offering_id 
                        FROM tbl_enrollments_payments ep
                        JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                        WHERE ep.id = ?";
            $stmt = $this->db->prepare($sqlData);
            $stmt->execute([$paymentId]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$info) throw new Exception("Registro de pago Binance no encontrado.");

            // 1. Aprobación financiera
            $this->db->prepare("UPDATE tbl_enrollments_payments SET status = 'APPROVED', validated_by = ?, validation_date = NOW() WHERE id = ?")
                     ->execute([$adminId, $paymentId]);

            // 2. EXPLOSIÓN DE DEUDA EN CASCADA
            $this->generateLedgerUSD((int)$info['enrollment_id'], (int)$info['user_id'], (int)$info['offering_id'], $paymentId, (float)$info['monto_pagado']);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Error approveBinancePayment: " . $e->getMessage());
            return false;
        }
    }

    private function generateLedgerUSD(int $enrollId, int $userId, int $offeringId, int $paymentId, float $montoPagado): void
    {
        $sqlPlan = "SELECT name, amount, due_date FROM tbl_academic_offering_payment_plans WHERE offering_id = ? ORDER BY id ASC";
        $stmtPlan = $this->db->prepare($sqlPlan);
        $stmtPlan->execute([$offeringId]);
        $plans = $stmtPlan->fetchAll(PDO::FETCH_ASSOC);

        $sqlLedger = "INSERT INTO tbl_financial_student_ledger 
                      (enrollment_id, user_id, payment_id, concept, amount_due, amount_paid, exchange_rate, due_date, status, processed_at) 
                      VALUES (?, ?, ?, ?, ?, ?, 1.0000, ?, ?, NOW())";
        $stmtLedger = $this->db->prepare($sqlLedger);

        $dineroRestante = $montoPagado;

        foreach ($plans as $plan) {
            $costoCuota = (float)$plan['amount'];
            if ($dineroRestante >= $costoCuota) {
                $abono = $costoCuota; $estatus = 'PAGADO'; $dineroRestante -= $costoCuota;
            } elseif ($dineroRestante > 0) {
                $abono = $dineroRestante; $estatus = 'ABONADO'; $dineroRestante = 0;
            } else {
                $abono = 0.00; $estatus = 'PENDIENTE';
            }

            $stmtLedger->execute([
                $enrollId, $userId, ($abono > 0) ? $paymentId : null, 
                $plan['name'], $costoCuota, $abono, 
                $plan['due_date'] ?? date('Y-m-d'), $estatus
            ]);
        }

        if ($dineroRestante > 0) {
            $stmtLedger->execute([
                $enrollId, $userId, $paymentId, 'SALDO A FAVOR (SOBREPAGO)', 
                0.00, $dineroRestante, date('Y-m-d'), 'A FAVOR'
            ]);
        }
    }

    /**
     * Rechaza el pago, limpia el Ledger y anula la inscripción (RECHAZADO).
     * Incluye detección inteligente de cruce de IDs.
     */
    public function rejectPaymentWithCleanup(int $paymentId, int $enrollId, int $adminId): bool
    {
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            // 1. INTELIGENCIA: Si no tenemos el enrollId, lo buscamos usando el paymentId
            if ($enrollId <= 0 && $paymentId > 0) {
                $stmt = $this->db->prepare("SELECT enrollment_id FROM tbl_enrollments_payments WHERE id = ? LIMIT 1");
                $stmt->execute([$paymentId]);
                $enrollId = (int)$stmt->fetchColumn();
            }

            // 2. INTELIGENCIA: Por si el Frontend mandó el enrollment_id disfrazado de payment_id
            if ($enrollId === 0 && $paymentId > 0) {
                $check = $this->db->prepare("SELECT id FROM tbl_enrollments WHERE id = ?");
                $check->execute([$paymentId]);
                if ($check->fetchColumn()) {
                    $enrollId = $paymentId; 
                }
            }

            // 3. LIMPIEZA MAESTRA
            if ($enrollId > 0) {
                // A. Borrar Ledger (Adiós deuda)
                $this->db->prepare("DELETE FROM tbl_financial_student_ledger WHERE enrollment_id = ?")
                         ->execute([$enrollId]);
                
                // B. Forzar estatus a RECHAZADO en la tabla principal
                $this->db->prepare("UPDATE tbl_enrollments SET status = 'RECHAZADO' WHERE id = ?")
                         ->execute([$enrollId]);
            }

            // 4. Marcar el pago específico como RECHAZADO
            if ($paymentId > 0) {
                $this->db->prepare("UPDATE tbl_enrollments_payments SET status = 'REJECTED' WHERE id = ?")
                         ->execute([$paymentId]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en rejectPaymentWithCleanup (Binance): " . $e->getMessage());
            return false;
        }
    }
}