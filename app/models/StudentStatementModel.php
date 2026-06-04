<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL
 * ARCHIVO: app/models/StudentStatementModel.php
 * PROPÓSITO: Consultas multi-programa de perfil financiero y movimientos.
 * VERSIÓN: 3.0.0 - Refactor Premium: Homologación de fechas, protección contra nulos y cruce exacto de pagos.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class StudentStatementModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Datos básicos para el encabezado del PDF Historial de Pagos (Global)
     * Resuelve el problema de "Información vacía" en el PDF consolidado.
     */
    public function getBasicStudentData(int $userId): ?array
    {
        $sql = "SELECT 
                    u.first_name, 
                    u.last_name, 
                    u.document_id, 
                    s.id as student_id
                FROM tbl_users u
                INNER JOIN tbl_students s ON u.id = s.user_id
                WHERE u.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    /**
     * Obtiene los programas inscritos del alumno para el selector de tarjetas.
     */
    public function getMyPrograms(int $userId): array
    {
        $sql = "SELECT 
                    ao.id as offering_id,
                    d.name as diplomado,
                    c.name as cohorte
                FROM tbl_student_matriculations m
                INNER JOIN tbl_students s ON m.student_id = s.id
                INNER JOIN tbl_academic_offerings ao ON m.offering_id = ao.id
                INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                INNER JOIN tbl_cohortes c ON ao.cohort_id = c.id
                WHERE s.user_id = ?
                ORDER BY ao.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el perfil financiero filtrando el Ledger a través de la relación con inscripciones.
     */
    public function getProfileByProgram(int $userId, int $offeringId): ?array
    {
        $sql = "SELECT 
                    s.id as student_id,
                    u.id as user_id,
                    u.first_name,
                    u.last_name,
                    u.document_id,
                    COALESCE(d.name, 'N/A') as diplomado,
                    COALESCE(c.name, 'N/A') as cohorte,
                    /* Cálculo de totales vinculados a la oferta mediante join con inscripciones */
                    COALESCE((SELECT SUM(l.amount_due) 
                              FROM tbl_financial_student_ledger l
                              INNER JOIN tbl_enrollments e ON l.enrollment_id = e.id
                              WHERE l.user_id = u.id AND e.offering_id = :offId1 AND l.status != 'ANULADO'), 0) as total_due,
                    COALESCE((SELECT SUM(l.amount_paid) 
                              FROM tbl_financial_student_ledger l
                              INNER JOIN tbl_enrollments e ON l.enrollment_id = e.id
                              WHERE l.user_id = u.id AND e.offering_id = :offId2 AND l.status != 'ANULADO'), 0) as total_paid,
                    /* Buscar la fecha del último pago validado */
                    (SELECT DATE_FORMAT(MAX(created_at), '%d/%m/%Y') 
                     FROM tbl_financial_payments 
                     WHERE student_id = s.id AND status IN ('APPROVED', 'VALIDADO', 'PAGADO')) as last_payment_date
                FROM tbl_users u
                INNER JOIN tbl_students s ON u.id = s.user_id
                INNER JOIN tbl_academic_offerings ao ON ao.id = :offId3
                INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                INNER JOIN tbl_cohortes c ON ao.cohort_id = c.id
                WHERE u.id = :userId
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':userId' => $userId,
            ':offId1' => $offeringId,
            ':offId2' => $offeringId,
            ':offId3' => $offeringId
        ]);
        
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$profile) return null;

        // Blindaje contra nulos si el estudiante es nuevo y aún no tiene pagos
        $profile['last_payment_date'] = $profile['last_payment_date'] ?: 'Sin pagos registrados';
        $profile['balance'] = (float)$profile['total_due'] - (float)$profile['total_paid'];
        
        return $profile;
    }

    /**
     * Obtiene los movimientos (Ledger) vinculados a una oferta específica.
     */
    public function getLedgerByProgram(int $userId, int $offeringId): array
    {
        $sql = "SELECT 
                    l.id,
                    l.concept,
                    l.amount_due,
                    l.amount_paid,
                    l.due_date,
                    DATE_FORMAT(l.due_date, '%d/%m/%Y') as formatted_date,
                    l.status,
                    p.reference_id
                FROM tbl_financial_student_ledger l
                INNER JOIN tbl_enrollments e ON l.enrollment_id = e.id
                LEFT JOIN tbl_financial_payments p ON l.payment_id = p.id
                WHERE l.user_id = ? AND e.offering_id = ? AND l.status != 'ANULADO'
                ORDER BY l.due_date ASC, l.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Historial Global de Pagos del Estudiante (Inscripción + Mensualidades).
     * VERSIÓN 3.1.0 - Blindaje contra errores de redondeo y rescate de $0.00
     */
    public function getPaymentHistory(int $userId): array
    {
        $sql = "
            SELECT 
                fecha,
                formatted_date,
                concepto,
                monto_real_bs,
                tasa,
                -- LÓGICA DE RESCATE: Si el USD es 0 o nulo, lo calculamos al vuelo para evitar el $0.00
                COALESCE(
                    NULLIF(monto_usd_raw, 0), 
                    ROUND(monto_real_bs / tasa, 2)
                ) as monto_usd,
                causa,
                tipo_pago,
                referencia
            FROM (
                /* 1. PAGOS DE INSCRIPCIÓN (ADMISIONES) */
                SELECT 
                    ep.created_at as fecha, 
                    DATE_FORMAT(ep.created_at, '%d/%m/%Y') as formatted_date,
                    'Pago de Inscripción' as concepto, 
                    -- Extraemos el monto nativo exacto (Ej: 5000.00)
                    COALESCE(CAST(JSON_VALUE(ep.payment_metadata, '$.detalles_transaccion.monto_nativo') AS DECIMAL(12,2)), ep.amount) as monto_real_bs,
                    -- Extraemos la tasa
                    COALESCE(CAST(JSON_VALUE(ep.payment_metadata, '$.tasa_cambio') AS DECIMAL(12,4)), 1) as tasa,
                    -- Extraemos el monto USD guardado
                    CAST(JSON_VALUE(ep.payment_metadata, '$.monto_sistema_usd') AS DECIMAL(12,2)) as monto_usd_raw,
                    'Inscripción' as causa, 
                    ep.method as tipo_pago, 
                    ep.reference_id as referencia
                FROM tbl_enrollments_payments ep
                INNER JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                WHERE e.user_id = :u1 AND ep.status IN ('APPROVED', 'VALIDADO', 'PAGADO')

                UNION ALL

                /* 2. PAGOS DE CUOTAS MENSUALES */
                SELECT 
                    fp.created_at as fecha, 
                    DATE_FORMAT(fp.created_at, '%d/%m/%Y') as formatted_date,
                    'Abono a cuenta' as concepto, 
                    COALESCE(CAST(JSON_VALUE(fp.payment_metadata, '$.detalles_transaccion.monto_nativo') AS DECIMAL(12,2)), fp.amount) as monto_real_bs,
                    COALESCE(CAST(JSON_VALUE(fp.payment_metadata, '$.tasa_cambio') AS DECIMAL(12,4)), 1) as tasa,
                    CAST(JSON_VALUE(fp.payment_metadata, '$.monto_sistema_usd') AS DECIMAL(12,2)) as monto_usd_raw,
                    'Pago / Abono Cuota' as causa, 
                    fp.method as tipo_pago, 
                    fp.reference_id as referencia
                FROM tbl_financial_payments fp
                INNER JOIN tbl_students s ON fp.student_id = s.id
                WHERE s.user_id = :u2 AND fp.status IN ('APPROVED', 'VALIDADO', 'PAGADO')
            ) AS p
            ORDER BY fecha ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':u1' => $userId, ':u2' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaymentVoucherData(string $tipo, string $referencia, int $userId): ?array
{
    if ($tipo === 'inscripcion') {
        $sql = "SELECT 
                    ep.method,
                    ep.amount,
                    ep.currency,
                    ep.reference_id,
                    ep.screenshot_path,
                    ep.payment_metadata
                FROM tbl_enrollments_payments ep
                INNER JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                WHERE ep.reference_id = :ref
                  AND e.user_id = :uid
                  AND ep.status = 'APPROVED'
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
                INNER JOIN tbl_students s ON fp.student_id = s.id
                WHERE fp.reference_id = :ref
                  AND s.user_id = :uid
                  AND fp.status = 'APPROVED'
                LIMIT 1";
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute([':ref' => $referencia, ':uid' => $userId]);
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


}