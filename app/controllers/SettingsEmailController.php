<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

require_once __DIR__ . '/../../tools/phpmailer/Exception.php';
require_once __DIR__ . '/../../tools/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../tools/phpmailer/SMTP.php';

final class SettingsEmailController extends Controller
{
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->getConnection();

        if (!isset($_SESSION['user'])) {
            $this->redirect('/login');
            exit;
        }
    }

    public function index(): void
    {
        // Auditoría con formato ARRAY (Como lo tienes en Estructura)
        AuditService::log([
            'module'      => 'SETTINGS_EMAIL',
            'action'      => 'ACCESS',
            'description' => 'Acceso al módulo independiente de correo',
            'event_type'  => 'NORMAL'
        ]);

        $stmt = $this->db->query("SELECT * FROM tbl_email_settings");
        $this->view('settings/mail', [
            'title'    => 'Configuración de Correo',
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
                    smtp_host = VALUES(smtp_host), smtp_port = VALUES(smtp_port), smtp_security = VALUES(smtp_security),
                    smtp_user = VALUES(smtp_user), smtp_password = VALUES(smtp_password), from_name = VALUES(from_name),
                    from_email = VALUES(from_email), asunto = VALUES(asunto), contenido = VALUES(contenido)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tipo' => $tipo, 
                ':host' => $_POST['smtp_host'] ?? '', 
                ':port' => $_POST['smtp_port'] ?? '465',
                ':security' => $_POST['smtp_security'] ?? 'SSL', 
                ':user' => $_POST['smtp_user'] ?? '', 
                ':pass' => $_POST['smtp_password'] ?? '',
                ':f_name' => $_POST['from_name'] ?? '', 
                ':f_email' => $_POST['from_email'] ?? '', 
                ':asunto' => $_POST['asunto'] ?? '', 
                ':contenido' => $_POST['contenido'] ?? ''
            ]);

            AuditService::log([
                'module'      => 'SETTINGS_EMAIL',
                'action'      => 'UPDATE',
                'description' => "Se actualizó la configuración de correo: {$tipo}",
                'event_type'  => 'WARNING'
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Configuración guardada']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
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
            $mail->CharSet = 'UTF-8';
            $mail->SMTPAuth = true;
            $mail->Host = $_POST['smtp_host'];
            $mail->Username = $_POST['smtp_user'];
            $mail->Password = $_POST['smtp_password'];
            $mail->Port = (int)$_POST['smtp_port'];
            $mail->SMTPSecure = ($_POST['smtp_security'] === 'SSL') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            
            $mail->setFrom($_POST['from_email'], $_POST['from_name']);
            $mail->addAddress($dest);
            $mail->isHTML(true);

            if ($mode === 'connection') {
                $mail->Subject = "Prueba de Conexión";
                $mail->Body = "SMTP funcionando.";
            } else {
                $mail->Subject = $_POST['asunto'];
                $mail->Body = $_POST['contenido'];
            }

            $mail->send();
            echo json_encode(['ok' => true, 'msg' => 'Enviado']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $mail->ErrorInfo]);
        }
        exit;
    }
}