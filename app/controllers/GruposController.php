<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/GruposController.php
 * Propósito: Controlador para la administración del catálogo maestro de Grupos.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\GruposModel;
use App\Services\AuditService;

class GruposController extends Controller
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
        $this->model = new GruposModel();
    }

    public function index(): void
    {
        AuditService::log(['module' => 'GRUPOS', 'action' => 'ACCESS_INDEX', 'description' => 'Ingresó al módulo maestro de Grupos.']);
        $search = $_GET['search'] ?? '';
        $this->view('academic/grupos/index', [
            'grupos' => $this->model->getAll($search),
            'search' => $search
        ]);
    }

    public function logAccess(): void
    {
        $action = $_GET['action'] ?? 'UNKNOWN';
        $id = (int)($_GET['id'] ?? 0);
        $grupo = ($id > 0) ? $this->model->getById($id) : null;
        
        $identificador = $grupo ? $grupo['name'] : "NUEVO GRUPO";
        $desc = match($action) {
            'CREATE_FORM'  => "Abrió el formulario para crear un nuevo grupo.",
            'EDIT_FORM'    => "Abrió el formulario de edición para el grupo: $identificador",
            'DELETE_ATTEMPT' => "Inició proceso de eliminación para: $identificador",
            default => "Acción en grupos: $action"
        };

        AuditService::log(['module' => 'GRUPOS', 'action' => $action, 'description' => $desc, 'entity_id' => $id ?: null]);
        header('Content-Type: application/json');
        echo json_encode(['logged' => true]);
        exit();
    }

    public function getDetails(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $grupo = $this->model->getById($id);
        header('Content-Type: application/json');
        echo json_encode(['ok' => ($grupo ? true : false), 'grupo' => $grupo]);
        exit();
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $_POST['created_by'] = $_SESSION['user']['id'];
        try {
            $id = $this->model->insert($_POST);
            AuditService::log([
                'module' => 'GRUPOS', 'action' => 'CREATE_SUCCESS', 
                'description' => "Creó el grupo: {$_POST['name']} ({$_POST['modality']})", 
                'entity_id' => $id, 'event_type' => 'SUCCESS'
            ]);
            header('Location: /diplomatic/public/academic/grupos?success=1');
            exit();
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/academic/grupos?error=1');
            exit();
        }
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $dataBefore = $this->model->getById($id);
        $_POST['updated_by'] = $_SESSION['user']['id'];
        
        if ($this->model->update($id, $_POST)) {
            AuditService::log([
                'module' => 'GRUPOS', 'action' => 'UPDATE_SUCCESS', 
                'description' => "Modificó el grupo: {$dataBefore['name']}", 
                'entity_id' => $id, 'event_type' => 'SUCCESS'
            ]);
        }
        header('Location: /diplomatic/public/academic/grupos?updated=1');
        exit();
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $grupo = $this->model->getById($id);

        if ($grupo) {
            $this->model->setInactive($id, $_SESSION['user']['id']);
            AuditService::log([
                'module' => 'GRUPOS', 'action' => 'DELETE_SUCCESS', 
                'description' => "Eliminó el grupo: {$grupo['name']}", 
                'entity_id' => $id, 'event_type' => 'WARNING'
            ]);
        }
        header('Location: /diplomatic/public/academic/grupos?deleted=1');
        exit();
    }
}