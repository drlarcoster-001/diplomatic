<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / REGISTRO DE PAGOS
 * ARCHIVO: app/controllers/FinancialPaymentRegistrationController_s5.php
 * PROPÓSITO: Trait asíncrono para enviar correo de notificación de pago con maquetación Premium.
 * VERSIÓN: 1.1.0 - FEATURE: Diseño de correo responsivo y consulta a tbl_email_settings.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use PHPMailer\PHPMailer\PHPMailer;
use Exception;
use PDO;
use Throwable;

trait FinancialPaymentRegistrationController_s5
{
    /**
     * Endpoint POST: Envía notificación por correo de pago recibido.
     * Ruta esperada: /financial/payment_registration/sendPaymentEmail
     */
    public function sendPaymentEmail(): void 
    {
        while (ob_get_level() > 0) ob_end_clean();
        ob_start();
        
        // Importación manual de librerías PHPMailer
        $toolsPath = realpath(__DIR__ . '/../../tools/phpmailer/');
        if ($toolsPath) {
            require_once $toolsPath . '/Exception.php';
            require_once $toolsPath . '/PHPMailer.php';
            require_once $toolsPath . '/SMTP.php';
        }

        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            if ($paymentId <= 0) throw new Exception("Identificador de pago no válido.");

            $db = (new Database())->getConnection();
            
            // Extraer datos completos del pago, estudiante y diplomado
            $sql = "SELECT 
                        fp.amount, fp.currency, fp.method, fp.reference_id,
                        u.first_name, u.last_name, u.email,
                        d.name AS diplomado_name
                    FROM tbl_financial_payments fp
                    INNER JOIN tbl_students s ON fp.student_id = s.id
                    INNER JOIN tbl_users u ON s.user_id = u.id
                    INNER JOIN tbl_student_matriculations sm ON fp.matriculation_id = sm.id
                    INNER JOIN tbl_academic_offerings o ON sm.offering_id = o.id
                    LEFT JOIN tbl_diplomados d ON o.diploma_id = d.id
                    WHERE fp.id = :pid LIMIT 1";
            
            $stmtData = $db->prepare($sql);
            $stmtData->execute([':pid' => $paymentId]);
            $data = $stmtData->fetch(PDO::FETCH_ASSOC);

            if (!$data) throw new Exception("Registro de pago no encontrado.");
            if (empty($data['email'])) throw new Exception("El estudiante no posee un correo válido.");

            // USO DE TABLA DE CONFIGURACIÓN DEL SISTEMA
            $stmtSmtp = $db->query("SELECT smtp_host, smtp_user, smtp_password, smtp_port, smtp_security, from_email, from_name FROM tbl_email_settings ORDER BY id ASC LIMIT 1");
            $smtp = $stmtSmtp->fetch(PDO::FETCH_ASSOC);
            if (!$smtp) throw new Exception("Configuración SMTP no disponible en el sistema.");

            // Nombre de la Empresa
            $stmtCo = $db->query("SELECT nombre_comercial FROM tbl_company_settings LIMIT 1");
            $nombreComercial = $stmtCo->fetchColumn() ?: 'Departamento de Finanzas';

            // Preparación de variables visuales
            $nombreEstudiante = htmlspecialchars(trim($data['first_name'] . ' ' . $data['last_name']));
            $nombreDiplomado  = htmlspecialchars($data['diplomado_name'] ?? 'Programa Académico');
            $metodoPago       = strtoupper(trim((string)$data['method']));
            $monto            = number_format((float)$data['amount'], 2, ',', '.') . ' ' . $data['currency'];
            $referencia       = htmlspecialchars((string)$data['reference_id']);
            $anioActual       = date('Y');

            // --- INICIO DE MAQUETACIÓN PREMIUM HTML ---
            $htmlHeader = "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            </head>
            <body style='margin: 0; padding: 20px; background-color: #f4f7f6; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
                    
                    <div style='background-color: #198754; padding: 30px 20px; text-align: center;'>
                        <h1 style='color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 0.5px;'>Confirmación de Recepción</h1>
                        <p style='color: #d1e7dd; margin: 10px 0 0 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;'>$nombreComercial</p>
                    </div>

                    <div style='padding: 35px 30px; color: #444444; line-height: 1.6;'>
                        <p style='font-size: 16px; margin-top: 0;'>Estimado/a <strong>$nombreEstudiante</strong>,</p>";

            $htmlFooter = "
                        <hr style='border: none; border-top: 1px solid #eaeaea; margin: 30px 0 20px 0;'>
                        <p style='font-size: 14px; color: #666666; margin: 0;'>Atentamente,</p>
                        <p style='font-size: 16px; font-weight: bold; color: #198754; margin: 5px 0 0 0;'>$nombreComercial</p>
                    </div>

                    <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eeeeee;'>
                        <p style='margin: 0; color: #888888; font-size: 12px;'>Este es un mensaje generado automáticamente por el sistema de gestión. Por favor, no responda a este correo.</p>
                        <p style='margin: 5px 0 0 0; color: #aaaaaa; font-size: 11px;'>&copy; $anioActual $nombreComercial. Todos los derechos reservados.</p>
                    </div>
                </div>
            </body>
            </html>";

            $asunto = "Registro de Pago Recibido: $nombreDiplomado";
            $cuerpoInfo = "
                        <p style='font-size: 15px;'>Hemos registrado exitosamente en nuestro sistema su reporte de pago asociado al programa <strong>$nombreDiplomado</strong>.</p>
                        
                        <div style='background-color: #fcfcfc; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 25px 0;'>
                            <h3 style='margin: 0 0 15px 0; color: #212529; font-size: 15px; border-bottom: 2px solid #198754; padding-bottom: 8px; display: inline-block; text-transform: uppercase;'>Detalles de la Operación</h3>
                            <table width='100%' style='font-size: 14px; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 10px 0; color: #6c757d; width: 40%;'><strong>Monto Validado:</strong></td>
                                    <td style='padding: 10px 0; color: #198754; font-weight: bold; font-size: 18px;'>$monto</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px 0; color: #6c757d; border-top: 1px solid #eeeeee;'><strong>Método de Pago:</strong></td>
                                    <td style='padding: 10px 0; color: #212529; border-top: 1px solid #eeeeee; font-weight: 500;'>$metodoPago</td>
                                </tr>
                                <tr>
                                    <td style='padding: 10px 0; color: #6c757d; border-top: 1px solid #eeeeee;'><strong>Número de Referencia:</strong></td>
                                    <td style='padding: 10px 0; color: #212529; border-top: 1px solid #eeeeee; font-family: monospace; font-size: 15px;'>$referencia</td>
                                </tr>
                            </table>
                        </div>

                        <div style='background-color: #fff8e1; border: 1px solid #ffecb5; border-left: 5px solid #ffc107; border-radius: 6px; padding: 20px; margin: 25px 0;'>
                            <div style='display: flex; align-items: center; margin-bottom: 10px;'>
                                <span style='background-color: #ffc107; color: #000; padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 11px; letter-spacing: 0.5px;'>ESTATUS: EN REVISIÓN (PENDIENTE)</span>
                            </div>
                            <p style='margin: 0; font-size: 14px; color: #664d03; line-height: 1.5;'>Su pago ha entrado en la cola de validación. Nuestro departamento verificará la transacción bancaria a la brevedad posible. Una vez confirmada, su estado de cuenta será liquidado automáticamente.</p>
                        </div>";
            // --- FIN DE MAQUETACIÓN ---

            $cuerpoHTML = $htmlHeader . $cuerpoInfo . $htmlFooter;

            // 4. Ejecución de PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['smtp_user'];
            $mail->Password   = $smtp['smtp_password'];
            $mail->Port       = (int)$smtp['smtp_port'];
            $mail->CharSet    = 'UTF-8';

            $mail->SMTPSecure = (strtoupper($smtp['smtp_security'] ?? '') === 'SSL') 
                                ? PHPMailer::ENCRYPTION_SMTPS 
                                : PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($data['email'], $nombreEstudiante);
            
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpoHTML;

            $mail->send();
            
            // 5. Salida JSON
            $this->emitirJSON_s5(['success' => true, 'message' => 'Correo de notificación diseñado y enviado exitosamente al estudiante.']);

        } catch (Throwable $e) {
            $this->emitirJSON_s5(['success' => false, 'message' => 'El pago se registró, pero falló el envío de correo: ' . $e->getMessage()], 400);
        }
    }

    private function emitirJSON_s5(array $payload, int $httpCode = 200): void 
    {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}