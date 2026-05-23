<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / MODELOS
 * ARCHIVO: app/models/FinancialPaymentRegistrationModel.php
 * PROPÓSITO: Persistencia atómica para búsqueda, matrículas y registro de pagos en cuarentena.
 * VERSIÓN: 2.6.0 - FIX: Flexibilidad de moneda (BS/USD) y retorno de ID para flujo S5.
 * REGLA DE EQUIPO: El campo 'amount' guarda el monto físico recibido y 'currency' su moneda original.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class FinancialPaymentRegistrationModel 
{
    private PDO $db;

    public function __construct() 
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * PASO 2: Obtiene programas y calcula el saldo real (PENDIENTE/ABONADO).
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
                GROUP BY o.id, d.name, c.cohort_code, o.class_start, sm.enrollment_id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * PASO 1: Búsqueda reactiva de estudiantes.
     */
    public function searchStudents(string $query): array 
    {
        $sql = "SELECT u.id, u.first_name, u.last_name, u.document_id AS cedula, u.avatar, s.student_code
                FROM tbl_students s
                INNER JOIN tbl_users u ON s.user_id = u.id
                WHERE (u.first_name LIKE :q1 OR u.last_name LIKE :q2 OR u.document_id LIKE :q3 OR s.student_code LIKE :q4)
                AND s.status = 'ACTIVO' LIMIT 8";
        $stmt = $this->db->prepare($sql);
        $term = "%$query%";
        $stmt->execute([':q1' => $term, ':q2' => $term, ':q3' => $term, ':q4' => $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * PASO 4: Detalle de cuotas para el Modal de Estado de Cuenta.
     */
    public function getAccountStatusDetails(int $userId, int $offeringId): array 
    {
        $sql = "SELECT 
                    fsl.id,
                    fsl.concept,
                    fsl.amount_due,
                    fsl.amount_paid,
                    (fsl.amount_due - fsl.amount_paid) AS amount_pending,
                    fsl.due_date,
                    fsl.status
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
     * PERSISTENCIA FINAL: Registro Atómico del Pago en Cuarentena.
     * Los pagos se insertan en estatus PENDING para validación administrativa.
     * * @param array $data Contiene montos, moneda y metadata pre-procesada.
     * @return int|bool Retorna el ID del pago o false en caso de error.
     */
    public function registerPayment(array $data): int|bool 
    {
        try {
            $this->db->beginTransaction();

            // 1. Obtener IDs técnicos vinculados a la matrícula
            $stmtInfo = $this->db->prepare("
                SELECT s.id AS student_id, sm.id AS matriculation_id
                FROM tbl_student_matriculations sm
                INNER JOIN tbl_students s ON sm.student_id = s.id
                WHERE s.user_id = ? AND sm.offering_id = ? LIMIT 1
            ");
            $stmtInfo->execute([$data['user_id'], $data['offering_id']]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            if (!$info) {
                throw new Exception("Error: No se encontró matrícula válida para el programa seleccionado.");
            }

            // 2. Insertar registro de pago (Normalizado)
            // FIX: :curr ahora es dinámico y :amount no se redondea forzosamente aquí para no perder decimales BCV
            $sqlPay = "INSERT INTO tbl_financial_payments 
                        (student_id, matriculation_id, method, amount, currency, reference_id, payment_metadata, screenshot_path, status, collector_id, created_at) 
                       VALUES (:sid, :mid, :meth, :amount, :curr, :ref, :meta, :scr, 'PENDING', :coll, NOW())";
            
            $stmtPay = $this->db->prepare($sqlPay);
            $stmtPay->execute([
                ':sid'    => $info['student_id'],
                ':mid'    => $info['matriculation_id'],
                ':meth'   => $data['method'],
                ':amount' => $data['amount'],   // Monto físico (BS o USD)
                ':curr'   => $data['currency'], // Moneda física (BS o USD)
                ':ref'    => $data['reference_id'] ?? 'N/A',
                ':meta'   => is_array($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : $data['metadata'],
                ':scr'    => $data['screenshot_path'],
                ':coll'   => $data['collector_id']
            ]);

            $paymentId = (int)$this->db->lastInsertId();

            $this->db->commit();
            return $paymentId; 

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error Crítico en registerPayment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * TASA DE CAMBIO: Recupera la última tasa oficial BCV.
     */
    public function getLatestExchangeRate(): float 
    {
        $sql = "SELECT dolar_bcv FROM tbl_financial_exchange_rates WHERE status = 'ACTIVE' ORDER BY rate_date DESC, id DESC LIMIT 1";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (float)$result['dolar_bcv'] : 1.00;
    }

public function getEffectiveRate(string $date): array|false
    {
        $sql = "SELECT dolar_bcv, rate_date 
                FROM tbl_financial_exchange_rates 
                WHERE rate_date <= :target_date 
                AND status = 'ACTIVE' 
                ORDER BY rate_date DESC, id DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':target_date' => $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el documento de identidad del estudiante (Cédula) por su ID de usuario.
     */
    public function getStudentIdCard(int $userId): ?string 
    {
        $sql = "SELECT document_id FROM tbl_users WHERE id = :uid LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (string)$result['document_id'] : null;
    }
}