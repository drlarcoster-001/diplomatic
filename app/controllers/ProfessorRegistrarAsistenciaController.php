<?php
/**
 * MÓDULO: PORTAL DOCENTE / REGISTRAR ASISTENCIA
 * ARCHIVO: app/controllers/ProfessorRegistrarAsistenciaController.php
 * PROPÓSITO: index() muestra sesiones PROGRAMADAS del profesor por oferta.
 *            sesion() carga el formulario de asistencia para una sesión.
 *            guardar() persiste la asistencia en tbl_sesion_asistencia vía AJAX.
 *            pdf() genera PDF con la asistencia marcada para imprimir.
 * VERSIÓN: 1.1.0 - Agrega método pdf() post-guardado.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ProfessorRegistrarAsistenciaController;
 *   $router->get('/professor/registrar-asistencia',          [ProfessorRegistrarAsistenciaController::class, 'index']);
 *   $router->get('/professor/registrar-asistencia/sesion',   [ProfessorRegistrarAsistenciaController::class, 'sesion']);
 *   $router->post('/professor/registrar-asistencia/guardar', [ProfessorRegistrarAsistenciaController::class, 'guardar']);
 *   $router->get('/professor/registrar-asistencia/pdf',      [ProfessorRegistrarAsistenciaController::class, 'pdf']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProfessorModel;
use App\Models\ProfessorRegistrarAsistenciaModel;
use App\Services\AuditService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class ProfessorRegistrarAsistenciaController extends Controller
{
    protected array $profesor;
    private ProfessorRegistrarAsistenciaModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'PROFESOR') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $profModel = new ProfessorModel();
        $profesor  = $profModel->getProfessorByUserId((int) $_SESSION['user']['id']);
        if (!$profesor) {
            header('Location: /diplomatic/public/dashboard?error=profesor_sin_expediente');
            exit;
        }
        $this->profesor = $profesor;
        $this->model    = new ProfessorRegistrarAsistenciaModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $professorId  = (int) $this->profesor['id'];
        $offeringId   = (int) ($_GET['offering_id'] ?? 0);
        $periodoId    = (int) ($_GET['periodo_id'] ?? 0);
        $periodos     = $this->model->getPeriodos($professorId);
        $ofertas      = $this->model->getMisOfertas($professorId, $periodoId);
        $sesiones     = [];
        $ofertaActiva = null;

        if ($offeringId) {
            foreach ($ofertas as $o) {
                if ((int) $o['offering_id'] === $offeringId) {
                    $ofertaActiva = $o;
                    break;
                }
            }
            if ($ofertaActiva) {
                $sesiones = $this->model->getMisSesiones($professorId, $offeringId);
            }
        } else {
            $sesiones = $this->model->getTodasSesiones($professorId, $periodoId);
        }

        $this->view('professor/registrar_asistencia/index', [
            'profesor'     => $this->profesor,
            'ofertas'      => $ofertas,
            'sesiones'     => $sesiones,
            'offeringId'   => $offeringId,
            'ofertaActiva' => $ofertaActiva,
            'periodos'     => $periodos,
            'periodoId'    => $periodoId,
        ]);
    }

    // =========================================================================
    // SESION
    // =========================================================================

    public function sesion(): void
    {
        $sesionId    = (int) ($_GET['sesion_id'] ?? 0);
        $professorId = (int) $this->profesor['id'];
        $sesion      = $sesionId ? $this->model->getSesion($sesionId, $professorId) : null;

        if (!$sesion) {
            header('Location: /diplomatic/public/professor/registrar-asistencia?error=notfound');
            exit;
        }

        $estudiantes = $this->model->getEstudiantes($sesionId, (int) $sesion['offering_id']);

        $this->view('professor/registrar_asistencia/sesion', [
            'profesor'    => $this->profesor,
            'sesion'      => $sesion,
            'estudiantes' => $estudiantes,
        ]);
    }

    // =========================================================================
    // GUARDAR — AJAX
    // =========================================================================

    public function guardar(): void
    {
        try {
            $sesionId    = (int) ($_POST['sesion_id'] ?? 0);
            $professorId = (int) $this->profesor['id'];
            $sesion      = $sesionId ? $this->model->getSesion($sesionId, $professorId) : null;

            if (!$sesion) {
                $this->jsonFinal(['success' => false, 'message' => 'Sesión no encontrada.'], 404);
                return;
            }
            if ($sesion['estado'] === 'DICTADA') {
                $this->jsonFinal(['success' => false, 'message' => 'Esta sesión ya fue procesada por el administrador.'], 422);
                return;
            }

            $asistenciaRaw = $_POST['asistencia'] ?? [];
            $asistencia    = [];
            foreach ($asistenciaRaw as $eid => $val) {
                $asistencia[(int) $eid] = (int) $val;
            }

            if (empty($asistencia)) {
                $this->jsonFinal(['success' => false, 'message' => 'No hay estudiantes para registrar.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->guardarAsistencia($sesionId, $asistencia, $userId);

            AuditService::log($userId, 'RegistrarAsistencia', 'GUARDAR',
                "Profesor registró asistencia sesión {$sesionId} — " . count($asistencia) . " estudiantes", $sesionId);

            $this->jsonFinal([
                'success'   => true,
                'message'   => 'Asistencia registrada correctamente.',
                'pdf_url'   => "/diplomatic/public/professor/registrar-asistencia/pdf?sesion_id={$sesionId}",
                'sesion_id' => $sesionId,
            ]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PDF — ASISTENCIA MARCADA
    // =========================================================================

    public function pdf(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $sesionId    = (int) ($_GET['sesion_id'] ?? 0);
            $professorId = (int) $this->profesor['id'];
            $sesion      = $sesionId ? $this->model->getSesion($sesionId, $professorId) : null;

            if (!$sesion) {
                http_response_code(404);
                echo 'Sesión no encontrada.';
                return;
            }

            $estudiantes = $this->model->getEstudiantes($sesionId, (int) $sesion['offering_id']);
            $html        = $this->buildPdfHtml($sesion, $estudiantes);

            require_once $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/tools/dompdf/autoload.inc.php';
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Times-Roman');
            $dompdf = new Dompdf($options);
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            $dompdf->stream('Asistencia_' . $sesionId . '.pdf', ['Attachment' => false]);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Error al generar PDF: ' . $e->getMessage();
        }
    }

    // =========================================================================
    // HTML DEL PDF (asistencia marcada)
    // =========================================================================

    private function buildPdfHtml(array $s, array $estudiantes): string
    {
        $pathUcla     = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
        $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
        $imgUcla      = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
        $imgMedicina  = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

        $profesor  = htmlspecialchars($s['profesor_nombre'] ?? '—');
        $diplomado = htmlspecialchars($s['diplomado_nombre'] ?? '—');
        $cohorte   = htmlspecialchars($s['cohorte_nombre']   ?? '—');
        $grupos    = htmlspecialchars($s['grupos_nombre']     ?? '—');
        $horario   = htmlspecialchars($s['horario_desc']     ?? '—');
        $fecha     = isset($s['fecha']) ? date('d/m/Y', strtotime($s['fecha'])) : '—';
        $tipo      = $s['tipo_horario'] === 'TEORICO' ? 'Teorica' : 'Practica';
        $totalEst  = count($estudiantes);
        $presentes = count(array_filter($estudiantes, fn($e) => (int)$e['asistio'] === 1));
        $ausentes  = $totalEst - $presentes;
        $fechaHoy  = date('d') . ' de ' . $this->getMes((int)date('m')) . ' de ' . date('Y');

        $filas = '';
        foreach ($estudiantes as $idx => $e) {
            $nombre   = htmlspecialchars($e['last_name'] . ', ' . $e['first_name']);
            $cedula   = htmlspecialchars($e['document_id']);
            $asistio  = (int)$e['asistio'];
            $estadoTxt = $asistio
                ? '<span style="color:#085041;font-weight:bold">P</span>'
                : '<span style="color:#A32D2D;font-weight:bold">A</span>';
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
<head><meta charset='UTF-8'>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:Times-Roman; font-size:11pt; color:#212529; padding:1.5cm; }
  table { width:100%; border-collapse:collapse; }
  th { background:#533AB7; color:#fff; padding:7px 8px; font-size:10pt; text-align:left; }
  .info-label { font-size:8pt; font-weight:bold; text-transform:uppercase; color:#6c757d; }
  .info-val   { font-size:11pt; font-weight:bold; margin-top:2px; }
  .info-cell  { background:#f8f9fa; padding:7px 10px; border:0.5px solid #dee2e6; }
</style>
</head>
<body>
<table style='margin-bottom:16px'>
  <tr>
    <td style='width:15%;text-align:left'>" . ($imgUcla ? "<img src='{$imgUcla}' style='width:65px'>" : '') . "</td>
    <td style='width:70%;text-align:center;font-weight:bold;font-size:10.5pt;line-height:1.5'>
      UNIVERSIDAD CENTROCCIDENTAL &ldquo;LISANDRO ALVARADO&rdquo;<br>
      DECANATO DE CIENCIAS DE LA SALUD<br>
      &ldquo;Dr. PABLO ACOSTA ORTIZ&rdquo;<br>
      COORDINACION DE EXTENSION
    </td>
    <td style='width:15%;text-align:right'>" . ($imgMedicina ? "<img src='{$imgMedicina}' style='width:65px'>" : '') . "</td>
  </tr>
</table>

<div style='text-align:center;font-weight:bold;font-size:13pt;text-decoration:underline;margin:14px 0 12px'>
  REGISTRO DE ASISTENCIA
</div>

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
        <div class='info-label'>Profesor</div>
        <div class='info-val'>{$profesor}</div>
      </div>
    </td>
    <td style='padding-left:6px;padding-top:6px'>
      <div class='info-cell'>
        <div class='info-label'>Grupo</div>
        <div class='info-val'>{$grupos}</div>
      </div>
    </td>
  </tr>
  <tr>
    <td style='padding-right:6px;padding-top:6px'>
      <div class='info-cell'>
        <div class='info-label'>Horario</div>
        <div class='info-val'>{$horario}</div>
      </div>
    </td>
    <td style='padding-left:6px;padding-top:6px'>
      <div class='info-cell'>
        <div class='info-label'>Fecha · Tipo</div>
        <div class='info-val'>{$fecha} &nbsp;·&nbsp; {$tipo} &nbsp;·&nbsp; {$presentes}P / {$ausentes}A</div>
      </div>
    </td>
  </tr>
</table>

<table>
  <thead>
    <tr>
      <th style='width:6%;text-align:center'>#</th>
      <th style='width:42%'>Apellidos y Nombres</th>
      <th style='width:18%;text-align:center'>Cedula</th>
      <th style='width:10%;text-align:center'>P/A</th>
      <th style='width:24%'>Firma</th>
    </tr>
  </thead>
  <tbody>{$filas}</tbody>
</table>

<table style='margin-top:50px'>
  <tr>
    <td style='width:40%;text-align:center;border-top:1px solid #333;padding-top:8px'>
      <div style='font-weight:bold;font-size:10pt'>{$profesor}</div>
      <div style='font-size:9pt;color:#555'>Profesor</div>
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
</body></html>";
    }

    private function getMes(int $m): string
    {
        return ['','enero','febrero','marzo','abril','mayo','junio',
                'julio','agosto','septiembre','octubre','noviembre','diciembre'][$m] ?? '';
    }

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