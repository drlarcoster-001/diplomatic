<?php
/**
 * MÓDULO - app/controllers/SettingsController.php
 * Controlador de gestión de configuraciones.
 * Administra Identidad Corporativa, SMTP, Seguridad y Base de Datos.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carga de dependencias de PHPMailer
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

        // Seguridad: Solo administradores acceden a este controlador
        $userRole = strtoupper(trim($_SESSION['user']['role'] ?? ''));
        if ($userRole !== 'ADMIN') {
            $projectRoot = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            header("Location: " . $projectRoot . "/dashboard");
            exit;
        }

        $this->db = (new Database())->getConnection();
    }

    /**
     * MENU PRINCIPAL DE CONFIGURACIÓN
     * Muestra las 5 tarjetas de acceso (Empresa, Correo, Sistema, Seguridad, BD)
     */
    public function index(): void
    {
        $this->view('settings/index', ['title' => 'Configuración General']);
    }

    /**
     * MÓDULO: DATOS DE EMPRESA
     * Gestiona la identidad legal y visual de la institución.
     */
    public function empresa(): void
    {
        $stmt = $this->db->query("SELECT * FROM tbl_company_settings LIMIT 1");
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $this->view('settings/empresa', [
            'title' => 'Datos de la Institución',
            'empresa' => $empresa
        ]);
    }

    /**
     * MÓDULO: CORREO / SMTP
     * Gestiona las pestañas de Inscripción y Certificados.
     */
    public function correo(): void
    {
        $stmt = $this->db->query("SELECT * FROM tbl_email_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('settings/mail', [
            'title' => 'Configuración de Correo',
            'settings' => $settings
        ]);
    }

    /**
     * PERSISTENCIA DE SMTP
     * Guarda la configuración evitando el error de parámetros duplicados (HY093).
     */
    public function saveCorreo(): void
    {
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json');
        
        try {
            $sql = "INSERT INTO tbl_email_settings 
                    (tipo_correo, protocolo, smtp_host, smtp_port, smtp_security, smtp_user, smtp_password, from_name, from_email, asunto, contenido) 
                    VALUES (:tipo, :prot, :host, :port, :security, :user, :pass, :f_name, :f_email, :asunto, :contenido)
                    ON DUPLICATE KEY UPDATE 
                    protocolo=:u_prot, smtp_host=:u_host, smtp_port=:u_port, smtp_security=:u_security, smtp_user=:u_user, 
                    smtp_password=:u_pass, from_name=:u_f_name, from_email=:u_f_email, asunto=:u_asunto, contenido=:u_contenido";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':tipo'         => $_POST['tipo_correo'] ?? 'INSCRIPCION',
                ':prot'         => $_POST['protocolo'] ?? 'SMTP',
                ':host'         => $_POST['smtp_host'] ?? '',
                ':port'         => (int)($_POST['smtp_port'] ?? 465),
                ':security'     => $_POST['smtp_security'] ?? 'SSL',
                ':user'         => $_POST['smtp_user'] ?? '',
                ':pass'         => $_POST['smtp_password'] ?? '',
                ':f_name'       => $_POST['from_name'] ?? '',
                ':f_email'      => $_POST['from_email'] ?? '',
                ':asunto'       => $_POST['asunto'] ?? '',
                ':contenido'    => $_POST['contenido'] ?? '',
                // Parámetros únicos para la actualización (UPDATE)
                ':u_prot'       => $_POST['protocolo'] ?? 'SMTP',
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
            echo json_encode(['ok' => false, 'msg' => 'Error de Base de Datos: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * PRUEBAS DE ENVÍO
     * Diferencia entre test de conexión (mensaje fijo) y test de plantilla (con etiquetas).
     */
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
                $mail->Subject = "Prueba de Conexión - Diplomatic";
                $mail->Body    = "Si usted ha recibido este mensaje, su conexión está funcionando correctamente.";
            } else {
                // Motor de reemplazo de etiquetas dinámicas
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

                $mail->Subject = $asunto ?: 'Prueba de Plantilla - Diplomatic';
                $mail->Body    = nl2br($mensaje);
            }

            $mail->send();
            echo json_encode(['ok' => true, 'msg' => '¡Correo enviado con éxito!']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error PHPMailer: ' . $mail->ErrorInfo]);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => 'Fallo técnico: ' . $e->getMessage()]);
        }
        exit;
    }
}