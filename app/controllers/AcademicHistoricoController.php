<?php
/**
 * MÓDULO: ACADÉMICO / HISTÓRICO
 * ARCHIVO: app/controllers/AcademicHistoricoController.php
 * PROPÓSITO: index() lista ofertas cerradas. manage() muestra detalle
 *            con estudiantes, notas y resultado. pdf() genera reporte.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\AcademicHistoricoController;
 *   $router->get('/academic/historico',        [AcademicHistoricoController::class, 'index']);
 *   $router->get('/academic/historico/manage', [AcademicHistoricoController::class, 'manage']);
 *   $router->get('/academic/historico/pdf',    [AcademicHistoricoController::class, 'pdf']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AcademicHistoricoModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class AcademicHistoricoController extends Controller
{
    private AcademicHistoricoModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $allowed = ['ADMIN', 'ACADEMIC'];
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed, true)) {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new AcademicHistoricoModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $search  = trim($_GET['search'] ?? '');
        $ofertas = $this->model->getOfertas($search);

        $this->view('academic/historico/index', [
            'ofertas' => $ofertas,
            'search'  => $search,
        ]);
    }

    // =========================================================================
    // MANAGE — DETALLE
    // =========================================================================

    public function manage(): void
    {
        $offeringId  = (int) ($_GET['offering_id'] ?? 0);
        $oferta      = $offeringId ? $this->model->getOferta($offeringId) : null;

        if (!$oferta) {
            header('Location: /diplomatic/public/academic/historico?error=notfound');
            exit;
        }

        $estudiantes = $this->model->getEstudiantes($offeringId);
        $aprobados   = count(array_filter($estudiantes, fn($e) => $e['aprobado']));
        $reprobados  = count($estudiantes) - $aprobados;

        $this->view('academic/historico/manage', [
            'oferta'      => $oferta,
            'offeringId'  => $offeringId,
            'estudiantes' => $estudiantes,
            'aprobados'   => $aprobados,
            'reprobados'  => $reprobados,
        ]);
    }

    // =========================================================================
    // PDF
    // =========================================================================

    public function pdf(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $offeringId  = (int) ($_GET['offering_id'] ?? 0);
            $oferta      = $offeringId ? $this->model->getOferta($offeringId) : null;

            if (!$oferta) {
                http_response_code(404);
                echo 'Oferta no encontrada.';
                return;
            }

            $estudiantes = $this->model->getEstudiantes($offeringId);
            $html        = $this->buildPdfHtml($oferta, $estudiantes);

            require_once $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/tools/dompdf/autoload.inc.php';
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Times-Roman');
            $dompdf = new Dompdf($options);
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            $dompdf->stream('Historico_' . $offeringId . '.pdf', ['Attachment' => false]);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Error: ' . $e->getMessage();
        }
    }

    // =========================================================================
    // HTML DEL PDF
    // =========================================================================

    private function buildPdfHtml(array $oferta, array $estudiantes): string
    {
        $pathUcla     = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
        $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
        $imgUcla      = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
        $imgMedicina  = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

        $diplomado  = htmlspecialchars($oferta['diplomado_nombre'] ?? '—');
        $cohorte    = htmlspecialchars($oferta['cohorte_nombre']   ?? '—');
        $grupos     = htmlspecialchars($oferta['grupos_nombre']    ?? '—');
        $notaMinima = (float) ($oferta['nota_minima'] ?? 15);
        $fechaCierre = !empty($oferta['fecha_cierre']) ? date('d/m/Y', strtotime($oferta['fecha_cierre'])) : '—';
        $fechaHoy   = date('d') . ' de ' . $this->getMes((int) date('m')) . ' de ' . date('Y');

        $aprobados  = count(array_filter($estudiantes, fn($e) => $e['aprobado']));
        $reprobados = count($estudiantes) - $aprobados;

        $filas = '';
        foreach ($estudiantes as $idx => $e) {
            $bg        = $idx % 2 === 0 ? '#ffffff' : '#f8f9fa';
            $resultado = $e['aprobado']
                ? '<span style="color:#085041;font-weight:bold">Aprobado</span>'
                : '<span style="color:#A32D2D;font-weight:bold">Reprobado</span>';
            $filas .= "<tr style='background:{$bg}'>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>" . ($idx + 1) . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6'>" . htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>" . htmlspecialchars($e['document_id']) . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>" . ($e['nota_teorica']  !== null ? (int)round((float)$e['nota_teorica'])  : '—') . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>" . ($e['nota_practica'] !== null ? (int)round((float)$e['nota_practica']) : '—') . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>" . ($e['nota_virtual']  !== null ? (int)round((float)$e['nota_virtual'])  : '—') . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center;font-weight:bold'>" . ($e['nota_final'] ?? '—') . "</td>
                <td style='padding:5px 8px;border:0.5px solid #dee2e6;text-align:center'>{$resultado}</td>
            </tr>";
        }

        return "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>
        <style>
          * { box-sizing:border-box; margin:0; padding:0; }
          body { font-family:Times-Roman; font-size:11pt; color:#212529; padding:1.5cm; }
          table { width:100%; border-collapse:collapse; }
          th { background:#533AB7; color:#fff; padding:7px 8px; font-size:10pt; text-align:left; }
          .info-cell { background:#f8f9fa; padding:7px 10px; border:0.5px solid #dee2e6; }
          .info-label { font-size:8pt; font-weight:bold; text-transform:uppercase; color:#6c757d; }
          .info-val { font-size:11pt; font-weight:bold; margin-top:2px; }
        </style></head><body>
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
          ACTA FINAL DE CALIFICACIONES
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
              <div class='info-cell'>
                <div class='info-label'>Resumen</div>
                <div class='info-val'>" . count($estudiantes) . " est. &nbsp;·&nbsp; {$aprobados} aprobados &nbsp;·&nbsp; {$reprobados} reprobados</div>
              </div>
            </td>
          </tr>
        </table>
        <table>
          <thead>
            <tr>
              <th style='width:5%;text-align:center'>#</th>
              <th style='width:28%'>Apellidos y Nombres</th>
              <th style='width:14%;text-align:center'>Cédula</th>
              <th style='width:10%;text-align:center'>Teórica</th>
              <th style='width:10%;text-align:center'>Práctica</th>
              <th style='width:10%;text-align:center'>Virtual</th>
              <th style='width:10%;text-align:center'>Final</th>
              <th style='width:13%;text-align:center'>Resultado</th>
            </tr>
          </thead>
          <tbody>{$filas}</tbody>
        </table>
        <div style='margin-top:10px;font-size:9pt;color:#555'>
          Nota mínima para aprobar: <strong>{$notaMinima}</strong> puntos &nbsp;·&nbsp; Fecha de cierre: <strong>{$fechaCierre}</strong>
        </div>
        <table style='margin-top:50px'>
          <tr>
            <td style='width:40%;text-align:center;border-top:1px solid #333;padding-top:8px'>
              <div style='font-weight:bold;font-size:10pt'>DR. RAFAEL CAMEJO</div>
              <div style='font-size:9pt;color:#555'>Coordinador General</div>
            </td>
            <td style='width:20%'></td>
            <td style='width:40%;text-align:center;border-top:1px solid #333;padding-top:8px'>
              <div style='font-weight:bold;font-size:10pt'>SECRETARÍA ACADÉMICA</div>
              <div style='font-size:9pt;color:#555'>Coordinación de Extensión</div>
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