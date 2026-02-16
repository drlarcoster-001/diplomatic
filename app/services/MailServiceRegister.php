<?php
/**
 * MODULE: REGISTER SERVICE
 * File: app/services/MailServiceRegister.php
 * Propósito: Motor de envío exclusivo para el proceso de pre-registro y validación.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PDO;
use Throwable;

require_once __DIR__ . '/../../tools/phpmailer/Exception.php';
require_once __DIR__ . '/../../tools/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../tools/phpmailer/SMTP.php';

class MailServiceRegister
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Envía el correo de validación usando la plantilla de 'INSCRIPCION'
     */
    public function enviarValidacion(string $destinatario, array $tags): array
    {
        try {
            // 1. Obtener la configuración guardada en el módulo de correo (INSCRIPCION)
            $stmt = $this->db->prepare("SELECT * FROM tbl_email_settings WHERE tipo_correo = 'INSCRIPCION' LIMIT 1");
            $stmt->execute();
            $conf = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conf) {
                throw new Exception("No se encontró la configuración de correo para 'INSCRIPCION'.");
            }

            // 2. Configurar PHPMailer con los datos de la DB
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Host       = $conf['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $conf['smtp_user'];
            $mail->Password   = $conf['smtp_password'];
            $mail->SMTPSecure = $conf['smtp_security'] === 'SSL' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)$conf['smtp_port'];

            // 3. Identidad
            $mail->setFrom($conf['from_email'], $conf['from_name']);
            $mail->addAddress($destinatario);

            // 4. Procesar Plantilla (Reemplazo de etiquetas según tu configuración)
            $asunto = $conf['asunto'];
            $mensaje = $conf['contenido']; // Usamos 'contenido' que es el nombre de tu columna

            foreach ($tags as $key => $val) {
                $mensaje = str_replace("{{$key}}", (string)$val, $mensaje);
                $asunto = str_replace("{{$key}}", (string)$val, $asunto);
            }

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $mensaje;

            $mail->send();
            
            return ['ok' => true, 'msg' => 'Correo de validación enviado.'];

        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => "Error en MailServiceRegister: " . $e->getMessage()];
        }
    }
}