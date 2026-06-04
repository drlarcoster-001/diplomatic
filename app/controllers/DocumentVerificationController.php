<?php
/**
 * MÓDULO: ADMINISTRATIVO / VERIFICACIÓN DE DOCUMENTOS
 * ARCHIVO: app/Controllers/DocumentVerificationController.php
 * PROPÓSITO: Gestionar la auditoría de requisitos, promoción a Estudiante (Matriculación) y notificaciones SMTP.
 * VERSIÓN: 1.3.3 - Fix: Acción 'Observar' ahora mantiene estatus REVISION sincronizado con el Modelo.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\DocumentVerificationModel;
use App\Core\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PDO;
use Exception;
use Throwable;

/**
 * IMPORTACIÓN MANUAL DE LIBRERÍAS (Basado en estructura tools/phpmailer/)
 */
$toolsPath = realpath(__DIR__ . '/../../tools/phpmailer/');
if ($toolsPath) {
    require_once $toolsPath . '/Exception.php';
    require_once $toolsPath . '/PHPMailer.php';
    require_once $toolsPath . '/SMTP.php';
}

class DocumentVerificationController extends Controller
{
    private DocumentVerificationModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $user = $_SESSION['user'] ?? null;
        
        // Verificación de rango y tipo de usuario para acceso administrativo
        if (!$user || $user['user_type'] !== 'INTERNAL' || !in_array(strtoupper($user['role'] ?? ''), ['ADMIN', 'ACADEMICO', 'SUPERADMIN'])) {
            header('Location: /diplomatic/public/dashboard');
            exit;
        }
        
