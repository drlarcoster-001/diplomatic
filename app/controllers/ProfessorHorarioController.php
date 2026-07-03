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
        $periodoId    = (int) ($_GET['periodo_id'] ?? 0);
        $diplomaId    = (int) ($_GET['diploma_id'] ?? 0);
        $periodos     = $this->model->getPeriodos($professorId);
        $diplomados   = $this->model->getDiplomadosPorPeriodo($professorId, $periodoId);
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
            'periodos'     => $periodos,
            'diplomados'   => $diplomados,
            'periodoId'    => $periodoId,
            'diplomaId'    => $diplomaId,
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

        // ── GRILLA TEÓRICA ────────────────────────────────────────────────────
        $diasOrden  = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
        $franjas    = [];
        $diasUsados = [];
        $gridTeo    = [];

        foreach ($teoricos as $t) {
            $key = $t['hora_inicio'];
            if (!isset($franjas[$key])) $franjas[$key] = ['inicio' => $t['hora_inicio'], 'fin' => $t['hora_fin']];
            if (!in_array($t['dia_semana'], $diasUsados, true)) $diasUsados[] = $t['dia_semana'];
            $gridTeo[$t['dia_semana']][$key] = $t;
        }
        ksort($franjas);
        usort($diasUsados, fn($a,$b) => array_search($a,$diasOrden) <=> array_search($b,$diasOrden));

        if (empty($teoricos)) {
            $htmlTeorico = "<tr><td colspan='8' style='padding:10px;text-align:center;color:#888'>Sin horario teorico asignado.</td></tr>";
        } else {
            // Encabezado días
            $htmlTeorico = "<tr style='background:#533AB7;color:#fff'><th style='padding:6px 8px;border:0.5px solid #4430a0;width:18%'>Horario</th>";
            foreach ($diasUsados as $dia) {
                $htmlTeorico .= "<th style='padding:6px 8px;border:0.5px solid #4430a0;text-align:center'>" . htmlspecialchars($dia) . "</th>";
            }
            $htmlTeorico .= "</tr>";
            // Filas
            foreach ($franjas as $franja) {
                $htmlTeorico .= "<tr>";
                $htmlTeorico .= "<td style='padding:6px 8px;border:0.5px solid #dee2e6;font-weight:bold;background:#f8f9fa'>{$franja['inicio']} – {$franja['fin']}</td>";
                foreach ($diasUsados as $dia) {
                    $celda = $gridTeo[$dia][$franja['inicio']] ?? null;
                    if ($celda) {
                        $grupo = htmlspecialchars($celda['grupo_nombre'] ?? '');
                        $htmlTeorico .= "<td style='padding:6px 4px;border:0.5px solid #dee2e6;text-align:center;background:#EEEDFE'>
                            <div style='background:#533AB7;color:#fff;border-radius:4px;padding:4px 6px;font-size:9pt;font-weight:bold'>Teorica</div>
                            " . ($grupo ? "<div style='font-size:8pt;margin-top:2px;color:#533AB7'>{$grupo}</div>" : '') . "
                        </td>";
                    } else {
                        $htmlTeorico .= "<td style='padding:6px 8px;border:0.5px solid #dee2e6;text-align:center;color:#ccc'>—</td>";
                    }
                }
                $htmlTeorico .= "</tr>";
            }
        }

        // ── CALENDARIOS MENSUALES ─────────────────────────────────────────────
        $calendarios = [];
        foreach ($practicos as $p) {
            $ts  = strtotime($p['fecha']);
            $ym  = date('Y-m', $ts);
            $dia = (int) date('j', $ts);
            $calendarios[$ym][$dia][] = $p;
        }
        ksort($calendarios);

        $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        $htmlPractica = '';
        if (empty($practicos)) {
            $htmlPractica = "<p style='text-align:center;color:#888;padding:10px'>Sin horario practico asignado.</p>";
        } else {
            foreach ($calendarios as $ym => $diasPractica) {
                [$anio, $mes] = explode('-', $ym);
                $nombreMes  = $meses[(int)$mes] . ' ' . $anio;
                $primerDia  = (int) date('N', strtotime("{$anio}-{$mes}-01"));
                $diasEnMes  = (int) date('t', strtotime("{$anio}-{$mes}-01"));

                $htmlPractica .= "
                <table style='width:100%;border-collapse:collapse;margin-bottom:16px'>
                  <tr>
                    <td colspan='7' style='background:#198754;color:#fff;text-align:center;padding:7px;font-weight:bold;font-size:11pt;border-radius:4px 4px 0 0'>
                      {$nombreMes}
                    </td>
                  </tr>
                  <tr style='background:#e8f5ee'>";
                foreach (['Lu','Ma','Mi','Ju','Vi','Sá','Do'] as $d) {
                    $htmlPractica .= "<th style='padding:5px;text-align:center;border:0.5px solid #c3e6cb;width:14.28%;font-size:9pt'>{$d}</th>";
                }
                $htmlPractica .= "</tr>";

                $diaActual = 1 - ($primerDia - 1);
                $filas = (int) ceil(($primerDia - 1 + $diasEnMes) / 7);
                for ($fila = 0; $fila < $filas; $fila++) {
                    $htmlPractica .= "<tr>";
                    for ($col = 0; $col < 7; $col++) {
                        if ($diaActual < 1 || $diaActual > $diasEnMes) {
                            $htmlPractica .= "<td style='padding:5px;border:0.5px solid #dee2e6;background:#f8f9fa'>&nbsp;</td>";
                        } else {
                            $tienePractica = isset($diasPractica[$diaActual]);
                            $bg = $tienePractica ? '#d1f0e0' : '#fff';
                            $htmlPractica .= "<td style='padding:4px;border:0.5px solid #dee2e6;background:{$bg};vertical-align:top'>";
                            $htmlPractica .= "<div style='font-weight:bold;font-size:9pt'>{$diaActual}</div>";
                            if ($tienePractica) {
                                foreach ($diasPractica[$diaActual] as $pr) {
                                    $htmlPractica .= "<div style='font-size:7pt;color:#085041;margin-top:2px'>" . htmlspecialchars($pr['centro_medico']) . "</div>";
                                }
                            }
                            $htmlPractica .= "</td>";
                        }
                        $diaActual++;
                    }
                    $htmlPractica .= "</tr>";
                }
                $htmlPractica .= "</table>";
            }
        }

        return "<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:Times-Roman; font-size:11pt; color:#212529; padding:1.5cm; }
  .info-label { font-size:8pt; font-weight:bold; text-transform:uppercase; color:#6c757d; }
  .info-val   { font-size:11pt; font-weight:bold; margin-top:2px; }
  .info-cell  { background:#f8f9fa; padding:7px 10px; border:0.5px solid #dee2e6; }
  .seccion    { font-size:11pt; font-weight:bold; margin:20px 0 8px; color:#533AB7; border-bottom:1.5px solid #533AB7; padding-bottom:4px; }
</style>
</head>
<body>

<table style='width:100%;border-collapse:collapse;margin-bottom:16px'>
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

<div style='text-align:center;font-weight:bold;font-size:13pt;text-decoration:underline;margin:14px 0 12px'>HORARIO ASIGNADO</div>

<table style='width:100%;border-collapse:collapse;margin-bottom:12px'>
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

<div class='seccion'>Horario Teorico</div>
<table style='width:100%;border-collapse:collapse;margin-bottom:20px'>
  <tbody>{$htmlTeorico}</tbody>
</table>

<div class='seccion'>Horario Practico</div>
{$htmlPractica}

<table style='width:100%;border-collapse:collapse;margin-top:50px'>
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