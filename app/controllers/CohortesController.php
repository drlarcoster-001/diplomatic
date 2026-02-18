<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/CohortesController.php
 * Propósito: Controlador para la administración de Cohortes Institucionales con auditoría descriptiva.
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
        AuditService::log(['module' => 'COHORTES', 'action' => 'ACCESS_INDEX', 'description' => 'El usuario ingresó al panel maestro de cohortes.']);
        $search = $_GET['search'] ?? '';
        $this->view('academic/cohortes/index', [
            'cohortes' => $this->model->getAll($search),
            'search'   => $search
        ]);
    }

    /**
     * Registra eventos originados desde el cliente (Ver, Abrir Formulario)
     */
    public function logAccess(): void
    {
        $action = $_GET['action'] ?? 'UNKNOWN';
        $id = (int)($_GET['id'] ?? 0);
        $cohort = ($id > 0) ? $this->model->getById($id) : null;
        
        $identificador = $cohort ? "[{$cohort['cohort_code']}] {$cohort['name']}" : "NUEVO REGISTRO";
        
        $desc = match($action) {
            'VIEW_DETAILS' => "Visualizó la ficha técnica de la cohorte: $identificador",
            'CREATE_FORM'  => "Abrió el formulario para crear una nueva cohorte.",
            'EDIT_FORM'    => "Abrió el formulario de edición para: $identificador",
            'DELETE_ATTEMPT' => "Inició proceso de eliminación para: $identificador",
            default => "Interacción con el módulo de cohortes: $action"
        };

        AuditService::log([
            'module' => 'COHORTES', 
            'action' => $action, 
            'description' => $desc,
            'entity_id' => $id ?: null
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
            AuditService::log([
                'module' => 'COHORTES', 
                'action' => 'CREATE_SUCCESS', 
                'description' => "Creó exitosamente la cohorte: [{$_POST['cohort_code']}] {$_POST['name']}", 
                'entity_id' => $id, 
                'event_type' => 'SUCCESS'
            ]);
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
        
        if ($dataBefore['cohort_status'] === 'Finalizada') {
            header('Location: /diplomatic/public/academic/cohortes?error=cant_edit_finished');
            exit();
        }

        $_POST['updated_by'] = $_SESSION['user']['id'];
        if ($this->model->update($id, $_POST)) {
            AuditService::log([
                'module' => 'COHORTES', 
                'action' => 'UPDATE_SUCCESS', 
                'description' => "Modificó datos de la cohorte: [{$dataBefore['cohort_code']}] {$dataBefore['name']}", 
                'entity_id' => $id, 
                'data_before' => $dataBefore, 
                'event_type' => 'SUCCESS'
            ]);
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
            if ($cohort['cohort_status'] !== 'Planificada') {
                header('Location: /diplomatic/public/academic/cohortes?error=cant_delete');
                exit();
            }

            $this->model->setInactive($id, $_SESSION['user']['id']);
            AuditService::log([
                'module' => 'COHORTES', 
                'action' => 'DELETE_SUCCESS', 
                'description' => "Inactivó (eliminó) la cohorte: [{$cohort['cohort_code']}] {$cohort['name']}", 
                'entity_id' => $id, 
                'event_type' => 'WARNING'
            ]);
        }
        header('Location: /diplomatic/public/academic/cohortes?deleted=1');
        exit();
    }

    public function changeStatus(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $status = $_GET['status'] ?? '';
        $cohort = $this->model->getById($id);

        if ($cohort && !empty($status)) {
            $this->model->updateStatus($id, $status, $_SESSION['user']['id']);
            AuditService::log([
                'module' => 'COHORTES', 
                'action' => 'STATUS_CHANGE', 
                'description' => "Cambió el estado de la cohorte [{$cohort['cohort_code']}] a: $status",
                'entity_id' => $id
            ]);
        }
        header('Location: /diplomatic/public/academic/cohortes?updated=1');
        exit();
    }

    public function getDetails(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $cohort = $this->model->getById($id);
        header('Content-Type: application/json');
        echo json_encode(['ok' => ($cohort ? true : false), 'cohorte' => $cohort]);
        exit();
    }
}