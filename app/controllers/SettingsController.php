<?php
/**
 * MÓDULO - app/controllers/SettingsController.php
 * Controlador de gestión de configuraciones técnicas con auditoría.
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

require_once __DIR__ . '/../../tools/phpmailer/Exception.php';
require_once __DIR__ . '/../../tools/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../tools/phpmailer/SMTP.php';

final class SettingsController extends Controller
{
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userRole = strtoupper(trim($_SESSION['user']['role'] ?? ''));
        if ($userRole !== 'ADMIN') {
            $this->redirect('/dashboard');
        }

        $this->db = (new Database())->getConnection();
    }

    public function index(): void
    {
        AuditService::log([
            'module' => 'SETTINGS', 'action' => 'ACCESS',
            'description' => 'Ingreso al menú de configuración general', 'event_type' => 'NORMAL'
        ]);
        $this->view('settings/index', ['title' => 'Configuración General']);
    }

    public function correo(): void
    {
        AuditService::log([
            'module' => 'SETTINGS_MAIL', 'action' => 'ACCESS',
            'description' => 'Acceso a configuración SMTP', 'event_type' => 'NORMAL'
        ]);
        $stmt = $this->db->query("SELECT * FROM tbl_email_settings");
        $this->view('settings/mail', ['title' => 'Configuración de Correo', 'settings' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function saveCorreo(): void
    {
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json');
        
        try {
            $tipo = $_POST['tipo_correo'] ?? 'INSCRIPCION';

            $sql = "INSERT INTO tbl_email_settings 
                    (tipo_correo, smtp_host, smtp_port, smtp_security, smtp_user, smtp_password, from_name, from_email, asunto, contenido) 
                    VALUES (:tipo, :host, :port, :security, :user, :pass, :f_name, :f_email, :asunto, :contenido)
                    ON DUPLICATE KEY UPDATE 
                    smtp_host=:u_host, smtp_port=:u_port, smtp_security=:u_security, smtp_user=:u_user, 
                    smtp_password=:u_pass, from_name=:u_f_name, from_email=:u_f_email, asunto=:u_asunto, contenido=:u_contenido";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tipo'       => $tipo,
                ':host'       => $_POST['smtp_host'] ?? '',
                ':port'       => (int)($_POST['smtp_port'] ?? 465),
                ':security'   => $_POST['smtp_security'] ?? 'SSL',
                ':user'       => $_POST['smtp_user'] ?? '',
                ':pass'       => $_POST['smtp_password'] ?? '',
                ':f_name'     => $_POST['from_name'] ?? '',
                ':f_email'    => $_POST['from_email'] ?? '',
                ':asunto'     => $_POST['asunto'] ?? '',
                ':contenido'  => $_POST['contenido'] ?? '',
                ':u_host'     => $_POST['smtp_host'] ?? '',
                ':u_port'     => (int)($_POST['smtp_port'] ?? 465),
                ':u_security' => $_POST['smtp_security'] ?? 'SSL',
                ':u_user'     => $_POST['smtp_user'] ?? '',
                ':u_pass'     => $_POST['smtp_password'] ?? '',
                ':u_f_name'   => $_POST['from_name'] ?? '',
                ':u_f_email'  => $_POST['from_email'] ?? '',
                ':u_asunto'   => $_POST['asunto'] ?? '',
                ':u_contenido'=> $_POST['contenido'] ?? ''
            ]);

            // GENERAMOS EL LOG ANTES DE RESPONDER AL NAVEGADOR
            AuditService::log([
                'module'      => 'SETTINGS_MAIL',
                'action'      => 'UPDATE',
                'description' => "Se actualizaron los parámetros de correo para: {$tipo}",
                'event_type'  => 'WARNING' // Amarillo en consola
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Configuración guardada']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error DB: ' . $e->getMessage()]);
        }
        exit;
    }

    public function testCorreo(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        
        try {
            $dest = $_POST['email_test'] ?? 'Unknown';
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
                $mail->Subject = "Prueba de Conexión - Diplomatic";
                $mail->Body    = "Conexión SMTP exitosa.";
            } else {
                $mail->Subject = $_POST['asunto'] ?: 'Prueba Plantilla';
                $mail->Body    = nl2br($_POST['contenido']);
            }

            $mail->send();

            // LOG DE ÉXITO
            AuditService::log([
                'module'      => 'SETTINGS_MAIL',
                'action'      => 'TEST_SEND',
                'description' => "Prueba de correo exitosa a: {$dest}",
                'event_type'  => 'SUCCESS'
            ]);

            echo json_encode(['ok' => true, 'msg' => '¡Correo enviado!']);
        } catch (Throwable $e) {
            // LOG DE FALLO
            AuditService::log([
                'module'      => 'SETTINGS_MAIL',
                'action'      => 'TEST_FAIL',
                'description' => "Falló la prueba de correo a: {$dest}. Error: " . $e->getMessage(),
                'event_type'  => 'ERROR'
            ]);
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }
}