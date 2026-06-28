<?php
/**
 * MÓDULO: ACADÉMICO / APROBAR ACTAS
 * ARCHIVO: app/controllers/AcademicAprobarActasController.php
 * PROPÓSITO: index() lista actas con filtro. manage() muestra detalle con
 *            estudiantes y notas. aprobar() y reversar() vía AJAX.
 *            pdf() genera PDF del acta para imprimir.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\AcademicAprobarActasController;
 *   $router->get('/academic/aprobar-actas',           [AcademicAprobarActasController::class, 'index']);
 *   $router->get('/academic/aprobar-actas/manage',    [AcademicAprobarActasController::class, 'manage']);
 *   $router->post('/academic/aprobar-actas/aprobar',  [AcademicAprobarActasController::class, 'aprobar']);
 *   $router->post('/academic/aprobar-actas/reversar', [AcademicAprobarActasController::class, 'reversar']);
 *   $router->get('/academic/aprobar-actas/pdf',       [AcademicAprobarActasController::class, 'pdf']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AcademicAprobarActasModel;
use App\Services\AuditService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class AcademicAprobarActasController extends Controller
{
    private AcademicAprobarActasModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $allowed = ['ADMIN', 'ACADEMIC'];
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed, true)) {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new AcademicAprobarActasModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $search    = trim($_GET['search'] ?? '');
        $estado    = $_GET['estado'] ?? '';
        $periodoId = (int) ($_GET['periodo_id'] ?? 0);
        $page      = max(1, (int) ($_GET['page'] ?? 1));
        $perPage   = 25;

        if (!in_array($estado, ['ENVIADA', 'APROBADA'], true)) $estado = '';

        $total      = $this->model->countActas($estado, $search, $periodoId);
        $totalPages = (int) ceil($total / $perPage);
        $actas      = $this->model->getActas($estado, $search, $page, $perPage, $periodoId);
        $periodos   = $this->model->getPeriodos();

        $this->view('academic/aprobar_actas/index', [
            'actas'      => $actas,
            'search'     => $search,
            'estado'     => $estado,
            'periodoId'  => $periodoId,
            'periodos'   => $periodos,
            'page'       => $page,
            'total'      => $total,
            'totalPages' => $totalPages,
            'perPage'    => $perPage,
        ]);
    }

    // =========================================================================
    // MANAGE — DETALLE DEL ACTA
    // =========================================================================

    public function manage(): void
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $acta = $id ? $this->model->getActaById($id) : null;

        if (!$acta) {
            header('Location: /diplomatic/public/academic/aprobar-actas?error=notfound');
            exit;
        }

        $estudiantes = $this->model->getEstudiantesConNotas(
            (int) $acta['offering_id'],
            $acta['modalidad']
        );
        $notaMinima  = $this->model->getNotaMinima((int) $acta['offering_id']);

        $this->view('academic/aprobar_actas/manage', [
            'acta'        => $acta,
            'estudiantes' => $estudiantes,
            'notaMinima'  => $notaMinima,
        ]);
    }

    // =========================================================================
    // APROBAR — AJAX
    // =========================================================================

    public function aprobar(): void
    {
        try {
            $id   = (int) ($_POST['id'] ?? 0);
            $acta = $id ? $this->model->getActaById($id) : null;

            if (!$acta) {
                $this->jsonFinal(['success' => false, 'message' => 'Acta no encontrada.'], 404);
                return;
            }
            if ($acta['estado'] !== 'ENVIADA') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden aprobar actas en estado ENVIADA.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->aprobarActa($id, $userId);

            AuditService::log($userId, 'AprobarActas', 'APROBAR',
                "Aprobó acta ID {$id} — oferta {$acta['offering_id']} modalidad {$acta['modalidad']}", $id);

            $this->jsonFinal(['success' => true, 'message' => 'Acta aprobada correctamente.']);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // REVERSAR — AJAX
    // =========================================================================

    public function reversar(): void
    {
        try {
            $id   = (int) ($_POST['id'] ?? 0);
            $acta = $id ? $this->model->getActaById($id) : null;

            if (!$acta) {
                $this->jsonFinal(['success' => false, 'message' => 'Acta no encontrada.'], 404);
                return;
            }
            if (!in_array($acta['estado'], ['ENVIADA', 'APROBADA'], true)) {
                $this->jsonFinal(['success' => false, 'message' => 'No se puede reversar esta acta.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->reversarActa($id, $userId);

            AuditService::log($userId, 'AprobarActas', 'REVERSAR',
                "Reversó acta ID {$id} a BORRADOR", $id);

            $this->jsonFinal(['success' => true, 'message' => 'Acta reversada. El profesor puede corregir las notas.']);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PDF
    // =========================================================================

    public function pdf(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $id   = (int) ($_GET['id'] ?? 0);
            $acta = $id ? $this->model->getActaById($id) : null;

            if (!$acta) {
                http_response_code(404);
                echo 'Acta no encontrada.';
                return;
            }

            $estudiantes = $this->model->getEstudiantesConNotas(
                (int) $acta['offering_id'],
                $acta['modalidad']
            );
            $notaMinima = $this->model->getNotaMinima((int) $acta['offering_id']);
            $html       = $this->buildPdfHtml($acta, $estudiantes, $notaMinima);

            require_once $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/tools/dompdf/autoload.inc.php';
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Times-Roman');
            $dompdf = new Dompdf($options);
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            $dompdf->stream('Acta_' . $id . '.pdf', ['Attachment' => false]);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Error: ' . $e->getMessage();
        }
    }

    // =========================================================================
    // HTML DEL PDF
    // =========================================================================

    private function buildPdfHtml(array $acta, array $estudiantes, float $notaMinima): string
    {
        $pathUcla     = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
        $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
        $imgUcla      = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
        $imgMedicina  = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

        $labelMod   = ['TEORICA' => 'Teorica', 'PRACTICA' => 'Practica', 'VIRTUAL' => 'Virtual'];
        $diplomado  = htmlspecialchars($acta['diplomado_nombre'] ?? '—');
        $cohorte    = htmlspecialchars($acta['cohorte_nombre']   ?? '—');
        $grupos     = htmlspecialchars($acta['grupos_nombre']    ?? '—');
        $profesor   = htmlspecialchars($acta['profesor_nombre']  ?? '—');
        $mod        = $labelMod[$acta['modalidad']] ?? $acta['modalidad'];
        $estado     = $acta['estado'];
        $fechaHoy   = date('d') . ' de ' . $this->getMes((int) date('m')) . ' de ' . date('Y');

        $totalEst   = count($estudiantes);
        $aprobados  = count(array_filter($estudiantes, fn($e) => $e['nota'] !== null && (float)$e['nota'] >= $notaMinima));
        $reprobados = count(array_filter($estudiantes, fn($e) => $e['nota'] !== null && (float)$e['nota'] < $notaMinima));

        $filas = '';
        foreach ($estudiantes as $idx => $e) {
            $nombre    = htmlspecialchars($e['last_name'] . ', ' . $e['first_name']);
            $cedula    = htmlspecialchars($e['document_id']);
            $nota      = $e['nota'] !== null ? number_format((float)$e['nota'], 2) : '—';
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
  ACTA DE NOTAS — {$mod}
</div>
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
        <div class='info-val'>{$totalEst} est. &nbsp;·&nbsp; {$aprobados} aprobados &nbsp;·&nbsp; {$reprobados} reprobados</div>
      </div>
    </td>
  </tr>
</table>
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
<div style='margin-top:10px;font-size:9pt;color:#555'>
  Nota minima para aprobar: <strong>{$notaMinima}</strong> puntos (escala 0-20)
</div>
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

    public function reporte(): void
{
    while (ob_get_level() > 0) ob_end_clean();
    try {
        $search = trim($_GET['search'] ?? '');
        $estado = $_GET['estado'] ?? '';
        if (!in_array($estado, ['ENVIADA','APROBADA'], true)) $estado = '';

        $periodoId = (int) ($_GET['periodo_id'] ?? 0);
        $actas     = $this->model->getActas($estado, $search, 1, 1000, $periodoId);
        $labelMod = ['TEORICA' => 'Teorica', 'PRACTICA' => 'Practica', 'VIRTUAL' => 'Virtual'];

        $pathUcla     = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
        $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
        $imgUcla      = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
        $imgMedicina  = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';
        $fechaHoy     = date('d') . ' de ' . $this->getMes((int) date('m')) . ' de ' . date('Y');
        $filtroLabel  = $estado === 'ENVIADA' ? 'Enviadas' : ($estado === 'APROBADA' ? 'Aprobadas' : 'Todas');

        $filas = '';
        foreach ($actas as $idx => $a) {
            $bg     = $idx % 2 === 0 ? '#ffffff' : '#f8f9fa';
            $est    = $a['estado'] === 'APROBADA'
                ? '<span style="color:#085041;font-weight:bold">Aprobada</span>'
                : '<span style="color:#633806;font-weight:bold">Enviada</span>';
            $grupos = htmlspecialchars($a['grupos_nombre'] ?? '—');
            $filas .= "<tr style='background:{$bg}'>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>" . ($idx + 1) . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6'>" . htmlspecialchars($a['diplomado_nombre']) . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6'>" . htmlspecialchars($a['cohorte_nombre']) . "<br><small style='color:#666'>{$grupos}</small></td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>" . ($labelMod[$a['modalidad']] ?? $a['modalidad']) . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6'>" . htmlspecialchars($a['profesor_nombre']) . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>" . (int)$a['aprobados'] . " / " . (int)$a['total_notas'] . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>{$est}</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>" . date('d/m/Y', strtotime($a['updated_at'])) . "</td>
            </tr>";
        }

        $html = "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>
        <style>
          * { box-sizing:border-box; margin:0; padding:0; }
          body { font-family:Times-Roman; font-size:10pt; color:#212529; padding:1.2cm; }
          table { width:100%; border-collapse:collapse; }
          th { background:#533AB7; color:#fff; padding:6px 8px; font-size:9pt; text-align:left; }
        </style></head><body>
        <table style='margin-bottom:14px'>
          <tr>
            <td style='width:15%;text-align:left'>" . ($imgUcla ? "<img src='{$imgUcla}' style='width:60px'>" : '') . "</td>
            <td style='width:70%;text-align:center;font-weight:bold;font-size:10pt;line-height:1.5'>
              UNIVERSIDAD CENTROCCIDENTAL &ldquo;LISANDRO ALVARADO&rdquo;<br>
              DECANATO DE CIENCIAS DE LA SALUD<br>&ldquo;Dr. PABLO ACOSTA ORTIZ&rdquo;<br>
              COORDINACION DE EXTENSION
            </td>
            <td style='width:15%;text-align:right'>" . ($imgMedicina ? "<img src='{$imgMedicina}' style='width:60px'>" : '') . "</td>
          </tr>
        </table>
        <div style='text-align:center;font-weight:bold;font-size:12pt;text-decoration:underline;margin:12px 0 10px'>
          REPORTE DE ACTAS — {$filtroLabel}
        </div>
        <table>
          <thead>
            <tr>
              <th style='width:5%;text-align:center'>#</th>
              <th style='width:22%'>Diplomado</th>
              <th style='width:18%'>Cohorte / Grupo</th>
              <th style='width:10%;text-align:center'>Modalidad</th>
              <th style='width:18%'>Profesor</th>
              <th style='width:10%;text-align:center'>Aprobados</th>
              <th style='width:9%;text-align:center'>Estado</th>
              <th style='width:8%;text-align:center'>Fecha</th>
            </tr>
          </thead>
          <tbody>{$filas}</tbody>
        </table>
        <div style='text-align:center;font-size:8pt;color:#888;margin-top:20px'>
          Generado en Barquisimeto, el {$fechaHoy} &nbsp;·&nbsp; Sistema DIPLOMATIC &copy; UCLA
        </div></body></html>";

        require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Times-Roman');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        $dompdf->stream('Reporte_Actas_' . date('Ymd') . '.pdf', ['Attachment' => false]);
        exit;

    } catch (\Throwable $e) {
        http_response_code(500);
        echo 'Error: ' . $e->getMessage();
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