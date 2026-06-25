<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / REPORTE DE PAGOS
 * ARCHIVO: app/controllers/ManagerialPagosReporteController.php
 * PROPÓSITO: index() muestra filtros en cascada y tabla de pagos validados.
 *            getDiplomados() devuelve ofertas (diplomado+grupo) por período.
 *            getUsuarios() devuelve usuarios por oferta.
 *            pdf() genera el reporte en PDF.
 * VERSIÓN: 1.2.0 - Cascada simplificada: Período → Oferta(Diplomado+Grupo) → Usuario.
 *                   Filtro de pagos por ao.id en lugar de diploma_id.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ManagerialPagosReporteController;
 *   $router->get('/managerial/pagos-reporte',            [ManagerialPagosReporteController::class, 'index']);
 *   $router->get('/managerial/pagos-reporte/diplomados', [ManagerialPagosReporteController::class, 'getDiplomados']);
 *   $router->get('/managerial/pagos-reporte/usuarios',   [ManagerialPagosReporteController::class, 'getUsuarios']);
 *   $router->get('/managerial/pagos-reporte/pdf',        [ManagerialPagosReporteController::class, 'pdf']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ManagerialPagosReporteModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class ManagerialPagosReporteController extends Controller
{
    private ManagerialPagosReporteModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new ManagerialPagosReporteModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $periodoId  = (int)  ($_GET['periodo_id']  ?? 0);
        $offeringId = (int)  ($_GET['offering_id'] ?? 0);
        $userId     = (int)  ($_GET['user_id']     ?? 0);
        $userSearch = trim(  $_GET['user_search']  ?? '');

        $periodos = $this->model->getPeriodos();
        $pagos    = [];
        $totales  = ['total_bs' => 0, 'total_usd' => 0, 'cantidad' => 0];

        if ($periodoId) {
            $pagos   = $this->model->getPagos($periodoId, $offeringId, $userId);
            $totales = $this->model->getTotales($pagos);
        }

        $this->view('managerial/pagos_reporte/index', [
            'periodos'   => $periodos,
            'periodoId'  => $periodoId,
            'offeringId' => $offeringId,
            'userId'     => $userId,
            'userSearch' => $userSearch,
            'pagos'      => $pagos,
            'totales'    => $totales,
        ]);
    }

    // =========================================================================
    // CASCADA — OFERTAS (Diplomado + Grupo) POR PERÍODO
    // =========================================================================

    public function getDiplomados(): void
    {
        $this->jsonResponse($this->model->getOfertasByPeriodo((int)($_GET['periodo_id'] ?? 0)));
    }

    // =========================================================================
    // CASCADA — USUARIOS POR OFERTA
    // =========================================================================

    public function getUsuarios(): void
    {
        $search = trim($_GET['search'] ?? '');
        $this->jsonResponse($this->model->getUsuariosByOferta(
            (int)($_GET['offering_id'] ?? 0),
            $search
        ));
    }

    // =========================================================================
    // PDF
    // =========================================================================

    public function pdf(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $periodoId  = (int) ($_GET['periodo_id']  ?? 0);
            $offeringId = (int) ($_GET['offering_id'] ?? 0);
            $userId     = (int) ($_GET['user_id']     ?? 0);

            $pagos   = $this->model->getPagos($periodoId, $offeringId, $userId);
            $totales = $this->model->getTotales($pagos);

            $pathUcla     = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
            $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
            $imgUcla      = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
            $imgMedicina  = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';
            $fechaHoy     = date('d') . ' de ' . $this->getMes((int)date('m')) . ' de ' . date('Y');

            $labelMethod = ['CASH' => 'Efectivo', 'ZELLE' => 'Zelle', 'BINANCE' => 'Binance', 'PAGOMOVIL' => 'Pago Móvil'];

            $filas = '';
            foreach ($pagos as $idx => $p) {
                $bg     = $idx % 2 === 0 ? '#ffffff' : '#f8f9fa';
                $nombre = htmlspecialchars($p['last_name'] . ', ' . $p['first_name']);
                $metodo = $labelMethod[$p['method']] ?? $p['method'];
                $fecha  = date('d/m/Y', strtotime($p['fecha_pago']));
                $ref    = htmlspecialchars($p['reference_id'] ?? '—');
                $monto  = number_format((float)$p['amount'], 2);
                $tasa   = $p['tasa_bcv'] ? number_format((float)$p['tasa_bcv'], 2) : '—';
                $usd    = $p['monto_usd'] ? '$' . number_format((float)$p['monto_usd'], 2) : '—';

                $filas .= "<tr style='background:{$bg}'>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:8pt'>{$nombre}</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:8pt;text-align:center'>{$fecha}</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:8pt'>{$metodo}</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:8pt'>{$ref}</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:8pt;text-align:right;font-weight:bold'>Bs. {$monto}</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:8pt;text-align:center'>{$tasa}</td>
                    <td style='padding:5px 6px;border:0.5px solid #dee2e6;font-size:8pt;text-align:right;font-weight:bold'>{$usd}</td>
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
                <td style='width:15%;text-align:left'>" . ($imgUcla ? "<img src='{$imgUcla}' style='width:55px'>" : '') . "</td>
                <td style='width:70%;text-align:center;font-weight:bold;font-size:10pt;line-height:1.5'>
                  UNIVERSIDAD CENTROCCIDENTAL &ldquo;LISANDRO ALVARADO&rdquo;<br>
                  DECANATO DE CIENCIAS DE LA SALUD<br>
                  &ldquo;Dr. PABLO ACOSTA ORTIZ&rdquo;<br>COORDINACION DE EXTENSION
                </td>
                <td style='width:15%;text-align:right'>" . ($imgMedicina ? "<img src='{$imgMedicina}' style='width:55px'>" : '') . "</td>
              </tr>
            </table>
            <div style='text-align:center;font-weight:bold;font-size:12pt;text-decoration:underline;margin:10px 0 6px'>
              REPORTE DE PAGOS
            </div>
            <div style='text-align:center;font-size:9pt;color:#666;margin-bottom:12px'>
              {$totales['cantidad']} pagos registrados
            </div>
            <table>
              <thead>
                <tr>
                  <th style='width:22%'>Estudiante</th>
                  <th style='width:10%;text-align:center'>Fecha</th>
                  <th style='width:12%'>Método</th>
                  <th style='width:18%'>Referencia</th>
                  <th style='width:14%;text-align:right'>Monto Bs.</th>
                  <th style='width:10%;text-align:center'>Tasa</th>
                  <th style='width:14%;text-align:right'>Monto USD</th>
                </tr>
              </thead>
              <tbody>{$filas}</tbody>
              <tfoot>
                <tr style='background:#EEEDFE;font-weight:bold'>
                  <td colspan='4' style='padding:6px 8px;border:0.5px solid #dee2e6;font-size:9pt;color:#3C3489'>
                    TOTAL ({$totales['cantidad']} pagos)
                  </td>
                  <td style='padding:6px 8px;border:0.5px solid #dee2e6;font-size:9pt;text-align:right;color:#3C3489'>
                    Bs. " . number_format($totales['total_bs'], 2) . "
                  </td>
                  <td style='padding:6px 8px;border:0.5px solid #dee2e6'></td>
                  <td style='padding:6px 8px;border:0.5px solid #dee2e6;font-size:9pt;text-align:right;color:#3C3489'>
                    \$" . number_format($totales['total_usd'], 2) . "
                  </td>
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
            $dompdf->stream('Reporte_Pagos_' . date('Ymd') . '.pdf', ['Attachment' => false]);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Error: ' . $e->getMessage();
        }
    }

    private function jsonResponse(array $data): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getMes(int $m): string
    {
        return ['','enero','febrero','marzo','abril','mayo','junio',
                'julio','agosto','septiembre','octubre','noviembre','diciembre'][$m] ?? '';
    }
}