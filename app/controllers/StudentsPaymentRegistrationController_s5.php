<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / REGISTRO DE PAGOS
 * ARCHIVO: app/controllers/StudentsPaymentRegistrationController_s5.php
 * PROPÓSITO: Trait asíncrono para enviar correo de notificación de reporte recibido (S5).
 * VERSIÓN: 1.0.0 - FEATURE: Maquetación Premium adaptada a la identidad del estudiante.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use PHPMailer\PHPMailer\PHPMailer;
use Exception;
use PDO;
use Throwable;

trait StudentsPaymentRegistrationController_s5
{
    /**
     * Endpoint POST: Envía notificación por correo de reporte de pago recibido.
     * Ruta esperada: /students/payment_registration/sendPaymentEmail
     */
    public function sendPaymentEmail(): void 
    {
        while (ob_get_level() > 0) ob_end_clean();
        ob_start();
        
        // Importación de librerías PHPMailer (Ruta estandarizada)
        $toolsPath = realpath(__DIR__ . '/../../tools/phpmailer/');
        if ($toolsPath) {
            require_once $toolsPath . '/Exception.php';
            require_once $toolsPath . '/PHPMailer.php';
            require_once $toolsPath . '/SMTP.php';
        }

        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            if ($paymentId <= 0) throw new Exception("Identificador de reporte no válido.");

            $db = (new Database())->getConnection();
            
            // Extraer datos para el cuerpo del correo (Misma estructura que el financiero)
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

            if (!$data) throw new Exception("Registro de reporte no encontrado.");

            // Configuración SMTP
            $stmtSmtp = $db->query("SELECT smtp_host, smtp_user, smtp_password, smtp_port, smtp_security, from_email, from_name FROM tbl_email_settings LIMIT 1");
            $smtp = $stmtSmtp->fetch(PDO::FETCH_ASSOC);
            if (!$smtp) throw new Exception("Configuración de correo no disponible.");

            // Nombre de la Institución
            $stmtCo = $db->query("SELECT nombre_comercial FROM tbl_company_settings LIMIT 1");
            $nombreComercial = $stmtCo->fetchColumn() ?: 'Centro de Autogestión Estudiantil';

            // Variables para el Template
            $nombreEstudiante = htmlspecialchars(trim($data['first_name'] . ' ' . $data['last_name']));
            $nombreDiplomado  = htmlspecialchars($data['diplomado_name'] ?? 'Programa Académico');
            $metodoPago       = strtoupper(trim((string)$data['method']));
            $monto            = number_format((float)$data['amount'], 2, ',', '.') . ' ' . $data['currency'];
            $referencia       = htmlspecialchars((string)$data['reference_id']);
            $anioActual       = date('Y');

            // --- MAQUETACIÓN PREMIUM ---
            $htmlHeader = "
            <!DOCTYPE html>
            <html lang='es'>
            <head><meta charset='UTF-8'></head>
            <body style='margin: 0; padding: 20px; background-color: #f4f7f6; font-family: sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; border: 1px solid #eeeeee;'>
                    <div style='background-color: #0d6efd; padding: 30px 20px; text-align: center;'>
                        <h1 style='color: #ffffff; margin: 0; font-size: 22px;'>Reporte Recibido</h1>
                        <p style='color: #e0eaff; margin: 5px 0 0 0; font-size: 13px; text-transform: uppercase;'>$nombreComercial</p>
                    </div>
                    <div style='padding: 30px; color: #444444; line-height: 1.6;'>
                        <p>Estimado/a <strong>$nombreEstudiante</strong>,</p>
                        <p>Hemos recibido correctamente su reporte de pago para el programa <strong>$nombreDiplomado</strong>.</p>";

            $htmlFooter = "
                        <hr style='border: none; border-top: 1px solid #eeeeee; margin: 30px 0;'>
                        <p style='font-size: 14px; color: #888888; margin: 0;'>Atentamente,</p>
                        <p style='font-size: 16px; font-weight: bold; color: #0d6efd; margin: 5px 0 0 0;'>Departamento de Administración</p>
                    </div>
                    <div style='background-color: #f8f9fa; padding: 15px; text-align: center;'>
                        <p style='margin: 0; color: #999999; font-size: 11px;'>&copy; $anioActual $nombreComercial. Autogestión Estudiantil.</p>
                    </div>
                </div>
            </body>
            </html>";

            $cuerpoInfo = "
                <div style='background-color: #fcfcfc; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0;'>
                    <table width='100%' style='font-size: 14px;'>
                        <tr><td style='color: #6c757d;'>Monto Reportado:</td><td style='color: #0d6efd; font-weight: bold; font-size: 18px; text-align: right;'>$monto</td></tr>
                        <tr><td style='color: #6c757d; border-top: 1px solid #eee; padding-top: 8px;'>Método:</td><td style='text-align: right; border-top: 1px solid #eee; padding-top: 8px;'>$metodoPago</td></tr>
                        <tr><td style='color: #6c757d; border-top: 1px solid #eee; padding-top: 8px;'>Referencia:</td><td style='text-align: right; border-top: 1px solid #eee; padding-top: 8px; font-family: monospace;'>$referencia</td></tr>
                    </table>
                </div>
                <div style='background-color: #fff4e5; border-left: 4px solid #fd7e14; padding: 15px; margin-bottom: 20px;'>
                    <p style='margin: 0; font-size: 13px; color: #856404;'><strong>Estatus: En Revisión.</strong> Su pago será validado en las próximas 24 a 48 horas hábiles. Recibirá una notificación una vez liquidado.</p>
                </div>";

            // Enviar Correo
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
            $mail->addAddress($data['email'], $nombreEstudiante);
            $mail->isHTML(true);
            $mail->Subject = "Reporte de Pago Recibido: $nombreDiplomado";
            $mail->Body    = $htmlHeader . $cuerpoInfo . $htmlFooter;

            $mail->send();
            
            $this->emitirJSON_s5(['success' => true, 'message' => 'Notificación enviada al estudiante.']);

        } catch (Throwable $e) {
            $this->emitirJSON_s5(['success' => false, 'message' => 'Reporte guardado, pero error al notificar: ' . $e->getMessage()], 400);
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