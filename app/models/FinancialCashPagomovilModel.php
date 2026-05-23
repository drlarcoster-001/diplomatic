<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / PAGO MÓVIL
 * ARCHIVO: app/models/FinancialCashPagomovilModel.php
 * PROPÓSITO: Manejo de persistencia para conciliación bancaria, cálculo multidivisa y generación atómica de Ledger.
 * VERSIÓN: 3.3.0 - FIX: Fallback en getLastGlobalRate para evitar visualización de 0,00 Bs.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class FinancialCashPagomovilModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Guarda las transacciones del banco y devuelve el número exacto de registros NUEVOS.
     */
    public function saveStatementBatch(array $data, int $userId): int
    {
        if (empty($data)) return 0;

        $insertedCount = 0;
        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();

            $sql = "INSERT IGNORE INTO tbl_financial_bank_transactions_mobile 
                    (op_type, op_date, reference_id, origin_phone, origin_bank, amount, admin_id, created_at) 
                    VALUES ('NC', ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);

            foreach ($data as $row) {
                $stmt->execute([
                    $row['date_tran'],
                    $row['reference'],
                    $row['phone_source'],
                    $row['bank_source'],
                    $row['amount_bs'],
                    $userId
                ]);
                $insertedCount += $stmt->rowCount();
            }

            $this->db->commit();
            return $insertedCount;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Error en FinancialCashPagomovilModel::saveStatementBatch -> " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene los pagos registrados (PENDING) con conciliación blindada.
     * FIX: Se elimina el salto a la tasa actual y se ancla la fecha al registro.
     */
public function getPendingPayments(array $filters = []): array
    {
        $params = [];
        
        $sql = "SELECT 
                    ep.id,
                    ep.enrollment_id,
                    ep.screenshot_path,
                    ep.payment_metadata,

                    'NC' as tipo,

                    COALESCE(
                        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.fecha_comprobante')), ''),
                        DATE(ep.created_at)
                    ) as fecha,

                    CONCAT(u.first_name, ' ', u.last_name) as estudiante,

                    JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_origen.banco_emisor')) as banco_origen,
                    JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_origen.cuenta_correo_telf')) as telefono_origen,

                    RIGHT(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.referencia')), 4) as referencia,

                    JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.monto_nativo')) as monto,

                    JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.tasa_cambio')) as tasa_bcv,
                    JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.monto_sistema_usd')) as monto_usd,

(
                        -- Primero busca en T-Pago
                        (SELECT COUNT(*) 
                         FROM tbl_financial_bank_transactions_mobile btm 
                         WHERE btm.amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.monto_nativo')) AS DECIMAL(20,2))
                         AND RIGHT(btm.reference_id, 4) = RIGHT(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.referencia')), 4)
                         AND ABS(DATEDIFF(btm.op_date, COALESCE(
                             NULLIF(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.fecha_comprobante')), ''),
                             DATE(ep.created_at)
                         ))) <= 3
                        )
                        +
                        -- Si no está en T-Pago, busca en Movimientos Mercantil
                        (SELECT COUNT(*) 
                         FROM tbl_financial_bank_transactions_account bta 
                         WHERE bta.amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.monto_nativo')) AS DECIMAL(20,2))
                         AND RIGHT(bta.reference_id, 4) = RIGHT(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.referencia')), 4)
                         AND ABS(DATEDIFF(bta.op_date, COALESCE(
                             NULLIF(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.fecha_comprobante')), ''),
                             DATE(ep.created_at)
                         ))) <= 3
                        )
                    ) as match_found

                FROM tbl_enrollments_payments ep
                JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                JOIN tbl_users u ON e.user_id = u.id
                WHERE ep.method = 'PAGOMOVIL' 
                AND ep.status = 'PENDING'";

        if (!empty($filters['text'])) {
            $sql .= " AND (JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.referencia')) LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $search = "%" . $filters['text'] . "%";
            array_push($params, $search, $search, $search);
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.fecha_comprobante')), ''), DATE(ep.created_at)) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.fecha_comprobante')), ''), DATE(ep.created_at)) <= ?";
            $params[] = $filters['date_to'];
        }

        $order = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.detalles_transaccion.fecha_comprobante')), ''), DATE(ep.created_at)) {$order}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Obtiene la tasa más reciente. 
     * FIX: Si no hay una 'ACTIVE', toma la última registrada por ID para evitar el 0,00.
     */
    public function getLastGlobalRate(): float
{
    // Buscamos la última tasa activa según fecha e ID
    $sql = "SELECT dolar_bcv FROM tbl_financial_exchange_rates 
            WHERE status = 'ACTIVE' 
            ORDER BY rate_date DESC, id DESC LIMIT 1";
    $stmt = $this->db->query($sql);
    $rate = $stmt->fetchColumn();

    // Si no hay activas, traemos la última registrada sin filtro de estatus
    if (!$rate) {
        $rate = $this->db->query("SELECT dolar_bcv FROM tbl_financial_exchange_rates ORDER BY id DESC LIMIT 1")->fetchColumn();
    }

    return $rate ? (float)$rate : 0.00;
}


    public function rejectPayment(int $paymentId, int $adminId): bool
    {
        try {
            $sql = "UPDATE tbl_enrollments_payments SET status = 'REJECTED', validated_by = ?, validation_date = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$adminId, $paymentId]);
        } catch (Exception $e) {
            error_log("Error rejectPayment (Pago Móvil): " . $e->getMessage());
            return false;
        }
    }
/**
     * APROBACIÓN DE PAGO: Cierra el ciclo y genera el Ledger.
     * FIX: Se elimina el cálculo de tasa manual y se extrae el monto USD directamente del JSON.
     */
    public function approvePayment(int $paymentId, string $reference, int $adminId): bool
    {
        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();

            // 1. Obtenemos los datos extrayendo la VERDAD del JSON (Metadata)
            $sqlData = "SELECT 
                            ep.enrollment_id, 
                            e.user_id, 
                            e.offering_id, 
                            ep.amount as monto_pagado_bs,
                            -- EXTRAEMOS LOS VALORES FIJOS DEL JSON
                            JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.monto_sistema_usd')) as monto_usd_fijo,
                            JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.tasa_cambio')) as tasa_fija
                        FROM tbl_enrollments_payments ep
                        JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                        WHERE ep.id = ?";
            
            $stmtData = $this->db->prepare($sqlData);
            $stmtData->execute([$paymentId]);
            $payInfo = $stmtData->fetch(PDO::FETCH_ASSOC);

            if (!$payInfo) throw new Exception("Error: No se encontró el registro del pago.");

            // Convertimos a números limpios lo que viene del JSON
            $tasaLimpia = (float)$payInfo['tasa_fija'];
            $montoFinalUSD = (float)$payInfo['monto_usd_fijo'];

            // 2. ACTUALIZACIÓN DE ESTATUS
            // Marcamos como aprobado y nos aseguramos de que el JSON tenga los valores correctos
            $sqlUpgrade = "UPDATE tbl_enrollments_payments 
                           SET status = 'APPROVED', 
                               validated_by = ?, 
                               validation_date = NOW()
                           WHERE id = ?";
            $this->db->prepare($sqlUpgrade)->execute([$adminId, $paymentId]);

            // Marcamos la conciliación en el banco (Lógica de 6 dígitos)
            $this->db->prepare("UPDATE tbl_financial_bank_transactions_mobile SET is_reconciled = 1, admin_id = ? WHERE reference_id = ?")
                     ->execute([$adminId, $reference]);

            // 3. GENERACIÓN DE LEDGER (Usando los valores reales del JSON)
            $this->generateLedger(
                (int)$payInfo['enrollment_id'], 
                (int)$payInfo['user_id'], 
                (int)$payInfo['offering_id'], 
                $paymentId, 
                $tasaLimpia, 
                $montoFinalUSD
            );

            return $this->db->commit();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Error en approvePayment: " . $e->getMessage());
            return false;
        }
    }



 public function approveMassivePayments(array $payments, int $adminId): int
    {
        $count = 0;
        foreach ($payments as $payment) {
            try {
                if ($this->approvePayment((int)$payment['id'], (string)$payment['reference'], $adminId)) {
                    $count++;
                }
            } catch (Exception $e) {
                error_log("Error aprobando pago ID {$payment['id']}: " . $e->getMessage());
            }
        }
        return $count;
    }   
    
private function generateLedger(int $enrollId, int $userId, int $offeringId, int $paymentId, float $tasa, float $montoPagadoUSD): void
{
    $sqlPlan = "SELECT name, amount, due_date FROM tbl_academic_offering_payment_plans WHERE offering_id = ? ORDER BY id ASC";
    $stmtPlan = $this->db->prepare($sqlPlan);
    $stmtPlan->execute([$offeringId]);
    $plans = $stmtPlan->fetchAll(PDO::FETCH_ASSOC);

    if (empty($plans)) throw new Exception("No hay plan de pagos.");

    $sqlLedger = "INSERT INTO tbl_financial_student_ledger 
                  (enrollment_id, user_id, payment_id, concept, amount_due, amount_paid, exchange_rate, due_date, status, processed_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmtLedger = $this->db->prepare($sqlLedger);

    $dineroRestante = $montoPagadoUSD;

    foreach ($plans as $plan) {
        $costoCuota = (float)$plan['amount'];
        $diferencia = $costoCuota - $dineroRestante;

        // TOLERANCIA: Si faltan menos de $0.15 USD, damos la cuota por pagada.
        // Esto evita que Paul (User 65) quede con deudas de centavos.
        if ($dineroRestante > 0 && $diferencia <= 0.15) {
            $abono = $costoCuota; // Forzamos el monto de la cuota completa
            $estatus = 'PAGADO';
            $dineroRestante = max(0, $dineroRestante - $costoCuota);
        } elseif ($dineroRestante >= $costoCuota) {
            $abono = $costoCuota;
            $estatus = 'PAGADO';
            $dineroRestante -= $costoCuota;
        } elseif ($dineroRestante > 0) {
            $abono = $dineroRestante;
            $estatus = 'ABONADO'; 
            $dineroRestante = 0;
        } else {
            $abono = 0.00;
            $estatus = 'PENDIENTE';
        }

        $stmtLedger->execute([
            $enrollId, $userId, ($abono > 0) ? $paymentId : null,
            $plan['name'], $costoCuota, $abono, $tasa,
            $plan['due_date'] ?? date('Y-m-d'), $estatus
        ]);
    }

    // Si sobra dinero después de pagar todo, se va a SALDO A FAVOR
    if ($dineroRestante > 0.15) {
        $stmtLedger->execute([
            $enrollId, $userId, $paymentId, 'SALDO A FAVOR (SOBREPAGO)', 
            0.00, $dineroRestante, $tasa, date('Y-m-d'), 'A FAVOR'
        ]);
    }
}

}