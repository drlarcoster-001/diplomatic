<?php
/**
 * MODULE: SETTINGS & CONFIGURATION
 * File: app/controllers/SettingsEmailController.php
 * Propósito: Gestión de SMTP y plantillas con lógica de guardado único (No incremental).
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService; 
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Throwable;

/**
 * IMPORTACIÓN DE LIBRERÍAS SMTP
 */
require_once __DIR__ . '/../../tools/phpmailer/Exception.php';
require_once __DIR__ . '/../../tools/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../tools/phpmailer/SMTP.php';

final class SettingsEmailController extends Controller
{
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->db = (new Database())->getConnection();

        if (!isset($_SESSION['user'])) {
            $this->redirect('/login');
            exit;
        }
    }

    public function index(): void
    {
        AuditService::log([
            'module'      => 'SETTINGS_EMAIL',
            'action'      => 'ACCESS',
            'description' => 'Acceso al módulo independiente de correo',
            'event_type'  => 'NORMAL'
        ]);

        $stmt = $this->db->query("SELECT * FROM tbl_email_settings");
        $this->view('settings/mail', [
            'title' => 'Configuración de Correo', 
            'settings' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    public function save(): void
    {
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json');
        
        try {
            $tipo = $_POST['tipo_correo'] ?? 'INSCRIPCION';
            $sql = "INSERT INTO tbl_email_settings 
                    (tipo_correo, smtp_host, smtp_port, smtp_security, smtp_user, smtp_password, from_name, from_email, asunto, contenido) 
                    VALUES (:tipo, :host, :port, :security, :user, :pass, :f_name, :f_email, :asunto, :contenido)
                    ON DUPLICATE KEY UPDATE 
                    smtp_host     = VALUES(smtp_host),
                    smtp_port     = VALUES(smtp_port),
                    smtp_security = VALUES(smtp_security),
                    smtp_user     = VALUES(smtp_user),
                    smtp_password = VALUES(smtp_password),
                    from_name     = VALUES(from_name),
                    from_email    = VALUES(from_email),
                    asunto        = VALUES(asunto),
                    contenido     = VALUES(contenido)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tipo'       => $tipo,
                ':host'       => $_POST['smtp_host'] ?? '',
                ':port'       => $_POST['smtp_port'] ?? '465',
                ':security'   => $_POST['smtp_security'] ?? 'SSL',
                ':user'       => $_POST['smtp_user'] ?? '',
                ':pass'       => $_POST['smtp_password'] ?? '',
                ':f_name'     => $_POST['from_name'] ?? '',
                ':f_email'    => $_POST['from_email'] ?? '',
                ':asunto'     => $_POST['asunto'] ?? '',
                ':contenido'  => $_POST['contenido'] ?? ''
            ]);

            AuditService::log([
                'module'      => 'SETTINGS_EMAIL',
                'action'      => 'UPDATE_CONFIG',
                'description' => "Se actualizó la configuración de correo: {$tipo}",
                'event_type'  => 'WARNING'
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Configuración sincronizada exitosamente']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function test(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        
        try {
            $dest = $_POST['email_test'] ?? '';
            $mode = $_POST['mode'] ?? 'connection';

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $_POST['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_POST['smtp_user'];
            $mail->Password   = $_POST['smtp_password'];
            $mail->SMTPSecure = ($_POST['smtp_security'] === 'SSL') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)$_POST['smtp_port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($_POST['from_email'], $_POST['from_name']);
            $mail->addAddress($dest);
            $mail->isHTML(true);

            if ($mode === 'connection') {
                $mail->Subject = "Prueba de Conexión SMTP";
                $mail->Body    = "La conexión con el servidor de correo se ha establecido correctamente.";
            } else {
                $mail->Subject = $_POST['asunto'];
                $body = $_POST['contenido'];
                // Reemplazos de prueba para etiquetas
                $body = str_replace(['{nombre}', '{apellido}', '{link_inscripcion}', '{link_descarga}'], ['Soporte', 'Diplomados', '#', '#'], $body);
                $mail->Body = $body;
            }

            $mail->send();

            AuditService::log([
                'module'      => 'SETTINGS_EMAIL',
                'action'      => 'TEST_SEND',
                'description' => "Envío de prueba exitoso a: {$dest}",
                'event_type'  => 'SUCCESS'
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Correo enviado con éxito']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => "Error: " . $mail->ErrorInfo]);
        }
        exit;
    }
}