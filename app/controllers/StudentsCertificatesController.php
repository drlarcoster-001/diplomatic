<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / CONSTANCIAS
 * ARCHIVO: app/controllers/StudentsCertificatesController.php
 * PROPÓSITO: Controlador maestro para generación de folios institucionales, persistencia en servidor y notificación vía link.
 * VERSIÓN: 2.5.0 - Fidelidad total UCLA: Restauración de plantillas originales, eliminación de adjuntos y persistencia jerárquica.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\StudentCertificatesModel;
use App\Core\Database;
use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PDO;
use Exception;
use Throwable;

final class StudentsCertificatesController extends Controller
{
    private StudentCertificatesModel $model;
    private int $currentStudentId;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user']['id'])) {
            header('Location: /diplomatic/public/login');
            exit();
        }

        $this->model = new StudentCertificatesModel();
        $userId = (int)$_SESSION['user']['id'];
        $this->currentStudentId = (int)$this->model->getStudentIdByUserId($userId);
    }

    public function index(): void {
    // Si no tiene inscripción APROBADA (currentStudentId === 0)
    if ($this->currentStudentId === 0) {
        // REDIRECCIÓN CORREGIDA: Apuntamos a /students, no a /dashboard
        header('Location: /diplomatic/public/students?alert=no_active_enrollment');
        exit();
    }
    $this->view('students/certificates/index', ['title' => 'Mis Certificados']);
}

    public function getPrograms(): void {
        $this->jsonFinal($this->model->getStudentPrograms($this->currentStudentId));
    }

    private function getFullUrlVerification(string $code): string {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $baseDir = str_replace('/index.php', '', $scriptName);
        return "{$protocol}://{$host}{$baseDir}/verificar.php?code={$code}";
    }

    /**
     * VISTA PREVIA (FOLIO VOLÁTIL PRE-)
     */
    public function generate(): void {
        while (ob_get_level() > 0) ob_end_clean();
        try {
            if ($this->currentStudentId === 0) throw new Exception("Perfil no válido.");
            $offeringId = (int)($_GET['offering_id'] ?? 0);
            $type = strtoupper($_GET['type'] ?? 'ESTUDIOS');

            $data = $this->fetchStudentMasterData($offeringId);
            if (!$data) throw new Exception("Error al cargar datos académicos.");

            $codigo = 'PRE-' . strtoupper(substr(md5(uniqid()), 0, 8));
            $url = $this->getFullUrlVerification($codigo);

            $this->streamPdfResponse($type, $data, $codigo, $url, false);
        } catch (Throwable $e) { die("Error en vista previa: " . $e->getMessage()); }
    }

    /**
     * DESCARGA OFICIAL (FOLIO CRT-)
     */
    public function finalizeAndDownload(): void {
        try {
            $offeringId = (int)($_GET['offering_id'] ?? 0);
            $type = strtoupper($_GET['type'] ?? 'ESTUDIOS');

            $certInfo = $this->issueNewCertificateRecord($offeringId, $type);
            $data = $this->fetchStudentMasterData($offeringId);

            $this->streamPdfResponse($type, $data, $certInfo['code'], $certInfo['qr_url'], true);
        } catch (Throwable $e) { die($e->getMessage()); }
    }

    /**
     * ENVÍO POR CORREO (NOTIFICACIÓN + LINK SEGURO)
     */
    public function sendEmail(): void {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            if ($this->currentStudentId === 0) throw new Exception("No autorizado.");
            $offeringId = (int)($_POST['offering_id'] ?? 0);
            $type = strtoupper($_POST['type'] ?? 'ESTUDIOS');

            $certInfo = $this->issueNewCertificateRecord($offeringId, $type);
            $data = $this->fetchStudentMasterData($offeringId);
            $smtp = $this->model->getSmtpSettings();

            if (!$data || !$smtp) throw new Exception("Configuración insuficiente.");

            // 1. Generar PDF y guardar en carpeta oficial del alumno
            $qrBase64 = $this->generateQrBase64($certInfo['qr_url']);
            require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
            $dompdf = new Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'Times-Roman']);
            $dompdf->loadHtml($this->loadTemplate($type, $data, $qrBase64, $certInfo['code']), 'UTF-8');
            $dompdf->render();
            
            $uploadPath = dirname(__DIR__, 2) . "/public/uploads/constancias/{$this->currentStudentId}/";
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
            $fileName = "Certificado_" . $certInfo['code'] . ".pdf";
            file_put_contents($uploadPath . $fileName, $dompdf->output());

            // 2. URL Pública para el botón del correo
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $publicUrl = "{$protocol}://{$_SERVER['HTTP_HOST']}/diplomatic/public/uploads/constancias/{$this->currentStudentId}/{$fileName}";

            // 3. Enviar Correo Profesional (Sin adjuntos)
            require_once dirname(__DIR__, 2) . '/tools/phpmailer/PHPMailer.php';
            require_once dirname(__DIR__, 2) . '/tools/phpmailer/SMTP.php';
            require_once dirname(__DIR__, 2) . '/tools/phpmailer/Exception.php';

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['smtp_user'];
            $mail->Password   = $smtp['smtp_password'];
            $mail->Port       = (int)$smtp['smtp_port'];
            $mail->CharSet    = 'UTF-8';
            if (strtoupper($smtp['smtp_security'] ?? '') === 'SSL') $mail->SMTPSecure = 'ssl';

            $nombres = htmlspecialchars($data['first_name'] . ' ' . $data['last_name']);
            $docType = ($type === 'INSCRIPCION') ? 'Planilla de Inscripción' : 'Constancia de Estudios';
            
            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($data['email'], $nombres);
            $mail->isHTML(true);
            $mail->Subject = "Documento Oficial Validado: $docType";

            $mail->Body = "
            <div style='background-color: #f4f7f9; padding: 40px 10px; font-family: sans-serif;'>
                <table align='center' border='0' cellpadding='0' cellspacing='0' style='max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; border: 1px solid #dee5ed; overflow: hidden;'>
                    <tr>
                        <td style='background-color: #003366; padding: 30px; text-align: center;'>
                            <h1 style='color: #ffffff; margin: 0; font-size: 20px; text-transform: uppercase;'>Plataforma Diplomados UCLA</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 40px 30px;'>
                            <p style='color: #2d3748; font-size: 16px;'>Estimado(a) <strong>$nombres</strong>,</p>
                            <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>
                                Se ha generado su <strong>$docType</strong> con el folio único <strong>{$certInfo['code']}</strong>. 
                                El documento ha sido validado digitalmente y puede descargarlo en el siguiente botón:
                            </p>
                            <div style='margin: 40px 0; text-align: center;'>
                                <a href='$publicUrl' target='_blank' style='background-color: #0056b3; color: #ffffff; padding: 18px 35px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 15px; display: inline-block; box-shadow: 0 4px 10px rgba(0,86,179,0.3);'>
                                    DESCARGAR DOCUMENTO
                                </a>
                            </div>
                            <p style='color: #718096; font-size: 12px; border-top: 1px solid #edf2f7; padding-top: 25px;'>
                                Si el botón no funciona, copie y pegue este link:<br> $publicUrl
                            </p>
                        </td>
                    </tr>
                </table>
            </div>";

            if ($mail->send()) {
                $this->jsonFinal(['ok' => true, 'message' => "La notificación ha sido enviada con éxito."]);
            }
        } catch (Throwable $e) { $this->jsonFinal(['ok' => false, 'message' => $e->getMessage()], 400); }
    }

    private function issueNewCertificateRecord(int $offeringId, string $type): array {
        $codigo = 'CRT-' . date('Y') . '-' . strtoupper(substr(md5(uniqid((string)rand(), true)), 0, 8));
        $url = $this->getFullUrlVerification($codigo);
        $payload = ['student_id' => $this->currentStudentId, 'offering_id' => $offeringId, 'type' => $type, 'code' => $codigo, 'qr_url' => $url];
        if ($this->model->registerCertificate($payload)) return ['code' => $codigo, 'qr_url' => $url];
        throw new Exception("Error al registrar folio.");
    }

    private function fetchStudentMasterData(int $offeringId): ?array {
        $db = (new Database())->getConnection();
        $sql = "SELECT u.*, d.name as diplomado_name, d.total_hours, c.start_date as lapso_inicio, c.end_date as lapso_fin, o.class_start, o.general_modality 
                FROM tbl_students s
                INNER JOIN tbl_users u ON s.user_id = u.id
                INNER JOIN tbl_academic_offerings o ON o.id = ?
                INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                INNER JOIN tbl_cohortes c ON o.cohort_id = c.id
                WHERE s.id = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$offeringId, $this->currentStudentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function streamPdfResponse(string $type, array $data, string $code, string $url, bool $download): void {
        while (ob_get_level() > 0) ob_end_clean();
        $qrBase64 = $this->generateQrBase64($url);
        require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
        $dompdf = new Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'Times-Roman']);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->loadHtml($this->loadTemplate($type, $data, $qrBase64, $code), 'UTF-8');
        $dompdf->render();
        $dompdf->stream("Documento_" . $code . ".pdf", ["Attachment" => $download]);
        exit;
    }

    private function generateQrBase64(string $url): string {
        require_once dirname(__DIR__, 2) . '/tools/phpqrcode/qrlib.php';
        $tempDir = dirname(__DIR__, 2) . '/public/assets/temp/';
        if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
        $filename = $tempDir . 'qr_' . uniqid() . '.png';
        \QRcode::png($url, $filename, 'L', 4, 2);
        $data = file_get_contents($filename);
        unlink($filename);
        return 'data:image/png;base64,' . base64_encode($data);
    }

    /**
     * CARGADOR DE PLANTILLAS (Fidelidad Master)
     */
    private function loadTemplate(string $type, array $d, string $qr, string $code): string {
        $pathUcla = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
        $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
        $imgUcla = file_exists($pathUcla) ? 'data:image/png;base64,' . base64_encode(file_get_contents($pathUcla)) : '';
        $imgMedicina = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

        $header = "
        <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; table-layout: fixed;'>
            <tr>
                <td style='width: 15%; text-align: left;'><img src='{$imgUcla}' style='width: 70px;'></td>
                <td style='width: 70%; text-align: center; font-weight: bold; font-size: 11pt; line-height: 1.2;'>
                    UNIVERSIDAD CENTROCCIDENTAL “LISANDRO ALVARADO”<br>
                    DECANATO DE CIENCIAS DE LA SALUD<br>
                    “Dr. PABLO ACOSTA ORTIZ” <br>
                    COORDINACIÓN DE EXTENSIÓN
                </td>
                <td style='width: 15%; text-align: right;'><img src='{$imgMedicina}' style='width: 70px;'></td>
            </tr>
        </table>";

        $qrHtml = "
        <div style='position: fixed; bottom: 0; right: 0; text-align: center; width: 190px;'>
            <img src='{$qr}' style='width: 90px; height: 90px; border: 1px solid #eee;'>
            <div style='font-size: 6.5pt; color: #555; margin-top: 5px; line-height: 1.1;'>
                Verificar Folio en:<br><b>{$_SERVER['HTTP_HOST']}</b><br>Folio: <b>{$code}</b>
            </div>
        </div>";

        $htmlContent = (strtoupper($type) === 'INSCRIPCION') ? $this->templateInscripcion($d, $header) : $this->templateEstudios($d, $header);
        return str_replace('</body>', $qrHtml . '</body>', $htmlContent);
    }

    private function templateEstudios(array $d, string $header): string {
        $nombres = mb_strtoupper($d['last_name'] . ", " . $d['first_name']);
        $lapso = mb_strtoupper($this->getMesEspanol((int)date('m', strtotime($d['lapso_inicio']))) . " " . date('Y', strtotime($d['lapso_inicio'])) . " – " . $this->getMesEspanol((int)date('m', strtotime($d['lapso_fin']))) . " " . date('Y', strtotime($d['lapso_fin'])));
        $inicio = date('d', strtotime($d['class_start'])) . " de " . $this->getMesEspanol((int)date('m', strtotime($d['class_start']))) . " de " . date('Y', strtotime($d['class_start']));

        return "<html><body style='font-family: Times-Roman; font-size: 12pt; text-align: justify; padding: 2cm;'>
                $header
                <div style='text-align: center; font-weight: bold; font-size: 14pt; text-decoration: underline; margin: 40px 0;'>CONSTANCIA DE ESTUDIO</div>
                <p>Quien suscribe, <strong>Dr. Rafael Alejandro Camejo Giménez</strong>, titular de la Cédula de Identidad <strong>N° v-14.399.195</strong>, en mi carácter de Coordinador General, hago constar que el (la) participante: <strong>$nombres</strong>; titular de la cédula de identidad: <strong>{$d['document_id']}</strong>, actualmente cursa el Diplomado: <strong>" . mb_strtoupper($d['diplomado_name']) . "</strong>, avalado institucionalmente, con una duración de <strong>{$d['total_hours']} horas académicas</strong>, en el lapso <strong>$lapso</strong>.</p>
                <p>La modalidad del Diplomado es <strong>{$d['general_modality']}</strong>, con clases teóricas y actividades prácticas desarrolladas en el Hospital Central “Dr. Antonio María Pineda”.</p>
                <p>Inicio de actividades: <strong>$inicio</strong>.</p>
                <p style='margin-top: 50px;'>En Barquisimeto, a los " . date('d') . " días del mes de " . $this->getMesEspanol((int)date('m')) . " de " . date('Y') . ".</p>
                <div style='margin-top: 80px; line-height: 1.2; text-align:center;'><strong>DR. RAFAEL CAMEJO<br>COORDINADOR GENERAL</strong><br>Sello y Validación Digital</div>
                </body></html>";
    }

    private function templateInscripcion(array $d, string $header): string {
        $nom = mb_strtoupper($d['first_name'] . " " . $d['last_name']);
        $fechaHoy = date('d') . " de " . $this->getMesEspanol((int)date('m')) . " de " . date('Y');
        return "<html><head><style>body { font-family: Times-Roman; font-size: 11pt; padding: 0.5cm; }.center { text-align: center; font-weight: bold; line-height: 1.2; }.data-table { width: 100%; border-collapse: collapse; border: 1.5px solid black; margin-top: 10px; }.data-table td { border: 1px solid black; padding: 8px; vertical-align: top; }.label { font-size: 8.5pt; font-weight: bold; display: block; text-transform: uppercase; color: #444; }.val { font-size: 11pt; padding-top: 2px; font-weight: bold; }</style></head><body>
                $header
                <div class='center' style='font-size: 14pt; margin-top: 20px;'>PLANILLA DE INSCRIPCIÓN</div>
                <div class='center' style='text-decoration: underline; margin-bottom: 15px;'>" . mb_strtoupper($d['diplomado_name']) . "</div>
                <p style='text-align: justify;'>Se hace constar la inscripción formal del profesional:</p>
                <div class='center' style='margin-bottom: 10px; font-weight: bold; font-size: 12pt;'>" . mb_strtoupper($d['undergraduate_degree'] ?? 'PROFESIONAL UNIVERSITARIO') . "</div>
                <table class='data-table'>
                    <tr><td colspan='2'><span class='label'>Nombres y Apellidos:</span><div class='val'>$nom</div></td></tr>
                    <tr><td width='45%'><span class='label'>C.I. Nº:</span><div class='val'>{$d['document_id']}</div></td><td width='55%'><span class='label'>Correo Electrónico:</span><div class='val'>{$d['email']}</div></td></tr>
                    <tr><td><span class='label'>Dirección de Habitación:</span><div class='val'>" . mb_strtoupper($d['address'] ?? 'SIN REGISTRAR') . "</div></td><td><span class='label'>Teléfono de Contacto:</span><div class='val'>" . ($d['phone'] ?? 'SIN REGISTRAR') . "</div></td></tr>
                </table>
                <div style='margin-top: 20px; font-weight: bold; font-size: 10pt;'>REQUISITOS CONSIGNADOS:</div>
                <table class='data-table' style='width: 100%;'>
                    <tr><td style='width: 85%;'>Copia de Título Universitario / Carta de Culminación</td><td style='text-align: center; font-weight: bold;'>[ SI ]</td></tr>
                    <tr><td>Copia de Cédula de Identidad</td><td style='text-align: center; font-weight: bold;'>[ SI ]</td></tr>
                    <tr><td>Comprobante de Pago de Inscripción (Caja)</td><td style='text-align: center; font-weight: bold;'>[ SI ]</td></tr>
                </table>
                <div style='margin-top: 20px; font-weight: bold; font-size: 10pt;'>OBSERVACIONES:</div>
                <div style='border: 1px solid black; height: 50px; width: 100%; padding: 8px; font-size: 9pt; color: #444; font-style: italic;'>Inscripción formalizada satisfactoriamente.</div>
                <p style='text-align: left; margin-top: 25px;'>Generado en la ciudad de Barquisimeto, el $fechaHoy</p>
                <table style='width: 100%; border-collapse: collapse; margin-top: 40px;'>
                    <tr><td width='45%' style='border-top: 1.5px solid black; text-align: center; padding-top: 10px;'><div style='font-weight: bold;'>DR. RAFAEL CAMEJO</div><div style='font-size: 9pt;'>COORDINADOR GENERAL</div></td><td width='10%'></td><td width='45%' style='border-top: 1.5px solid black; text-align: center; padding-top: 10px;'><div style='font-weight: bold;'>$nom</div><div style='font-size: 9pt;'>FIRMA DEL PARTICIPANTE</div></td></tr>
                </table>
                <div style='text-align: center; font-size: 8pt; font-weight: bold; margin-top: 40px; color: #888;'>VALIDACIÓN DIGITAL - SELLO ELECTRÓNICO INSTITUCIONAL</div>
                </body></html>";
    }

    private function getMesEspanol(int $mes): string {
        $meses = [1=>"enero",2=>"febrero",3=>"marzo",4=>"abril",5=>"mayo",6=>"junio",7=>"julio",8=>"agosto",9=>"septiembre",10=>"octubre",11=>"noviembre",12=>"diciembre"];
        return $meses[$mes] ?? "";
    }

    private function jsonFinal(array $payload, int $code = 200): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}