<?php
/**
 * MÓDULO: PANEL GERENCIAL / REPORTE DE PAGOS
 * ARCHIVO: app/controllers/ManagerialPaymentsReportController.php
 * PROPÓSITO: Controlador maestro para reportes ejecutivos multipágina y matriz de recaudación.
 * VERSIÓN: 7.5.0 
 * LOGIC: Coordinación de Reporte Multipágina (Resumen, Matriz General y Segmentación por Diplomado).
 * FIX: Extracción de vista pura para PDF (Elimina el sidebar/layout del sistema en la exportación).
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ManagerialPaymentsReportModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Throwable;

final class ManagerialPaymentsReportController extends Controller
{
    private ManagerialPaymentsReportModel $reportModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Seguridad: Validación de sesión activa
        if (empty($_SESSION['user']['id'])) {
            $this->redirect('/login');
            exit;
        }

        // Seguridad: Blindaje de rol administrativo
        $userRole = strtoupper(trim($_SESSION['user']['role'] ?? ''));
        if ($userRole !== 'ADMIN' && $userRole !== 'ADMINISTRATOR') {
            $this->redirect('/dashboard');
            exit;
        }

        $this->reportModel = new ManagerialPaymentsReportModel();
    }

    /**
     * Carga la interfaz principal del reporte gerencial.
     */
    public function index(): void {
        if (ob_get_level() > 0) ob_clean(); 
        
        $this->view('managerial/payments_report/index', [
            'offerings' => $this->reportModel->getOfferingsList()
        ]);
    }

    /**
     * Procesa y retorna los datos para la matriz en formato JSON (AJAX).
     * Incluye el resumen por diplomado para la construcción de Hojas en Excel.
     */
    public function getReportData(): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $f = [
                'student'     => trim($_GET['student'] ?? ''),
                'offering_id' => trim($_GET['offering_id'] ?? 'ALL'),
                'group_id'    => trim($_GET['group_id'] ?? 'ALL'), // <-- Grupos 15/04/2026
                'status'      => trim($_GET['status'] ?? 'ALL') 
            ];

            $page   = (int)($_GET['page'] ?? 1);
            $limit  = 25; 
            $offset = ($page - 1) * $limit;

            // 1. Matriz Detallada (Segmentada para la Web)
            $data = $this->reportModel->getMatrixData($f, $limit, $offset);
            
            // 2. Resumen Ejecutivo (Para la Hoja 1 del Reporte / Excel)
            $diplomaSummary = $this->reportModel->getSummaryByDiploma($f);

            // 3. Totales Globales de Caja
            $totals = $this->reportModel->getGlobalTotals($f);
            
            // 4. Conteo para paginación
            $totalRecords = $this->reportModel->countMatrixTotal($f);

            echo json_encode([
                'ok' => true, 
                'data' => $data,
                'summary' => [
                    'total_records'    => $totalRecords,
                    'total_aprobado'   => $totals['total_aprobado'],
                    'total_compromiso' => $totals['total_compromiso'],
                    'total_general'    => $totals['total_general'],
                    'diploma_summary'  => $diplomaSummary, // Data para Hoja 1
                    'page'             => $page,
                    'pages'            => ceil($totalRecords / $limit)
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'ok' => false, 
                'message' => 'Fallo en obtención de datos: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Genera la exportación masiva a PDF con estructura de páginas separadas.
     * PÁGINA 1: Resumen por Diplomado.
     * PÁGINA 2: Matriz General de Estudiantes.
     * PÁGINA 3+: Un bloque por cada Diplomado.
     */
    public function exportPdf(): void {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $f = [
                'student'     => trim($_GET['student'] ?? ''),
                'offering_id' => trim($_GET['offering_id'] ?? 'ALL'),
                'status'      => trim($_GET['status'] ?? 'ALL')
            ];

            // A. DATA PÁGINA 1: Resumen Ejecutivo
            $summary = $this->reportModel->getSummaryByDiploma($f);

            // B. DATA PÁGINA 2: Matriz Completa (Sin paginación: limit -1)
            $fullMatrix = $this->reportModel->getMatrixData($f, -1);
            
            // C. DATA PÁGINA 3+: Agrupación por Diplomado
            $groupedData = [];
            foreach ($fullMatrix as $row) {
                $groupedData[$row['diplomado']][] = $row;
            }

            if (empty($fullMatrix)) {
                throw new Exception("No hay datos para exportar con los filtros aplicados.");
            }

            // Totales de pie de página
            $totals = $this->reportModel->getGlobalTotals($f);

            // Configuración Dompdf
            require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
            $options = new Options();
            $options->set('isRemoteEnabled', true); 
            $options->set('defaultFont', 'Helvetica');

            $dompdf = new Dompdf($options);
            $dompdf->setPaper('letter', 'landscape'); 

            ob_start();
            
            // CORRECCIÓN CLAVE: Puenteamos $this->view() para evitar cargar el sidebar web
            $viewPath = dirname(__DIR__) . '/views/managerial/payments_report/pdf_export.php';
            
            if (!file_exists($viewPath)) {
                throw new Exception("No se encontró la plantilla del PDF.");
            }

            // Extraemos las variables manualmente para inyectarlas en el archivo crudo
            $viewData = [
                'summary'     => $summary,
                'fullMatrix'  => $fullMatrix,
                'groupedData' => $groupedData,
                'totals'      => $totals,
                'filters'     => $f,
                'title'       => 'INFORME EJECUTIVO DE RECAUDACIÓN CONSOLIDADA'
            ];
            extract($viewData);

            // Importamos el archivo directamente. HTML puro, sin el Layout del sistema.
            require $viewPath; 

            $htmlContent = ob_get_clean();

            $dompdf->loadHtml($htmlContent);
            $dompdf->render();

            $dompdf->stream("Informe_Ejecutivo_" . date('Ymd_His') . ".pdf", [
                "Attachment" => false
            ]);
            exit;

        } catch (Throwable $e) {
            die("Error Crítico PDF: " . $e->getMessage());
        }
    }


/**
     * Retorna los grupos asociados a una oferta específica (Formato JSON blindado)
     */
    public function getGroupsByOffering(): void {
        // 1. Destruimos absolutamente cualquier HTML o espacio en blanco previo
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // 2. Forzamos la cabecera JSON
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $offeringId = (int)($_GET['offering_id'] ?? 0);
            
            if ($offeringId > 0) {
                // Llamamos al modelo
                $groups = $this->reportModel->getGroupsByOfferingId($offeringId);
                echo json_encode($groups, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([]);
            }
        } catch (\Throwable $e) {
            // Si el modelo falla por algo, capturamos el error en formato JSON
            http_response_code(500);
            echo json_encode([
                'error' => true, 
                'msg' => 'Error de servidor: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Redirección segura con detección de entorno.
     */
    protected function redirect(string $path): void {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $baseDir = str_replace('/index.php', '', $scriptName);
        $urlBase = (strpos($baseDir, 'public') === false) ? $baseDir . '/public' : $baseDir;
        
        header("Location: " . $urlBase . $path);
        exit;
    }
}