<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / CONTROL ACADÉMICO
 * ARCHIVO: app/controllers/ManagerialAcademicControlController.php
 * PROPÓSITO: Controlador maestro para trazabilidad académica, gestión dinámica de grupos y exportación PDF.
 * VERSIÓN: 1.3.1 - Sincronización con Modelo v1.7.5: Inyección de estatus dinámicos (DB) a la vista.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ManagerialAcademicControlModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Throwable;

final class ManagerialAcademicControlController extends Controller
{
    private ManagerialAcademicControlModel $academicModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // 1. SEGURIDAD: Validación de sesión activa
        if (empty($_SESSION['user']['id'])) {
            $this->redirect('/login');
            exit;
        }

        // 2. SEGURIDAD: Blindaje de rol administrativo
        $userRole = strtoupper(trim($_SESSION['user']['role'] ?? ''));
        if ($userRole !== 'ADMIN' && $userRole !== 'ADMINISTRATOR') {
            $this->redirect('/dashboard');
            exit;
        }

        $this->academicModel = new ManagerialAcademicControlModel();
    }

    /**
     * Carga la interfaz principal.
     * EXTRA: Ahora solicita los estatus dinámicos de la BD para el filtro.
     */
    public function index(): void {
        if (ob_get_level() > 0) ob_clean(); 
        
        $this->view('managerial/academic_control/index', [
            'offerings'   => $this->academicModel->getOfferingsList(),
            'db_statuses' => $this->academicModel->getStudentStatuses() // <-- Inyección dinámica
        ]);
    }

    /**
     * Procesa y retorna los datos para la matriz en formato JSON (AJAX).
     */
    public function getEnrollmentData(): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $filters = [
                'student'            => trim($_GET['student'] ?? ''),
                'offering_id'        => trim($_GET['offering_id'] ?? 'ALL'),
                'group_id'           => trim($_GET['group_id'] ?? 'ALL'),
                'participant_status' => trim($_GET['participant_status'] ?? 'ALL')
            ];

            $page = (int)($_GET['page'] ?? 1);
            $limit = 25;
            $offset = ($page - 1) * $limit;

            $data = $this->academicModel->getEnrollmentTracking($filters, $limit, $offset);
            $totalRecords = $this->academicModel->countEnrollmentTracking($filters);

            echo json_encode([
                'ok' => true, 
                'data' => $data, 
                'pagination' => [
                    'total_records' => $totalRecords, 
                    'page' => $page, 
                    'pages' => ceil($totalRecords / $limit)
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
     * Genera la exportación masiva a PDF.
     * Utiliza el límite -1 que el Modelo v1.7.5 ya procesa para traer todo el universo.
     */
    public function exportPdf(): void {
        while (ob_get_level() > 0) ob_end_clean();

        try {
            $f = [
                'student'            => trim($_GET['student'] ?? ''),
                'offering_id'        => trim($_GET['offering_id'] ?? 'ALL'),
                'group_id'           => trim($_GET['group_id'] ?? 'ALL'),
                'participant_status' => trim($_GET['participant_status'] ?? 'ALL')
            ];

            // Carga de data sin límites (Modelo 1.7.5 omite LIMIT si recibe -1)
            $fullData = $this->academicModel->getEnrollmentTracking($f, -1);

            if (empty($fullData)) {
                throw new Exception("No hay registros con los filtros seleccionados.");
            }

            // Configuración Dompdf
            require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Helvetica');

            $dompdf = new Dompdf($options);
            $dompdf->setPaper('letter', 'landscape');

ob_start();
            $viewPath = dirname(__DIR__) . '/views/managerial/academic_control/pdf_export.php';
            
            if (!file_exists($viewPath)) {
                throw new Exception("No se encontró la plantilla de exportación.");
            }

            // 1. Definimos los datos en una variable para evitar el error del IDE
            $viewData = [
                'data'    => $fullData, 
                'filters' => $f, 
                'title'   => 'TRAZABILIDAD ACADÉMICA - REPORTE GERENCIAL'
            ];

            // 2. Ahora extraemos la variable, no el array literal
            extract($viewData); 

            require $viewPath;

            $htmlContent = ob_get_clean();
            $dompdf->loadHtml($htmlContent);
            $dompdf->render();

            $dompdf->stream("Reporte_Academico_" . date('Ymd') . ".pdf", ["Attachment" => false]);
            exit;

        } catch (Throwable $e) {
            die("Error Crítico PDF: " . $e->getMessage());
        }
    }

    /**
     * Retorna los grupos asociados a una oferta (JSON).
     */
    public function getGroups(): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $offeringId = (int)($_GET['offering_id'] ?? 0);
            $groups = $offeringId > 0 ? $this->academicModel->getGroupsByOffering($offeringId) : [];
            echo json_encode($groups, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Redirección segura compatible con /diplomatic/public/
     */
    protected function redirect(string $path): void {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $baseDir = str_replace('/index.php', '', $scriptName);
        $urlBase = (strpos($baseDir, 'public') === false) ? $baseDir . '/public' : $baseDir;
        
        header("Location: " . $urlBase . $path);
        exit;
    }
}