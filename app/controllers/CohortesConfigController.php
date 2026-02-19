<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/CohortesConfigController.php
 * Propósito: Controlador para la configuración avanzada de Cohortes (Estatus y Borrado Físico).
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CohortesConfigModel;
use App\Services\AuditService;

class CohortesConfigController extends Controller
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;
        
        // Bloqueo de seguridad: Solo ADMIN o OPERATOR
        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR'])) {
            header('Location: /diplomatic/public/academic');
            exit();
        }
        $this->model = new CohortesConfigModel();
    }

    public function index(): void
    {
        AuditService::log(['module' => 'COHORTES_CONFIG', 'action' => 'ACCESS', 'description' => 'Ingreso al panel de configuración avanzada de cohortes.']);
        $search = $_GET['search'] ?? '';
        $this->view('academic/cohortes_config/index', [
            'cohortes' => $this->model->getAll($search),
            'search'   => $search
        ]);
    }

    public function getDetails(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $cohort = $this->model->getById($id);
        header('Content-Type: application/json');
        echo json_encode(['ok' => ($cohort ? true : false), 'cohorte' => $cohort]);
        exit();
    }

    public function updateStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $status = $_POST['cohort_status'] ?? '';
        $cohort = $this->model->getById($id);

        if ($cohort && !empty($status)) {
            $this->model->forceUpdateStatus($id, $status, $_SESSION['user']['id']);
            AuditService::log([
                'module' => 'COHORTES_CONFIG', 
                'action' => 'STATUS_FORCED', 
                'description' => "Forzó el estado de la cohorte [{$cohort['cohort_code']}] a: $status (y restauró si estaba eliminada).",
                'entity_id' => $id,
                'event_type' => 'WARNING'
            ]);
        }
        header('Location: /diplomatic/public/academic/cohortes-config?updated=1');
        exit();
    }

    public function hardDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $cohort = $this->model->getById($id);

        if ($cohort) {
            try {
                $this->model->deletePhysically($id);
                AuditService::log([
                    'module' => 'COHORTES_CONFIG', 
                    'action' => 'HARD_DELETE', 
                    'description' => "Borrado FÍSICO de la base de datos de la cohorte: [{$cohort['cohort_code']}] {$cohort['name']}", 
                    'entity_id' => $id, 
                    'event_type' => 'SECURITY'
                ]);
                header('Location: /diplomatic/public/academic/cohortes-config?deleted=1');
                exit();
            } catch (\Exception $e) {
                // Si falla (Constraint Violation), significa que tiene movimientos (grupos, alumnos, pagos vinculados)
                AuditService::log([
                    'module' => 'COHORTES_CONFIG', 
                    'action' => 'HARD_DELETE_FAILED', 
                    'description' => "Intento fallido de borrado físico. La cohorte [{$cohort['cohort_code']}] tiene movimientos y dependencias.", 
                    'event_type' => 'ERROR'
                ]);
                header('Location: /diplomatic/public/academic/cohortes-config?error=has_movements');
                exit();
            }
        } else {
            header('Location: /diplomatic/public/academic/cohortes-config?error=1');
            exit();
        }
    }
}