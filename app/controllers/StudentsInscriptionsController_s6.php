<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES / CORREOS
 * ARCHIVO: app/controllers/StudentsInscriptionsController_s6.php
 * PROPÓSITO: Controlador asíncrono para enviar el correo de confirmación de inscripción.
 * VERSIÓN: 1.1.2 - FIX: Eliminación de columna 'validate_cert' inexistente y estandarización de PHPMailer.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\StudentsInscriptionsModel_s6;
use App\Core\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PDO;
use Exception;
use Throwable;

/**
 * IMPORTACIÓN MANUAL DE LIBRERÍAS (Basado en estructura tools/phpmailer/)
 */
$toolsPath = realpath(__DIR__ . '/../../tools/phpmailer/');
if ($toolsPath) {
    require_once $toolsPath . '/Exception.php';
    require_once $toolsPath . '/PHPMailer.php';
    require_once $toolsPath . '/SMTP.php';
}

final class StudentsInscriptionsController_s6 extends Controller
{
    public function sendEmail(): void 
    {
        // 1. Blindaje total de búfer para garantizar JSON limpio
        while (ob_get_level() > 0) ob_end_clean();
        ob_start();
        
        $db = (new Database())->getConnection();
        $debugInfo = [];
        
        try {
            $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
            if ($enrollmentId <= 0) throw new Exception("Identificador de inscripción no válido.");

            $model = new StudentsInscriptionsModel_s6();
            
            // Obtener datos del expediente
            $data = $model->getEnrollmentData($enrollmentId);
            if (!$data) throw new Exception("Expediente no encontrado para el ID: $enrollmentId");

            // 2. Obtener configuración SMTP (Sin pedir validate_cert)
            $stmt = $db->prepare("SELECT smtp_host, smtp_user, smtp_password, smtp_port, smtp_security, from_email, from_name FROM tbl_email_settings WHERE tipo_correo = 'INSCRIPCION' LIMIT 1");
            $stmt->execute();
            $smtp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$smtp) throw new Exception("Configuración SMTP no disponible en la base de datos.");

            // Obtención del nombre comercial
            $stmtCo = $db->query("SELECT nombre_comercial FROM tbl_company_settings LIMIT 1");
            $nombreComercial = $stmtCo->fetchColumn() ?: 'Coordinación Académica';

            $nombreEstudiante = htmlspecialchars(trim($data['first_name'] . ' ' . $data['last_name']));
            $nombreDiplomado  = htmlspecialchars($data['diplomado_name']);
            $metodoPago       = strtoupper(trim((string)($data['payment_method'] ?? '')));

            $debugInfo = [
                'metodo' => $metodoPago,
                'email'  => $data['email'] ?? 'N/A'
            ];

            // 3. Maquetación Visual (Estandarización Card)
            $htmlHeader = "
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; font-family: Arial, sans-serif;'>
                <div style='background-color: #0d6efd; padding: 20px; text-align: center;'>
                    <h2 style='color: #ffffff; margin: 0;'>Notificación de Inscripción</h2>
                </div>
                <div style='padding: 30px; color: #333333; line-height: 1.6;'>
                    <p style='font-size: 16px;'>Hola, <strong>$nombreEstudiante</strong>:</p>";

            $htmlFooter = "
                    <br>
                    <hr style='border: none; border-top: 1px solid #eeeeee; margin: 20px 0;'>
                    <p style='font-size: 14px; color: #666666;'>Atentamente,</p>
                    <p style='font-size: 16px; font-weight: bold; color: #0d6efd;'>$nombreComercial</p>
                </div>
                <div style='background-color: #f8f9fa; padding: 15px; text-align: center; color: #999999; font-size: 11px;'>
                    Este es un mensaje automático. Por favor no responda directamente a este remitente.
                </div>
            </div>";

            if ($metodoPago === 'CASH' || $metodoPago === 'EFECTIVO') {
                $asunto = "Registro Recibido: $nombreDiplomado";
                $cuerpoInfo = "
                    <p>Tu solicitud para participar en <strong>$nombreDiplomado</strong> ha sido registrada exitosamente.</p>
                    <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                        <strong style='color: #856404;'>Estatus: EN REVISIÓN - COMPROMISO PAGO</strong><br>
                        <p style='margin-top: 5px; font-size: 14px;'>Has seleccionado el pago en taquilla. Tu cupo está reservado temporalmente; para formalizar tu inscripción, debes acudir a nuestra sede administrativa y realizar el pago correspondiente.</p>
                    </div>";
            } else {
                $asunto = "Pago en Revisión: $nombreDiplomado";
                $cuerpoInfo = "
                    <p>Hemos recibido tu comprobante de pago digital para el programa <strong>$nombreDiplomado</strong>.</p>
                    <div style='background-color: #cfe2ff; border-left: 4px solid #0d6efd; padding: 15px; margin: 20px 0;'>
                        <strong style='color: #084298;'>Estatus: EN REVISIÓN</strong><br>
                        <p style='margin-top: 5px; font-size: 14px;'>Nuestro equipo administrativo verificará la transacción en las próximas horas. Una vez validado, recibirás tu confirmación de acceso definitiva.</p>
                    </div>";
            }

            $cuerpoHTML = $htmlHeader . $cuerpoInfo . $htmlFooter;

            // 4. Configuración y ejecución de PHPMailer
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
            
            // 5. Salida JSON limpia
            $this->emitirJSON([
                'success' => true, 
                'message' => 'Confirmación enviada satisfactoriamente.'
            ]);

        } catch (Throwable $e) {
            $this->emitirJSON([
                'success' => false, 
                'message' => 'Fallo en envío de correo: ' . $e->getMessage(),
                'debug'   => $debugInfo
            ], 400);
        }
    }

    /**
     * Helper para emitir JSON y terminar la ejecución sin basura de búfer.
     */
    private function emitirJSON(array $payload, int $httpCode = 200): void 
    {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}