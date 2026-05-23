<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / EFECTIVO (CASH)
 * ARCHIVO: app/controllers/FinancialCashEfectivoController.php
 * PROPÓSITO: Controlador operativo para conciliación y rechazo de pagos en ventanilla.
 * VERSIÓN: 1.4.0 
 * * NOTA PARA EL EQUIPO DE DESARROLLO:
 * Si el módulo sigue redirigiendo al dashboard, usen la siguiente URL para depurar:
 * http://localhost/diplomatic/public/financial/cash-operations/efectivo?debug_security=1
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialCashEfectivoModel;
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

class FinancialCashEfectivoController extends Controller
{
    private FinancialCashEfectivoModel $model;

    public function __construct()
    {
        // Iniciar sesión si no existe
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $user = $_SESSION['user'] ?? null;

        /**
         * NORMALIZACIÓN DE DATOS (PROTECCIÓN CONTRA TYPOS EN BD)
         * El Bootstrap 1.9.0 guarda 'user_type' tal cual viene de la BD. 
         * Forzamos a Mayúsculas para asegurar compatibilidad.
         */
        $userType     = strtoupper((string)($user['user_type'] ?? ''));
        $userRole     = strtoupper((string)($user['role'] ?? ''));
        $allowedRoles = ['ADMIN', 'FINANZAS', 'SUPERADMIN'];

        /**
         * 🛠️ BLOQUE DE DEPURACIÓN (Solo para programadores)
         * Se activa agregando ?debug_security=1 a la URL
         */
        if (isset($_GET['debug_security'])) {
            header('Content-Type: text/html; charset=utf-8');
            echo "<h2>🕵️ DEBUG DE SEGURIDAD - MÓDULO EFECTIVO</h2>";
            echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
            echo "<tr><td><b>¿Usuario en Sesión?</b></td><td>" . ($user ? '✅ SÍ' : '❌ NO') . "</td></tr>";
            echo "<tr><td><b>User Type detectado:</b></td><td>'$userType' (Esperado: INTERNAL)</td></tr>";
            echo "<tr><td><b>Rol detectado:</b></td><td>'$userRole' (Esperado: ".implode(' o ', $allowedRoles).")</td></tr>";
            echo "<tr><td><b>ID de Usuario:</b></td><td>" . ($user['id'] ?? 'N/A') . "</td></tr>";
            echo "</table>";
            echo "<h3>Contenido de \$_SESSION['user']:</h3><pre>"; print_r($user); echo "</pre>";
            
            if (!$user || $userType !== 'INTERNAL' || !in_array($userRole, $allowedRoles)) {
                echo "<h3 style='color:red;'>🚨 RESULTADO: EL PORTERO TE ESTÁ RECHAZANDO</h3>";
            } else {
                echo "<h3 style='color:green;'>✅ RESULTADO: ACCESO VÁLIDO SEGÚN REGLAS</h3>";
            }
            exit; // Detenemos la ejecución para que el programador vea los datos
        }

        /**
         * FILTRO DE SEGURIDAD (EL PORTERO)
         */
        if (!$user || $userType !== 'INTERNAL' || !in_array($userRole, $allowedRoles)) {
            if (ob_get_level() > 0) ob_end_clean();
            $this->redirect('/dashboard');
            exit;
        }

        $this->model = new FinancialCashEfectivoModel();
    }

    /**
     * Carga la interfaz principal de conciliación de efectivo.
     * Vista: financial/cash_operations/efectivo/index.php
     */
    public function index(): void
    {
        if (ob_get_level() > 0) ob_end_clean();

        $this->view('financial/cash_operations/efectivo/index', [
            'title' => 'Gestión de Caja: Efectivo'
        ]);
    }

    /**
     * API: Obtiene listado de compromisos pendientes.
     * GET /financial/cash-operations/efectivo/getPendingPayments
     */
    public function getPendingPayments(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $filters = [
                'text' => $_GET['text'] ?? '',
                'date' => $_GET['date'] ?? ''
            ];
            $data = $this->model->getPendingCashCommitments($filters);
            echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Procesa validación con arqueo.
     * POST /financial/cash-operations/efectivo/validatePayment
     */
public function validatePayment(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $paymentId    = (int)($_POST['payment_id'] ?? 0);
            $rawBreakdown = $_POST['breakdown'] ?? null; 
            $adminId      = (int)$_SESSION['user']['id'];

            if ($paymentId <= 0) throw new Exception("ID de pago inválido.");
            if (!$rawBreakdown) throw new Exception("El desglose de billetes es requerido.");

            $breakdown = is_string($rawBreakdown) ? json_decode($rawBreakdown, true) : $rawBreakdown;
            if (!is_array($breakdown)) throw new Exception("Formato de arqueo incorrecto.");

            // --- 1. Obtener detalles (USANDO reference_id SEGÚN TU SQL) ---
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

            // --- 2. Procesar el cobro en el modelo ---
            $result = $this->model->approveCashPayment($paymentId, $breakdown, $adminId);

            if ($result) {
                // --- 3. Enviar Recibo Digital detallado ---
                if ($userData) {
                    $this->sendValidationEmail(
                        $userData['email'], 
                        $userData['full_name'], 
                        $userData['diploma_name'], 
                        $userData['reference_id'] ?? 'S/R', // Columna corregida
                        $userData['amount'],
                        $userData['currency'],
                        $userData['method'],
                        $userData['fecha_validacion']
                    );
                }
                echo json_encode(['ok' => true, 'message' => "¡Cobro registrado y recibo enviado!"]);
            } else {
                throw new Exception("Error al procesar el cobro en el modelo.");
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }


    /**
     * API: Rechazo de pago.
     * POST /financial/cash-operations/efectivo/rejectPayment
     */
    public function rejectPayment(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $reason    = trim((string)($_POST['reason'] ?? 'Rechazo administrativo'));
            $adminId   = (int)$_SESSION['user']['id'];

            if ($paymentId <= 0) throw new Exception("ID de pago inválido.");

            $result = $this->model->rejectCashPayment($paymentId, $reason, $adminId);

            if ($result) {
                echo json_encode(['ok' => true, 'message' => "Compromiso de efectivo rechazado."]);
            } else {
                throw new Exception("Error al procesar rechazo.");
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
            $mail->Subject = "✅ Recibo de Pago: $diplomaName";

            $mail->Body = "
            <div style='background-color: #f4f7f6; padding: 30px 10px; font-family: sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0;'>
                    <div style='background-color: $successColor; padding: 20px; text-align: center;'>
                        <h2 style='color: #ffffff; margin: 0; font-size: 20px;'>RECIBO DE CAJA DIGITAL</h2>
                    </div>

                    <div style='padding: 30px; color: #444444;'>
                        <p style='font-size: 16px;'>Hola <strong>$fullName</strong>,</p>
                        <p>Hemos procesado tu pago en efectivo en nuestra sede. Aquí tienes los detalles de tu recibo:</p>
                        
                        <table style='width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #fcfcfc;'>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Programa:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold;'>$diplomaName</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Referencia / Recibo:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold; color: $successColor;'>#$reference</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Monto Entregado:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold; font-size: 18px;'>$montoFormateado</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Método:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee;'>EFECTIVO ($method)</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Fecha de Operación:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee;'>$date</td>
                            </tr>
                        </table>

                        <div style='background-color: #e9f7ef; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; text-align: center;'>
                            <strong>Estatus: PAGO RECIBIDO Y CONCILIADO</strong>
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
            error_log("Fallo envío correo Efectivo: " . $e->getMessage());
        }
    }

}