<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / ZELLE
 * ARCHIVO: app/Controllers/FinancialCashZelleController.php
 * PROPÓSITO: Controlador operativo para validación individual de pagos Zelle y auditoría.
 * VERSIÓN: 1.1.0 - FIX: Estandarización MVC, limpieza de búfer y anulación profunda de inscripciones.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialCashZelleModel;
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

class FinancialCashZelleController extends Controller
{
    private FinancialCashZelleModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $user = $_SESSION['user'] ?? null;
        
        // FIX: Se fuerza a mayúsculas para evitar falsos positivos por capitalización en BD
        if (!$user || $user['user_type'] !== 'INTERNAL' || !in_array(strtoupper($user['role'] ?? ''), ['ADMIN', 'FINANZAS', 'SUPERADMIN'])) {
            $this->redirect('/dashboard');
            exit;
        }
        
        $this->model = new FinancialCashZelleModel();
    }

    /**
     * Limpia el búfer de salida para evitar errores de JSON (Token '<')
     */
    private function clearOutputBuffer(): void
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Establece las cabeceras JSON estándar y previene inyección de HTML
     */
    private function setJsonHeaders(): void
    {
        $this->clearOutputBuffer();
        header('Content-Type: application/json; charset=utf-8');
    }

    public function index(): void
    {
        $this->clearOutputBuffer();
        $this->view('financial/cash_operations/zelle/index');
    }

    public function getPendingPayments(): void
    {
        $this->setJsonHeaders();

        try {
            $filters = [
                'text' => $_GET['text'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? ''
            ];
            $data = $this->model->getPendingZellePayments($filters);
            
            // FIX: JSON_UNESCAPED_UNICODE para proteger acentos y eñes en los nombres
            echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    
public function validatePayment(): void
    {
        $this->setJsonHeaders();

        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $reference = trim((string)($_POST['reference'] ?? ''));
            $adminId = (int)$_SESSION['user']['id'];

            if ($paymentId <= 0) throw new Exception("ID de pago inválido.");

            // 1. Obtener detalles para el comprobante ANTES de aprobar
            $db = (new \App\Core\Database())->getConnection();
            $sqlInfo = "SELECT u.email, CONCAT(u.first_name, ' ', u.last_name) as full_name, 
                               d.name as diploma_name, ep.amount, ep.currency, ep.method, ep.reference_id,
                               DATE_FORMAT(NOW(), '%d/%m/%Y %h:%i %p') as fecha_validacion
                        FROM tbl_enrollments_payments ep
                        JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                        JOIN tbl_users u ON e.user_id = u.id
                        JOIN tbl_academic_offerings o ON e.offering_id = o.id
                        JOIN tbl_diplomados d ON o.diploma_id = d.id
                        WHERE ep.id = ? LIMIT 1";
            $stmt = $db->prepare($sqlInfo);
            $stmt->execute([$paymentId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            // Protocolo de Seguridad: Verificar duplicados
            if ($this->model->isReferenceDuplicate($reference, $paymentId)) {
                throw new Exception("ALERTA: Esta referencia de Zelle ya ha sido utilizada.");
            }

            // 2. Procesar aprobación
            $result = $this->model->approveZellePayment($paymentId, $adminId);

            if ($result) {
                // 3. Enviar Correo de Confirmación Zelle
                if ($userData) {
                    $this->sendValidationEmail(
                        $userData['email'], 
                        $userData['full_name'], 
                        $userData['diploma_name'], 
                        $reference, // Usamos la referencia que viene por POST
                        $userData['amount'],
                        $userData['currency'],
                        "ZELLE (Dólares)",
                        $userData['fecha_validacion']
                    );
                }

                AuditService::log([
                    'module' => 'FINANCIAL_ZELLE',
                    'action' => 'APPROVE_PAYMENT',
                    'description' => "Zelle Aprobado y Correo Enviado. Ref: $reference",
                    'event_type' => 'SUCCESS'
                ]);

                echo json_encode(['ok' => true, 'message' => "Pago Zelle validado y comprobante enviado al estudiante."]);
            } else {
                throw new Exception("Error al procesar la aprobación.");
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Acción de Rechazo (MVC Limpio con destrucción de Ledger)
     */
    public function rejectPayment(): void
    {
        $this->setJsonHeaders();

        try {
            // Capturamos todos los posibles IDs para pasárselos al modelo inteligente
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $enrollId  = (int)($_POST['enrollment_id'] ?? 0);
            $adminId   = (int)$_SESSION['user']['id'];

            if ($paymentId <= 0 && $enrollId <= 0) {
                throw new Exception("ID de pago inválido.");
            }

            // Llamamos a la función inteligente que pusimos en el modelo
            $result = $this->model->rejectPaymentWithCleanup($paymentId, $enrollId, $adminId);

            if ($result) {
                AuditService::log([
                    'module' => 'FINANCIAL_ZELLE',
                    'action' => 'REJECT_PAYMENT',
                    'description' => "Zelle Rechazado y Ledger limpio. PaymentID: $paymentId",
                    'event_type' => 'WARNING'
                ]);
                echo json_encode(['ok' => true, 'message' => "La inscripción fue anulada, estatus cambiado a RECHAZADO y Ledger limpio."]);
            } else {
                throw new Exception("Error al rechazar el pago en la base de datos.");
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
            $montoFormateado = number_format((float)$amount, 2, '.', ',') . ' ' . $currency;

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
            $mail->Subject = "✅ Confirmación de Pago Zelle - $reference";

            $mail->Body = "
            <div style='background-color: #f4f7f6; padding: 30px 10px; font-family: sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0;'>
                    <div style='background-color: $successColor; padding: 20px; text-align: center;'>
                        <h2 style='color: #ffffff; margin: 0; font-size: 20px;'>COMPROBANTE DE PAGO ZELLE</h2>
                    </div>

                    <div style='padding: 30px; color: #444444;'>
                        <p style='font-size: 16px;'>Hola <strong>$fullName</strong>,</p>
                        <p>Tu transferencia vía <strong>Zelle</strong> ha sido verificada y aprobada exitosamente por nuestro departamento de finanzas.</p>
                        
                        <table style='width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #fcfcfc;'>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Programa:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold;'>$diplomaName</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Referencia Zelle:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold; color: $successColor;'>$reference</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Monto Validado:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold; font-size: 18px;'>$montoFormateado</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Método:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee;'>$method</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Fecha Validación:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee;'>$date</td>
                            </tr>
                        </table>

                        <div style='background-color: #e9f7ef; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; text-align: center;'>
                            <strong>¡Inscripción Confirmada!</strong> Tu estado de cuenta ha sido actualizado satisfactoriamente.
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
            error_log("Error de correo Zelle: " . $e->getMessage());
        }
    }
}