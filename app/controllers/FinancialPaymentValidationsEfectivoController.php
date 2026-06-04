<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS (EFECTIVO)
 * ARCHIVO: app/Controllers/FinancialPaymentValidationsEfectivoController.php
 * PROPÓSITO: Controlador para validar reportes de pago en efectivo existentes.
 * VERSIÓN: 1.1.0 - Ajustado para validar registros específicos de tbl_financial_payments.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialPaymentValidationsEfectivoModel;
use App\Services\AuditService;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PDO;

/**
 * CARGA MANUAL DE PHPMailer
 */
$phpmailerPath = realpath(__DIR__ . '/../../tools/phpmailer/');
if ($phpmailerPath) {
    require_once $phpmailerPath . '/Exception.php';
    require_once $phpmailerPath . '/PHPMailer.php';
    require_once $phpmailerPath . '/SMTP.php';
}



class FinancialPaymentValidationsEfectivoController extends Controller
{
    private FinancialPaymentValidationsEfectivoModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $user = $_SESSION['user'] ?? null;
        $authorizedRoles = ['ADMIN', 'FINANZAS', 'SUPERADMIN']; 
        
        $accessGranted = (
            $user && 
            $user['user_type'] === 'INTERNAL' && 
            isset($user['role']) && 
            in_array(strtoupper($user['role']), $authorizedRoles)
        );

        if (!$accessGranted) {
            header('Location: /dashboard');
            exit;
        }

