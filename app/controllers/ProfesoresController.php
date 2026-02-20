<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/ProfesoresController.php
 * Propósito: Controlador para la administración de expedientes docentes.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProfesoresModel;

class ProfesoresController extends Controller
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // CORRECCIÓN: Si el usuario está logueado, permitir acceso. 
        // Evita que las peticiones AJAX reciban el HTML del Dashboard por redirección.
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
        $this->view('academic/profesores/index', ['profesores' => $this->model->getAll($_GET['search'] ?? ''), 'search' => $_GET['search'] ?? '']);
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

    /**
     * Devuelve JSON limpio para la ficha del profesor
     */
    public function getDetails(): void {
        if (ob_get_length()) ob_clean(); // ELIMINA CUALQUIER SALIDA HTML PREVIA
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
            echo json_encode(['success' => true, 'path' => $path]);
        } catch (\Exception $e) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); }
        exit();
    }

    // Métodos de gestión de tablas relacionadas
    public function saveFormation() { $this->model->insertFormation($_POST); header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); }
    public function deleteFormation() { $this->model->deleteFormation((int)$_POST['id']); header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); }
    public function saveWork() { $this->model->insertWork($_POST); header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); }
    public function deleteWork() { $this->model->deleteWork((int)$_POST['id']); header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); }
    public function saveSpecialty() { $this->model->insertSpecialty($_POST); header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); }
    public function deleteSpecialty() { $this->model->deleteSpecialty((int)$_POST['id']); header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); }
    
    public function uploadDocument(): void {
        $id = (int)$_POST['professor_id'];
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/profesores_docs/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $name = 'doc_' . $id . '_' . time() . '.' . pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $dir . $name)) {
            $this->model->insertDocument(['professor_id' => $id, 'document_type' => $_POST['document_type'], 'document_name' => $_POST['document_name'], 'file_path' => '/diplomatic/public/assets/uploads/profesores_docs/' . $name]);
        }
        header("Location: /diplomatic/public/academic/profesores/edit?id=$id&updated=1");
        exit();
    }
    public function deleteDocument() { $this->model->deleteDocument((int)$_POST['id']); header("Location: /diplomatic/public/academic/profesores/edit?id=".$_POST['professor_id']."&updated=1"); }
    public function delete() { $this->model->setInactive((int)$_POST['id'], $_SESSION['user']['id']); header('Location: /diplomatic/public/academic/profesores?deleted=1'); exit(); }
}