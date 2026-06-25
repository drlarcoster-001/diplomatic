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

    // === ACCESO DE USUARIO (Portal de Profesores) ===
    // Agregar este método a ProfesoresController.php, junto a los demás métodos.

    public function createAccess(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        try {
            $profesorId = (int) ($_POST['profesor_id'] ?? 0);
            $email      = trim($_POST['email'] ?? '');
            $password   = $_POST['password'] ?? '';

            $profesor = $profesorId ? $this->model->getById($profesorId) : null;
            if (!$profesor) {
                header('Location: /diplomatic/public/academic/profesores?error=notfound');
                exit;
            }
            if (!empty($profesor['user_id'])) {
                header('Location: /diplomatic/public/academic/profesores?error=ya_tiene_acceso');
                exit;
            }
            if ($email === '' || strlen($password) < 6) {
                header('Location: /diplomatic/public/academic/profesores?error=incompleto');
                exit;
            }
            if ($this->model->emailExists($email)) {
                header('Location: /diplomatic/public/academic/profesores?error=email_duplicado');
                exit;
            }

            $this->model->createAccessForProfessor($profesorId, $email, $password, $profesor['first_name'], $profesor['last_name']);

            AuditService::log([
                'module' => 'ACADEMIC_PROFESORES',
                'action' => 'CREAR_ACCESO',
                'description' => "Creó acceso de usuario para el profesor \"{$profesor['full_name']}\""
            ]);

            header('Location: /diplomatic/public/academic/profesores?acceso_creado=1');
            exit;
        } catch (\Throwable $e) {
            header('Location: /diplomatic/public/academic/profesores?error=db');
            exit;
        }
    }

 public function searchUsuarios(): void
{
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    try {
        $term    = trim($_GET['term'] ?? '');
        $results = $this->model->searchUsuariosProfesores($term);
        echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

public function vincular(): void
{
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    try {
        $profesorId = (int) ($_POST['profesor_id'] ?? 0);
        $userId     = (int) ($_POST['user_id']     ?? 0);

        if (!$profesorId || !$userId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos.']);
            exit;
        }

        $this->model->vincularUsuario($profesorId, $userId);

        AuditService::log($_SESSION['user']['id'], 'ACADEMIC_PROFESORES', 'VINCULAR',
            "Vinculó usuario {$userId} al profesor {$profesorId}", $profesorId);

        echo json_encode(['success' => true, 'message' => 'Usuario vinculado correctamente.'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

public function desvincular(): void
{
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    try {
        $profesorId = (int) ($_POST['profesor_id'] ?? 0);

        if (!$profesorId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos.']);
            exit;
        }

        $this->model->desvincularUsuario($profesorId);

        AuditService::log($_SESSION['user']['id'], 'ACADEMIC_PROFESORES', 'DESVINCULAR',
            "Desvinculó usuario del profesor {$profesorId}", $profesorId);

        echo json_encode(['success' => true, 'message' => 'Usuario desvinculado correctamente.'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
}