<?php
/**
 * MÓDULO: SERVICIOS DE CORREO
 * Archivo: app/services/MailServiceRegister.php
 * Propósito: Envío de correos con soporte para etiquetas de llaves simples { } y dobles {{ }}.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Throwable;

final class MailServiceRegister
{
    private $db;

    public function __construct() 
    { 
        $this->db = (new Database())->getConnection(); 
    }

    /**
     * Envía correos procesando etiquetas dinámicas
     */
    public function enviarValidacion(string $emailDestino, array $datos, string $tipo = 'INSCRIPCION'): array
    {
        try {
            // 1. Obtener configuración de la DB
            $stmt = $this->db->prepare("SELECT * FROM tbl_email_settings WHERE tipo_correo = ? LIMIT 1");
            $stmt->execute([$tipo]);
            $conf = $stmt->fetch();

            if (!$conf) throw new \Exception("No existe configuración para el tipo: " . $tipo);

            // 2. Cargar PHPMailer con rutas absolutas
            $toolsPath = dirname(__DIR__, 2) . '/tools/phpmailer/';
            require_once $toolsPath . 'Exception.php';
            require_once $toolsPath . 'PHPMailer.php';
            require_once $toolsPath . 'SMTP.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $conf['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $conf['smtp_user'];
            $mail->Password   = $conf['smtp_password'];
            $mail->SMTPSecure = ($conf['smtp_security'] === 'SSL') ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)$conf['smtp_port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($conf['from_email'], $conf['from_name']);
            $mail->addAddress($emailDestino);
            $mail->isHTML(true);
            $mail->Subject = $conf['asunto'];

            // 3. Procesar etiquetas (Soporta {etiqueta} y {{etiqueta}})
            $body = $conf['contenido'];
            foreach ($datos as $key => $val) {
                $body = str_replace(['{{' . $key . '}}', '{' . $key . '}'], (string)$val, $body);
            }
            $mail->Body = $body;

            $mail->send();
            return ['ok' => true, 'msg' => 'Enviado con éxito.'];

        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }
}