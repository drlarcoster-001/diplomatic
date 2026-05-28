<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/Controllers/AdministrativeInscriptionsController.php
 * PROPÓSITO: Controlador Orquestador. Maneja el catálogo, el Wizard y consultas financieras dinámicas.
 * VERSIÓN: 3.3.1 - FIX: Inyección de tasa de cambio dinámica (tasaDelDia) a la vista del Wizard y blindaje de búfer.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdministrativeInscriptionsModel;

final class AdministrativeInscriptionsController extends Controller
{
    private AdministrativeInscriptionsModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (empty($_SESSION['user']['id'])) $this->redirect('/login');
        
        $role = strtoupper($_SESSION['user']['role'] ?? '');
        $authorizedRoles = ['ADMIN', 'SUPERADMIN', 'ADMINISTRADOR', 'OPERATOR'];
        
        if (!in_array($role, $authorizedRoles)) {
            $this->redirect('/dashboard');
        }

        $this->model = new AdministrativeInscriptionsModel();
    }

    public function index(): void
    {
        $offerings = $this->model->getOpenOfferings();

        $this->view('administrative/inscriptions/index', [
            'title'         => 'Catálogo de Ofertas Académicas',
            'openOfferings' => $offerings,
            'breadcrumbs'   => [
                ['label' => 'Inicio', 'url' => '/dashboard'],
                ['label' => 'Panel Administrativo', 'url' => '/administrative'],
                ['label' => 'Catálogo de Inscripciones', 'url' => null]
            ]
        ]);
    }

    public function create(): void
    {
        $offeringId = (int)($_GET['offering_id'] ?? 0);
        
        if ($offeringId <= 0) {
            $this->redirect('/administrative/inscriptions');
        }

        // --- INYECCIÓN DE TASA DE CAMBIO DINÁMICA ---
        // Se obtiene la tasa real desde la base de datos usando el modelo
        $today = date('Y-m-d');
        $rateRow = $this->model->getEffectiveRate($today);
        if (!$rateRow) {
            $this->redirect('/administrative/inscriptions?error=sin_tasa');
        }
        $tasaActiva = (float)$rateRow['dolar_bcv'];

        $this->view('administrative/inscriptions/create', [
            'title'         => 'Asistente de Inscripción Manual',
            'offering_id'   => $offeringId,
            'tasaDelDia'    => $tasaActiva, // <- Variable enviada a la vista (create.php)
            'breadcrumbs'   => [
                ['label' => 'Inicio', 'url' => '/dashboard'],
                ['label' => 'Panel Administrativo', 'url' => '/administrative'],
                ['label' => 'Inscripciones', 'url' => '/administrative/inscriptions'],
                ['label' => 'Nueva Inscripción', 'url' => null]
            ]
        ]);
    }

    /**
     * Verifica si un estudiante ya está inscrito.
     * El modelo debe filtrar para NO contar los estados 'RECHAZADO'.
     */
    public function checkExisting(): void
    {
        try {
            $userId = (int)($_GET['user_id'] ?? 0);
            $offeringId = (int)($_GET['offering_id'] ?? 0);

            if ($userId <= 0 || $offeringId <= 0) {
                throw new \Exception("Faltan parámetros de validación.");
            }

            $exists = $this->model->checkExistingEnrollment($userId, $offeringId);

            $this->sendJson([
                'success' => true,
                'exists'  => $exists,
                'message' => $exists ? 'El estudiante ya posee una inscripción activa o pendiente.' : 'Disponible.'
            ]);

        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * RECHAZO ADMINISTRATIVO (Endpoint para el botón de rechazo)
     * Sincroniza tbl_enrollments (RECHAZADO) y tbl_enrollments_payments (REJECTED).
     */
    public function reject(): void
    {
        try {
            $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
            $reason = trim((string)($_POST['reason'] ?? 'Rechazo administrativo.'));

            if ($enrollmentId <= 0) throw new \Exception("ID de inscripción no válido.");

            // Esta función en el modelo debe ejecutar el UPDATE dual
            $success = $this->model->rejectEnrollmentFull($enrollmentId, $reason);

            $this->sendJson([
                'success' => $success,
                'message' => $success ? 'Inscripción rechazada con éxito. El usuario ha sido liberado.' : 'Error al procesar el rechazo.'
            ]);

        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(): void
    {
        try {
            $rawMetadata = $_POST['payment_metadata'] ?? '{}';
            $metaObj = json_decode((string)$rawMetadata, true);
            
            $metaMethod = trim((string)($metaObj['metodo'] ?? ''));
            $postMethod = trim((string)($_POST['payment_method_type'] ?? ''));

            $method = strtoupper($metaMethod ?: ($postMethod ?: 'CASH'));
            
            $s4Handler = new \App\Controllers\AdministrativeInscriptionsController_s4();
            $paymentData = $s4Handler->sanitizePaymentData($_POST, $method);

            $enrollmentStatus = ($method === 'CASH') ? 'COMPROMISO' : 'REVISION';

            $enrollData = [
                'user_id'              => (int)($_POST['user_id'] ?? 0),
                'offering_id'          => (int)($_POST['offering_id'] ?? 0),
                'undergraduate_degree' => $_POST['undergraduate_degree'] ?? 'N/A',
                'provenance'           => $_POST['provenance'] ?? 'N/A',
                'status'               => $enrollmentStatus, 
                'created_by'           => $_SESSION['user']['id']
            ];

            $cleanPath = function($path) {
                if (!$path) return null;
                return str_replace(['public/', 'public\\'], ['', ''], (string)$path);
            };

            $documents = [
                'ID_CARD' => $cleanPath($_POST['doc_id'] ?? null),
                'DEGREE'  => $cleanPath($_POST['doc_degree'] ?? null),
                'CV'      => $cleanPath($_POST['doc_cv'] ?? null),
                'PAYMENT' => $cleanPath($_POST['pay_screenshot'] ?? null)
            ];

            $enrollmentId = $this->model->executeEnrollment($enrollData, $documents, $paymentData);

            if ($enrollmentId > 0) {
                $this->sendJson([
                    'success' => true,
                    'enroll_id' => $enrollmentId,
                    'message' => '¡Inscripción administrativa procesada!'
                ]);
            } else {
                throw new \Exception("Error en base de datos al ejecutar executeEnrollment.");
            }

        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getPaymentPlan(): void
    {
        try {
            $offeringId = (int)($_REQUEST['offering_id'] ?? 0);
            if ($offeringId <= 0) throw new \Exception("ID no válido.");

            $plan = $this->model->getOfferingPaymentPlan($offeringId);
            $this->sendJson(['success' => true, 'plan' => $plan ?? []]);
        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function sendJson(array $data, int $code = 200): void
    {
        while (ob_get_level()) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}