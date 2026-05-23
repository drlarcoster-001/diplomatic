<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / NOTIFICACIONES
 * ARCHIVO: app/controllers/FinancialPaymentValidationsNotificationsController.php
 * PROPÓSITO: Envío de comprobantes de pago con diseño institucional y firma automática.
 * VERSIÓN: 1.3.1 - Mejora visual del correo y carga de metadatos institucionales.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PDO;
use Exception;
use Throwable;

final class FinancialPaymentValidationsNotificationsController extends Controller
{
    /**
     * Envía correo de confirmación tras aprobación de pago con diseño profesional.
     */
    public function sendPaymentApprovedEmail(): void 
    {
        // Blindaje de búfer para evitar basura HTML en la respuesta JSON
        if (ob_get_level() > 0) ob_end_clean();
        ob_start();
        
        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            if ($paymentId <= 0) throw new Exception("ID de pago no recibido.");

            // Importación manual de PHPMailer
            $basePath = dirname(__DIR__, 2);
            require_once $basePath . '/tools/phpmailer/Exception.php';
            require_once $basePath . '/tools/phpmailer/PHPMailer.php';
            require_once $basePath . '/tools/phpmailer/SMTP.php';

            $db = (new Database())->getConnection();

            // 1. Obtener Datos del Pago, Estudiante e Institución
            $sql = "SELECT p.reference_id, p.payment_metadata, p.created_at, u.email, 
                           CONCAT(u.first_name, ' ', u.last_name) as full_name,
                           (SELECT nombre_comercial FROM tbl_company_settings LIMIT 1) as company_name
                    FROM tbl_financial_payments p
                    INNER JOIN tbl_students s ON p.student_id = s.id
                    INNER JOIN tbl_users u ON s.user_id = u.id
                    WHERE p.id = ? LIMIT 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$paymentId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) throw new Exception("No se encontró el registro del pago.");

            // 2. Preparar variables para la plantilla
            $meta = json_decode($data['payment_metadata'] ?? '{}', true);
            $monto = number_format((float)($meta['monto_sistema_usd'] ?? 0), 2);
            $referencia = $data['reference_id'];
            $nombreAlumno = $data['full_name'];
            $fecha = date('d/m/Y', strtotime($data['created_at']));
            $institucion = $data['company_name'] ?: 'Administración Académica';

            // 3. Cargar configuración SMTP
            $stmtSmtp = $db->prepare("SELECT * FROM tbl_email_settings WHERE tipo_correo = 'INSCRIPCION' LIMIT 1");
            $stmtSmtp->execute();
            $smtp = $stmtSmtp->fetch(PDO::FETCH_ASSOC);

            if (!$smtp) throw new Exception("Falta configuración SMTP 'INSCRIPCION'.");

            // 4. Maquetación del Correo Institucional
            $cuerpoHTML = "
            <div style='background-color: #f8f9fa; padding: 40px 20px; font-family: Segoe UI, Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e9ecef;'>
                    
                    <div style='background-color: #0d6efd; padding: 30px; text-align: center;'>
                        <h1 style='color: #ffffff; margin: 0; font-size: 22px; text-transform: uppercase; letter-spacing: 1px;'>Comprobante de Pago</h1>
                    </div>

                    <div style='padding: 35px; color: #444444; line-height: 1.6;'>
                        <p style='font-size: 17px;'>Estimado(a) <strong>$nombreAlumno</strong>,</p>
                        <p>Le informamos que su reporte de pago ha sido <strong>validado satisfactoriamente</strong> por nuestro departamento administrativo.</p>
                        
                        <div style='background-color: #f1f3f5; border-radius: 8px; padding: 25px; margin: 30px 0; border-left: 5px solid #198754;'>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 5px 0; color: #6c757d; font-size: 14px;'>Referencia:</td>
                                    <td style='padding: 5px 0; color: #212529; font-weight: bold; text-align: right;'>#$referencia</td>
                                </tr>
                                <tr>
                                    <td style='padding: 5px 0; color: #6c757d; font-size: 14px;'>Fecha:</td>
                                    <td style='padding: 5px 0; color: #212529; text-align: right;'>$fecha</td>
                                </tr>
                                <tr>
                                    <td colspan='2' style='border-top: 1px solid #dee2e6; padding-top: 10px; margin-top: 10px;'></td>
                                </tr>
                                <tr>
                                    <td style='padding: 5px 0; color: #212529; font-weight: bold; font-size: 16px;'>MONTO PROCESADO:</td>
                                    <td style='padding: 5px 0; color: #198754; font-size: 22px; font-weight: bold; text-align: right;'>$monto USD</td>
                                </tr>
                            </table>
                        </div>

                        <p style='font-size: 14px; color: #6c757d;'>Su estado de cuenta ha sido actualizado automáticamente. Ya puede visualizar sus abonos en el portal estudiantil.</p>
                        
                        <div style='margin-top: 45px; padding-top: 25px; border-top: 1px solid #eeeeee;'>
                            <p style='margin: 0; font-size: 15px; font-weight: bold; color: #0d6efd;'>Departamento de Finanzas</p>
                            <p style='margin: 0; font-size: 14px; color: #212529;'>$institucion</p>
                        </div>
                    </div>

                    <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eeeeee;'>
                        <p style='font-size: 11px; color: #adb5bd; margin: 0;'>Este es un mensaje automático generado por el sistema. Por favor, no responda a esta dirección de correo.</p>
                    </div>
                </div>
            </div>";

            // 5. Configuración y Envío de Correo
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['smtp_user'];
            $mail->Password   = $smtp['smtp_password'];
            $mail->Port       = (int)$smtp['smtp_port'];
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPSecure = (strtoupper($smtp['smtp_security'] ?? '') === 'SSL') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($data['email'], $nombreAlumno);
            
            $mail->isHTML(true);
            $mail->Subject = "✅ Comprobante de Pago Validado - Ref: $referencia";
            $mail->Body    = $cuerpoHTML;

            $mail->send();

            $this->emitirJSON(['success' => true, 'message' => 'Notificación institucional enviada.']);

        } catch (Throwable $e) {
            $this->emitirJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Helper para garantizar salida JSON limpia.
     */
    private function emitirJSON(array $payload): void 
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}