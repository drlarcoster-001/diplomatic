<?php
/**
 * MÓDULO: ADMINISTRATIVO / ESTUDIANTES
 * ARCHIVO: app/controllers/AdministrativeStudentsController.php
 * PROPÓSITO: Gestionar el directorio de estudiantes, carga AJAX y actualización de estatus institucional.
 * VERSIÓN: 1.0.4 - Fix de roles de seguridad (ADMIN, ACADEMIC) y manejo correcto de respuestas AJAX.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdministrativeStudentsModel;
use Exception;

class AdministrativeStudentsController extends Controller {
    private AdministrativeStudentsModel $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $user = $_SESSION['user'] ?? null;
        
        // Verificación de seguridad ESTRICTA según roles reales
        $userRole = strtoupper($user['role'] ?? '');
        
        if (!$user || $user['user_type'] !== 'INTERNAL' || !in_array($userRole, ['ADMIN', 'ACADEMIC'])) {
            
            // Si es una petición AJAX (Fetch/jQuery), devolvemos JSON de error en lugar de HTML
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_GET['search']) || isset($_POST['status']);
            
            if ($isAjax) {
                // BLINDAJE: Limpiamos cualquier basura que PHP haya intentado imprimir antes (el causante del '<')
                while (ob_get_level() > 0) ob_end_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Acceso denegado: Se requieren permisos de Administrador o Académico.']);
                exit;
            }
            
            // Si es navegación normal, redirigimos al Dashboard
            $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
            $this->redirect($basePath . '/dashboard');
            exit;
        }
        
        $this->model = new AdministrativeStudentsModel();
    }

public function index(): void {
    if (ob_get_level() > 0) ob_end_clean();
    
    // Traemos los diplomados para el filtro
    $diplomados = $this->model->getDiplomadosList();

    $this->view('administrative/students/index', [
        'title' => 'Directorio de Estudiantes',
        'diplomados' => $diplomados
    ]);
}

public function list(): void {
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    $filters = [
        'search'     => $_GET['search'] ?? '',
        'diploma_id' => $_GET['diplomado'] ?? '', // id="filter-diplomado"
        'status'     => $_GET['status'] ?? '',    // id="filter-status"
        'docs'       => $_GET['docs'] ?? ''       // id="filter-docs"
    ];

    $students = $this->model->getAllStudents($filters);
    echo json_encode(['ok' => true, 'data' => $students], JSON_UNESCAPED_UNICODE);
    exit;
}


    
public function updateStatus(): void {
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $newStatus = trim(strtoupper($_POST['status'] ?? ''));
        
        // AGREGAMOS 'CONGELADO' a la lista de permitidos para que coincida con tu DB
        $validStatuses = ['ACTIVO', 'EGRESADO', 'RETIRADO', 'SUSPENDIDO', 'CONGELADO'];

        if ($studentId <= 0) throw new Exception("ID de estudiante inválido.");
        if (!in_array($newStatus, $validStatuses)) throw new Exception("Estatus no permitido en la base de datos.");

        $result = $this->model->updateStudentStatus($studentId, $newStatus);

        if ($result) {
            echo json_encode(['ok' => true, 'message' => "Estatus actualizado a $newStatus."]);
        } else {
            throw new Exception("No se pudo actualizar el estatus.");
        }
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    }
    exit;
}


}