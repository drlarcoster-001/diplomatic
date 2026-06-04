<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/services/MailService.php
 * Propósito: Servicio centralizado de envío de correos con soporte avanzado y auditoría.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PDO;
use Throwable;

class MailService
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Envía un correo electrónico basado en una configuración de la DB
     */
    public function enviar(string $tipo, string $destinatario, array $datos = [], string $emailPrueba = null): array
    {
        try {
            // 1. Obtener configuración de la base de datos
            $stmt = $this->db->prepare("SELECT * FROM tbl_email_settings WHERE tipo_correo = :tipo LIMIT 1");
            $stmt->execute([':tipo' => $tipo]);
            $conf = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conf) throw new Exception("No se encontró configuración para: $tipo");

            // 2. Configurar PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Host       = $conf['smtp_host'];
            $mail->SMTPAuth   = (bool)$conf['require_auth'];
            $mail->Username   = $conf['smtp_user'];
            $mail->Password   = $conf['smtp_password'];
            $mail->SMTPSecure = $conf['smtp_security'] === 'SSL' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)$conf['smtp_port'];
            $mail->Timeout    = (int)($conf['timeout'] ?? 30); // Campo Senior

            // Opción Senior: Validar o ignorar certificados
            if (!($conf['validate_cert'] ?? 1)) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            }

            // 3. Remitente e Identidad
            $mail->setFrom($conf['from_email'], $conf['from_name']);
            if (!empty($conf['reply_to'])) {
                $mail->addReplyTo($conf['reply_to']);
            }
            $mail->addAddress($emailPrueba ?? $destinatario);

            // 4. Procesar Plantilla (Reemplazo de etiquetas)
            $asunto = $conf['asunto'];
            $mensaje = $conf['mensaje'];
            foreach ($datos as $key => $val) {
                $mensaje = str_replace("{{$key}}", (string)$val, $mensaje);
                $asunto = str_replace("{{$key}}", (string)$val, $asunto);
            }

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $mensaje;

            // 5. Envío y Log de Eventos
            $mail->send();
            $this->registrarEvento("ENVÍO EXITOSO", "Correo $tipo enviado a $destinatario");
            
            return ['ok' => true, 'msg' => 'Correo enviado correctamente.'];

        } catch (Throwable $e) {
            $errorMsg = "Error enviando $tipo a $destinatario: " . $e->getMessage();
            $this->registrarEvento("ERROR CORREO", $errorMsg);
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    private function registrarEvento(string $accion, string $detalles): void
    {
        try {
            $sql = "INSERT INTO tbl_eventos (usuario_id, accion, detalles, fecha) VALUES (:uid, :acc, :det, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':uid' => $_SESSION['user']['id'] ?? 0,
                ':acc' => $accion,
                ':det' => $detalles
            ]);
        } catch (Throwable $e) { /* Error silencioso en log */ }
    }
}