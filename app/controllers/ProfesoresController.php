<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/controllers/ProfesoresController.php
 * PROPÓSITO: Administración integral de expedientes docentes con blindaje contra errores de integridad referencial.
 * VERSIÓN: 1.4.3 - Fix: Captura de PDOException en borrado físico y validación extendida de dependencias.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProfesoresModel;
use App\Services\AuditService;

class ProfesoresController extends Controller
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
        $this->model = new ProfesoresModel();
    }

    public function index(): void {
        AuditService::log([
            'module' => 'ACADEMIC_PROFESORES',
            'action' => 'ACCESS_INDEX',
            'description' => 'El usuario ingresó al directorio maestro de profesores.'
        ]);
        $this->view('academic/profesores/index', [
            'profesores' => $this->model->getAll($_GET['search'] ?? ''), 
            'search' => $_GET['search'] ?? ''
        ]);
    }

    /**
     * Eliminación de Profesor (Maestro)
     * VERSIÓN 1.4.3: Implementa Try-Catch para gestionar restricciones de llave foránea (Error 1451).
     */
    public function delete(): void 
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = (int)$_POST['id'];
        $profesor = $this->model->getById($id);

        if ($profesor) {
            // 1. Verificación previa de dependencias conocidas
            if ($this->model->hasDependencies($id)) {
                AuditService::log([
                    'module' => 'ACADEMIC_PROFESORES', 
                    'action' => 'DELETE_BLOCKED', 
                    'description' => "Intento de eliminar profesor con historial: {$profesor['full_name']}", 
                    'entity_id' => $id,
                    'event_type' => 'WARNING'
                ]);
                header('Location: /diplomatic/public/academic/profesores?error=has_dependencies');
                exit();
            }

            // 2. Intento de borrado físico con captura de excepciones de base de datos
            try {
                if ($this->model->deletePhysical($id)) {
                    AuditService::log([
                        'module' => 'ACADEMIC_PROFESORES', 
                        'action' => 'DELETE_PHYSICAL', 
                        'description' => "Eliminación física definitiva del profesor: {$profesor['full_name']}", 
                        'entity_id' => $id,
                        'event_type' => 'WARNING'
                    ]);
                    header('Location: /diplomatic/public/academic/profesores?deleted=1');
                } else {
                    header('Location: /diplomatic/public/academic/profesores?error=1');
                }
            } catch (\PDOException $e) {
                // Captura específica de error de restricción de integridad (FK violation)
                AuditService::log([
                    'module' => 'ACADEMIC_PROFESORES', 
                    'action' => 'DELETE_ERROR_INTEGRITY', 
                    'description' => "Error de integridad al intentar borrar a {$profesor['full_name']}: " . $e->getMessage(), 
                    'entity_id' => $id,
                    'event_type' => 'ERROR'
                ]);
                
                // Redirige con un error específico para mostrar el Popup de "Registro en uso"
                header('Location: /diplomatic/public/academic/profesores?error=integrity_violation');
            }
        }
        exit();
    }

    public function getDetails(): void {
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json');
        $id = (int)($_GET['id'] ?? 0);
        $profesor = $this->model->getDetails($id);
        echo json_encode(['ok' => (bool)$profesor, 'profesor' => $profesor]);
        exit();
    }

    public function logAccess(): void {
        $action = $_GET['action'] ?? 'VIEW';
        $id = (int)($_GET['id'] ?? 0);
        AuditService::log([
            'module' => 'ACADEMIC_PROFESORES',
            'action' => $action,
            'entity_id' => $id,
            'description' => "Interacción de usuario: $action en ID: $id"
        ]);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit();
    }

    public function create(): void {
        $this->view('academic/profesores/create');
    }

    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        $profesor = $this->model->getDetails($id);
        if (!$profesor) { header('Location: /diplomatic/public/academic/profesores'); exit(); }
        $this->view('academic/profesores/edit', ['profesor' => $profesor]);
    }

    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        try {
            $newId = $this->model->insertBasic($_POST, $_SESSION['user']['id']);
            header("Location: /diplomatic/public/academic/profesores/edit?id={$newId}&created=1&tab=datos");
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/academic/profesores/create?error=duplicate');
        }
        exit();
    }

    public function updateBase(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $this->model->updateBasicData($id, $_POST, $_SESSION['user']['id']);
        AuditService::log([
            'module' => 'ACADEMIC_PROFESORES', 'action' => 'UPDATE_BASE_DATA',
            'entity_id' => $id, 'db_action' => 'UPDATE', 'description' => "Actualizó datos básicos."
        ]);
        header("Location: /diplomatic/public/academic/profesores/edit?id={$id}&updated=1&tab=datos");
        exit();
    }

    // --- GESTIÓN DE FORMACIÓN ACADÉMICA ---

    public function saveFormation(): void { 
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        $profId = (int)$_POST['professor_id'];
        $data = [
            'professor_id'   => $profId,
            'degree_title'   => $_POST['degree_title'] ?? '',
            'academic_level' => $_POST['academic_level'] ?? 'Pregrado',
            'study_area'     => $_POST['study_area'] ?? null,
            'institution'    => $_POST['institution'] ?? '',
            'year_obtained'  => $_POST['year_obtained'] ?? null
        ];
        if ($id > 0) $this->model->updateFormation($id, $data);
        else $this->model->insertFormation($data);
        header("Location: /diplomatic/public/academic/profesores/edit?id=$profId&updated=1&tab=formacion"); 
        exit(); 
    }

    public function deleteFormation(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $profId = (int)$_POST['professor_id'];
        if ($this->model->deleteFormation($id)) {
            header("Location: /diplomatic/public/academic/profesores/edit?id=$profId&deleted=1&tab=formacion");
        } else {
            header("Location: /diplomatic/public/academic/profesores/edit?id=$profId&error=1&tab=formacion");
        }
        exit();
    }

    // --- GESTIÓN DE EXPERIENCIA LABORAL ---

    public function saveWork(): void { 
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        $profId = (int)$_POST['professor_id'];
        $isCurrent = isset($_POST['is_current']) ? 1 : 0;
        $data = [
            'professor_id' => $profId,
            'job_title'    => $_POST['job_title'] ?? '',
            'institution'  => $_POST['institution'] ?? '',
            'description'  => $_POST['description'] ?? null,
            'start_date'   => $_POST['start_date'] ?? null,
            'end_date'     => ($isCurrent == 1) ? null : ($_POST['end_date'] ?? null),
            'is_current'   => $isCurrent
        ];
        if ($id > 0) $this->model->updateWork($id, $data);
        else $this->model->insertWork($data);
        header("Location: /diplomatic/public/academic/profesores/edit?id=$profId&updated=1&tab=experiencia"); 
        exit(); 
    }

    public function deleteWork(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $profId = (int)$_POST['professor_id'];
        if ($this->model->deleteWork($id)) {
            header("Location: /diplomatic/public/academic/profesores/edit?id=$profId&deleted=1&tab=experiencia");
        } else {
            header("Location: /diplomatic/public/academic/profesores/edit?id=$profId&error=1&tab=experiencia");
        }
        exit();
    }

    // --- GESTIÓN DE ESPECIALIDADES ---

    public function saveSpecialty(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        $profId = (int)$_POST['professor_id'];
        $data = [
            'professor_id'   => $profId,
            'specialty_name' => $_POST['specialty_name'] ?? '',
            'is_main'        => isset($_POST['is_main']) ? 1 : 0
        ];
        if ($id > 0) $this->model->updateSpecialty($id, $data);
        else $this->model->insertSpecialty($data);
        header("Location: /diplomatic/public/academic/profesores/edit?id=$profId&updated=1&tab=especialidades");
        exit();
    }

    public function deleteSpecialty(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $profId = (int)$_POST['professor_id'];
        if ($this->model->deleteSpecialty($id)) {
            header("Location: /diplomatic/public/academic/profesores/edit?id=$profId&deleted=1&tab=especialidades");
        } else {
            header("Location: /diplomatic/public/academic/profesores/edit?id=$profId&error=1&tab=especialidades");
        }
        exit();
    }

    // --- GESTIÓN DE DOCUMENTOS ---

    public function deleteDocument(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $profId = (int)$_POST['professor_id'];
        if ($this->model->deleteDocument($id)) {
            header("Location: /diplomatic/public/academic/profesores/edit?id=$profId&deleted=1&tab=documentos");
        } else {
            header("Location: /diplomatic/public/academic/profesores/edit?id=$profId&error=1&tab=documentos");
        }
        exit();
    }
}