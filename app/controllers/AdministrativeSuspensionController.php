<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / SUSPENSIONES
 * ARCHIVO: app/controllers/AdministrativeSuspensionController.php
 * PROPÓSITO: Controlador maestro para la gestión de morosidad, suspensiones duales y notificaciones profesionales.
 * VERSIÓN: 2.2.5 - Fix: Sincronización de variables SMTP y manejo de excepciones en sendEmail.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdministrativeSuspensionModel;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PDO;
use Throwable;

/**
 * CARGA DE PHPMailer (Ajustada a tu estructura tools/)
 */
$phpmailerPath = realpath(__DIR__ . '/../../tools/phpmailer/');
if ($phpmailerPath) {
    require_once $phpmailerPath . '/Exception.php';
    require_once $phpmailerPath . '/PHPMailer.php';
    require_once $phpmailerPath . '/SMTP.php';
}

class AdministrativeSuspensionController extends Controller
{
    private AdministrativeSuspensionModel $model;
    private PDO $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Blindaje de seguridad: Solo administradores
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit();
        }

        $this->model = new AdministrativeSuspensionModel();
        // Obtenemos conexión para consultas rápidas de configuración
        $this->db = (new \App\Core\Database())->getConnection();
    }

    /**
     * Dashboard Principal: Lista de Diplomados/Cohortes.
     */
    public function index(): void
    {
        ob_start();
        $this->view('administrative/suspensions/index', [
            'offerings' => $this->model->getOfferingsDashboard()
        ]);
        ob_end_flush();
    }

    /**
     * Gestión Detallada: Grid con filtros y Trigger de Popup.
     */
    public function manage(): void
    {
        ob_start();
        $offeringId = (int)($_GET['id'] ?? 0);

        if ($offeringId === 0) {
            header('Location: /diplomatic/public/administrative/suspensions');
            exit;
        }

        $students = $this->model->getStudentsByOffering($offeringId);

        $this->view('administrative/suspensions/manage', [
            'title'       => 'Gestión de Suspensiones',
            'offering_id' => $offeringId,
            'students'    => $students
        ]);
        ob_end_flush();
    }

    /**
     * Acción AJAX: Actualizar estatus dual (Users + Students).
     */
    public function toggleStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = (int)($_POST['user_id'] ?? 0);
                $status = $_POST['status'] ?? '';

                if ($userId === 0 || !in_array($status, ['ACTIVE', 'SUSPENDED'])) {
                    throw new \Exception("Parámetros de estado inválidos.");
                }

                if ($this->model->updateStudentStatus($userId, $status)) {
                    $accion = ($status === 'SUSPENDED') ? 'SUSPENDIDO' : 'REACTIVADO';
                    
                    $this->jsonFinal([
                        'status'  => 'success',
                        'message' => "El estudiante ha sido {$accion} exitosamente.",
                        'action'  => $status
                    ]);
                } else {
                    throw new \Exception("No se pudo actualizar el registro dual.");
                }

            } catch (Throwable $e) {
                $this->jsonFinal(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        }
    }

    /**
     * ENVÍO DE CORREO PROFESIONAL (ESTILO LUJOSO)
     * Notifica al estudiante sobre su suspensión o reactivación.
     */
    public function sendEmail(): void 
    {
        // Limpiamos cualquier residuo de búfer para asegurar salida JSON pura
        while (ob_get_level() > 0) ob_end_clean();
        
        try {
            $userId = (int)($_POST['user_id'] ?? 0);
            $status = $_POST['status'] ?? ''; // 'SUSPENDED' o 'ACTIVE'
            $deuda  = $_POST['deuda'] ?? 'Compromisos administrativos pendientes';

            if ($userId <= 0) {
                throw new \Exception("ID de usuario no recibido o inválido.");
            }

            // 1. Obtener datos del destinatario directamente de tbl_users
            $sql = "SELECT u.email, CONCAT(u.first_name, ' ', u.last_name) as nombre 
                    FROM tbl_users u WHERE u.id = :id LIMIT 1";
            $stmtU = $this->db->prepare($sql);
            $stmtU->execute([':id' => $userId]);
            $userData = $stmtU->fetch(PDO::FETCH_ASSOC);

            if (!$userData || empty($userData['email'])) {
                throw new \Exception("El estudiante con ID {$userId} no posee un correo válido en el sistema.");
            }

            // 2. Configuración SMTP (Buscamos la configuración 'GENERAL' o 'INSCRIPCION')
            $stmtConf = $this->db->prepare("SELECT * FROM tbl_email_settings WHERE tipo_correo IN ('GENERAL', 'INSCRIPCION') ORDER BY (tipo_correo = 'GENERAL') DESC LIMIT 1");
            $stmtConf->execute();
            $conf = $stmtConf->fetch(PDO::FETCH_ASSOC);

            if (!$conf) {
                throw new \Exception("Error Crítico: No existe configuración SMTP activa en 'tbl_email_settings'.");
            }

            $stmtCo = $this->db->query("SELECT nombre_comercial FROM tbl_company_settings LIMIT 1");
            $nombreComercial = $stmtCo->fetchColumn() ?: 'Coordinación Académica';

            // 3. Configurar PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Host     = $conf['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $conf['smtp_user'];
            $mail->Password = $conf['smtp_password'];
            $mail->Port     = (int)$conf['smtp_port'];
            
            // Seguridad dinámica
            $security = strtoupper((string)$conf['smtp_security']);
            if ($security === 'SSL') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($security === 'TLS' || $security === 'STARTTLS') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom($conf['from_email'], $conf['from_name']);
            $mail->addAddress($userData['email']);

            // 4. Maquetación Profesional y Lujosa
            $isSuspension = ($status === 'SUSPENDED');
            $primaryColor = $isSuspension ? '#dc3545' : '#198754';
            $labelStatus  = $isSuspension ? 'AVISO DE SUSPENSIÓN' : 'NOTIFICACIÓN DE REACTIVACIÓN';
            
            $htmlHeader = "
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; font-family: Segoe UI, Arial, sans-serif;'>
                <div style='background-color: $primaryColor; padding: 25px; text-align: center;'>
                    <h2 style='color: #ffffff; margin: 0; text-transform: uppercase; letter-spacing: 2px; font-size: 20px;'>$labelStatus</h2>
                </div>
                <div style='padding: 40px; color: #333333; line-height: 1.6;'>
                    <p style='font-size: 17px;'>Estimado(a) <strong>{$userData['nombre']}</strong>:</p>";

            $htmlFooter = "
                    <br>
                    <p style='font-size: 14px; color: #777777;'>Si tiene alguna duda sobre este proceso o desea reportar un pago, por favor contacte a soporte administrativo.</p>
                    <hr style='border: none; border-top: 1px solid #eeeeee; margin: 30px 0;'>
                    <p style='font-size: 14px; color: #888888; margin-bottom: 5px;'>Atentamente,</p>
                    <p style='font-size: 18px; font-weight: bold; color: $primaryColor; margin-top: 0;'>$nombreComercial</p>
                </div>
                <div style='background-color: #f8f9fa; padding: 15px; text-align: center; color: #bbbbbb; font-size: 11px;'>
                    Este mensaje es generado automáticamente por el sistema Diplomatic. No responda a este remitente.
                </div>
            </div>";

            if ($isSuspension) {
                $cuerpo = "
                    <p>Le informamos que su acceso a la plataforma de aprendizaje ha sido <strong>suspendido temporalmente</strong> por motivos administrativos.</p>
                    <div style='background-color: #fff5f5; border-left: 5px solid #dc3545; padding: 20px; margin: 25px 0;'>
                        <strong style='color: #a94442;'>Motivo de la restricción:</strong><br>
                        <p style='margin-top: 8px; font-size: 15px;'>$deuda</p>
                    </div>
                    <p>Para recuperar el acceso a sus recursos académicos, por favor regularice su situación a la brevedad posible.</p>";
            } else {
                $cuerpo = "
                    <p>Nos complace informarle que su estatus ha sido actualizado y su cuenta se encuentra nuevamente <strong>ACTIVA</strong>.</p>
                    <div style='background-color: #f6fff9; border-left: 5px solid #198754; padding: 20px; margin: 25px 0;'>
                        <strong style='color: #155724;'>¡Acceso Restaurado!</strong><br>
                        <p style='margin-top: 8px; font-size: 15px;'>Ya puede ingresar al sistema.</p>
                    </div>";
            }

            $mail->isHTML(true);
            $mail->Subject = "$labelStatus - $nombreComercial";
            $mail->Body    = $htmlHeader . $cuerpo . $htmlFooter;

            $mail->send();
            $this->jsonFinal(['status' => 'success', 'message' => 'Correo enviado correctamente.']);

        } catch (Throwable $e) {
            $this->jsonFinal(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Blindaje de salida JSON pura.
     */
    private function jsonFinal(array $payload, int $code = 200): void 
    {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}