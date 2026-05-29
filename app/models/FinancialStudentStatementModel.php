<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / ESTADOS DE CUENTA
 * ARCHIVO: app/models/FinancialStudentStatementModel.php
 * PROPÓSITO: Motor contable unificado con resolución de llaves (User/Student/Matriculation).
 * VERSIÓN: 3.3.2 - Inclusión de historial completo (Inscripción + Cuotas) en getPaymentHistoryByEnrollment.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class FinancialStudentStatementModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

/**
     * PASO 1: Búsqueda predictiva de alumnos (CORREGIDA v3.3.3)
     * Devuelve el enrollment_id exacto para evitar cruces de perfiles.
     */
public function searchStudentsForDropdown(string $term): array
{
    $sql = "SELECT 
                e.id as enrollment_id, -- ID real de la cuenta financiera
                u.id as user_id,
                u.document_id,
                u.first_name,
                u.last_name,
                d.name as diplomado
            FROM tbl_users u
            INNER JOIN tbl_enrollments e ON u.id = e.user_id
            INNER JOIN tbl_academic_offerings ao ON e.offering_id = ao.id
            INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
            WHERE (u.document_id LIKE :term1 OR u.first_name LIKE :term2 OR u.last_name LIKE :term3 OR d.name LIKE :term4)
              AND u.status = 'ACTIVE'
              AND e.status != 'RECHAZADO'
            ORDER BY u.first_name ASC
            LIMIT 15";
            
    $stmt = $this->db->prepare($sql);
    $searchTerm = "%$term%";
    $stmt->execute([
        ':term1' => $searchTerm, 
        ':term2' => $searchTerm, 
        ':term3' => $searchTerm,
        ':term4' => $searchTerm
    ]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    /**
     * PASO 2: Perfil financiero segmentado por Inscripción.
     * ESTANDARIZACIÓN: Se usa 'enrollment_id' explícitamente para el Frontend.
     */
    public function getStudentFinancialProfileByEnrollment(int $enrollmentId): ?array
    {
        $sql = "SELECT 
                    e.id as enrollment_id,
                    u.id as user_id,
                    u.first_name,
                    u.last_name,
                    u.document_id,
                    d.name as diplomado,
                    c.name as cohorte,
                    /* Totales desde el Ledger (Fuente de verdad en USD) */
                    IFNULL((SELECT SUM(amount_due) FROM tbl_financial_student_ledger 
                            WHERE enrollment_id = e.id AND status != 'ANULADO'), 0) as total_due,
                    IFNULL((SELECT SUM(amount_paid) FROM tbl_financial_student_ledger 
                            WHERE enrollment_id = e.id AND status != 'ANULADO'), 0) as total_paid
                FROM tbl_enrollments e
                INNER JOIN tbl_users u ON e.user_id = u.id
                INNER JOIN tbl_academic_offerings ao ON e.offering_id = ao.id
                INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                INNER JOIN tbl_cohortes c ON ao.cohort_id = c.id
                WHERE e.id = ? 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$enrollmentId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile) return null;

        /**
         * Fecha de última actividad basada en pagos efectivos.
         */
        $sqlDate = "SELECT DATE_FORMAT(MAX(processed_at), '%d/%m/%Y') 
                    FROM tbl_financial_student_ledger 
                    WHERE enrollment_id = ? AND amount_paid > 0 AND status != 'ANULADO'";
        
        $stmtDate = $this->db->prepare($sqlDate);
        $stmtDate->execute([$enrollmentId]);
        $profile['last_payment_date'] = $stmtDate->fetchColumn() ?: 'Sin pagos registrados';

        $profile['balance'] = (float)$profile['total_due'] - (float)$profile['total_paid'];
        
        return $profile;
    }

    /**
     * PASO 3: Movimientos detallados (Ledger) por Inscripción.
     */
    public function getLedgerMovementsByEnrollment(int $enrollmentId): array
    {
        $sql = "SELECT 
                    l.id,
                    l.concept,
                    l.amount_due,
                    l.amount_paid,
                    DATE_FORMAT(l.due_date, '%d/%m/%Y') as formatted_date,
                    l.status,
                    p.reference_id
                FROM tbl_financial_student_ledger l
                LEFT JOIN tbl_financial_payments p ON l.payment_id = p.id
                WHERE l.enrollment_id = ? AND l.status != 'ANULADO'
                ORDER BY l.due_date ASC, l.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$enrollmentId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * PASO 4: Historial de Pagos Unificado.
     * IMPORTANTE: Resuelve la relación Pagos -> Matrícula -> Inscripción e incluye el pago inicial.
     */
public function getPaymentHistoryByEnrollment(int $enrollmentId): array
{
    $sql = "
        SELECT 
            payment_id,
            causa,
            fecha,
            formatted_date,
            concept,
            monto_real_bs,
            moneda,
            tasa,
            -- Si el monto_usd es 0 o nulo en el JSON, lo calculamos para que no quede vacío
            IF(monto_usd > 0, monto_usd, ROUND(monto_real_bs / tasa, 2)) as monto_usd,
            tipo_pago,
            referencia
        FROM (
            SELECT 
                ep.created_at as fecha,
                ep.id as payment_id,
                DATE_FORMAT(ep.created_at, '%d/%m/%Y') as formatted_date,
                'Pago Inicial de Inscripción' as concept,
                -- Ruta: detalles_transaccion -> monto_nativo
                CAST(JSON_VALUE(ep.payment_metadata, '$.detalles_transaccion.monto_nativo') AS DECIMAL(12,2)) as monto_real_bs,
                ep.currency as moneda,
                -- Ruta: raíz -> tasa_cambio
                CAST(JSON_VALUE(ep.payment_metadata, '$.tasa_cambio') AS DECIMAL(12,4)) as tasa,
                -- Ruta: raíz -> monto_sistema_usd
                CAST(JSON_VALUE(ep.payment_metadata, '$.monto_sistema_usd') AS DECIMAL(12,2)) as monto_usd,
                'Inscripción' as causa,
                ep.method as tipo_pago,
                ep.reference_id as referencia
            FROM tbl_enrollments_payments ep
            WHERE ep.enrollment_id = :enroll1 AND ep.status = 'APPROVED'

            UNION ALL

            SELECT 
                fp.created_at as fecha,
                fp.id as payment_id,
                DATE_FORMAT(fp.created_at, '%d/%m/%Y') as formatted_date,
                'Abono a Cuenta / Cuota' as concept,
                CAST(JSON_VALUE(fp.payment_metadata, '$.detalles_transaccion.monto_nativo') AS DECIMAL(12,2)) as monto_real_bs,
                fp.currency as moneda,
                CAST(JSON_VALUE(fp.payment_metadata, '$.tasa_cambio') AS DECIMAL(12,4)) as tasa,
                CAST(JSON_VALUE(fp.payment_metadata, '$.monto_sistema_usd') AS DECIMAL(12,2)) as monto_usd,
                'Mensualidad' as causa,
                fp.method as tipo_pago,
                fp.reference_id as referencia
            FROM tbl_financial_payments fp
            INNER JOIN tbl_student_matriculations m ON fp.matriculation_id = m.id
            WHERE m.enrollment_id = :enroll2 AND fp.status = 'APPROVED'
        ) AS pagos
        ORDER BY fecha ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([':enroll1' => $enrollmentId, ':enroll2' => $enrollmentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getPaymentVoucherData(string $tipo, string $referencia, int $enrollmentId): ?array
{
    if ($tipo === 'inscripcion') {
        $sql = "SELECT 
                    method,
                    amount,
                    currency,
                    reference_id,
                    screenshot_path,
                    payment_metadata
                FROM tbl_enrollments_payments
                WHERE reference_id = :ref
                  AND enrollment_id = :enroll
                  AND status = 'APPROVED'
                LIMIT 1";
    } else {
        $sql = "SELECT 
                    fp.method,
                    fp.amount,
                    fp.currency,
                    fp.reference_id,
                    fp.screenshot_path,
                    fp.payment_metadata
                FROM tbl_financial_payments fp
                INNER JOIN tbl_student_matriculations m ON fp.matriculation_id = m.id
                WHERE fp.reference_id = :ref
                  AND m.enrollment_id = :enroll
                  AND fp.status = 'APPROVED'
                LIMIT 1";
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute([':ref' => $referencia, ':enroll' => $enrollmentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) return null;

    $meta = json_decode($row['payment_metadata'] ?? '{}', true) ?? [];

    $result = [
        'method'          => $row['method'],
        'amount'          => $row['amount'],
        'currency'        => $row['currency'],
        'reference_id'    => $row['reference_id'],
        'screenshot_path' => $row['screenshot_path'],
        'origen'          => [
            'identificador'      => $meta['detalles_origen']['identificador']      ?? 'N/A',
            'cuenta_correo_telf' => $meta['detalles_origen']['cuenta_correo_telf'] ?? 'N/A',
            'banco_emisor'       => $meta['detalles_origen']['banco_emisor']       ?? 'N/A',
        ],
        'transaccion' => [
            'fecha_comprobante' => $meta['detalles_transaccion']['fecha_comprobante'] ?? 'N/A',
            'monto_nativo'      => $meta['detalles_transaccion']['monto_nativo']      ?? $row['amount'],
            'moneda_nativa'     => $meta['detalles_transaccion']['moneda_nativa']     ?? $row['currency'],
        ],
        'tasa_cambio' => $meta['tasa_cambio']       ?? 1,
        'monto_usd'   => $meta['monto_sistema_usd'] ?? $row['amount'],
    ];

    if ($row['method'] === 'CASH') {
        $result['arqueo'] = [
            'desglose_billetes' => $meta['desglose_billetes']            ?? [],
            'agente_receptor'   => $meta['auditoria']['agente_receptor'] ?? 'N/A',
            'fecha_recepcion'   => $meta['auditoria']['fecha_recepcion'] ?? 'N/A',
        ];
    }

    return $result;
}


public function eliminarPago(int $paymentId, int $enrollmentId): bool
{
    $this->db->beginTransaction();

    try {
        $stmt = $this->db->prepare("
            SELECT fp.id, fp.screenshot_path
            FROM tbl_financial_payments fp
            INNER JOIN tbl_student_matriculations m ON fp.matriculation_id = m.id
            WHERE fp.id = ? AND m.enrollment_id = ? AND fp.status = 'APPROVED'
            LIMIT 1
        ");
        $stmt->execute([$paymentId, $enrollmentId]);
        $pago = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pago) {
            throw new Exception('Pago no encontrado.');
        }

        // 1. Eliminar archivo físico
        if (!empty($pago['screenshot_path'])) {
            $fullPath = dirname(__DIR__, 2) . '/public/' . $pago['screenshot_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        // 2. Eliminar registro
        $this->db->prepare(
            "DELETE FROM tbl_financial_payments WHERE id = ?"
        )->execute([$pago['id']]);

        // 3. Recalcular ledger
        $this->recalcularLedger($enrollmentId);

        $this->db->commit();
        return true;

    } catch (Exception $e) {
        $this->db->rollBack();
        throw $e;
    }
}
/**
 * Recalcula los montos Bs. del ledger usando la tasa BCV actual.
 * Lee la tasa desde tbl_exchange_rates (la más reciente).
 */
public function recalcularLedger(int $enrollmentId): bool
{
    // 1. Resetear todas las filas activas
    $this->db->prepare("
        UPDATE tbl_financial_student_ledger
        SET amount_paid = 0, status = 'PENDIENTE', payment_id = NULL
        WHERE enrollment_id = ? AND status != 'ANULADO'
    ")->execute([$enrollmentId]);

    // 2. Sumar total pagado de inscripción
    // 2. Sumar total pagado de inscripción en USD
    $stmtInsc = $this->db->prepare("
        SELECT COALESCE(SUM(CAST(JSON_VALUE(payment_metadata, '$.monto_sistema_usd') AS DECIMAL(12,2))), 0) as total
        FROM tbl_enrollments_payments
        WHERE enrollment_id = ? AND status = 'APPROVED'
    ");
    $stmtInsc->execute([$enrollmentId]);
    $totalInsc = (float)$stmtInsc->fetchColumn();

    
    // 3. Sumar total pagado de cuotas en USD
    $stmtCuotas = $this->db->prepare("
        SELECT COALESCE(SUM(CAST(JSON_VALUE(fp.payment_metadata, '$.monto_sistema_usd') AS DECIMAL(12,2))), 0) as total
        FROM tbl_financial_payments fp
        INNER JOIN tbl_student_matriculations m ON fp.matriculation_id = m.id
        WHERE m.enrollment_id = ? AND fp.status = 'APPROVED'
    ");
    $stmtCuotas->execute([$enrollmentId]);
    $totalCuotas = (float)$stmtCuotas->fetchColumn();

    // 4. Total disponible para distribuir
    $montoRestante = $totalInsc + $totalCuotas;

    // 5. Distribuir en cascada sobre las cuotas en orden
    $stmtFilas = $this->db->prepare("
        SELECT id, amount_due
        FROM tbl_financial_student_ledger
        WHERE enrollment_id = ? AND status != 'ANULADO'
        ORDER BY due_date ASC, id ASC
    ");
    $stmtFilas->execute([$enrollmentId]);
    $filas = $stmtFilas->fetchAll(PDO::FETCH_ASSOC);

    foreach ($filas as $fila) {
        if ($montoRestante <= 0) break;

        $abonar = min($montoRestante, (float)$fila['amount_due']);
        $status = $abonar >= (float)$fila['amount_due'] ? 'PAGADO' : 'ABONADO';

        $this->db->prepare("
            UPDATE tbl_financial_student_ledger
            SET amount_paid = ?, status = ?
            WHERE id = ?
        ")->execute([$abonar, $status, $fila['id']]);

        $montoRestante -= $abonar;
    }

    return true;
}


}