<?php
/**
 * MÓDULO: FINANCIERO / LIBRO DE EGRESOS
 * ARCHIVO: app/controllers/FinancialLibroEgresosController.php
 * PROPÓSITO: index() lista registros con filtros. pdf() genera reporte PDF
 *            con los filtros activos usando DomPDF.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\FinancialLibroEgresosController;
 *   $router->get('/financial/libro-egresos',      [FinancialLibroEgresosController::class, 'index']);
 *   $router->get('/financial/libro-egresos/pdf',  [FinancialLibroEgresosController::class, 'pdf']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialLibroEgresosModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class FinancialLibroEgresosController extends Controller
{
    private FinancialLibroEgresosModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $allowed = ['ADMIN', 'FINANZAS'];
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed, true)) {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new FinancialLibroEgresosModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $filtros = [
            'desde'     => $_GET['desde']     ?? '',
            'hasta'     => $_GET['hasta']     ?? '',
            'tipo'      => $_GET['tipo']      ?? '',
            'movimiento'=> $_GET['movimiento']?? '',
            'search'    => trim($_GET['search'] ?? ''),
        ];

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;
        $total   = $this->model->countEgresos($filtros);
        $pages   = (int) ceil($total / $perPage);
        $egresos = $this->model->getEgresos($filtros, $page, $perPage);
        $totales = $this->model->getTotales($filtros);

        $this->view('financial/libro_egresos/index', [
            'egresos' => $egresos,
            'filtros' => $filtros,
            'totales' => $totales,
            'page'    => $page,
            'pages'   => $pages,
            'total'   => $total,
            'perPage' => $perPage,
        ]);
    }

    // =========================================================================
    // PDF
    // =========================================================================

    public function pdf(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $filtros = [
                'desde'     => $_GET['desde']     ?? '',
                'hasta'     => $_GET['hasta']     ?? '',
                'tipo'      => $_GET['tipo']      ?? '',
                'movimiento'=> $_GET['movimiento']?? '',
                'search'    => trim($_GET['search'] ?? ''),
            ];

            $egresos = $this->model->getAllEgresos($filtros);
            $totales = $this->model->getTotales($filtros);

            $pathUcla     = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
            $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
            $imgUcla      = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
            $imgMedicina  = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';

            $fechaHoy    = date('d') . ' de ' . $this->getMes((int)date('m')) . ' de ' . date('Y');
            $labelTipos  = ['NOMINA' => 'Nómina', 'PROVEEDOR' => 'Proveedor', 'DIRECTA' => 'Directa'];

            // Filtro label
            $filtroTexto = [];
            if ($filtros['desde'])     $filtroTexto[] = 'Desde: ' . $filtros['desde'];
            if ($filtros['hasta'])     $filtroTexto[] = 'Hasta: ' . $filtros['hasta'];
            if ($filtros['tipo'])      $filtroTexto[] = 'Tipo: ' . ($labelTipos[$filtros['tipo']] ?? $filtros['tipo']);
            if ($filtros['movimiento']) $filtroTexto[] = 'Movimiento: ' . $filtros['movimiento'];
            $filtroLabel = !empty($filtroTexto) ? implode(' · ', $filtroTexto) : 'Sin filtros aplicados';

            $filas = '';
            foreach ($egresos as $idx => $e) {
                $bg  = $idx % 2 === 0 ? '#ffffff' : '#f8f9fa';
                $esPago = $e['tipo_movimiento'] === 'PAGO';
                $colorMov = $esPago ? '#A32D2D' : '#085041';
                $signo    = $esPago ? '-' : '+';
                $filas .= "<tr style='background:{$bg}'>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:9pt'>" . date('d/m/Y', strtotime($e['fecha'])) . "</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:9pt'>" . htmlspecialchars($e['numero_orden'] ?? '—') . "</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:9pt'>" . ($labelTipos[$e['tipo']] ?? $e['tipo']) . "</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:8pt'>" . htmlspecialchars($e['concepto'] ?? '—') . "</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:8pt'>" . htmlspecialchars($e['destinatario'] ?? '—') . "</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:9pt;text-align:right;color:{$colorMov};font-weight:bold'>{$signo}$" . number_format(abs((float)$e['monto_usd']), 2) . "</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:9pt;text-align:center'>" . number_format((float)$e['tasa_bcv'], 2) . "</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:9pt;text-align:right;color:{$colorMov};font-weight:bold'>{$signo}Bs. " . number_format(abs((float)$e['monto_bs']), 2) . "</td>
                </tr>";
            }

            $totalPagosUsd   = number_format((float)($totales['total_pagos_usd']   ?? 0), 2);
            $totalReversasUsd = number_format((float)($totales['total_reversas_usd'] ?? 0), 2);
            $netoUsd = number_format((float)($totales['total_pagos_usd'] ?? 0) - (float)($totales['total_reversas_usd'] ?? 0), 2);

            $html = "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>
            <style>
              * { box-sizing:border-box; margin:0; padding:0; }
              body { font-family:Times-Roman; font-size:10pt; color:#212529; padding:1.2cm; }
              table { width:100%; border-collapse:collapse; }
              th { background:#533AB7; color:#fff; padding:6px 8px; font-size:9pt; text-align:left; }
            </style></head><body>
            <table style='margin-bottom:14px'>
              <tr>
                <td style='width:15%;text-align:left'>" . ($imgUcla ? "<img src='{$imgUcla}' style='width:55px'>" : '') . "</td>
                <td style='width:70%;text-align:center;font-weight:bold;font-size:10pt;line-height:1.5'>
                  UNIVERSIDAD CENTROCCIDENTAL &ldquo;LISANDRO ALVARADO&rdquo;<br>
                  DECANATO DE CIENCIAS DE LA SALUD<br>
                  &ldquo;Dr. PABLO ACOSTA ORTIZ&rdquo;<br>
                  COORDINACION DE EXTENSION
                </td>
                <td style='width:15%;text-align:right'>" . ($imgMedicina ? "<img src='{$imgMedicina}' style='width:55px'>" : '') . "</td>
              </tr>
            </table>
            <div style='text-align:center;font-weight:bold;font-size:12pt;text-decoration:underline;margin:10px 0 6px'>
              LIBRO DE EGRESOS
            </div>
            <div style='text-align:center;font-size:9pt;color:#666;margin-bottom:12px'>{$filtroLabel}</div>
            <table>
              <thead>
                <tr>
                  <th style='width:9%'>Fecha</th>
                  <th style='width:11%'>Orden</th>
                  <th style='width:9%'>Tipo</th>
                  <th style='width:22%'>Concepto</th>
                  <th style='width:18%'>Destinatario</th>
                  <th style='width:10%;text-align:right'>Monto USD</th>
                  <th style='width:8%;text-align:center'>Tasa</th>
                  <th style='width:13%;text-align:right'>Monto Bs.</th>
                </tr>
              </thead>
              <tbody>{$filas}</tbody>
              <tfoot>
                <tr style='background:#f8f9fa;font-weight:bold'>
                  <td colspan='5' style='padding:6px 8px;border:0.5px solid #dee2e6;font-size:9pt'>TOTAL EGRESOS</td>
                  <td style='padding:6px 8px;border:0.5px solid #dee2e6;font-size:9pt;text-align:right;color:#A32D2D'>-\${$totalPagosUsd}</td>
                  <td style='padding:6px 8px;border:0.5px solid #dee2e6'></td>
                  <td style='padding:6px 8px;border:0.5px solid #dee2e6;font-size:9pt;text-align:right;color:#A32D2D'>Reversas: +\${$totalReversasUsd}</td>
                </tr>
                <tr style='background:#EEEDFE;font-weight:bold'>
                  <td colspan='5' style='padding:6px 8px;border:0.5px solid #dee2e6;font-size:9pt;color:#3C3489'>NETO EGRESOS</td>
                  <td colspan='3' style='padding:6px 8px;border:0.5px solid #dee2e6;font-size:10pt;text-align:right;color:#3C3489'>-\${$netoUsd}</td>
                </tr>
              </tfoot>
            </table>
            <div style='text-align:center;font-size:8pt;color:#888;margin-top:16px'>
              Generado en Barquisimeto, el {$fechaHoy} &nbsp;·&nbsp; Sistema DIPLOMATIC &copy; UCLA
            </div>
            </body></html>";

            require_once $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/tools/dompdf/autoload.inc.php';
            $opts = new Options();
            $opts->set('isRemoteEnabled', true);
            $opts->set('defaultFont', 'Times-Roman');
            $dompdf = new Dompdf($opts);
            $dompdf->setPaper('letter', 'landscape');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            $dompdf->stream('Libro_Egresos_' . date('Ymd') . '.pdf', ['Attachment' => false]);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Error: ' . $e->getMessage();
        }
    }

    private function getMes(int $m): string
    {
        return ['','enero','febrero','marzo','abril','mayo','junio',
                'julio','agosto','septiembre','octubre','noviembre','diciembre'][$m] ?? '';
    }
}