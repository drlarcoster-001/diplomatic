<?php
/**
 * MÓDULO: SEGURIDAD DE USUARIOS
 * Archivo: app/controllers/UserSecurityController.php
 * Propósito: Gestionar credenciales y estados con auditoría adaptada al AuditService.
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
        if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }
        $this->securityModel = new UserSecurityModel();
    }

    public function index(): void
    {
        $users = $this->securityModel->getAllUsersForSecurity();
        $this->view('users/security_grid', ['users' => $users]);
    }

    public function changePassword(): void
    {
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
    }

    public function changeStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId    = $_POST['user_id'] ?? null;
            $newStatus = $_POST['status'] ?? null; 
            $userEmail = $_POST['user_email'] ?? 'Desconocido';

            if ($userId && $newStatus) {
                if ($this->securityModel->setNewStatus($userId, $newStatus)) {
                    
                    $verbo = ($newStatus === 'ACTIVE') ? 'activado' : 'desactivado';
                    
                    AuditService::log([
                        'module'      => 'SEGURIDAD',
                        'action'      => ($newStatus === 'ACTIVE') ? 'ACTIVAR' : 'DESACTIVAR',
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
    }
}