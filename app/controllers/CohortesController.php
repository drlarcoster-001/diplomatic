<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/CohortesController.php
 * Propósito: Controlador para la administración de Cohortes Institucionales.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CohortesModel;
use App\Services\AuditService;

class CohortesController extends Controller
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;
        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR', 'ACADEMIC'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }
        $this->model = new CohortesModel();
    }

    public function index(): void
    {
        AuditService::log(['module' => 'COHORTES', 'action' => 'ACCESS_INDEX', 'description' => 'Ingreso al panel de cohortes.']);
        $search = $_GET['search'] ?? '';
        $this->view('academic/cohortes/index', [
            'cohortes' => $this->model->getAll($search),
            'search'   => $search
        ]);
    }

    public function logAccess(): void
    {
        $action = $_GET['action'] ?? 'UNKNOWN';
        $id = $_GET['id'] ?? null;
        AuditService::log([
            'module' => 'COHORTES', 'action' => $action, 
            'description' => "Interacción usuario: $action" . ($id ? " ID: $id" : "")
        ]);
        header('Content-Type: application/json');
        echo json_encode(['logged' => true]);
        exit();
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $_POST['created_by'] = $_SESSION['user']['id'];
        try {
            $id = $this->model->insert($_POST);
            AuditService::log(['module' => 'COHORTES', 'action' => 'CREATE_SUCCESS', 'description' => 'Creó: '.$_POST['name'], 'entity_id' => $id, 'event_type' => 'SUCCESS']);
            header('Location: /diplomatic/public/academic/cohortes?success=1');
            exit();
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/academic/cohortes?error=1');
            exit();
        }
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $dataBefore = $this->model->getById($id);
        
        // PROTECCIÓN CONTRA EDICIÓN DE COHORTES FINALIZADAS
        if ($dataBefore['cohort_status'] === 'Finalizada') {
            AuditService::log(['module' => 'COHORTES', 'action' => 'UPDATE_BLOCKED', 'description' => 'Intento editar cohorte finalizada: '.$dataBefore['name'], 'event_type' => 'WARNING']);
            header('Location: /diplomatic/public/academic/cohortes?error=cant_edit_finished');
            exit();
        }

        $_POST['updated_by'] = $_SESSION['user']['id'];

        if ($this->model->update($id, $_POST)) {
            AuditService::log(['module' => 'COHORTES', 'action' => 'UPDATE_SUCCESS', 'description' => 'Modificó: '.$_POST['name'], 'entity_id' => $id, 'data_before' => $dataBefore, 'event_type' => 'SUCCESS']);
        }
        header('Location: /diplomatic/public/academic/cohortes?updated=1');
        exit();
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $cohort = $this->model->getById($id);

        if ($cohort) {
            // Solo se borra si está Planificada.
            if ($cohort['cohort_status'] !== 'Planificada') {
                AuditService::log(['module' => 'COHORTES', 'action' => 'DELETE_BLOCKED', 'description' => 'Intento borrar cohorte activa: '.$cohort['name'], 'event_type' => 'WARNING']);
                header('Location: /diplomatic/public/academic/cohortes?error=cant_delete');
                exit();
            }

            $this->model->setInactive($id, $_SESSION['user']['id']);
            AuditService::log(['module' => 'COHORTES', 'action' => 'DELETE_SUCCESS', 'description' => 'Inactivó: '.$cohort['name'], 'entity_id' => $id, 'event_type' => 'WARNING']);
        }
        header('Location: /diplomatic/public/academic/cohortes?deleted=1');
        exit();
    }

    public function create(): void { $this->logAccess(); }

    public function getDetails(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $cohort = $this->model->getById($id);
        echo json_encode(['ok' => ($cohort ? true : false), 'cohorte' => $cohort]);
        exit();
    }

    public function changeStatus(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $status = $_GET['status'] ?? '';
        $this->model->updateStatus($id, $status, $_SESSION['user']['id']);
        AuditService::log(['module' => 'COHORTES', 'action' => 'STATUS_CHANGE', 'description' => "Cambio estado a $status ID:$id"]);
        header('Location: /diplomatic/public/academic/cohortes?updated=1');
        exit();
    }
}