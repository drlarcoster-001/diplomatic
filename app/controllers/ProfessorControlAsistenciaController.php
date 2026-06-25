<?php
/**
 * MÓDULO: PORTAL DOCENTE / CONTROL DE ASISTENCIA
 * ARCHIVO: app/controllers/ProfessorControlAsistenciaController.php
 * PROPÓSITO: Muestra las sesiones del profesor por oferta y permite descargar
 *            la lista de asistencia en blanco (PDF) para llevar al aula.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ProfessorControlAsistenciaController;
 *   $router->get('/professor/control-asistencia',     [ProfessorControlAsistenciaController::class, 'index']);
 *   $router->get('/professor/control-asistencia/pdf', [ProfessorControlAsistenciaController::class, 'pdf']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProfessorModel;
use App\Models\ProfessorControlAsistenciaModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class ProfessorControlAsistenciaController extends Controller
{
    protected array $profesor;
    private ProfessorControlAsistenciaModel $model;

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
        $this->model    = new ProfessorControlAsistenciaModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $professorId  = (int) $this->profesor['id'];
        $offeringId   = (int) ($_GET['offering_id'] ?? 0);
        $ofertas      = $this->model->getMisOfertas($professorId);
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
        }

        $this->view('professor/control_asistencia/index', [
            'profesor'     => $this->profesor,
            'ofertas'      => $ofertas,
            'sesiones'     => $sesiones,
            'offeringId'   => $offeringId,
            'ofertaActiva' => $ofertaActiva,
        ]);
    }

    // =========================================================================
    // PDF — LISTA EN BLANCO PARA EL AULA
    // =========================================================================

    public function pdf(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $sesionId    = (int) ($_GET['sesion_id'] ?? 0);
            $professorId = (int) $this->profesor['id'];
            $sesion      = $sesionId ? $this->model->getSesionParaPdf($sesionId, $professorId) : null;

            if (!$sesion) {
                http_response_code(404);
                echo 'Sesión no encontrada.';
                return;
            }

            $estudiantes = $this->model->getEstudiantesPorOferta((int) $sesion['offering_id']);
            $html        = $this->buildPdfHtml($sesion, $estudiantes);

            require_once $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/tools/dompdf/autoload.inc.php';

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Times-Roman');

            $dompdf = new Dompdf($options);
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            $dompdf->stream('Lista_Asistencia_' . $sesionId . '.pdf', ['Attachment' => false]);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Error al generar PDF: ' . $e->getMessage();
        }
    }

    // =========================================================================
    // HTML DEL PDF (lista en blanco — sin asistencia marcada)
    // =========================================================================

    private function buildPdfHtml(array $s, array $estudiantes): string
    {
        $pathUcla     = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
        $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
        $imgUcla      = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
        $imgMedicina  = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

        $profesor  = htmlspecialchars(($s['last_name'] ?? '') . ', ' . ($s['first_name'] ?? ''));
        $diplomado = htmlspecialchars($s['diplomado_nombre'] ?? '—');
        $cohorte   = htmlspecialchars($s['cohorte_nombre']   ?? '—');
        $horario   = htmlspecialchars($s['horario_desc']     ?? '—');
        $fecha     = isset($s['fecha']) ? date('d/m/Y', strtotime($s['fecha'])) : '—';
        $tipo      = $s['tipo_horario'] === 'TEORICO' ? 'Teórica' : 'Práctica';
        $totalEst  = count($estudiantes);
        $fechaHoy  = date('d') . ' de ' . $this->getMes((int) date('m')) . ' de ' . date('Y');

        $filas = '';
        foreach ($estudiantes as $idx => $e) {
            $nombre = htmlspecialchars($e['last_name'] . ', ' . $e['first_name']);
            $cedula = htmlspecialchars($e['document_id']);
            $bg     = $idx % 2 === 0 ? '#ffffff' : '#f8f9fa';
            $filas .= "<tr style='background:{$bg}'>
                <td style='padding:6px 8px;border:0.5px solid #dee2e6;text-align:center'>" . ($idx + 1) . "</td>
                <td style='padding:6px 8px;border:0.5px solid #dee2e6'>{$nombre}</td>
                <td style='padding:6px 8px;border:0.5px solid #dee2e6;text-align:center'>{$cedula}</td>
                <td style='padding:6px 8px;border:0.5px solid #dee2e6;text-align:center'>&nbsp;</td>
                <td style='padding:6px 8px;border:0.5px solid #dee2e6'>&nbsp;</td>
            </tr>";
        }

        if (empty($filas)) {
            $filas = "<tr><td colspan='5' style='padding:14px;text-align:center;color:#888'>
                Sin estudiantes inscritos en esta oferta.</td></tr>";
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

<!-- ENCABEZADO -->
<table style='margin-bottom:16px'>
  <tr>
    <td style='width:15%;text-align:left'>" . ($imgUcla ? "<img src='{$imgUcla}' style='width:65px'>" : '') . "</td>
    <td style='width:70%;text-align:center;font-weight:bold;font-size:10.5pt;line-height:1.5'>
      UNIVERSIDAD CENTROCCIDENTAL &ldquo;LISANDRO ALVARADO&rdquo;<br>
      DECANATO DE CIENCIAS DE LA SALUD<br>
      &ldquo;Dr. PABLO ACOSTA ORTIZ&rdquo;<br>
      COORDINACIÓN DE EXTENSIÓN
    </td>
    <td style='width:15%;text-align:right'>" . ($imgMedicina ? "<img src='{$imgMedicina}' style='width:65px'>" : '') . "</td>
  </tr>
</table>

<!-- TÍTULO -->
<div style='text-align:center;font-weight:bold;font-size:13pt;text-decoration:underline;margin:14px 0 12px'>
  LISTA DE ASISTENCIA
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
        <div class='info-label'>Profesor</div>
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
        <div class='info-val'>{$tipo} &nbsp;·&nbsp; {$totalEst} estudiantes</div>
      </div>
    </td>
  </tr>
</table>

<!-- TABLA DE ESTUDIANTES -->
<table>
  <thead>
    <tr>
      <th style='width:6%;text-align:center'>#</th>
      <th style='width:40%'>Apellidos y Nombres</th>
      <th style='width:18%;text-align:center'>Cédula</th>
      <th style='width:12%;text-align:center'>P / A</th>
      <th style='width:24%'>Firma</th>
    </tr>
  </thead>
  <tbody>{$filas}</tbody>
</table>

<!-- FIRMAS -->
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
        return ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'][$m] ?? '';
    }
}