        $this->model = new DocumentVerificationModel();
    }

    public function index(): void
    {
        // Blindaje de búfer para evitar el error "Unexpected token <" al renderizar la vista base
        if (ob_get_level() > 0) ob_end_clean();
        
        $this->view('administrative/document_verification/index', [
            'title' => 'Verificación de Documentos y Formalización'
        ]);
    }

    /**
     * Obtiene la lista de verificaciones pendientes vía AJAX.
     */
    public function getPendingVerifications(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $filters = [
                'status' => $_GET['status'] ?? 'REVISION',
                'search' => $_GET['search'] ?? ''
            ];
            
            $data = $this->model->getPendingList($filters);
            echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * GATILLO VERDE: Aprueba documentos, crea Ficha, Matricula y envía Correo SMTP.
     */
    public function approveDocuments(): void
    {
        // Blindaje estricto de búfer para asegurar que el proceso de envío de correo no ensucie el JSON
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
            $adminId      = (int)$_SESSION['user']['id'];

            if ($enrollmentId <= 0) throw new Exception("ID de inscripción inválido.");

            // El modelo procesa la disparidad de tipos (INT UNSIGNED vs INT) en la transacción
            $result = $this->model->promoteToStudent($enrollmentId, $adminId);

            if ($result['success']) {
                $email       = $result['email'] ?? '';
                $firstName   = $result['first_name'] ?? 'Participante';
                $diplomaName = $result['diploma_name'] ?? 'Diplomado';
                $studentCode = $result['student_code'] ?? 'N/A';

                if (!empty($email)) {
                    $this->sendWelcomeEmailSMTP($email, $firstName, $diplomaName, $studentCode);
                }

                echo json_encode([
                    'ok' => true, 
                    'message' => "¡Aprobado! Se ha formalizado la inscripción. Expediente: " . $studentCode
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception($result['error'] ?? "No se pudo formalizar la inscripción.");
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Procesa el rechazo definitivo guardando el motivo en 'observations'.
     */
    public function rejectDocuments(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
            $reason       = trim((string)($_POST['reason'] ?? ''));
            $adminId      = (int)$_SESSION['user']['id'];

            if ($enrollmentId <= 0) throw new Exception("ID de inscripción inválido.");
            if (empty($reason)) throw new Exception("Debe indicar un motivo para el rechazo.");

            // Sincronización con el modelo para persistencia en tbl_enrollments.observations y cambio a RECHAZADO
            $result = $this->model->updateEnrollmentStatus($enrollmentId, 'RECHAZADO', $reason, $adminId);

            if ($result) {
                echo json_encode(['ok' => true, 'message' => "Inscripción rechazada y motivo registrado."], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception("Error al procesar el rechazo en la base de datos.");
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Registra observaciones para corrección del estudiante MANTENIENDO el Estatus REVISION.
     */
    public function observeDocuments(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
            $observation  = trim((string)($_POST['observation'] ?? ''));
            $adminId      = (int)$_SESSION['user']['id'];

            if ($enrollmentId <= 0) throw new Exception("ID de inscripción inválido.");
            if (empty($observation)) throw new Exception("Debe indicar qué documento requiere corrección.");

            // Sincronizado: Se llama al método que mantiene el estatus en REVISION y solo guarda la observación
            $result = $this->model->addObservationToEnrollment($enrollmentId, $observation, $adminId);

            if ($result) {
                echo json_encode(['ok' => true, 'message' => "Observación registrada. El expediente continúa en revisión."], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception("Error al guardar la observación técnica.");
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Notificación SMTP: Envía correo de bienvenida con el código de expediente.
     */
    private function sendWelcomeEmailSMTP(string $email, string $firstName, string $diplomaName, string $studentCode): void
    {
        if (ob_get_level() > 0) ob_clean();

        try {
            $db = (new Database())->getConnection();
            
            $stmt = $db->prepare("SELECT smtp_host, smtp_user, smtp_password, smtp_port, smtp_security, from_email, from_name FROM tbl_email_settings WHERE tipo_correo = 'INSCRIPCION' LIMIT 1");
            $stmt->execute();
            $smtp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$smtp) return;

            $stmtCo = $db->query("SELECT nombre_comercial FROM tbl_company_settings LIMIT 1");
            $nombreComercial = $stmtCo->fetchColumn() ?: 'Coordinación Académica';

            $subject = "¡Felicidades! Tu inscripción ha sido formalizada 🎉";
            
            $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
                <div style='background-color: #0d6efd; color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>¡Bienvenido a bordo, $firstName!</h2>
                </div>
                <div style='padding: 30px; background-color: #ffffff; color: #333333; line-height: 1.6;'>
                    <p>Nos complace informarte que tus requisitos administrativos y financieros han sido <strong>validados con éxito</strong>.</p>
                    <p>Ya eres oficialmente estudiante regular del programa:</p>
                    <h3 style='color: #0d6efd; text-align: center;'>$diplomaName</h3>
                    
                    <div style='background-color: #f8f9fa; border-left: 4px solid #198754; padding: 15px; margin: 20px 0;'>
                        <p style='margin: 0; font-size: 14px;'>Tu número de expediente académico oficial es:</p>
                        <p style='margin: 5px 0 0 0; font-size: 18px; font-weight: bold; letter-spacing: 1px;'>$studentCode</p>
                    </div>
                    
                    <p>Prepárate para iniciar esta gran experiencia de aprendizaje.</p>
                    <br><hr style='border: none; border-top: 1px solid #eeeeee; margin: 20px 0;'>
                    <p style='font-size: 14px; color: #666666;'>Atentamente,</p>
                    <p style='font-size: 16px; font-weight: bold; color: #0d6efd;'>$nombreComercial</p>
                </div>
            </div>";

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['smtp_user'];
            $mail->Password   = $smtp['smtp_password'];
            $mail->Port       = (int)$smtp['smtp_port'];
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPDebug  = 0;

            $mail->SMTPSecure = (strtoupper($smtp['smtp_security'] ?? '') === 'SSL') 
                                ? PHPMailer::ENCRYPTION_SMTPS 
                                : PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($email, $firstName);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlContent;

            $mail->send();

        } catch (Throwable $e) {
            error_log("Fallo envío correo de bienvenida a $email: " . $e->getMessage());
        }
    }

    /**
     * Genera el Listado de Auditoría en PDF.
     * Estructura: Página 1 (Revisión) y luego (Compromiso).
     */
    public function ImprimirListadoPDF(): void
    {
        // 1. Limpieza total de búfer
        while (ob_get_level() > 0) ob_end_clean();

        try {
            // 2. Obtener los datos usando el método existente del modelo
            $listRevision   = $this->model->getPendingList(['status' => 'REVISION']);
            $listCompromiso = $this->model->getPendingList(['status' => 'COMPROMISO']);

            // 3. Preparar Dompdf
            require_once __DIR__ . '/../../tools/dompdf/autoload.inc.php';
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new \Dompdf\Dompdf($options);

            $fechaHoy = date("d/m/Y H:i A");

            // 4. Construcción del HTML
            $html = "
            <html>
            <head>
                <style>
                    body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; margin: 10px; }
                    .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
                    .title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
                    .section-title { background-color: #f8f9fa; padding: 8px; font-weight: bold; border: 1px solid #ddd; margin-top: 20px; text-transform: uppercase; color: #0d6efd; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    th { background-color: #343a40; color: white; border: 1px solid #000; padding: 6px; font-size: 9px; text-transform: uppercase; }
                    td { border: 1px solid #ccc; padding: 6px; text-align: center; }
                    .text-left { text-align: left; }
                    .footer { position: fixed; bottom: -20px; left: 0; right: 0; text-align: right; font-size: 8px; color: #777; }
                    .status-pago { font-weight: bold; color: #198754; }
                </style>
            </head>
            <body>
                <div class='footer'>Reporte de Auditoría - Generado el: $fechaHoy</div>

                <div class='header'>
                    <div class='title'>Auditoría de Expedientes por Formalizar</div>
                    <div style='font-size: 11px; margin-top: 5px;'>Control Académico y Verificación de Documentos</div>
                </div>

                <div class='section-title'>I. Expedientes por Formalizar (En Revisión)</div>
                <table>
                    <thead>
                        <tr>
                            <th width='15%'>Fecha Solicitud</th>
                            <th width='25%'>Participante</th>
                            <th width='12%'>Cédula</th>
                            <th width='15%'>Teléfono</th> <th width='23%'>Diplomado</th>
                            <th width='10%'>Estatus Pago</th>
                        </tr>
                    </thead>
                    <tbody>";

            if (empty($listRevision)) {
                $html .= "<tr><td colspan='5' style='padding: 20px; color: #999;'>No hay expedientes en estatus de REVISIÓN actualmente.</td></tr>";
            } else {
                foreach ($listRevision as $r) {
                    $html .= "
                        <tr>
                            <td>{$r['fecha_solicitud']}</td>
                            <td class='text-left'>".mb_strtoupper($r['participante'], 'UTF-8')."</td>
                            <td>{$r['cedula']}</td>
                            <td>".($r['telefono'] ?? 'S/T')."</td> <td class='text-left'>{$r['diplomado']}</td>
                            <td class='status-pago'>".($r['payment_status'] ?? 'PENDIENTE')."</td>
                        </tr>";

                }
            }

            $html .= "</tbody></table>

                <div style='margin-top: 30px;'></div>

                <div class='section-title'>II. Expedientes Pendientes (En Compromiso)</div>
                <table>
                    <thead>
                        <tr>
                            <th width='15%'>Fecha Solicitud</th>
                            <th width='30%'>Participante</th>
                            <th width='12%'>Cédula</th>
                            <th width='28%'>Diplomado</th>
                            <th width='15%'>Pago Financiero</th>
                        </tr>
                    </thead>
                    <tbody>";

            if (empty($listCompromiso)) {
                $html .= "<tr><td colspan='5' style='padding: 20px; color: #999;'>No hay expedientes bajo COMPROMISO actualmente.</td></tr>";
            } else {
                foreach ($listCompromiso as $c) {
                    $html .= "
                    <tr>
                        <td>{$c['fecha_solicitud']}</td>
                        <td class='text-left'>".mb_strtoupper($c['participante'], 'UTF-8')."</td>
                        <td>{$c['cedula']}</td>
                        <td class='text-left'>{$c['diplomado']}</td>
                        <td class='status-pago'>".($c['payment_status'] ?? 'PENDIENTE')."</td>
                    </tr>";
                }
            }

            $html .= "
                    </tbody>
                </table>
            </body>
            </html>";

            // 5. Generar PDF
            $dompdf->loadHtml($html);
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->render();

            $dompdf->stream("Auditoria_Documentos_".date('dmY').".pdf", ["Attachment" => false]);
            exit;

        } catch (Throwable $e) {
            die("Error generando reporte: " . $e->getMessage());
        }
    }
}