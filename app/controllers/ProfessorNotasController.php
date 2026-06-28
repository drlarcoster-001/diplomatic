<?php
/**
 * MÓDULO: PORTAL DOCENTE / CARGAR NOTAS
 * ARCHIVO: app/controllers/ProfessorNotasController.php
 * PROPÓSITO: index() muestra selector de oferta y modalidad. Las notas se
 *            cargan por modalidad del profesor. guardar() persiste en
 *            tbl_notas_estudiantes. generarActa() crea/actualiza tbl_actas
 *            con estado ENVIADA. pdf() genera PDF del acta con DomPDF.
 * VERSIÓN: 1.1.0 - Agrega método pdf() para imprimir acta.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ProfessorNotasController;
 *   $router->get('/professor/notas',               [ProfessorNotasController::class, 'index']);
 *   $router->get('/professor/notas/pdf',           [ProfessorNotasController::class, 'pdf']);
 *   $router->post('/professor/notas/guardar',      [ProfessorNotasController::class, 'guardar']);
 *   $router->post('/professor/notas/generar-acta', [ProfessorNotasController::class, 'generarActa']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProfessorModel;
use App\Models\ProfessorNotasModel;
use App\Services\AuditService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class ProfessorNotasController extends Controller
{
    protected array $profesor;
    private ProfessorNotasModel $model;

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
        $this->model    = new ProfessorNotasModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $professorId = (int) $this->profesor['id'];
        $offeringId  = (int) ($_GET['offering_id'] ?? 0);
        $modalidad   = $_GET['modalidad'] ?? '';
        $periodoId   = (int) ($_GET['periodo_id'] ?? 0);

        $modalidadesValidas = ['TEORICA', 'PRACTICA', 'VIRTUAL'];
        if (!in_array($modalidad, $modalidadesValidas, true)) $modalidad = '';

        $periodos     = $this->model->getPeriodos($professorId);
        $ofertas      = $this->model->getMisOfertas($professorId, $periodoId);
        $ofertaActiva = null;
        $modalidades  = [];
        $estudiantes  = [];
        $notaMinima   = 15.00;
        $acta         = null;
        $todasCargadas = false;

        if ($offeringId) {
            foreach ($ofertas as $o) {
                if ((int) $o['offering_id'] === $offeringId) {
                    $ofertaActiva = $o;
                    break;
                }
            }

            if ($ofertaActiva) {
                $modalidades = $this->model->getMisModalidades($professorId, $offeringId);

                if (count($modalidades) === 1 && $modalidad === '') {
                    $modalidad = $modalidades[0];
                }

                if ($modalidad && in_array($modalidad, $modalidades, true)) {
                    $notaMinima    = $this->model->getNotaMinima($offeringId);
                    $estudiantes   = $this->model->getEstudiantesConNotas($offeringId, $modalidad);
                    $acta          = $this->model->getActa($offeringId, $modalidad);
                    $todasCargadas = $this->model->todasNotasCargadas($offeringId, $modalidad);
                }
            }
        }

        $this->view('professor/notas/index', [
            'profesor'      => $this->profesor,
            'ofertas'       => $ofertas,
            'offeringId'    => $offeringId,
            'ofertaActiva'  => $ofertaActiva,
            'modalidades'   => $modalidades,
            'modalidad'     => $modalidad,
            'estudiantes'   => $estudiantes,
            'notaMinima'    => $notaMinima,
            'acta'          => $acta,
            'todasCargadas' => $todasCargadas,
            'periodos'      => $periodos,
            'periodoId'     => $periodoId,
        ]);
    }

    // =========================================================================
    // PDF — ACTA DE NOTAS
    // =========================================================================

    public function pdf(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $offeringId  = (int) ($_GET['offering_id'] ?? 0);
            $modalidad   = $_GET['modalidad'] ?? '';
            $professorId = (int) $this->profesor['id'];

            if (!$offeringId || !$modalidad) {
                http_response_code(404);
                echo 'Parámetros inválidos.';
                return;
            }

            if (!$this->model->profesorTieneModalidad($professorId, $offeringId, $modalidad)) {
                http_response_code(403);
                echo 'No tienes acceso a esta acta.';
                return;
            }

            $oferta      = $this->model->getOferta($offeringId);
            $estudiantes = $this->model->getEstudiantesConNotas($offeringId, $modalidad);
            $notaMinima  = $this->model->getNotaMinima($offeringId);
            $acta        = $this->model->getActa($offeringId, $modalidad);

            if (!$oferta) {
                http_response_code(404);
                echo 'Oferta no encontrada.';
                return;
            }

            $html = $this->buildPdfHtml($oferta, $estudiantes, $modalidad, $notaMinima, $acta);

            require_once $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/tools/dompdf/autoload.inc.php';
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Times-Roman');
            $dompdf = new Dompdf($options);
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            $dompdf->stream('Acta_' . $modalidad . '_' . $offeringId . '.pdf', ['Attachment' => false]);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Error al generar PDF: ' . $e->getMessage();
        }
    }

    // =========================================================================
    // HTML DEL PDF
    // =========================================================================

    private function buildPdfHtml(array $oferta, array $estudiantes, string $modalidad, float $notaMinima, ?array $acta): string
    {
        $pathUcla     = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
        $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
        $imgUcla      = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
        $imgMedicina  = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

        $labelModalidad = ['TEORICA' => 'Teorica', 'PRACTICA' => 'Practica', 'VIRTUAL' => 'Virtual'];
        $diplomado  = htmlspecialchars($oferta['diplomado_nombre'] ?? '—');
        $cohorte    = htmlspecialchars($oferta['cohorte_nombre']   ?? '—');
        $grupos     = htmlspecialchars($oferta['grupos_nombre']    ?? '—');
        $profesor   = htmlspecialchars($this->profesor['full_name'] ?? '—');
        $mod        = $labelModalidad[$modalidad] ?? $modalidad;
        $fechaHoy   = date('d') . ' de ' . $this->getMes((int) date('m')) . ' de ' . date('Y');
        $estado     = $acta ? $acta['estado'] : 'BORRADOR';

        $totalEst   = count($estudiantes);
        $aprobados  = count(array_filter($estudiantes, fn($e) => $e['nota'] !== null && (float)$e['nota'] >= $notaMinima));
        $reprobados = count(array_filter($estudiantes, fn($e) => $e['nota'] !== null && (float)$e['nota'] < $notaMinima));
        $sinNota    = count(array_filter($estudiantes, fn($e) => $e['nota'] === null));

        $filas = '';
        foreach ($estudiantes as $idx => $e) {
            $nombre = htmlspecialchars($e['last_name'] . ', ' . $e['first_name']);
            $cedula = htmlspecialchars($e['document_id']);
            $nota   = $e['nota'] !== null ? number_format((float)$e['nota'], 2) : '—';
            $resultado = $e['nota'] !== null
                ? ((float)$e['nota'] >= $notaMinima
                    ? '<span style="color:#085041;font-weight:bold">Aprobado</span>'
                    : '<span style="color:#A32D2D;font-weight:bold">Reprobado</span>')
                : '<span style="color:#888">Sin nota</span>';
            $bg = $idx % 2 === 0 ? '#ffffff' : '#f8f9fa';
            $filas .= "<tr style='background:{$bg}'>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>" . ($idx + 1) . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6'>{$nombre}</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>{$cedula}</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center;font-weight:bold'>{$nota}</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>{$resultado}</td>
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
  ACTA DE NOTAS — {$mod}
</div>

<!-- DATOS -->
<table style='margin-bottom:12px'>
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
  <tr>
    <td style='padding-right:6px;padding-top:6px'>
      <div class='info-cell'><div class='info-label'>Modalidad</div><div class='info-val'>{$mod}</div></div>
    </td>
    <td style='padding-left:6px;padding-top:6px'>
      <div class='info-cell'>
        <div class='info-label'>Resumen</div>
        <div class='info-val'>{$totalEst} estudiantes &nbsp;·&nbsp; {$aprobados} aprobados &nbsp;·&nbsp; {$reprobados} reprobados</div>
      </div>
    </td>
  </tr>
</table>

<!-- TABLA DE NOTAS -->
<table>
  <thead>
    <tr>
      <th style='width:6%;text-align:center'>#</th>
      <th style='width:38%'>Apellidos y Nombres</th>
      <th style='width:16%;text-align:center'>Cedula</th>
      <th style='width:14%;text-align:center'>Nota</th>
      <th style='width:26%;text-align:center'>Resultado</th>
    </tr>
  </thead>
  <tbody>{$filas}</tbody>
</table>

<!-- NOTA MINIMA -->
<div style='margin-top:10px;font-size:9pt;color:#555'>
  Nota minima para aprobar: <strong>{$notaMinima}</strong> puntos (escala 0-20)
</div>

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
  Generado en Barquisimeto, el {$fechaHoy} &nbsp;·&nbsp; Sistema DIPLOMATIC &copy; UCLA &nbsp;·&nbsp; Estado: {$estado}
</div>
</body></html>";
    }

    private function getMes(int $m): string
    {
        return ['','enero','febrero','marzo','abril','mayo','junio',
                'julio','agosto','septiembre','octubre','noviembre','diciembre'][$m] ?? '';
    }

    // =========================================================================
    // GUARDAR NOTAS — AJAX
    // =========================================================================

    public function guardar(): void
    {
        try {
            $offeringId  = (int) ($_POST['offering_id'] ?? 0);
            $modalidad   = $_POST['modalidad'] ?? '';
            $professorId = (int) $this->profesor['id'];

            if (!$offeringId || !$modalidad) {
                $this->jsonFinal(['success' => false, 'message' => 'Faltan datos requeridos.'], 422);
                return;
            }

            if (!$this->model->profesorTieneModalidad($professorId, $offeringId, $modalidad)) {
                $this->jsonFinal(['success' => false, 'message' => 'No tienes acceso a esa modalidad.'], 403);
                return;
            }

            $acta = $this->model->getActa($offeringId, $modalidad);
            if ($acta && $acta['estado'] === 'APROBADA') {
                $this->jsonFinal(['success' => false, 'message' => 'El acta ya fue aprobada. No se pueden modificar las notas.'], 422);
                return;
            }

            $notasRaw = $_POST['notas'] ?? [];
            $notas    = [];
            foreach ($notasRaw as $eid => $nota) {
                $val = str_replace(',', '.', trim($nota));
                if ($val === '' || !is_numeric($val)) continue;
                $notas[(int) $eid] = (float) $val;
            }

            if (empty($notas)) {
                $this->jsonFinal(['success' => false, 'message' => 'No hay notas para guardar.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->guardarNotas($offeringId, $professorId, $modalidad, $notas, $userId);

            AuditService::log($userId, 'Notas', 'GUARDAR',
                "Guardó " . count($notas) . " notas — oferta {$offeringId} modalidad {$modalidad}", $offeringId);

            $todasCargadas = $this->model->todasNotasCargadas($offeringId, $modalidad);

            $this->jsonFinal([
                'success'        => true,
                'message'        => 'Notas guardadas correctamente.',
                'todas_cargadas' => $todasCargadas,
            ]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // GENERAR ACTA — AJAX
    // =========================================================================

    public function generarActa(): void
    {
        try {
            $offeringId  = (int) ($_POST['offering_id'] ?? 0);
            $modalidad   = $_POST['modalidad'] ?? '';
            $professorId = (int) $this->profesor['id'];

            if (!$offeringId || !$modalidad) {
                $this->jsonFinal(['success' => false, 'message' => 'Faltan datos requeridos.'], 422);
                return;
            }

            if (!$this->model->profesorTieneModalidad($professorId, $offeringId, $modalidad)) {
                $this->jsonFinal(['success' => false, 'message' => 'No tienes acceso a esa modalidad.'], 403);
                return;
            }

            if (!$this->model->todasNotasCargadas($offeringId, $modalidad)) {
                $this->jsonFinal(['success' => false, 'message' => 'Debes cargar la nota de todos los estudiantes antes de generar el acta.'], 422);
                return;
            }

            $acta = $this->model->getActa($offeringId, $modalidad);
            if ($acta && $acta['estado'] === 'APROBADA') {
                $this->jsonFinal(['success' => false, 'message' => 'El acta ya fue aprobada por el administrador.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $actaId = $this->model->generarActa($offeringId, $modalidad, $professorId, $userId);

            AuditService::log($userId, 'Actas', 'GENERAR',
                "Generó acta {$actaId} — oferta {$offeringId} modalidad {$modalidad}", $actaId);

            $this->jsonFinal([
                'success' => true,
                'message' => 'Acta enviada al administrador para su aprobación.',
                'acta_id' => $actaId,
            ]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
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