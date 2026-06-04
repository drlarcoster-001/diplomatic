<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / INSCRIPCIONES
 * ARCHIVO: app/controllers/AdministrativeInscriptionsController_s6.php
 * PROPÓSITO: Envío de correo directo (Local Driver) para evitar conflictos con MailService o DB.
 * VERSIÓN: 3.0.0 - Independiente de MailService.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\StudentsInscriptionsModel_s6; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PDO;
use Throwable;

/**
 * CARGA MANUAL DE PHPMailer (Ubicación según tu estructura tools/)
 */
$phpmailerPath = realpath(__DIR__ . '/../../tools/phpmailer/');
if ($phpmailerPath) {
    require_once $phpmailerPath . '/Exception.php';
    require_once $phpmailerPath . '/PHPMailer.php';
    require_once $phpmailerPath . '/SMTP.php';
}

final class AdministrativeInscriptionsController_s6 extends Controller
{
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function sendEmail(): void 
    {
        // Limpiamos cualquier residuo de búfer
        while (ob_get_level() > 0) ob_end_clean();
        
        try {
            $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
            if ($enrollmentId <= 0) throw new Exception("ID de inscripción inválido.");

            // 1. Obtener datos del estudiante y diplomado
            $model = new StudentsInscriptionsModel_s6();
            $data = $model->getEnrollmentData($enrollmentId);
            if (!$data) throw new Exception("No se encontraron datos de la inscripción.");

            // 2. Obtener configuración SMTP (Usando las columnas que SÍ existen: 'contenido')
            $stmt = $this->db->prepare("SELECT * FROM tbl_email_settings WHERE tipo_correo = 'INSCRIPCION' LIMIT 1");
            $stmt->execute();
            $conf = $stmt->fetch(PDO::FETCH_ASSOC);

            // --- NUEVO: Obtener nombre de la empresa para la firma ---
            $stmtCo = $this->db->query("SELECT nombre_comercial FROM tbl_company_settings LIMIT 1");
            $nombreComercial = $stmtCo->fetchColumn() ?: 'Coordinación Académica';

            if (!$conf) throw new Exception("No hay configuración SMTP para 'INSCRIPCION'.");

            // 3. Configurar PHPMailer directamente
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Host    = $conf['smtp_host'];
            $mail->SMTPAuth = true; // Forzamos true ya que tenemos credenciales
            $mail->Username = $conf['smtp_user'];
            $mail->Password = $conf['smtp_password'];
            $mail->Port     = (int)$conf['smtp_port'];
            
            // Seguridad dinámica
            $mail->SMTPSecure = (strtoupper($conf['smtp_security']) === 'SSL') 
                                ? PHPMailer::ENCRYPTION_SMTPS 
                                : PHPMailer::ENCRYPTION_STARTTLS;

            // Remitente y Destinatario
            $mail->setFrom($conf['from_email'], $conf['from_name']);
            $mail->addAddress($data['email']);




            // 4. Procesar Plantilla (Usando la columna 'contenido')
            // --- NUEVO: Variables y Maquetación Profesional ---
            $nombreEstudiante = htmlspecialchars(trim($data['first_name'] . ' ' . $data['last_name']));
            $nombreDiplomado  = htmlspecialchars($data['diplomado_name']);
            $metodoPago       = strtoupper(trim((string)($data['payment_method'] ?? '')));

            // Estructura del Header (La parte azul de arriba)
            $htmlHeader = "
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; font-family: Arial, sans-serif;'>
                <div style='background-color: #0d6efd; padding: 20px; text-align: center;'>
                    <h2 style='color: #ffffff; margin: 0;'>Notificación de Inscripción</h2>
                </div>
                <div style='padding: 30px; color: #333333; line-height: 1.6;'>
                    <p style='font-size: 16px;'>Hola, <strong>$nombreEstudiante</strong>:</p>";

            // Estructura del Footer (La firma y el pie de página)
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

            // Lógica para decidir qué mensaje enviar según el pago
            if ($metodoPago === 'CASH' || $metodoPago === 'EFECTIVO') {
                $asunto = "Registro Recibido: $nombreDiplomado";
                $cuerpoMensaje = "
                    <p>Tu solicitud para participar en <strong>$nombreDiplomado</strong> ha sido registrada exitosamente.</p>
                    <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                        <strong style='color: #856404;'>Estatus: EN REVISIÓN - COMPROMISO PAGO</strong><br>
                        <p style='margin-top: 5px; font-size: 14px;'>Has seleccionado el pago en taquilla. Para formalizar tu inscripción, debes acudir a nuestra sede administrativa y realizar el pago correspondiente.</p>
                    </div>";
            } else {
                $asunto = "Pago en Revisión: $nombreDiplomado";
                $cuerpoMensaje = "
                    <p>Hemos recibido tu comprobante de pago digital para el programa <strong>$nombreDiplomado</strong>.</p>
                    <div style='background-color: #cfe2ff; border-left: 4px solid #0d6efd; padding: 15px; margin: 20px 0;'>
                        <strong style='color: #084298;'>Estatus: EN REVISIÓN</strong><br>
                        <p style='margin-top: 5px; font-size: 14px;'>Nuestro equipo administrativo verificará la transacción en las próximas horas. Una vez validado, recibirás tu confirmación definitiva.</p>
                    </div>";
            }

            $cuerpoFinalHTML = $htmlHeader . $cuerpoMensaje . $htmlFooter;


            $mail->isHTML(true);
            $mail->Subject = $asunto;         // Usamos el asunto dinámico
            $mail->Body    = $cuerpoFinalHTML; // Usamos el HTML profesional

            // 5. Enviar
            $mail->send();

            $this->emitirJSON(['success' => true, 'message' => 'Correo enviado exitosamente.']);

        } catch (Throwable $e) {
            $this->emitirJSON([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ], 400);
        }
    }

    private function emitirJSON(array $payload, int $httpCode = 200): void {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}