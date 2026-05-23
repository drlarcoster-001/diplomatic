<?php
/**
 * MÓDULO: SEGURIDAD DE USUARIOS
 * Archivo: app/controllers/UserSecurityController.php
 * Propósito: Gestionar credenciales y estados con validación AJAX para evitar redirecciones HTML.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserSecurityModel;
use App\Services\AuditService;

class UserSecurityController extends Controller
{
    private $securityModel;

    public function __construct()
    {
        $role = $_SESSION['user']['role'] ?? '';

        // Validación estricta sobre la role_key 'ADMIN'
        if ($role !== 'ADMIN') {
            // Si la petición es AJAX, devolvemos JSON para evitar que el JS reciba el HTML del Dashboard
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Sesión no autorizada (ADMIN role_key required).']);
                exit;
            }

            header('Location: /diplomatic/public/dashboard');
            exit();
        }
        $this->securityModel = new UserSecurityModel();
    }

    public function index(): void
    {
        $this->view('users/security_grid', []);
    }

    public function getUsers(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $text   = trim($_GET['text'] ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 25;
        $offset = ($page - 1) * $limit;

        $filters = ['text' => $text];

        $data  = $this->securityModel->getUsersPaginated($filters, $limit, $offset);
        $total = $this->securityModel->getTotalUsers($filters);

        echo json_encode([
            'ok'   => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages'  => (int)ceil($total / $limit),
                'total_records'=> $total
            ]
        ]);
        exit;
    }

    public function updatePassword(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId    = $_POST['user_id'] ?? null;
            $userEmail = $_POST['user_email'] ?? 'Desconocido';
            $password  = $_POST['password'] ?? null;

            if ($userId && $password) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                if ($this->securityModel->setNewPassword($userId, $passwordHash)) {
                    
                    AuditService::log([
                        'module'      => 'SEGURIDAD',
                        'action'      => 'RESET_PASSWORD',
                        'description' => "Se ha actualizado la contraseña del usuario: $userEmail (ID: $userId)",
                        'entity'      => 'tbl_users',
                        'entity_id'   => $userId,
                        'db_action'   => 'UPDATE'
                    ]);

                    echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada.']);
                    exit;
                }
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Error al procesar la solicitud.']);
        exit;
    }

    public function updateStatus(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId    = $_POST['user_id'] ?? null;
            $newStatus = $_POST['status'] ?? null; 
            $userEmail = $_POST['user_email'] ?? 'Desconocido';

            if ($userId && $newStatus) {
                if ($this->securityModel->setNewStatus($userId, $newStatus)) {
                    $verbo = ($newStatus === 'ACTIVE') ? 'activado' : 'inactivado';
                    
                    AuditService::log([
                        'module'      => 'SEGURIDAD',
                        'action'      => ($newStatus === 'ACTIVE') ? 'ACTIVAR' : 'INACTIVAR',
                        'description' => "Se ha $verbo al usuario con ID: $userId ($userEmail)",
                        'entity'      => 'tbl_users',
                        'entity_id'   => $userId,
                        'db_action'   => 'UPDATE'
                    ]);

                    echo json_encode(['status' => 'success', 'message' => "Usuario $verbo correctamente."]);
                    exit;
                }
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'No se pudo cambiar el estado.']);
        exit;
    }

    public function deletePhysical(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_POST['user_id'] ?? null;

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'ID no válido.']);
                exit;
            }

            $result = $this->securityModel->deleteUserPhysically($userId);

            if ($result['success']) {
                AuditService::log([
                    'module'      => 'SEGURIDAD',
                    'action'      => 'ELIMINAR_FISICO',
                    'description' => "Eliminación definitiva del usuario ID: $userId",
                    'entity'      => 'tbl_users',
                    'entity_id'   => $userId,
                    'db_action'   => 'DELETE'
                ]);
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $result['message']]);
            }
            exit;
        }
    }
}