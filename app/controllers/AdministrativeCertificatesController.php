<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / CONSTANCIAS
 * ARCHIVO: app/controllers/AdministrativeCertificatesController.php
 * PROPÓSITO: Controlador maestro para generación de folios, persistencia oficial en servidor y notificación vía Magic Link (Protección SMTP).
 * VERSIÓN: 7.8.0 - Fidelidad original de PDF, blindaje de búfer, bypass de QR y eliminación de adjuntos en email para evitar bloqueos.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdministrativeCertificatesModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use Exception;
use Throwable;

final class AdministrativeCertificatesController extends Controller
{
    private AdministrativeCertificatesModel $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $this->model = new AdministrativeCertificatesModel();

        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            if ($this->isAjaxRequest()) {
                $this->jsonFinal(['ok' => false, 'message' => 'Sesión expirada'], 401);
            }
            header('Location: /diplomatic/public/login');
            exit();
        }
    }

    /**
     * DETECTOR DINÁMICO DE RUTA: URL base para el validador externo independiente.
     */
    private function getFullUrlVerification(string $code): string {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $baseDir = str_replace('/index.php', '', $scriptName);
        return "{$protocol}://{$host}{$baseDir}/verificar.php?code={$code}";
    }

    public function index(): void {
        $this->view('administrative/certificates/index', ['title' => 'Gestión de Constancias']);
    }

    public function search(): void {
    $term   = trim($_GET['term'] ?? '');
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 25;
    $offset = ($page - 1) * $limit;

    $data  = $this->model->searchStudentsPaged($term, $limit, $offset);
    $total = $this->model->countStudents($term);

    $this->jsonFinal([
        'ok'    => true,
        'data'  => $data,
        'total' => $total,
        'pages' => max(1, (int)ceil($total / $limit)),
        'page'  => $page
    ]);
}

    public function getStudentPrograms(): void {
        $userId = (int)($_GET['user_id'] ?? 0);
        try {
            $data = $this->model->getStudentPrograms($userId);
            $this->jsonFinal(['ok' => true, 'data' => $data]);
        } catch (Throwable $e) {
            $this->jsonFinal(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * VISTA PREVIA (MODO VOLÁTIL PRE-)
     */
    public function generate(): void {
        while (ob_get_level() > 0) ob_end_clean();
        
        try {
            $userId = (int)($_GET['student_id'] ?? 0);
            $offeringId = (int)($_GET['offering_id'] ?? 0);
            $type = strtoupper($_GET['type'] ?? 'ESTUDIOS');

            $data = $this->model->getFullDataForCert($userId, $offeringId);
            if (!$data) throw new Exception("Expediente académico no encontrado.");

            $codigoPreview = 'PRE-' . strtoupper(substr(md5(uniqid()), 0, 8));
            $urlPreview = $this->getFullUrlVerification($codigoPreview);

            $this->streamPdfResponse($type, $data, $codigoPreview, $urlPreview, false, $offeringId);
        } catch (Throwable $e) { die("Error en previsualización: " . $e->getMessage()); }
    }

    /**
     * DESCARGA OFICIAL (REGISTRO CRT- EN BD Y DESCARGA)
     */
    public function finalizeAndDownload(): void {
        try {
            $userId = (int)($_GET['student_id'] ?? 0);
            $offeringId = (int)($_GET['offering_id'] ?? 0);
            $type = strtoupper($_GET['type'] ?? 'ESTUDIOS');

            $certInfo = $this->issueNewCertificateRecord($userId, $offeringId, $type);
            $data = $this->model->getFullDataForCert($userId, $offeringId);

            $this->streamPdfResponse($type, $data, $certInfo['code'], $certInfo['qr_url'], true, $offeringId);
        } catch (Throwable $e) { die("Error al procesar emisión oficial: " . $e->getMessage()); }
    }

    /**
     * ENVÍO DE NOTIFICACIÓN POR EMAIL (SIN ADJUNTOS - PROTECCIÓN SMTP)
     * Persiste el archivo en la ruta oficial y envía el Magic Link.
     */
    public function sendEmail(): void {
        while (ob_get_level() > 0) ob_end_clean();
        
        try {
            $userId = (int)($_POST['student_id'] ?? 0);
            $offeringId = (int)($_POST['offering_id'] ?? 0);
            $type = strtoupper($_POST['type'] ?? 'ESTUDIOS');

            // 1. Registro oficial y captura de datos
            $certInfo = $this->issueNewCertificateRecord($userId, $offeringId, $type);
            $data = $this->model->getFullDataForCert($userId, $offeringId);
            $smtp = $this->model->getSmtpSettings();

            if (!$smtp) throw new Exception("Configuración SMTP no disponible.");

            // 2. Generar PDF y Guardar en la ruta PERMANENTE del servidor
            $qrBase64 = $this->generateQrBase64($certInfo['qr_url']);
            require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
            $dompdf = new Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'Times-Roman']);
            $dompdf->loadHtml($this->loadTemplate($type, $data, $qrBase64, $certInfo['code']), 'UTF-8');
            $dompdf->render();
            
            $uploadPath = dirname(__DIR__, 2) . "/public/uploads/constancias/{$userId}/";
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
            $fileName = "Constancia_" . $certInfo['code'] . ".pdf";
            $fullFilePath = $uploadPath . $fileName;
            file_put_contents($fullFilePath, $dompdf->output());

            // 3. Construcción del Magic Link para el correo
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $publicDownloadUrl = "{$protocol}://{$_SERVER['HTTP_HOST']}/diplomatic/public/uploads/constancias/{$userId}/{$fileName}";

            // 4. Envío vía PHPMailer (Notificación HTML Profesional)
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
            $mail->Subject = "Constancia Digital Oficial - Folio " . $certInfo['code'];
            
            // BODY PREMIUM SIN ADJUNTOS
            $mail->Body = "
            <div style='background-color: #f4f7f9; padding: 40px 20px; font-family: sans-serif; text-align: center;'>
                <table align='center' border='0' cellpadding='0' cellspacing='0' style='max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; border: 1px solid #dee5ed; overflow: hidden; text-align: left;'>
                    <tr>
                        <td style='background-color: #003366; padding: 30px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0; font-size: 18px; text-transform: uppercase;'>Plataforma Diplomados UCLA</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 40px 30px;'>
                            <p style='color: #333; font-size: 16px;'>Estimado(a) <strong>$nombres</strong>,</p>
                            <p style='color: #555; font-size: 15px; line-height: 1.6;'>
                                Se ha generado satisfactoriamente su <strong>$docType</strong> oficial. El documento cuenta con el folio único <strong>{$certInfo['code']}</strong> y está disponible para su descarga segura:
                            </p>
                            <div style='margin: 35px 0; text-align: center;'>
                                <a href='$publicDownloadUrl' target='_blank' style='background-color: #0056b3; color: #ffffff; padding: 18px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 14px; display: inline-block; box-shadow: 0 4px 10px rgba(0,86,179,0.3);'>
                                    DESCARGAR DOCUMENTO OFICIAL
                                </a>
                            </div>
                            <p style='color: #888; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px;'>
                                Si el botón no funciona, copie y pegue esta dirección en su navegador:<br>
                                <span style='color: #0056b3;'>$publicDownloadUrl</span>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>";

            if ($mail->send()) {
                $this->jsonFinal(['ok' => true, 'message' => "Folio {$certInfo['code']} enviado vía link a " . $data['email']]);
            }

        } catch (Throwable $e) { $this->jsonFinal(['ok' => false, 'message' => $e->getMessage()], 400); }
    }

    private function issueNewCertificateRecord(int $userId, int $offeringId, string $type): array {
        $codigo = 'CRT-' . date('Y') . '-' . strtoupper(substr(md5(uniqid((string)rand(), true)), 0, 8));
        $url = $this->getFullUrlVerification($codigo);
        $payload = ['user_id' => $userId, 'offering_id' => $offeringId, 'type' => $type, 'code' => $codigo, 'qr_url' => $url];
        if ($this->model->registerCertificate($payload)) return ['code' => $codigo, 'qr_url' => $url];
        throw new Exception("Error al registrar el folio.");
    }

    private function streamPdfResponse(string $type, array $data, string $code, string $url, bool $download, int $offeringId = 0): void {
        while (ob_get_level() > 0) ob_end_clean();
        $qrBase64 = $this->generateQrBase64($url);
        require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Times-Roman');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->loadHtml($this->loadTemplate($type, $data, $qrBase64, $code, $offeringId), 'UTF-8');
        $dompdf->render();

        $dompdf->stream("Constancia_" . $code . ".pdf", ["Attachment" => $download]);
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

    private function loadTemplate(string $type, array $d, string $qr, string $code, int $offeringId = 0): string {
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

        if (strtoupper($type) === 'INSCRIPCION') {
            $htmlContent = $this->templateInscripcion($d, $header);
        } elseif (strtoupper($type) === 'ESTUDIOS_HORARIO') {
            $horario     = $this->model->getHorarioForCert($offeringId);
            $htmlContent = $this->templateEstudiosConHorario($d, $header, $horario);
        } else {
            $htmlContent = $this->templateEstudios($d, $header);
        }
        return str_replace('</body>', $qrHtml . '</body>', $htmlContent);
    }

    private function templateEstudios(array $d, string $header): string {
        $nombres = mb_strtoupper($d['last_name'] . ", " . $d['first_name']);
        $lapso = mb_strtoupper($this->getMesEspanol((int)date('m', strtotime($d['cohorte_inicio']))) . " " . date('Y', strtotime($d['cohorte_inicio'])) . " – " . $this->getMesEspanol((int)date('m', strtotime($d['cohorte_fin']))) . " " . date('Y', strtotime($d['cohorte_fin'])));
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


    private function templateEstudiosConHorario(array $d, string $header, array $horario): string {
    $nombres = mb_strtoupper($d['last_name'] . ", " . $d['first_name']);
    $lapso   = mb_strtoupper(
        $this->getMesEspanol((int)date('m', strtotime($d['cohorte_inicio']))) . " " .
        date('Y', strtotime($d['cohorte_inicio'])) . " – " .
        $this->getMesEspanol((int)date('m', strtotime($d['cohorte_fin']))) . " " .
        date('Y', strtotime($d['cohorte_fin']))
    );

    // Tabla teóricas
    $filasTeoricas = '';
    if (!empty($horario['teoricas'])) {
        foreach ($horario['teoricas'] as $t) {
            $hi = date('h:i A', strtotime($t['hora_inicio']));
            $hf = date('h:i A', strtotime($t['hora_fin']));
            $filasTeoricas .= "<tr><td style='padding:6px 12px;border:0.5px solid #ccc;text-align:center'>{$t['dia_semana']} — {$hi} a {$hf}</td></tr>";
        }
    } else {
        $filasTeoricas = "<tr><td style='padding:6px 12px;text-align:center;color:#888'>Sin horario teórico registrado</td></tr>";
    }

    // Tabla prácticas
    $filasPracticas = '';
    if (!empty($horario['practicas'])) {
        // Agrupar fechas por centro médico
        $porCentro = [];
        foreach ($horario['practicas'] as $p) {
            $centro = $p['centro_medico'] ?? 'Sin centro';
            $ts     = strtotime($p['fecha']);
            $dia    = date('d', $ts);
            $mes    = $this->getMesEspanol((int)date('m', $ts));
            $anio   = date('Y', $ts);
            $porCentro[$centro][] = "{$dia} de {$mes} de {$anio}";
        }
        foreach ($porCentro as $centro => $fechas) {
            $filasPracticas .= "<tr><td style='padding:6px 12px;border:0.5px solid #ccc;text-align:center'>" . implode(', ', $fechas) . "</td></tr>";
        }
    } else {
        $filasPracticas = "<tr><td style='padding:6px 12px;text-align:center;color:#888'>Sin fechas de práctica registradas</td></tr>";
    }

    $fechaHoy = date('d') . ' de ' . $this->getMesEspanol((int)date('m')) . ' de ' . date('Y');

    return "<html><body style='font-family:Times-Roman;font-size:12pt;text-align:justify;padding:2cm'>
        {$header}
        <div style='text-align:center;font-weight:bold;font-size:14pt;text-decoration:underline;margin:40px 0 20px'>CONSTANCIA DE ESTUDIO</div>
        <p>Quien suscribe, <strong>Dr. Rafael Alejandro Camejo Giménez</strong>, titular de la Cédula de Identidad <strong>N° v-14.399.195</strong>, en mi carácter de Coordinador General, hago constar que el (la) participante: <strong>{$nombres}</strong>; titular de la cédula de identidad: <strong>{$d['document_id']}</strong>, actualmente cursa el Diplomado: <strong>" . mb_strtoupper($d['diplomado_name']) . "</strong>, avalado por la Universidad Centroccidental &ldquo;Lisandro Alvarado&rdquo;, con una duración de <strong>{$d['total_hours']} horas académicas</strong>, en el lapso <strong>{$lapso}</strong>.</p>

        <table style='width:100%;border-collapse:collapse;margin-top:20px'>
            <thead>
                <tr><th style='background:#f0f0f0;padding:8px 12px;border:0.5px solid #ccc;text-align:center;font-size:11pt'>Clases Teóricas</th></tr>
            </thead>
            <tbody>{$filasTeoricas}</tbody>
        </table>

        <table style='width:100%;border-collapse:collapse;margin-top:10px'>
            <thead>
                <tr><th style='background:#f0f0f0;padding:8px 12px;border:0.5px solid #ccc;text-align:center;font-size:11pt'>PRÁCTICAS</th></tr>
            </thead>
            <tbody>{$filasPracticas}</tbody>
        </table>

        <p style='margin-top:30px'>En Barquisimeto, a los {$fechaHoy}.</p>
        <div style='margin-top:80px;line-height:1.2;text-align:center'>
            <strong>Dr. Rafael Camejo<br>Coordinador General</strong>
        </div>
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

    private function isAjaxRequest(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }
}