        $this->model = new FinancialPaymentValidationsEfectivoModel();
    }

    public function index(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        $this->view('financial/payment_validations/efectivo/index', [
            'title' => 'Validación de Cuotas: Efectivo'
        ]);
    }

    /**
     * Lista los PAGOS PENDIENTES reportados en tbl_financial_payments
     */
    public function getPendingPayments(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $filters = ['text' => $_GET['text'] ?? ''];
            
            // Ahora este método en el modelo devuelve registros de tbl_financial_payments
            $data = $this->model->getStudentsWithPendingLedger($filters);
            echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Valida un registro de pago específico y aplica la cascada
     */
public function validatePayment(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0); 
            $amount    = (float)($_POST['amount'] ?? 0); 
            $currency  = $_POST['currency'] ?? 'USD';
            $adminId   = (int)$_SESSION['user']['id'];
            
            $rawBreakdown = $_POST['breakdown'] ?? null;
            $breakdown = is_string($rawBreakdown) ? json_decode($rawBreakdown, true) : $rawBreakdown;

            if ($paymentId <= 0) throw new Exception("ID de pago no válido.");
            if ($amount <= 0) throw new Exception("Debe ingresar el monto recibido.");

            // --- SQL CORREGIDO: fp -> s -> u ---
            $db = (new \App\Core\Database())->getConnection();
            $sqlInfo = "SELECT u.email, CONCAT(u.first_name, ' ', u.last_name) as full_name, 
                               d.name as diploma_name,
                               DATE_FORMAT(NOW(), '%d/%m/%Y %h:%i %p') as fecha_validacion
                        FROM tbl_financial_payments fp
                        JOIN tbl_students s ON fp.student_id = s.id
                        JOIN tbl_users u ON s.user_id = u.id
                        JOIN tbl_enrollments e ON s.enrollment_id = e.id
                        JOIN tbl_academic_offerings o ON e.offering_id = o.id
                        JOIN tbl_diplomados d ON o.diploma_id = d.id
                        WHERE fp.id = ? LIMIT 1";
            
            $stmt = $db->prepare($sqlInfo);
            $stmt->execute([$paymentId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Procesar en el modelo (Aquí es donde se guarda en 'observation')
            $result = $this->model->processCashPayment($paymentId, $amount, $currency, $breakdown, $adminId);

            if ($result) {
                if ($userData) {
                    // Generamos un número de recibo basado en el ID ya que no hay reference_id
                    $refRecibo = "REC-C-" . str_pad((string)$paymentId, 6, "0", STR_PAD_LEFT);

                    $this->sendValidationEmail(
                        $userData['email'], 
                        $userData['full_name'], 
                        $userData['diploma_name'], 
                        $refRecibo,
                        $amount,
                        $currency,
                        "EFECTIVO (CASH)",
                        $userData['fecha_validacion']
                    );
                }

                echo json_encode(['ok' => true, 'message' => "¡Pago validado y comprobante enviado!"]);
            } else {
                throw new Exception("Error al procesar la validación.");
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }


    /**
     * Rechaza un registro de pago en efectivo
     * POST /financial/payment_validations/efectivo/rejectPayment
     */
    public function rejectPayment(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $reason    = trim((string)($_POST['reason'] ?? ''));
            $adminId   = (int)$_SESSION['user']['id'];

            if ($paymentId <= 0) {
                throw new Exception("ID de pago no válido para el rechazo.");
            }

            if (empty($reason)) {
                throw new Exception("Debe indicar un motivo para rechazar el pago.");
            }

            // Llamamos al modelo para el cambio de estatus quirúrgico
            $result = $this->model->rejectCashPayment($paymentId, $reason, $adminId);

            if ($result) {
                // Registro en Auditoría
                AuditService::log([
                    'module'      => 'FINANCIAL_CASH_VALIDATION',
                    'action'      => 'REJECT_CASH',
                    'description' => "Pago ID $paymentId RECHAZADO por admin ID $adminId. Motivo: $reason",
                    'event_type'  => 'WARNING'
                ]);

                echo json_encode([
                    'ok' => true, 
                    'message' => "El pago ha sido rechazado correctamente."
                ]);
            } else {
                throw new Exception("No se pudo procesar el rechazo en la base de datos.");
            }

        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function sendValidationEmail($email, $fullName, $diplomaName, $reference, $amount, $currency, $method, $date): void
    {
        try {
            $db = (new \App\Core\Database())->getConnection();
            $stmt = $db->prepare("SELECT * FROM tbl_email_settings WHERE tipo_correo = 'INSCRIPCION' LIMIT 1");
            $stmt->execute();
            $conf = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$conf) return;

            $nombreInstitucion = "Coordinación Académica DIPLOMATIC"; 
            $successColor = '#198754';
            $montoFormateado = number_format((float)$amount, 2, ',', '.') . ' ' . $currency;

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Host = $conf['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $conf['smtp_user'];
            $mail->Password = $conf['smtp_password'];
            $mail->Port = (int)$conf['smtp_port'];
            $mail->SMTPSecure = (strtoupper($conf['smtp_security']) === 'SSL') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom($conf['from_email'], $conf['from_name']);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "✅ Recibo de Pago de Cuota - $reference";

            $mail->Body = "
            <div style='background-color: #f4f7f6; padding: 30px 10px; font-family: sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0;'>
                    <div style='background-color: $successColor; padding: 20px; text-align: center;'>
                        <h2 style='color: #ffffff; margin: 0; font-size: 20px;'>RECIBO DE PAGO DE CUOTA</h2>
                    </div>
                    <div style='padding: 30px; color: #444444;'>
                        <p style='font-size: 16px;'>Hola <strong>$fullName</strong>,</p>
                        <p>Se ha validado satisfactoriamente el pago de tu cuota académica:</p>
                        <table style='width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #fcfcfc;'>
                            <tr><td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Programa:</td><td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold;'>$diplomaName</td></tr>
                            <tr><td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>No. Recibo:</td><td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold; color: $successColor;'>#$reference</td></tr>
                            <tr><td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Monto Recibido:</td><td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold; font-size: 18px;'>$montoFormateado</td></tr>
                            <tr><td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Método:</td><td style='padding: 12px; border-bottom: 1px solid #eee;'>$method</td></tr>
                            <tr><td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Fecha Validación:</td><td style='padding: 12px; border-bottom: 1px solid #eee;'>$date</td></tr>
                        </table>
                        <div style='background-color: #e9f7ef; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; text-align: center;'>
                            Su pago ha sido aplicado correctamente a su estado de cuenta.
                        </div>
                    </div>
                    <div style='background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                        <p style='margin: 0;'>Atentamente,</p>
                        <p style='margin: 5px 0 0 0; font-weight: bold; color: $successColor; font-size: 14px;'>$nombreInstitucion</p>
                    </div>
                </div>
            </div>";

            $mail->send();
        } catch (\Throwable $e) {
            error_log("Error de correo Cuota Efectivo: " . $e->getMessage());
        }
    }

}