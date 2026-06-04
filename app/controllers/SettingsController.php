<?php
/**
 * MODULE: SETTINGS & CONFIGURATION
 * File: app/controllers/SettingsController.php
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

final class SettingsController extends Controller
{
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->db = (new Database())->getConnection();

        /**
         * SEGURIDAD: 
         * Se valida la existencia de sesión activa para permitir el acceso.
         */
        if (!isset($_SESSION['user'])) {
            $this->redirect('/login');
            exit;
        }
    }

    /**
     * Menú principal de configuraciones
     */
    public function index(): void
    {
        $this->view('settings/index', ['title' => 'Configuración General']);
    }

    /**
     * Vista de configuración de correo
     */
    public function correo(): void
    {
        $stmt = $this->db->query("SELECT * FROM tbl_email_settings");
        $this->view('settings/mail', [
            'title' => 'Configuración de Correo', 
            'settings' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    /**
     * Guarda o actualiza (Lógica No Incremental).
     * Funciona gracias al índice UNIQUE en la columna 'tipo_correo'.
     */
    public function saveCorreo(): void
    {
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json');
        
        try {
            $tipo = $_POST['tipo_correo'] ?? 'INSCRIPCION';
            
            // Lógica INSERT ... ON DUPLICATE KEY UPDATE profesional
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

            echo json_encode(['ok' => true, 'msg' => 'Configuración sincronizada exitosamente']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error de base de datos: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Prueba de envío SMTP con datos en tiempo real
     */
    public function testCorreo(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        
        try {
            $dest = $_POST['email_test'] ?? '';
            $mode = $_POST['mode'] ?? 'connection';
            $fechaEnvio = date('Y-m-d'); 

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
                $mail->Subject = "Prueba de Conexión SMTP - Plataforma Diplomados";
                $mail->Body    = "
                    <h3>Verificación de Servidor</h3>
                    <p>Este es un mensaje de prueba para verificar la configuración SMTP.</p>
                    <hr>
                    <p><b>Fecha:</b> {$fechaEnvio}</p>
                    <p><b>Sistema:</b> Plataforma Diplomados</p>
                ";
            } else {
                $mail->Subject = $_POST['asunto'] ?: 'Prueba Plantilla';
                $body = $_POST['contenido'] ?? '';
                // Reemplazos de prueba para etiquetas dinámicas
                $body = str_replace(['{nombre}', '{apellido}', '{link_inscripcion}', '{link_descarga}'], ['Usuario', 'Prueba', '#', '#'], $body);
                $mail->Body = $body;
            }

            $mail->send();
            echo json_encode(['ok' => true, 'msg' => '¡Correo enviado con éxito!']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => "Error PHPMailer: " . ($mail->ErrorInfo ?? $e->getMessage())]);
        }
        exit;
    }
}