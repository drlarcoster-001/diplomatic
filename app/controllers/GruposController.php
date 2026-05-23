<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/GruposController.php
 * Propósito: Controlador para la administración de Grupos con eliminación inteligente y auditoría de eventos.
 * Version: 1.2.0 - Implementación de borrado físico condicional y registro de eventos.
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
        
        // Seguridad institucional
        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR', 'ACADEMIC'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }
        $this->model = new GruposModel();
    }

    /**
     * Listado maestro de grupos.
     */
    public function index(): void
    {
        // Registro de acceso al módulo
        AuditService::log([
            'module' => 'ACADEMIC_GRUPOS', 
            'action' => 'ACCESS', 
            'description' => 'Ingreso al listado maestro de grupos académicos.'
        ]);

        $search = $_GET['search'] ?? '';
        $this->view('academic/grupos/index', [
            'grupos' => $this->model->getAll($search),
            'search' => $search
        ]);
    }

    /**
     * Registro de logs de interfaz (Modales/Botones).
     */
    public function logAccess(): void
    {
        $action = $_GET['action'] ?? 'UNKNOWN';
        $id = (int)($_GET['id'] ?? 0);
        $grupo = ($id > 0) ? $this->model->getById($id) : null;
        
        $identificador = $grupo ? $grupo['name'] : "NUEVO GRUPO";
        $desc = match($action) {
            'CREATE_FORM'    => "Abrió el formulario para crear un nuevo grupo.",
            'EDIT_FORM'      => "Abrió el formulario de edición para el grupo: $identificador",
            'DELETE_ATTEMPT' => "Inició proceso de eliminación para: $identificador",
            default          => "Acción en grupos: $action"
        };

        AuditService::log([
            'module' => 'ACADEMIC_GRUPOS', 
            'action' => $action, 
            'description' => $desc, 
            'entity_id' => $id ?: null
        ]);

        header('Content-Type: application/json');
        echo json_encode(['logged' => true]);
        exit();
    }

    /**
     * Procesa la inserción de un nuevo grupo.
     */
    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $_POST['created_by'] = $_SESSION['user']['id'];
        try {
            $id = $this->model->insert($_POST);
            
            AuditService::log([
                'module' => 'ACADEMIC_GRUPOS', 
                'action' => 'CREATE_SUCCESS', 
                'description' => "Creó el grupo académico: {$_POST['name']}", 
                'entity_id' => $id, 
                'event_type' => 'SUCCESS'
            ]);

            header('Location: /diplomatic/public/academic/grupos?success=1');
            exit();
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/academic/grupos?error=1');
            exit();
        }
    }

    /**
     * Procesa la edición de un grupo.
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = (int)$_POST['id'];
        $_POST['updated_by'] = $_SESSION['user']['id'];
        
        if ($this->model->update($id, $_POST)) {
            AuditService::log([
                'module' => 'ACADEMIC_GRUPOS', 
                'action' => 'UPDATE_SUCCESS', 
                'description' => "Actualizó información del grupo ID: $id", 
                'entity_id' => $id, 
                'event_type' => 'SUCCESS'
            ]);
        }
        
        header('Location: /diplomatic/public/academic/grupos?updated=1');
        exit();
    }

    /**
     * Eliminación Inteligente:
     * Si ha sido utilizado, bloquea y avisa.
     * Si no, lo borra físicamente de la base de datos.
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = (int)$_POST['id'];
        $grupo = $this->model->getById($id);

        if ($grupo) {
            // 1. Verificar si el registro ya tiene historia/uso
            if ($this->model->hasDependencies($id)) {
                AuditService::log([
                    'module' => 'ACADEMIC_GRUPOS', 
                    'action' => 'DELETE_BLOCKED', 
                    'description' => "Intento fallido de eliminar grupo utilizado: {$grupo['name']}", 
                    'entity_id' => $id, 
                    'event_type' => 'WARNING'
                ]);
                header('Location: /diplomatic/public/academic/grupos?error=has_dependencies');
                exit();
            }

            // 2. Si no tiene uso, procedemos al borrado físico
            if ($this->model->deletePhysical($id)) {
                AuditService::log([
                    'module' => 'ACADEMIC_GRUPOS', 
                    'action' => 'DELETE_PHYSICAL', 
                    'description' => "Se eliminó físicamente el grupo: {$grupo['name']}", 
                    'entity_id' => $id, 
                    'event_type' => 'WARNING'
                ]);
                header('Location: /diplomatic/public/academic/grupos?deleted=1');
            } else {
                header('Location: /diplomatic/public/academic/grupos?error=1');
            }
        }
        exit();
    }

    public function getDetails(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $grupo = $this->model->getById($id);
        header('Content-Type: application/json');
        echo json_encode(['ok' => (bool)$grupo, 'grupo' => $grupo]);
        exit();
    }
}