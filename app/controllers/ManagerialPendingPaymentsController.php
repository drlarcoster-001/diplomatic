<?php
/**
 * MÓDULO: PANEL GERENCIAL / PAGOS PENDIENTES
 * ARCHIVO: app/controllers/ManagerialPendingPaymentsController.php
 * PROPÓSITO: Controlador para auditar y listar pagos de inscripciones y cuotas en estatus PENDING.
 * VERSIÓN: 1.1.0 - Adición de exportación PDF standalone (sin sidebar) y agrupación de data en tránsito.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ManagerialPendingPaymentsModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Throwable;

final class ManagerialPendingPaymentsController extends Controller
{
    private ManagerialPendingPaymentsModel $pendingModel;

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

        $this->pendingModel = new ManagerialPendingPaymentsModel();
    }

    /**
     * Carga la interfaz principal del módulo de pagos pendientes.
     */
    public function index(): void {
        if (ob_get_level() > 0) ob_clean(); 
        
        $this->view('managerial/pending_payments/index', [
            'offerings' => $this->pendingModel->getOfferingsList()
        ]);
    }

    /**
     * Procesa y retorna los datos de pagos pendientes en formato JSON (AJAX).
     */
    public function getPendingData(): void {
        // Blindaje de búfer para evitar el error "Unexpected token <" en JSON
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $filters = [
                'student'     => trim($_GET['student'] ?? ''),
                'offering_id' => trim($_GET['offering_id'] ?? 'ALL'),
                'origin'      => trim($_GET['origin'] ?? 'ALL') // ALL, INSCRIPTION, INSTALLMENT
            ];

            $page   = (int)($_GET['page'] ?? 1);
            $limit  = 25; 
            $offset = ($page - 1) * $limit;

            $data = $this->pendingModel->getPendingPayments($filters, $limit, $offset);
            $totalRecords = $this->pendingModel->countPendingPayments($filters);

            echo json_encode([
                'ok' => true, 
                'data' => $data,
                'pagination' => [
                    'total_records' => $totalRecords,
                    'page'          => $page,
                    'pages'         => ceil($totalRecords / $limit)
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'ok' => false, 
                'message' => 'Error al obtener pagos pendientes: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Genera la exportación a PDF de la data en tránsito.
     * Puntea la vista del sistema para generar el documento limpio.
     */
    public function exportPdf(): void {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $filters = [
                'student'     => trim($_GET['student'] ?? ''),
                'offering_id' => trim($_GET['offering_id'] ?? 'ALL'),
                'origin'      => trim($_GET['origin'] ?? 'ALL')
            ];

            // Obtenemos toda la data sin límite para exportar
            $fullData = $this->pendingModel->getPendingPayments($filters, -1);
            
            if (empty($fullData)) {
                throw new Exception("No hay datos en tránsito para exportar con estos filtros.");
            }

            // Agrupamos la data y calculamos el total general
            $groupedData = [];
            $totalPendingUsd = 0.0;

            foreach ($fullData as $row) {
                $groupedData[$row['diplomado']][] = $row;
                $totalPendingUsd += (float)$row['monto_usd'];
            }

            // Configuración Dompdf
            require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
            $options = new Options();
            $options->set('isRemoteEnabled', true); 
            $options->set('defaultFont', 'Helvetica');

            $dompdf = new Dompdf($options);
            $dompdf->setPaper('letter', 'landscape'); 

            ob_start();
            
            // Bypass de $this->view() para evitar el layout
            $viewPath = dirname(__DIR__) . '/views/managerial/pending_payments/pdf_export.php';
            
            if (!file_exists($viewPath)) {
                throw new Exception("No se encontró la plantilla del PDF.");
            }

            extract([
                'fullData'        => $fullData,
                'groupedData'     => $groupedData,
                'totalPendingUsd' => $totalPendingUsd
            ]);

            require $viewPath; 

            $htmlContent = ob_get_clean();

            $dompdf->loadHtml($htmlContent);
            $dompdf->render();

            $dompdf->stream("Auditoria_Pagos_Transito_" . date('Ymd_His') . ".pdf", [
                "Attachment" => false
            ]);
            exit;

        } catch (Throwable $e) {
            die("Error Crítico PDF: " . $e->getMessage());
        }
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