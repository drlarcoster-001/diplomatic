<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / ESTADO DE RESULTADOS
 * ARCHIVO: app/controllers/ManagerialEstadoResultadosController.php
 * PROPÓSITO: index() muestra el reporte de ingresos vs egresos con filtro
 *            por fechas. pdf() genera el reporte en PDF con DomPDF.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ManagerialEstadoResultadosController;
 *   $router->get('/managerial/estado-resultados',     [ManagerialEstadoResultadosController::class, 'index']);
 *   $router->get('/managerial/estado-resultados/pdf', [ManagerialEstadoResultadosController::class, 'pdf']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ManagerialEstadoResultadosModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class ManagerialEstadoResultadosController extends Controller
{
    private ManagerialEstadoResultadosModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new ManagerialEstadoResultadosModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $ingresos       = $this->model->getIngresos($desde, $hasta);
        $egresos        = $this->model->getEgresos($desde, $hasta);
        $totalIngreso   = (float) ($ingresos['total']    ?? 0);
        $totalNomina    = (float) ($egresos['nomina']    ?? 0);
        $totalProveedor = (float) ($egresos['proveedor'] ?? 0);
        $totalDirecta   = (float) ($egresos['directa']  ?? 0);
        $totalEgreso    = $totalNomina + $totalProveedor + $totalDirecta;
        $saldo          = $totalIngreso - $totalEgreso;

        $this->view('managerial/estado_resultados/index', [
            'desde'          => $desde,
            'hasta'          => $hasta,
            'ingresos'       => $ingresos,
            'egresos'        => $egresos,
            'totalIngreso'   => $totalIngreso,
            'totalNomina'    => $totalNomina,
            'totalProveedor' => $totalProveedor,
            'totalDirecta'   => $totalDirecta,
            'totalEgreso'    => $totalEgreso,
            'saldo'          => $saldo,
        ]);
    }

    // =========================================================================
    // PDF
    // =========================================================================

    public function pdf(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $desde          = $_GET['desde'] ?? date('Y-m-01');
            $hasta          = $_GET['hasta'] ?? date('Y-m-d');
            $ingresos       = $this->model->getIngresos($desde, $hasta);
            $egresos        = $this->model->getEgresos($desde, $hasta);
            $totalIngreso   = (float) ($ingresos['total']    ?? 0);
            $totalNomina    = (float) ($egresos['nomina']    ?? 0);
            $totalProveedor = (float) ($egresos['proveedor'] ?? 0);
            $totalDirecta   = (float) ($egresos['directa']  ?? 0);
            $totalEgreso    = $totalNomina + $totalProveedor + $totalDirecta;
            $saldo          = $totalIngreso - $totalEgreso;

            $pathUcla     = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
            $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
            $imgUcla      = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
            $imgMedicina  = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';
            $fechaHoy     = date('d') . ' de ' . $this->getMes((int)date('m')) . ' de ' . date('Y');
            $fDesde       = date('d/m/Y', strtotime($desde));
            $fHasta       = date('d/m/Y', strtotime($hasta));
            $colorSaldo   = $saldo >= 0 ? '#085041' : '#A32D2D';
            $signoSaldo   = $saldo >= 0 ? '+' : '-';
            $reversas     = (float) ($egresos['reversas'] ?? 0);

            $filaReversas = $reversas > 0
                ? "<tr style='background:#ffffff'>
                    <td style='padding:7px 12px;border-bottom:0.5px solid #dee2e6;color:#085041'>
                        <i>Reversas</i>
                    </td>
                    <td style='padding:7px 12px;border-bottom:0.5px solid #dee2e6;text-align:right;color:#085041;font-weight:bold'>
                        +\$" . number_format($reversas, 2) . "
                    </td>
                   </tr>"
                : '';

            $html = "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'>
            <style>
              * { box-sizing:border-box; margin:0; padding:0; }
              body { font-family:Times-Roman; font-size:11pt; color:#212529; padding:1.5cm; }
              table { width:100%; border-collapse:collapse; }
              .sec-titulo { font-size:11pt; font-weight:bold; padding:8px 12px; border-left:4px solid; margin:16px 0 0; }
              .sec-ingreso { background:#d1e7dd; color:#085041; border-color:#198754; }
              .sec-egreso  { background:#f8d7da; color:#842029; border-color:#dc3545; }
              .fila-item td { padding:7px 12px; border-bottom:0.5px solid #dee2e6; }
              .fila-total td { padding:8px 12px; font-weight:bold; background:#f8f9fa; border-top:2px solid #dee2e6; }
              .fila-saldo td { padding:12px 16px; font-weight:bold; font-size:13pt; border-top:3px solid #533AB7; }
            </style></head><body>
            <table style='margin-bottom:16px'>
              <tr>
                <td style='width:15%;text-align:left'>" . ($imgUcla ? "<img src='{$imgUcla}' style='width:60px'>" : '') . "</td>
                <td style='width:70%;text-align:center;font-weight:bold;font-size:10.5pt;line-height:1.5'>
                  UNIVERSIDAD CENTROCCIDENTAL &ldquo;LISANDRO ALVARADO&rdquo;<br>
                  DECANATO DE CIENCIAS DE LA SALUD<br>&ldquo;Dr. PABLO ACOSTA ORTIZ&rdquo;<br>
                  COORDINACION DE EXTENSION
                </td>
                <td style='width:15%;text-align:right'>" . ($imgMedicina ? "<img src='{$imgMedicina}' style='width:60px'>" : '') . "</td>
              </tr>
            </table>
            <div style='text-align:center;font-weight:bold;font-size:14pt;text-decoration:underline;margin:14px 0 4px'>
              ESTADO DE RESULTADOS
            </div>
            <div style='text-align:center;font-size:10pt;color:#666;margin-bottom:20px'>
              Período: {$fDesde} al {$fHasta}
            </div>
            <div class='sec-titulo sec-ingreso'>INGRESOS</div>
            <table>
              <tr class='fila-item'>
                <td>Pagos de Estudiantes</td>
                <td style='text-align:right;color:#085041;font-weight:bold'>+\$" . number_format($totalIngreso, 2) . "</td>
              </tr>
              <tr class='fila-total'>
                <td>TOTAL INGRESOS</td>
                <td style='text-align:right;color:#085041'>+\$" . number_format($totalIngreso, 2) . "</td>
              </tr>
            </table>
            <div class='sec-titulo sec-egreso'>EGRESOS</div>
            <table>
              <tr class='fila-item'>
                <td>Nómina</td>
                <td style='text-align:right;color:#A32D2D;font-weight:bold'>-\$" . number_format($totalNomina, 2) . "</td>
              </tr>
              <tr class='fila-item'>
                <td>Proveedores</td>
                <td style='text-align:right;color:#A32D2D;font-weight:bold'>-\$" . number_format($totalProveedor, 2) . "</td>
              </tr>
              <tr class='fila-item'>
                <td>Directa</td>
                <td style='text-align:right;color:#A32D2D;font-weight:bold'>-\$" . number_format($totalDirecta, 2) . "</td>
              </tr>
              {$filaReversas}
              <tr class='fila-total'>
                <td>TOTAL EGRESOS</td>
                <td style='text-align:right;color:#A32D2D'>-\$" . number_format($totalEgreso, 2) . "</td>
              </tr>
            </table>
            <table style='margin-top:8px'>
              <tr class='fila-saldo' style='background:#EEEDFE'>
                <td style='color:#3C3489'>SALDO DEL PERÍODO</td>
                <td style='text-align:right;color:{$colorSaldo};font-size:14pt'>{$signoSaldo}\$" . number_format(abs($saldo), 2) . "</td>
              </tr>
            </table>
            <div style='text-align:center;font-size:8pt;color:#888;margin-top:30px'>
              Generado en Barquisimeto, el {$fechaHoy} &nbsp;·&nbsp; Sistema DIPLOMATIC &copy; UCLA
            </div>
            </body></html>";

            require_once $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/tools/dompdf/autoload.inc.php';
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Times-Roman');
            $dompdf = new Dompdf($options);
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            $dompdf->stream('Estado_Resultados_' . date('Ymd') . '.pdf', ['Attachment' => false]);
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