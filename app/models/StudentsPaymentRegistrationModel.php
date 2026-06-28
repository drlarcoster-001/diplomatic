<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / MODELOS
 * ARCHIVO: app/models/StudentsPaymentRegistrationModel.php
 * PROPÓSITO: Persistencia y consultas estrictamente limitadas al estudiante en sesión.
 * VERSIÓN: 1.1.1 - FIX: Corregido Warning de índice "result" y habilitada moneda dinámica.
 * REGLA DE EQUIPO: El campo 'amount' guarda el monto físico y 'currency' la moneda nativa.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class StudentsPaymentRegistrationModel 
{
    private PDO $db;

    public function __construct() 
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * SEGURIDAD: Verifica si el usuario tiene un perfil de estudiante activo.
     */
    public function getStudentIdByUserId(int $userId): ?int 
    {
        $stmt = $this->db->prepare("SELECT id FROM tbl_students WHERE user_id = ? AND status = 'ACTIVO' LIMIT 1");
        $stmt->execute([$userId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? (int)$res['id'] : null;
    }

    /**
     * PASO 1 (Autogestión): Obtiene los datos del estudiante logueado.
     */
    public function getStudentDataById(int $userId): ?array 
    {
        $sql = "SELECT u.id, u.first_name, u.last_name, u.document_id AS cedula, u.avatar, s.student_code
                FROM tbl_users u
                INNER JOIN tbl_students s ON u.id = s.user_id
                WHERE u.id = :uid AND s.status = 'ACTIVO' LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * PASO 2: Obtiene programas del estudiante y calcula el saldo real (PENDIENTE/ABONADO).
     */
    public function getStudentEnrollments(int $userId): array 
    {
        $sql = "SELECT 
                    o.id AS offering_id, 
                    d.name AS diploma_name, 
                    c.cohort_code AS cohort_name,
                    o.class_start,
                    COALESCE((
                        SELECT SUM(fsl.amount_due - COALESCE(fsl.amount_paid, 0))
                        FROM tbl_financial_student_ledger fsl
                        WHERE fsl.enrollment_id = sm.enrollment_id 
                        AND fsl.user_id = u.id
                        AND fsl.status IN ('PENDIENTE', 'ABONADO')
                    ), 0) AS total_pending
                FROM tbl_users u
                INNER JOIN tbl_students s ON u.id = s.user_id
                INNER JOIN tbl_student_matriculations sm ON s.id = sm.student_id
                INNER JOIN tbl_academic_offerings o ON sm.offering_id = o.id
                LEFT JOIN tbl_diplomados d ON o.diploma_id = d.id
                LEFT JOIN tbl_cohortes c ON o.cohort_id = c.id
                WHERE u.id = :uid
                AND o.status = 'ABIERTA'
                AND c.cohort_status IN ('En curso', 'Reabierta')
                GROUP BY o.id, d.name, c.cohort_code, o.class_start, sm.enrollment_id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * PASO 4: Detalle de cuotas para el Modal de Estado de Cuenta.
     */
    public function getAccountStatusDetails(int $userId, int $offeringId): array 
    {
        $sql = "SELECT 
                    fsl.id, fsl.concept, fsl.amount_due, fsl.amount_paid,
                    (fsl.amount_due - fsl.amount_paid) AS amount_pending,
                    fsl.due_date, fsl.status
                FROM tbl_users u
                INNER JOIN tbl_students s ON u.id = s.user_id
                INNER JOIN tbl_student_matriculations sm ON s.id = sm.student_id
                INNER JOIN tbl_financial_student_ledger fsl ON sm.enrollment_id = fsl.enrollment_id
                WHERE u.id = :uid AND sm.offering_id = :oid
                ORDER BY fsl.due_date ASC, fsl.id ASC"; 

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':oid', $offeringId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * PERSISTENCIA FINAL: Registro Atómico del Pago por Autogestión.
     * Los pagos entran en estatus PENDING para validación administrativa.
     */
    public function registerPayment(array $data): int|bool 
    {
        try {
            $this->db->beginTransaction();

            // 1. Obtener IDs técnicos vinculados a la matrícula del estudiante
            $stmtInfo = $this->db->prepare("
                SELECT s.id AS student_id, sm.id AS matriculation_id
                FROM tbl_student_matriculations sm
                INNER JOIN tbl_students s ON sm.student_id = s.id
                WHERE s.user_id = ? AND sm.offering_id = ? LIMIT 1
            ");
            $stmtInfo->execute([$data['user_id'], $data['offering_id']]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            if (!$info) {
                throw new Exception("Error: No se encontró matrícula activa para este programa.");
            }

            // 2. Insertar en tbl_financial_payments
            // FIX: :curr ahora es dinámico y acepta lo enviado por el controlador (BS/USD)
            $sqlPay = "INSERT INTO tbl_financial_payments 
                        (student_id, matriculation_id, method, amount, currency, reference_id, payment_metadata, screenshot_path, status, collector_id, created_at) 
                       VALUES (:sid, :mid, :meth, :amount, :curr, :ref, :meta, :scr, 'PENDING', :coll, NOW())";
            
            $stmtPay = $this->db->prepare($sqlPay);
            $stmtPay->execute([
                ':sid'    => $info['student_id'],
                ':mid'    => $info['matriculation_id'],
                ':meth'   => $data['method'],
                ':amount' => $data['amount'],
                ':curr'   => $data['currency'],
                ':ref'    => $data['reference_id'] ?? 'N/A',
                ':meta'   => is_array($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : $data['metadata'],
                ':scr'    => $data['screenshot_path'],
                ':coll'   => $data['collector_id'] 
            ]);

            $paymentId = (int)$this->db->lastInsertId();

            $this->db->commit();
            return $paymentId;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Error Crítico Autogestión (registerPayment): " . $e->getMessage());
            return false;
        }
    }

    /**
     * TASA DE CAMBIO: Recupera la última tasa BCV activa.
     * FIX: Eliminada referencia inexistente a ['result'] que causaba Warnings.
     */
    public function getLatestExchangeRate(): float 
    {
        $sql = "SELECT dolar_bcv FROM tbl_financial_exchange_rates WHERE status = 'ACTIVE' ORDER BY rate_date DESC, id DESC LIMIT 1";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Acceso directo a la columna; si no hay datos, se asume paridad 1.00
        return $result ? (float)$result['dolar_bcv'] : 1.00;
    }

    public function getEffectiveRate(string $date) 
{
    // Busca la tasa igual o anterior a la fecha proporcionada para manejar feriados y fines de semana.
    $sql = "SELECT dolar_bcv, rate_date 
            FROM tbl_financial_exchange_rates 
            WHERE rate_date <= :target_date 
            AND status = 'ACTIVE' 
            ORDER BY rate_date DESC, id DESC 
            LIMIT 1";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':target_date' => $date]);
    return $stmt->fetch(\PDO::FETCH_ASSOC);
}
}