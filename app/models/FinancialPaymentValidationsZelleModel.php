<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS (ZELLE)
 * ARCHIVO: app/models/FinancialPaymentValidationsZelleModel.php
 * PROPÓSITO: Persistencia y lógica contable para la validación de cuotas vía Zelle (USD).
 * VERSIÓN: 1.0.1 - Sincronización con metadata y Cascada Ledger.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception; // IMPORTACIÓN CRÍTICA PARA EL MANEJO DE ERRORES

final class FinancialPaymentValidationsZelleModel
{
    private PDO $db;

    public function __construct() 
    { 
        $this->db = (new Database())->getConnection(); 
    }

    public function getPendingZellePayments(array $filters = []): array
    {
        $params = [];
        $sql = "SELECT p.id, p.student_id, CONCAT(u.first_name, ' ', u.last_name) as estudiante,
                       p.reference_id as referencia, p.amount as monto_usd, p.screenshot_path,
                       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p.payment_metadata, '$.detalles_origen.nombre_titular')), 'No reportado') as titular,
                       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p.payment_metadata, '$.detalles_origen.cuenta_correo_telf')), 'No reportado') as correo,
                       COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p.payment_metadata, '$.detalles_transaccion.fecha_comprobante')), DATE_FORMAT(p.created_at, '%Y-%m-%d')) as fecha_pago
                FROM tbl_financial_payments p
                INNER JOIN tbl_students s ON p.student_id = s.id
                INNER JOIN tbl_users u ON s.user_id = u.id
                WHERE p.method = 'ZELLE' AND p.status = 'PENDING'";

        if (!empty($filters['text'])) {
            $sql .= " AND (p.reference_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $search = "%{$filters['text']}%";
            array_push($params, $search, $search, $search);
        }

        $stmt = $this->db->prepare($sql . " ORDER BY p.id ASC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function approveZellePayment(int $paymentId, int $adminId): bool
    {
        try {
            $this->db->beginTransaction();

            // 1. Obtener datos del pago y conectarlo con el Ledger a través del estudiante
            $sqlPago = "SELECT p.amount, p.student_id, s.user_id, s.enrollment_id 
                        FROM tbl_financial_payments p
                        INNER JOIN tbl_students s ON p.student_id = s.id
                        WHERE p.id = ?";
            $stmtPago = $this->db->prepare($sqlPago);
            $stmtPago->execute([$paymentId]);
            $pago = $stmtPago->fetch(PDO::FETCH_OBJ);

            if (!$pago) {
                throw new Exception("El pago no existe.");
            }

            // 2. Cambiar el estatus del pago a APPROVED
            $this->db->prepare("UPDATE tbl_financial_payments SET status = 'APPROVED' WHERE id = ?")
                     ->execute([$paymentId]);

            // 3. EJECUTAR LA CASCADA EN EL LEDGER
            $montoDisponible = (float)$pago->amount;

            // Buscar todas las cuotas que aún necesitan dinero, ordenadas por fecha de vencimiento
            $sqlCuotas = "SELECT id, amount_due, amount_paid 
                          FROM tbl_financial_student_ledger 
                          WHERE user_id = ? AND enrollment_id = ? AND status IN ('PENDIENTE', 'VENCIDO', 'ABONADO') 
                          ORDER BY due_date ASC, id ASC";
            $stmtCuotas = $this->db->prepare($sqlCuotas);
            $stmtCuotas->execute([$pago->user_id, $pago->enrollment_id]);
            $cuotas = $stmtCuotas->fetchAll(PDO::FETCH_OBJ);

            foreach ($cuotas as $cuota) {
                if ($montoDisponible <= 0) break; // Si se acabó el dinero, salimos del ciclo

                $deudaActual = (float)$cuota->amount_due - (float)$cuota->amount_paid;

                if ($montoDisponible >= $deudaActual) {
                    // Paga la cuota completa
                    $nuevoPagado = (float)$cuota->amount_due;
                    $montoDisponible -= $deudaActual;
                    $nuevoStatus = 'PAGADO';
                } else {
                    // Abona a la cuota (no alcanza para el total)
                    $nuevoPagado = (float)$cuota->amount_paid + $montoDisponible;
                    $montoDisponible = 0;
                    $nuevoStatus = 'ABONADO';
                }

                // Actualizar la cuota en el Ledger
                $this->db->prepare("UPDATE tbl_financial_student_ledger 
                                    SET amount_paid = ?, status = ?, processed_at = NOW(), payment_id = ? 
                                    WHERE id = ?")
                         ->execute([$nuevoPagado, $nuevoStatus, $paymentId, $cuota->id]);
            }

            // 4. Si después de pagar todo aún sobra dinero, crear el saldo A FAVOR
            if ($montoDisponible > 0) {
                $this->db->prepare("INSERT INTO tbl_financial_student_ledger 
                                    (enrollment_id, payment_id, user_id, concept, amount_due, amount_paid, due_date, processed_at, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, CURDATE(), NOW(), 'A FAVOR')")
                         ->execute([
                             $pago->enrollment_id, 
                             $paymentId, 
                             $pago->user_id, 
                             'SALDO A FAVOR (Excedente de Zelle)', 
                             0.00, 
                             $montoDisponible
                         ]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception("Error crítico en la cascada financiera: " . $e->getMessage());
        }
    }

    public function rejectZellePayment(int $id): bool
    {
        return $this->db->prepare("UPDATE tbl_financial_payments SET status = 'REJECTED' WHERE id = ?")->execute([$id]);
    }
}