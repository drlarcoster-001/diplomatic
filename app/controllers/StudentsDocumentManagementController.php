<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / CONTROLADORES
 * ARCHIVO: app/controllers/StudentsDocumentManagementController.php
 * PROPÓSITO: Orquestador para la carga, visualización y eliminación de recaudos.
 * VERSIÓN: 1.3.5 - Fix: Blindaje estricto de búfer en métodos AJAX para evitar error "Unexpected token <".
 * NOTA: Sincronización de rutas para coincidir con Bootstrap.php (/students/documents/upload).
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\StudentsDocumentManagementModel;

class StudentsDocumentManagementController extends Controller
{
    protected StudentsDocumentManagementModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new StudentsDocumentManagementModel();

        if (!isset($_SESSION['user']['id'])) {
            header('Location: /diplomatic/public/login');
            exit;
        }

        /*$userId = (int) $_SESSION['user']['id'];
        $studentId = $this->model->getStudentIdByUserId($userId);
        
        if ($studentId === null) {
            header('Location: /diplomatic/public/students?alert=needs_enrollment');
            exit;
        }*/
    }

    public function index(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        ob_start();

        $userId = (int) $_SESSION['user']['id'];
        $enrollmentId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        $this->view('students/document_management/index', [
            'title'        => 'Gestión de Documentos',
            'enrollments'  => $this->model->getStudentEnrollments($userId),
            'selected_id'  => $enrollmentId,
            'current_docs' => $enrollmentId ? $this->model->getEnrollmentDocs($enrollmentId, $userId) : null,
            'user'         => $_SESSION['user']
        ]);
        
        ob_end_flush();
    }

    public function upload(): void
    {
        // BLINDAJE CRÍTICO: Limpiar cualquier salida previa (notices/warnings)
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            $userId = (int) $_SESSION['user']['id'];
            $eid    = (int) ($_POST['enrollment_id'] ?? 0);
            $column = $_POST['doc_type'] ?? '';

            if (!in_array($column, ['doc_id_card', 'doc_degree', 'doc_cv'])) {
                throw new \Exception('Tipo de documento no válido');
            }

            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception('Error en el archivo recibido');
            }

            $uploadDir = "uploads/enrollments/{$userId}/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $fileName  = "DOC_" . $userId . "_" . $column . "_" . time() . "." . $extension;
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
                $this->model->updateDocumentField($eid, $userId, $column, $targetFile);
                echo json_encode(['status' => 'success', 'message' => 'Archivo cargado']);
            } else {
                throw new \Exception('No se pudo guardar el archivo físico');
            }

        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit; // Evita que se añada cualquier HTML después del JSON
    }

    public function deleteDocument(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = (int) $_SESSION['user']['id'];
            $eid    = (int) ($input['enrollment_id'] ?? 0);
            $column = $input['column'] ?? '';

            $docData = $this->model->getEnrollmentDocs($eid, $userId);
            if ($docData && !empty($docData[$column])) {
                if (file_exists($docData[$column])) unlink($docData[$column]);
            }

            $this->model->updateDocumentField($eid, $userId, $column, null);
            echo json_encode(['status' => 'success']);
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}