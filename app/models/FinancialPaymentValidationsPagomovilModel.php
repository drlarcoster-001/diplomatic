<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS
 * ARCHIVO: app/models/FinancialPaymentValidationsPagomovilModel.php
 * VERSIÓN: 3.0.0 - CASCADE & RECONCILED FIX
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class FinancialPaymentValidationsPagomovilModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene la tasa BCV más reciente (Forzando la búsqueda por fecha de calendario).
     */
    public function getLastGlobalRate(): float
    {
        $sql = "SELECT dolar_bcv FROM tbl_financial_exchange_rates ORDER BY rate_date DESC, id DESC LIMIT 1";
        $stmt = $this->db->query($sql);
        $rate = $stmt->fetchColumn();

        return $rate ? (float)$rate : 0.00;
    }

/**
     * Obtiene los pagos PENDING con cruce de conciliación y paginación.
     */
public function getPendingPayments(array $filters = [], int $limit = 25, int $offset = 0): array
{
    $sql = "SELECT 
                fp.id, fp.payment_metadata, fp.student_id,
                CONCAT(s.first_name, ' ', s.last_name) as estudiante,
                fp.screenshot_path,

                JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.referencia')) as referencia,

                JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.monto_nativo')) as monto_bs_json,
                JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.monto_sistema_usd')) as monto_usd_json,
                JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.tasa_cambio')) as tasa_pago_json,
                JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.fecha_comprobante')) as fecha_json,

                (
                    -- Primero busca en T-Pago
                    (SELECT COUNT(*) 
                     FROM tbl_financial_bank_transactions_mobile btm 
                     WHERE RIGHT(btm.reference_id, 4) = RIGHT(JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.referencia')), 4)
                     AND ABS(btm.amount - CAST(JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.monto_nativo')) AS DECIMAL(20,2))) <= 0.01
                     AND ABS(DATEDIFF(btm.op_date, JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.fecha_comprobante')))) <= 3
                    )
                    +
                    -- Si no está en T-Pago, busca en Movimientos Mercantil
                    (SELECT COUNT(*) 
                     FROM tbl_financial_bank_transactions_account bta 
                     WHERE RIGHT(bta.reference_id, 4) = RIGHT(JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.referencia')), 4)
                     AND ABS(bta.amount - CAST(JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.monto_nativo')) AS DECIMAL(20,2))) <= 0.01
                     AND ABS(DATEDIFF(bta.op_date, JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.fecha_comprobante')))) <= 3
                    )
                ) as match_found

            FROM tbl_financial_payments fp
            INNER JOIN tbl_students s ON fp.student_id = s.id
            WHERE fp.method = 'PAGOMOVIL' AND UPPER(fp.status) = 'PENDING'";

    $bindings = [];

    if (!empty($filters['text'])) {
        $sql .= " AND (JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.referencia')) LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
        $term = "%" . trim($filters['text']) . "%";
        $bindings[] = $term;
        $bindings[] = $term;
        $bindings[] = $term;
    }

    if (!empty($filters['date_from'])) {
        $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.fecha_comprobante')) >= ?";
        $bindings[] = trim($filters['date_from']);
    }

    if (!empty($filters['date_to'])) {
        $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.fecha_comprobante')) <= ?";
        $bindings[] = trim($filters['date_to']);
    }

    $order = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
    $sql .= " ORDER BY JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.detalles_transaccion.fecha_comprobante')) {$order} LIMIT ? OFFSET ?";

    $stmt = $this->db->prepare($sql);

    foreach ($bindings as $i => $value) {
        $stmt->bindValue($i + 1, $value, PDO::PARAM_STR);
    }

    $stmt->bindValue(count($bindings) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($bindings) + 2, $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getTotalPendingPayments(array $filters = []): int
{
    $params = [];
    $sql = "SELECT COUNT(fp.id)
            FROM tbl_financial_payments fp
            INNER JOIN tbl_students s ON fp.student_id = s.id
            WHERE fp.method = 'PAGOMOVIL' AND UPPER(fp.status) = 'PENDING'";

    if (!empty($filters['text'])) {
        $sql .= " AND (fp.reference_id LIKE ? OR CONCAT(s.first_name, ' ', s.last_name) LIKE ?)";
        $search = "%" . $filters['text'] . "%";
        array_push($params, $search, $search);
    }

    // ✅ NUEVO
    if (!empty($filters['date'])) {
        $sql .= " AND DATE(fp.created_at) = ?";
        $params[] = $filters['date'];
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}



/**
     * LÓGICA MAESTRA: APROBACIÓN EN CASCADA
     * Versión 3.5.2 - Blindada contra centavos y sincronizada con Estatus Activo
     */
    public function approvePaymentCascade(int $paymentId, int $adminId): bool
    {
        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();

            // 1. Obtener datos del pago (Basado en tu tbl_financial_payments)
            $stmt = $this->db->prepare("SELECT student_id, reference_id, payment_metadata, matriculation_id FROM tbl_financial_payments WHERE id = ?");
            $stmt->execute([$paymentId]);
            $pago = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pago) throw new Exception("Registro de pago no encontrado.");

            $studentId = (int)$pago['student_id'];
            $referencia = $pago['reference_id'];
            
            // Extraer monto USD del JSON
            $meta = json_decode($pago['payment_metadata'], true);
            $montoDisponible = (float)($meta['monto_sistema_usd'] ?? 0);

            if ($montoDisponible <= 0) throw new Exception("El monto en el JSON es 0. No se puede procesar.");

            // 2. Buscamos el user_id y enrollment_id en tbl_students para el Ledger y Activación
            $stmtStudent = $this->db->prepare("SELECT user_id, enrollment_id FROM tbl_students WHERE id = ? LIMIT 1");
            $stmtStudent->execute([$studentId]);
            $studentData = $stmtStudent->fetch(PDO::FETCH_ASSOC);

            if (!$studentData) throw new Exception("No se encontró el estudiante vinculado.");

            $userId = (int)$studentData['user_id'];
            $enrollId = (int)$studentData['enrollment_id'];

            // 3. Buscar cuotas en el Ledger (tbl_financial_student_ledger)
            $sqlLedger = "SELECT id, amount_due, amount_paid 
                          FROM tbl_financial_student_ledger 
                          WHERE user_id = ? AND status IN ('PENDIENTE', 'VENCIDO', 'ABONADO') 
                          ORDER BY due_date ASC";
            $stmtLedger = $this->db->prepare($sqlLedger);
            $stmtLedger->execute([$userId]);
            $cuotas = $stmtLedger->fetchAll(PDO::FETCH_ASSOC);

            // 4. REPARTO EN CASCADA CON TOLERANCIA DE CENTAVOS
            foreach ($cuotas as $cuota) {
                if ($montoDisponible < 0.01) break;

                $deudaPendiente = (float)$cuota['amount_due'] - (float)$cuota['amount_paid'];

                // TOLERANCIA: Si el dinero que tengo + 0.15 USD cubre la deuda, lo damos por PAGADO
                if (($montoDisponible + 0.15) >= $deudaPendiente) {
                    $this->db->prepare("UPDATE tbl_financial_student_ledger SET amount_paid = amount_due, status = 'PAGADO' WHERE id = ?")
                             ->execute([$cuota['id']]);
                    $montoDisponible = max(0, $montoDisponible - $deudaPendiente);
                } else {
                    // Abono parcial normal
                    $this->db->prepare("UPDATE tbl_financial_student_ledger SET amount_paid = amount_paid + ?, status = 'ABONADO' WHERE id = ?")
                             ->execute([$montoDisponible, $cuota['id']]);
                    $montoDisponible = 0;
                }
            }

            // 5. ACTUALIZAR REPORTE DE PAGO (Solo columna status según tu SQL)
            $this->db->prepare("UPDATE tbl_financial_payments SET status = 'APPROVED' WHERE id = ?")->execute([$paymentId]);

            // 6. CONCILIAR EN EL BANCO
            $this->db->prepare("UPDATE tbl_financial_bank_transactions_mobile SET is_reconciled = 1, admin_id = ? WHERE RIGHT(reference_id, 6) = RIGHT(?, 6)")
         ->execute([$adminId, $referencia]);

            // 7. SINCRONIZACIÓN DE ESTATUS (Lo que faltaba para evitar el error de la imagen)
            // Activamos al estudiante en tbl_students
            $this->db->prepare("UPDATE tbl_students SET status = 'ACTIVO' WHERE id = ?")->execute([$studentId]);
            
            // Aprobamos la inscripción en tbl_enrollments
            $this->db->prepare("UPDATE tbl_enrollments SET status = 'APROBADO' WHERE id = ?")->execute([$enrollId]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Error Cascade: " . $e->getMessage());
            throw new Exception("ERROR TÉCNICO: " . $e->getMessage());
        }
    }


/**
     * Rechaza el pago (Versión corregida para tu estructura SQL)
     */
    public function rejectPayment(int $paymentId, int $adminId): bool
    {
        // Solo actualizamos 'status' porque 'validated_by' no existe en tu tabla
        $sql = "UPDATE tbl_financial_payments SET status = 'REJECTED' WHERE id = ?";
        return $this->db->prepare($sql)->execute([$paymentId]);
    }
    /**
     * Guarda el lote del Excel.
     */
    public function saveStatementBatch(array $data, int $userId): int
    {
        if (empty($data)) return 0;
        $count = 0;
        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();
            $sql = "INSERT IGNORE INTO tbl_financial_bank_transactions_mobile (op_type, op_date, reference_id, origin_phone, origin_bank, amount, admin_id, created_at) VALUES ('NC', ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            foreach ($data as $row) {

            $montoQueLlego = $row['amount_bs'];
            // 🔥 RADAR: Vemos qué llegó desde el controlador
                    error_log("RADAR (Modelo) - El monto que llegó para la ref {$row['reference']} es: " . $montoQueLlego);

                    // Opcional: Si quieres probar si la Base de Datos es la culpable (el parásito final),
                    // descomenta la siguiente línea para forzar otro número justo antes del impacto:
                    // $montoQueLlego = 2222222.00; 

                    $stmt->execute([
                        $row['date_tran'], 
                        $row['reference'], 
                        $row['phone_source'], 
                        $row['bank_source'], 
                        $montoQueLlego, // Asegúrate de usar esta variable en el execute
                        $userId
                    ]);


                //$stmt->execute([$row['date_tran'], $row['reference'], $row['phone_source'], $row['bank_source'], $row['amount_bs'], $userId]);
                $count += $stmt->rowCount();
            }
            $this->db->commit();
            return $count;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return 0;
        }
    }
}

/**
 * Helper para extraer datos de JSON si tu versión de MySQL no soporta JSON_EXTRACT directamente en PHP
 */
function JSON_EXTRACT_VAL($json, $path) {
    $data = json_decode($json, true);
    $keys = explode('.', str_replace('$.', '', $path));
    foreach ($keys as $key) {
        $data = $data[$key] ?? null;
    }
    return $data;
}