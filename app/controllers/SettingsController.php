<?php
/**
 * MÓDULO: CONFIGURACIÓN
 * Archivo: app/controllers/SettingsController.php
 * Propósito: Controlador central para gestión de SMTP y navegación de ajustes.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../tools/phpmailer/Exception.php';
require_once __DIR__ . '/../../tools/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../../tools/phpmailer/SMTP.php';

final class SettingsController extends Controller
{
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->getConnection();
    }

    public function index(): void
    {
        $this->view('settings/index', ['title' => 'Ajustes']);
    }

    public function correo(): void
    {
        $stmt = $this->db->query("SELECT * FROM tbl_email_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->view('settings/mail', ['settings' => $settings]);
    }

    public function saveCorreo(): void
    {
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json');
        try {
            $sql = "INSERT INTO tbl_email_settings 
                    (tipo_correo, smtp_host, smtp_port, smtp_security, smtp_user, smtp_password, from_name, from_email, asunto, contenido) 
                    VALUES (:tipo, :host, :port, :security, :user, :pass, :f_name, :f_email, :asunto, :contenido)
                    ON DUPLICATE KEY UPDATE 
                    smtp_host=:host, smtp_port=:port, smtp_security=:security, smtp_user=:user, 
                    smtp_password=:pass, from_name=:f_name, from_email=:f_email, asunto=:asunto, contenido=:contenido";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tipo'      => $_POST['tipo_correo'] ?? 'INSCRIPCION',
                ':host'      => $_POST['smtp_host'] ?? '',
                ':port'      => (int)($_POST['smtp_port'] ?? 465),
                ':security'  => $_POST['smtp_security'] ?? 'SSL',
                ':user'      => $_POST['smtp_user'] ?? '',
                ':pass'      => $_POST['smtp_password'] ?? '',
                ':f_name'    => $_POST['from_name'] ?? 'Diplomatic',
                ':f_email'   => $_POST['from_email'] ?? '',
                ':asunto'    => $_POST['asunto'] ?? '',
                ':contenido' => $_POST['contenido'] ?? ''
            ]);
            echo json_encode(['ok' => true, 'msg' => 'Configuración guardada']);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    public function testCorreo(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $_POST['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_POST['smtp_user'];
            $mail->Password   = $_POST['smtp_password'];
            $mail->SMTPSecure = ($_POST['smtp_security'] === 'SSL') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)$_POST['smtp_port'];
            $mail->setFrom($_POST['from_email'], $_POST['from_name']);
            $mail->addAddress($_POST['email_test']);
            $mail->isHTML(true);
            $mail->Subject = 'Prueba SMTP Diplomatic';
            $mail->Body    = "Exito al conectar con mail.plataformadiplomados.com";
            $mail->send();
            echo json_encode(['ok' => true, 'msg' => '¡Correo enviado con éxito!']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $mail->ErrorInfo]);
        }
        exit;
    }
}