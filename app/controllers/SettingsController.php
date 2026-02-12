<?php
/**
 * MÓDULO - app/controllers/SettingsController.php
 * Controlador de configuración del sistema.
 * Diferencia entre pruebas de conexión técnica y pruebas de plantillas dinámicas.
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

    public function index(): void { $this->view('settings/index', ['title' => 'Ajustes']); }

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
                    smtp_host=:u_host, smtp_port=:u_port, smtp_security=:u_security, smtp_user=:u_user, 
                    smtp_password=:u_pass, from_name=:u_f_name, from_email=:u_f_email, asunto=:u_asunto, contenido=:u_contenido";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tipo'         => $_POST['tipo_correo'] ?? 'INSCRIPCION',
                ':host'         => $_POST['smtp_host'] ?? '',
                ':port'         => (int)($_POST['smtp_port'] ?? 465),
                ':security'     => $_POST['smtp_security'] ?? 'SSL',
                ':user'         => $_POST['smtp_user'] ?? '',
                ':pass'         => $_POST['smtp_password'] ?? '',
                ':f_name'       => $_POST['from_name'] ?? '',
                ':f_email'      => $_POST['from_email'] ?? '',
                ':asunto'       => $_POST['asunto'] ?? '',
                ':contenido'    => $_POST['contenido'] ?? '',
                ':u_host'       => $_POST['smtp_host'] ?? '',
                ':u_port'       => (int)($_POST['smtp_port'] ?? 465),
                ':u_security'   => $_POST['smtp_security'] ?? 'SSL',
                ':u_user'       => $_POST['smtp_user'] ?? '',
                ':u_pass'       => $_POST['smtp_password'] ?? '',
                ':u_f_name'     => $_POST['from_name'] ?? '',
                ':u_f_email'    => $_POST['from_email'] ?? '',
                ':u_asunto'     => $_POST['asunto'] ?? '',
                ':u_contenido'  => $_POST['contenido'] ?? ''
            ]);
            echo json_encode(['ok' => true, 'msg' => 'Configuración guardada correctamente']);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
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
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($_POST['from_email'], $_POST['from_name']);
            $mail->addAddress($_POST['email_test']);
            $mail->isHTML(true);

            $mode = $_POST['mode'] ?? 'connection';

            if ($mode === 'connection') {
                // MENSAJE DE CONEXIÓN PURO
                $mail->Subject = "Prueba de Conexión - Diplomatic";
                $mail->Body    = "Si usted ha recibido este mensaje, su conexión está funcionando.";
            } else {
                // MENSAJE DE PLANTILLA CON ETIQUETAS
                $tags = [
                    '{nombre}'           => 'Juan',
                    '{apellido}'         => 'Pérez',
                    '{plataforma}'       => 'Diplomatic Online',
                    '{link_activacion}'  => '<a href="#" style="background:#0d6efd; color:#fff; padding:8px 15px; text-decoration:none; border-radius:4px;">Activar mi cuenta</a>',
                    '{nombre_diplomado}' => 'Diplomado en Gestión Pública',
                    '{link_descarga}'    => '<a href="#" style="color:#198754; font-weight:bold;">Descargar Certificado</a>'
                ];
                $asunto  = str_replace(array_keys($tags), array_values($tags), $_POST['asunto']);
                $mensaje = str_replace(array_keys($tags), array_values($tags), $_POST['contenido']);
                
                $mail->Subject = $asunto ?: 'Prueba de Plantilla';
                $mail->Body    = nl2br($mensaje);
            }

            $mail->send();
            echo json_encode(['ok' => true, 'msg' => '¡Correo enviado con éxito!']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'Fallo: ' . $mail->ErrorInfo]);
        }
        exit;
    }
}