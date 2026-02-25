<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/ProfesoresController.php
 * Propósito: Controlador para la administración de expedientes docentes con sistema de auditoría.
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
        
        if (!isset($_SESSION['user'])) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => 'Sesión expirada']);
                exit;
            }
            header('Location: /diplomatic/public/');
            exit();
        }
        $this->model = new ProfesoresModel();
    }

    public function index(): void {
        // Auditoría: Entrada al módulo
        AuditService::log([
            'module' => 'PROFESORES',
            'action' => 'ACCESS_INDEX',
            'description' => 'El usuario ingresó al directorio maestro de profesores.'
        ]);
        $this->view('academic/profesores/index', ['profesores' => $this->model->getAll($_GET['search'] ?? ''), 'search' => $_GET['search'] ?? '']);
    }

    /**
     * Registra eventos de auditoría disparados desde el JavaScript (Ficha, Intento borrar)
     */
    public function logAccess(): void {
        $action = $_GET['action'] ?? 'VIEW';
        $id = (int)($_GET['id'] ?? 0);
        
        $desc = match($action) {
            'VIEW_DETAILS'   => "Visualizó la Ficha Resumen del profesor ID: $id",
            'DELETE_ATTEMPT' => "Inició proceso de eliminación para el profesor ID: $id",
            default          => "Interacción de usuario: $action en ID: $id"
        };

        AuditService::log([
            'module' => 'PROFESORES',
            'action' => $action,
            'entity_id' => $id,
            'description' => $desc
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
        
        // Auditoría: Apertura de expediente
        AuditService::log([
            'module' => 'PROFESORES',
            'action' => 'EDIT_FORM',
            'entity_id' => $id,
            'description' => "Abrió el expediente detallado del docente: {$profesor['full_name']}"
        ]);
        
        $this->view('academic/profesores/edit', ['profesor' => $profesor]);
    }

    public function getDetails(): void {
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json');
        $id = (int)($_GET['id'] ?? 0);
        $profesor = $this->model->getDetails($id);
        echo json_encode(['ok' => ($profesor ? true : false), 'profesor' => $profesor]);
        exit();
    }

    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        try {
            $newId = $this->model->insertBasic($_POST, $_SESSION['user']['id']);
            
            // Auditoría: Creación exitosa
            AuditService::log([
                'module' => 'PROFESORES',
                'action' => 'CREATE_SUCCESS',
                'entity_id' => $newId,
                'db_action' => 'INSERT',
                'description' => "Registró el perfil base del docente: {$_POST['first_name']} {$_POST['last_name']}"
            ]);

            header("Location: /diplomatic/public/academic/profesores/edit?id={$newId}&created=1");
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/academic/profesores/create?error=duplicate');
        }
        exit();
    }

    public function updateBase(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $this->model->updateBasicData($id, $_POST, $_SESSION['user']['id']);
        
        // Auditoría: Actualización de datos principales
        AuditService::log([
            'module' => 'PROFESORES',
            'action' => 'UPDATE_BASE_DATA',
            'entity_id' => $id,
            'db_action' => 'UPDATE',
            'description' => "Actualizó los datos básicos (DNI, Nombres, Biografía) del docente."
        ]);

        header("Location: /diplomatic/public/academic/profesores/edit?id={$id}&updated=1");
        exit();
    }

    public function uploadPhoto(): void {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        try {
            $id = (int)$_POST['id'];
            $img = str_replace(['data:image/png;base64,', ' '], ['', '+'], $_POST['image']);
            $dir = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/profesores/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $name = 'prof_' . $id . '_' . time() . '.png';
            file_put_contents($dir . $name, base64_decode($img));
            $path = '/diplomatic/public/assets/uploads/profesores/' . $name;
            $this->model->updatePhoto($id, $path);

            // Auditoría: Cambio de foto
            AuditService::log([
                'module' => 'PROFESORES',
                'action' => 'UPDATE_PHOTO',
                'entity_id' => $id,
                'description' => 'Actualizó la fotografía del perfil profesional.'
            ]);

            echo json_encode(['success' => true, 'path' => $path]);
        } catch (\Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
        exit();
    }

    // ====== MÉTODOS DE SUBSECCIONES (Auditoría incluida) ======

    public function saveFormation() { 
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            $this->model->updateFormation($id, $_POST);
            $act = 'UPDATE_FORMATION';
            $desc = "Modificó registro de formación académica: {$_POST['degree_title']}";
        } else {
            $this->model->insertFormation($_POST);
            $act = 'ADD_FORMATION';
            $desc = "Añadió nueva formación académica: {$_POST['degree_title']}";
        }
        AuditService::log(['module' => 'PROFESORES', 'action' => $act, 'entity_id' => $_POST['professor_id'], 'description' => $desc]);
        header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); exit(); 
    }
    
    public function saveWork() { 
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            $this->model->updateWork($id, $_POST);
            $act = 'UPDATE_WORK';
            $desc = "Actualizó trayectoria laboral: {$_POST['job_title']}";
        } else {
            $this->model->insertWork($_POST);
            $act = 'ADD_WORK';
            $desc = "Añadió nueva experiencia laboral: {$_POST['job_title']}";
        }
        AuditService::log(['module' => 'PROFESORES', 'action' => $act, 'entity_id' => $_POST['professor_id'], 'description' => $desc]);
        header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); exit(); 
    }
    
    public function saveSpecialty() { 
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            $this->model->updateSpecialty($id, $_POST);
            $act = 'UPDATE_SPECIALTY';
            $desc = "Modificó área de especialidad: {$_POST['specialty_name']}";
        } else {
            $this->model->insertSpecialty($_POST);
            $act = 'ADD_SPECIALTY';
            $desc = "Añadió nueva especialidad: {$_POST['specialty_name']}";
        }
        AuditService::log(['module' => 'PROFESORES', 'action' => $act, 'entity_id' => $_POST['professor_id'], 'description' => $desc]);
        header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); exit(); 
    }

    public function uploadDocument(): void {
        $id = (int)$_POST['professor_id'];
        $docId = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        
        $path = null;
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $dir = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/profesores_docs/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $name = 'doc_' . $id . '_' . time() . '.' . pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $dir . $name)) {
                $path = '/diplomatic/public/assets/uploads/profesores_docs/' . $name;
                $_POST['file_path'] = $path;
            }
        }

        if ($docId > 0) {
            $this->model->updateDocumentData($docId, $_POST);
            $act = 'UPDATE_DOC';
            $desc = "Actualizó metadatos del documento: {$_POST['document_name']}";
        } else {
            $this->model->insertDocument(['professor_id' => $id, 'document_type' => $_POST['document_type'], 'document_name' => $_POST['document_name'], 'file_path' => $path]);
            $act = 'ADD_DOC';
            $desc = "Cargó nuevo soporte digital: {$_POST['document_name']}";
        }
        
        AuditService::log(['module' => 'PROFESORES', 'action' => $act, 'entity_id' => $id, 'description' => $desc]);
        header("Location: /diplomatic/public/academic/profesores/edit?id=$id&updated=1"); exit();
    }

    // ====== ELIMINACIONES ======

    public function deleteFormation() { 
        $this->model->deleteFormation((int)$_POST['id']); 
        AuditService::log(['module' => 'PROFESORES', 'action' => 'DELETE_FORMATION', 'entity_id' => $_POST['professor_id'], 'description' => 'Eliminó un registro de formación académica.']);
        header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); exit(); 
    }
    public function deleteWork() { 
        $this->model->deleteWork((int)$_POST['id']); 
        AuditService::log(['module' => 'PROFESORES', 'action' => 'DELETE_WORK', 'entity_id' => $_POST['professor_id'], 'description' => 'Eliminó un registro de experiencia laboral.']);
        header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); exit(); 
    }
    public function deleteSpecialty() { 
        $this->model->deleteSpecialty((int)$_POST['id']); 
        AuditService::log(['module' => 'PROFESORES', 'action' => 'DELETE_SPECIALTY', 'entity_id' => $_POST['professor_id'], 'description' => 'Eliminó una área de especialidad.']);
        header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); exit(); 
    }
    public function deleteDocument() { 
        $this->model->deleteDocument((int)$_POST['id']); 
        AuditService::log(['module' => 'PROFESORES', 'action' => 'DELETE_DOC', 'entity_id' => $_POST['professor_id'], 'description' => 'Eliminó un documento del expediente.']);
        header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); exit(); 
    }
    public function delete() { 
        $id = (int)$_POST['id'];
        $this->model->setInactive($id, $_SESSION['user']['id']); 
        AuditService::log(['module' => 'PROFESORES', 'action' => 'DELETE_PROFESSOR', 'entity_id' => $id, 'description' => 'Realizó la baja lógica (is_active=0) del docente.']);
        header('Location: /diplomatic/public/academic/profesores?deleted=1'); exit(); 
    }
}