<?php
/**
 * MÓDULO: RECURSOS HUMANOS / PROCESAR SESIONES
 * ARCHIVO: app/controllers/ResourcesProcesarSesionesController.php
 * PROPÓSITO: Procesamiento de sesiones programadas → DICTADAS con registro de
 *            asistencia. PDF generado con DomPDF igual que constancias.
 * VERSIÓN: 2.0.0 - DomPDF integrado, HTML del PDF construido en el controlador.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ResourcesProcesarSesionesController;
 *   $router->get('/resources/procesar-sesiones',            [ResourcesProcesarSesionesController::class, 'index']);
 *   $router->get('/resources/procesar-sesiones/manage',     [ResourcesProcesarSesionesController::class, 'manage']);
 *   $router->get('/resources/procesar-sesiones/asistencia', [ResourcesProcesarSesionesController::class, 'asistencia']);
 *   $router->post('/resources/procesar-sesiones/procesar',  [ResourcesProcesarSesionesController::class, 'procesar']);
 *   $router->get('/resources/procesar-sesiones/pdf',        [ResourcesProcesarSesionesController::class, 'pdf']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ResourcesProcesarSesionesModel;
use App\Services\AuditService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class ResourcesProcesarSesionesController extends Controller
{
    private ResourcesProcesarSesionesModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new ResourcesProcesarSesionesModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $search     = trim($_GET['search'] ?? '');
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $perPage    = 25;
        $total      = $this->model->countOfertas($search);
        $totalPages = (int) ceil($total / $perPage);
        $ofertas    = $this->model->getOfertasConSesiones($search, $page, $perPage);

        $this->view('resources/procesar_sesiones/index', [
            'ofertas'    => $ofertas,
            'search'     => $search,
            'page'       => $page,
            'total'      => $total,
            'totalPages' => $totalPages,
            'perPage'    => $perPage,
        ]);
    }

    // =========================================================================
    // MANAGE
    // =========================================================================

    public function manage(): void
    {
        $offeringId = (int) ($_GET['offering_id'] ?? 0);
        $oferta     = $offeringId ? $this->model->getOferta($offeringId) : null;

        if (!$oferta) {
            header('Location: /diplomatic/public/resources/procesar-sesiones?error=notfound');
            exit;
        }

        $sesiones = $this->model->getSesionesByOffering($offeringId);

        $this->view('resources/procesar_sesiones/manage', [
            'oferta'     => $oferta,
            'offeringId' => $offeringId,
            'sesiones'   => $sesiones,
        ]);
    }

    // =========================================================================
    // ASISTENCIA (AJAX)
    // =========================================================================

    public function asistencia(): void
    {
        try {
            $sesionId   = (int) ($_GET['sesion_id']   ?? 0);
            $offeringId = (int) ($_GET['offering_id'] ?? 0);
            $sesion     = $sesionId ? $this->model->getSesionById($sesionId) : null;

            if (!$sesion) {
                $this->jsonFinal(['success' => false, 'message' => 'Sesión no encontrada.'], 404);
                return;
            }

            $estudiantes = $this->model->getEstudiantesConAsistencia($sesionId, $offeringId);
            $this->jsonFinal(['success' => true, 'sesion' => $sesion, 'estudiantes' => $estudiantes]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PROCESAR (AJAX)
    // =========================================================================

    public function procesar(): void
    {
        try {
            $sesionId   = (int) ($_POST['sesion_id']   ?? 0);
            $offeringId = (int) ($_POST['offering_id'] ?? 0);
            $sesion     = $sesionId ? $this->model->getSesionById($sesionId) : null;

            if (!$sesion) {
                $this->jsonFinal(['success' => false, 'message' => 'Sesión no encontrada.'], 404);
                return;
            }
            if ($sesion['estado'] === 'DICTADA') {
                $this->jsonFinal(['success' => false, 'message' => 'Esta sesión ya fue procesada.'], 422);
                return;
            }

            $asistenciaRaw = $_POST['asistencia'] ?? [];
            $asistencia    = [];
            foreach ($asistenciaRaw as $eid => $val) {
                $asistencia[(int) $eid] = (int) $val;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->procesarSesion($sesionId, $asistencia, $userId);

            AuditService::log($userId, 'Sesiones', 'DICTADA',
                "Sesión {$sesionId} marcada DICTADA — " . count($asistencia) . " registros", $sesionId);

            $sesiones = $this->model->getSesionesByOffering($offeringId);
            $this->jsonFinal(['success' => true, 'message' => 'Sesión procesada correctamente.', 'sesiones' => $sesiones]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PDF CON DOMPDF
    // =========================================================================

    public function dictadas(): void
    {
        try {
            $offeringId = (int) ($_GET['offering_id'] ?? 0);
            $data = $this->model->getSesionesDictadasByOffering($offeringId);
            $this->jsonFinal(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function reversar(): void
    {
        try {
            $sesionId   = (int) ($_POST['sesion_id']   ?? 0);
            $offeringId = (int) ($_POST['offering_id'] ?? 0);
            $sesion     = $sesionId ? $this->model->getSesionById($sesionId) : null;

            if (!$sesion) {
                $this->jsonFinal(['success' => false, 'message' => 'Sesión no encontrada.'], 404);
                return;
            }
            if ($sesion['estado'] !== 'DICTADA') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden reversar sesiones DICTADAS.'], 422);
                return;
            }
            if ($this->model->estaEnNomina($sesionId)) {
                $this->jsonFinal(['success' => false, 'message' => 'Esta sesión ya está incluida en una nómina. Sácala de la nómina primero.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->reversarSesion($sesionId, $userId);

            AuditService::log($userId, 'Sesiones', 'REVERSAR',
                "Reversó sesión {$sesionId} de DICTADA a PROGRAMADA", $sesionId);

            $dictadas = $this->model->getSesionesDictadasByOffering($offeringId);
            $this->jsonFinal(['success' => true, 'message' => 'Sesión reversada a PROGRAMADA.', 'dictadas' => $dictadas]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function pdf(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $sesionId = (int) ($_GET['sesion_id'] ?? 0);
            $datos    = $sesionId ? $this->model->getDatosAsistenciaPdf($sesionId) : null;

            if (!$datos) {
                http_response_code(404);
                echo 'Sesión no encontrada.';
                return;
            }

            $html = $this->buildPdfHtml($datos['sesion'], $datos['estudiantes']);

            require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Times-Roman');

            $dompdf = new Dompdf($options);
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            $dompdf->stream('Asistencia_Sesion_' . $sesionId . '.pdf', ['Attachment' => false]);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Error al generar PDF: ' . $e->getMessage();
        }
    }

    // =========================================================================
    // CONSTRUCCIÓN DEL HTML DEL PDF
    // =========================================================================

    private function buildPdfHtml(array $s, array $estudiantes): string
    {
        $pathUcla     = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
        $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
        $imgUcla      = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
        $imgMedicina  = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

        $profesor    = htmlspecialchars(($s['last_name'] ?? '') . ', ' . ($s['first_name'] ?? ''));
        $diplomado   = htmlspecialchars($s['diplomado_nombre'] ?? '—');
        $cohorte     = htmlspecialchars($s['cohorte_nombre']  ?? '—');
        $horario     = htmlspecialchars($s['horario_desc']    ?? '—');
        $fecha       = isset($s['fecha']) ? date('d/m/Y', strtotime($s['fecha'])) : '—';
        $tipo        = $s['tipo_horario'] === 'TEORICO' ? 'Teórica' : 'Práctica';
        $totalEst    = count($estudiantes);
        $presentes   = count(array_filter($estudiantes, fn($e) => (int)$e['asistio'] === 1));
        $ausentes    = $totalEst - $presentes;
        $fechaHoy    = date('d') . ' de ' . $this->getMes((int)date('m')) . ' de ' . date('Y');

        $filas = '';
        foreach ($estudiantes as $idx => $e) {
            $nombre   = htmlspecialchars($e['last_name'] . ', ' . $e['first_name']);
            $cedula   = htmlspecialchars($e['document_id']);
            $asistio  = (int)$e['asistio'];
            $estadoTxt = $asistio ? '<span style="color:#085041;font-weight:bold">✓ Asistió</span>'
                                  : '<span style="color:#A32D2D;font-weight:bold">✗ Faltó</span>';
            $bg = $idx % 2 === 0 ? '#ffffff' : '#f8f9fa';
            $filas .= "<tr style='background:{$bg}'>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>" . ($idx + 1) . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6'>{$nombre}</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>{$cedula}</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>{$estadoTxt}</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6'>&nbsp;</td>
            </tr>";
        }

        return "<!DOCTYPE html>
<html lang='es'>
<head>
<meta charset='UTF-8'>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Times-Roman; font-size: 11pt; color: #212529; padding: 1.5cm; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #533AB7; color: #fff; padding: 7px 8px; font-size: 10pt; text-align: left; }
  .info-label { font-size: 8pt; font-weight: bold; text-transform: uppercase; color: #6c757d; }
  .info-val   { font-size: 11pt; font-weight: bold; margin-top: 2px; }
  .info-cell  { background: #f8f9fa; padding: 7px 10px; border: 0.5px solid #dee2e6; }
</style>
</head>
<body>

<!-- ENCABEZADO -->
<table style='margin-bottom:16px'>
  <tr>
    <td style='width:15%;text-align:left'>
      " . ($imgUcla ? "<img src='{$imgUcla}' style='width:65px'>" : '') . "
    </td>
    <td style='width:70%;text-align:center;font-weight:bold;font-size:10.5pt;line-height:1.3'>
      UNIVERSIDAD CENTROCCIDENTAL &ldquo;LISANDRO ALVARADO&rdquo;<br>
      DECANATO DE CIENCIAS DE LA SALUD<br>
      &ldquo;Dr. PABLO ACOSTA ORTIZ&rdquo;<br>
      COORDINACIÓN DE EXTENSIÓN
    </td>
    <td style='width:15%;text-align:right'>
      " . ($imgMedicina ? "<img src='{$imgMedicina}' style='width:65px'>" : '') . "
    </td>
  </tr>
</table>

<!-- TÍTULO -->
<div style='text-align:center;font-weight:bold;font-size:13pt;text-decoration:underline;margin:14px 0 12px'>
  FORMATO DE ASISTENCIA
</div>

<!-- DATOS DE LA SESIÓN -->
<table style='margin-bottom:12px'>
  <tr>
    <td style='width:50%;padding-right:6px'>
      <div class='info-cell'>
        <div class='info-label'>Diplomado</div>
        <div class='info-val'>{$diplomado}</div>
      </div>
    </td>
    <td style='width:50%;padding-left:6px'>
      <div class='info-cell'>
        <div class='info-label'>Cohorte</div>
        <div class='info-val'>{$cohorte}</div>
      </div>
    </td>
  </tr>
  <tr>
    <td style='padding-right:6px;padding-top:6px'>
      <div class='info-cell'>
        <div class='info-label'>Profesor / Personal</div>
        <div class='info-val'>{$profesor}</div>
      </div>
    </td>
    <td style='padding-left:6px;padding-top:6px'>
      <div class='info-cell'>
        <div class='info-label'>Fecha de Sesión</div>
        <div class='info-val'>{$fecha}</div>
      </div>
    </td>
  </tr>
  <tr>
    <td style='padding-right:6px;padding-top:6px'>
      <div class='info-cell'>
        <div class='info-label'>Horario / Grupo</div>
        <div class='info-val'>{$horario}</div>
      </div>
    </td>
    <td style='padding-left:6px;padding-top:6px'>
      <div class='info-cell'>
        <div class='info-label'>Tipo de Sesión</div>
        <div class='info-val'>{$tipo} &nbsp;·&nbsp; {$totalEst} estudiantes &nbsp;·&nbsp; {$presentes} presentes &nbsp;·&nbsp; {$ausentes} ausentes</div>
      </div>
    </td>
  </tr>
</table>

<!-- TABLA DE ESTUDIANTES -->
<table>
  <thead>
    <tr>
      <th style='width:6%;text-align:center'>#</th>
      <th style='width:38%'>Apellidos y Nombres</th>
      <th style='width:16%;text-align:center'>Cédula</th>
      <th style='width:16%;text-align:center'>Asistencia</th>
      <th style='width:24%'>Firma</th>
    </tr>
  </thead>
  <tbody>
    {$filas}
  </tbody>
</table>

<!-- FIRMAS -->
<table style='margin-top:50px'>
  <tr>
    <td style='width:40%;text-align:center;border-top:1px solid #333;padding-top:8px'>
      <div style='font-weight:bold;font-size:10pt'>{$profesor}</div>
      <div style='font-size:9pt;color:#555'>Profesor / Personal</div>
    </td>
    <td style='width:20%'></td>
    <td style='width:40%;text-align:center;border-top:1px solid #333;padding-top:8px'>
      <div style='font-weight:bold;font-size:10pt'>DR. RAFAEL CAMEJO</div>
      <div style='font-size:9pt;color:#555'>Coordinador General</div>
    </td>
  </tr>
</table>

<div style='text-align:center;font-size:8pt;color:#888;margin-top:24px'>
  Generado en Barquisimeto, el {$fechaHoy} &nbsp;·&nbsp; Sistema DIPLOMATIC &copy; UCLA
</div>

</body>
</html>";
    }

    private function getMes(int $m): string
    {
        return ['','enero','febrero','marzo','abril','mayo','junio',
                'julio','agosto','septiembre','octubre','noviembre','diciembre'][$m] ?? '';
    }

    // =========================================================================
    // JSON
    // =========================================================================

    private function jsonFinal(array $payload, int $code = 200): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        try { echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); }
        catch (Throwable $e) { echo json_encode(['success' => false, 'message' => 'Error JSON.']); }
        exit;
    }
}