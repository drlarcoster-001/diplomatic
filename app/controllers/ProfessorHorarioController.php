<?php
/**
 * MÓDULO: PORTAL DOCENTE / MI HORARIO
 * ARCHIVO: app/controllers/ProfessorHorarioController.php
 * PROPÓSITO: index() muestra selector de oferta y compila horarios teóricos
 *            y prácticos del profesor. pdf() genera PDF imprimible con
 *            ambos horarios usando DomPDF.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ProfessorHorarioController;
 *   $router->get('/professor/horario',     [ProfessorHorarioController::class, 'index']);
 *   $router->get('/professor/horario/pdf', [ProfessorHorarioController::class, 'pdf']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProfessorModel;
use App\Models\ProfessorHorarioModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class ProfessorHorarioController extends Controller
{
    protected array $profesor;
    private ProfessorHorarioModel $model;

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
        $this->model    = new ProfessorHorarioModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $professorId  = (int) $this->profesor['id'];
        $offeringId   = (int) ($_GET['offering_id'] ?? 0);
        $ofertas      = $this->model->getMisOfertas($professorId);
        $ofertaActiva = null;
        $teoricos     = [];
        $practicos    = [];

        if ($offeringId) {
            foreach ($ofertas as $o) {
                if ((int) $o['offering_id'] === $offeringId) {
                    $ofertaActiva = $o;
                    break;
                }
            }
            if ($ofertaActiva) {
                $teoricos  = $this->model->getHorariosTeoricos($professorId, $offeringId);
                $practicos = $this->model->getHorariosPracticos($professorId, $offeringId);
            }
        }

        $this->view('professor/horario/index', [
            'profesor'     => $this->profesor,
            'ofertas'      => $ofertas,
            'offeringId'   => $offeringId,
            'ofertaActiva' => $ofertaActiva,
            'teoricos'     => $teoricos,
            'practicos'    => $practicos,
        ]);
    }

    // =========================================================================
    // PDF
    // =========================================================================

    public function pdf(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $professorId = (int) $this->profesor['id'];
            $offeringId  = (int) ($_GET['offering_id'] ?? 0);

            if (!$offeringId) {
                http_response_code(404);
                echo 'Oferta no especificada.';
                return;
            }

            $oferta    = $this->model->getOferta($offeringId);
            $teoricos  = $this->model->getHorariosTeoricos($professorId, $offeringId);
            $practicos = $this->model->getHorariosPracticos($professorId, $offeringId);

            if (!$oferta) {
                http_response_code(404);
                echo 'Oferta no encontrada.';
                return;
            }

            $html = $this->buildPdfHtml($oferta, $teoricos, $practicos);

            require_once $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/tools/dompdf/autoload.inc.php';
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Times-Roman');
            $dompdf = new Dompdf($options);
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            $dompdf->stream('Horario_' . $offeringId . '.pdf', ['Attachment' => false]);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Error al generar PDF: ' . $e->getMessage();
        }
    }

    // =========================================================================
    // HTML DEL PDF
    // =========================================================================

    private function buildPdfHtml(array $oferta, array $teoricos, array $practicos): string
    {
        $pathUcla     = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
        $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
        $imgUcla      = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
        $imgMedicina  = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

        $diplomado = htmlspecialchars($oferta['diplomado_nombre'] ?? '—');
        $cohorte   = htmlspecialchars($oferta['cohorte_nombre']   ?? '—');
        $grupos    = htmlspecialchars($oferta['grupos_nombre']     ?? '—');
        $profesor  = htmlspecialchars($this->profesor['full_name'] ?? '—');
        $fechaHoy  = date('d') . ' de ' . $this->getMes((int) date('m')) . ' de ' . date('Y');

        // Filas teóricas
        $filasTeorico = '';
        if (empty($teoricos)) {
            $filasTeorico = "<tr><td colspan='3' style='padding:10px;text-align:center;color:#888'>Sin horario teorico asignado.</td></tr>";
        } else {
            foreach ($teoricos as $idx => $t) {
                $bg = $idx % 2 === 0 ? '#ffffff' : '#f8f9fa';
                $filasTeorico .= "<tr style='background:{$bg}'>
                    <td style='padding:6px 10px;border:0.5px solid #dee2e6'>" . htmlspecialchars($t['dia_semana']) . "</td>
                    <td style='padding:6px 10px;border:0.5px solid #dee2e6;text-align:center'>{$t['hora_inicio']} – {$t['hora_fin']}</td>
                    <td style='padding:6px 10px;border:0.5px solid #dee2e6'>" . htmlspecialchars($t['grupo_nombre'] ?? '—') . "</td>
                </tr>";
            }
        }

        // Filas prácticas
        $filasPractica = '';
        if (empty($practicos)) {
            $filasPractica = "<tr><td colspan='3' style='padding:10px;text-align:center;color:#888'>Sin horario practico asignado.</td></tr>";
        } else {
            foreach ($practicos as $idx => $p) {
                $bg    = $idx % 2 === 0 ? '#ffffff' : '#f8f9fa';
                $fecha = date('d/m/Y', strtotime($p['fecha']));
                $filasPractica .= "<tr style='background:{$bg}'>
                    <td style='padding:6px 10px;border:0.5px solid #dee2e6;text-align:center'>{$fecha}</td>
                    <td style='padding:6px 10px;border:0.5px solid #dee2e6'>" . htmlspecialchars($p['centro_medico']) . "</td>
                    <td style='padding:6px 10px;border:0.5px solid #dee2e6'>" . htmlspecialchars($p['grupo_practica']) . "</td>
                </tr>";
            }
        }

        return "<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:Times-Roman; font-size:11pt; color:#212529; padding:1.5cm; }
  table { width:100%; border-collapse:collapse; }
  th { background:#533AB7; color:#fff; padding:7px 10px; font-size:10pt; text-align:left; }
  .info-label { font-size:8pt; font-weight:bold; text-transform:uppercase; color:#6c757d; }
  .info-val   { font-size:11pt; font-weight:bold; margin-top:2px; }
  .info-cell  { background:#f8f9fa; padding:7px 10px; border:0.5px solid #dee2e6; }
  .seccion    { font-size:11pt; font-weight:bold; margin:20px 0 8px; color:#533AB7; border-bottom:1.5px solid #533AB7; padding-bottom:4px; }
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
      COORDINACION DE EXTENSION
    </td>
    <td style='width:15%;text-align:right'>" . ($imgMedicina ? "<img src='{$imgMedicina}' style='width:65px'>" : '') . "</td>
  </tr>
</table>

<!-- TÍTULO -->
<div style='text-align:center;font-weight:bold;font-size:13pt;text-decoration:underline;margin:14px 0 12px'>
  HORARIO ASIGNADO
</div>

<!-- DATOS -->
<table style='margin-bottom:8px'>
  <tr>
    <td style='width:50%;padding-right:6px'>
      <div class='info-cell'><div class='info-label'>Diplomado</div><div class='info-val'>{$diplomado}</div></div>
    </td>
    <td style='width:50%;padding-left:6px'>
      <div class='info-cell'><div class='info-label'>Cohorte</div><div class='info-val'>{$cohorte}</div></div>
    </td>
  </tr>
  <tr>
    <td style='padding-right:6px;padding-top:6px'>
      <div class='info-cell'><div class='info-label'>Grupo</div><div class='info-val'>{$grupos}</div></div>
    </td>
    <td style='padding-left:6px;padding-top:6px'>
      <div class='info-cell'><div class='info-label'>Profesor</div><div class='info-val'>{$profesor}</div></div>
    </td>
  </tr>
</table>

<!-- HORARIO TEÓRICO -->
<div class='seccion'>Horario Teorico</div>
<table>
  <thead>
    <tr>
      <th style='width:30%'>Dia</th>
      <th style='width:30%;text-align:center'>Horario</th>
      <th style='width:40%'>Grupo</th>
    </tr>
  </thead>
  <tbody>{$filasTeorico}</tbody>
</table>

<!-- HORARIO PRÁCTICO -->
<div class='seccion'>Horario Practico</div>
<table>
  <thead>
    <tr>
      <th style='width:20%;text-align:center'>Fecha</th>
      <th style='width:45%'>Centro Medico</th>
      <th style='width:35%'>Grupo</th>
    </tr>
  </thead>
  <tbody>{$filasPractica}</tbody>
</table>

<!-- FIRMA -->
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
}