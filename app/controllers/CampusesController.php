<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/CampusesController.php
 * Propósito: Controlador para la administración de Sedes con auditoría y navegación jerárquica.
 * Version: 1.1.6 - Estandarización de rutas y soporte para breadcrumbs.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CampusesModel;
use App\Services\AuditService;

class CampusesController extends Controller
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;
        
        // Verificación de seguridad institucional
        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR', 'ACADEMIC'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }
        $this->model = new CampusesModel();
    }

    /**
     * Muestra el listado maestro de sedes.
     */
    public function index(): void
    {
        AuditService::log([
            'module' => 'SEDES', 
            'action' => 'ACCESS_INDEX', 
            'description' => 'El usuario ingresó al panel de administración de sedes.'
        ]);

        $search = $_GET['search'] ?? '';
        $this->view('academic/campuses/index', [
            'campuses' => $this->model->getAll($search),
            'search'   => $search
        ]);
    }

    /**
     * Procesa el registro de una nueva sede.
     */
    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        try {
            $id = $this->model->insert($_POST);
            
            AuditService::log([
                'module' => 'SEDES', 
                'action' => 'CREATE_SUCCESS', 
                'description' => "Registró una nueva sede: {$_POST['name']}", 
                'entity_id' => $id
            ]);

            header('Location: /diplomatic/public/academic/campuses?created=1');
            exit();
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/academic/campuses?error=1');
            exit();
        }
    }

    /**
     * Procesa la actualización de datos de una sede.
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $this->model->update($id, $_POST)) {
            AuditService::log([
                'module' => 'SEDES', 
                'action' => 'UPDATE_SUCCESS', 
                'description' => "Actualizó la información de la sede ID: $id", 
                'entity_id' => $id
            ]);
        }
        
        header('Location: /diplomatic/public/academic/campuses?updated=1');
        exit();
    }

    /**
     * Borrado Inteligente (Inactivación o eliminación según dependencias).
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = (int)($_POST['id'] ?? 0);
        $campus = $this->model->getById($id);

        if ($campus) {
            $this->model->smartDelete($id);

            AuditService::log([
                'module' => 'SEDES', 
                'action' => 'DELETE_ATTEMPT', 
                'description' => "Procesó eliminación inteligente para la sede: {$campus['name']} (ID: $id)", 
                'entity_id' => $id
            ]);
        }

        header('Location: /diplomatic/public/academic/campuses?deleted=1');
        exit();
    }

    /**
     * Obtiene detalles para el modal y registra la visualización.
     */
    public function getDetails(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $campus = $this->model->getById($id);
        
        if ($campus) {
            AuditService::log([
                'module' => 'SEDES',
                'action' => 'VIEW_DETAILS',
                'description' => "Visualizó los detalles de la sede: {$campus['name']} (ID: $id)",
                'entity_id' => $id
            ]);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'ok' => (bool)$campus, 
            'campus' => $campus
        ]);
        exit();
    }
}