<?php
/**
 * MODULE: USERS, ROLES & ACCESS
 * File: app/controllers/AuthController.php
 * Propósito: Gestión de acceso con auditoría de intentos fallidos y éxitos.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Services\AuditService;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!empty($_SESSION['user']['id'])) {
            $this->redirect('/dashboard');
        }
        $this->view('auth/login');
    }

    public function doLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $email = (string)($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $model = new UserModel();
        $result = $model->verifyLogin($email, $password);

        // --- CASO 1: FALLO DE AUTENTICACIÓN ---
        if (!$result['ok']) {
            AuditService::log([
                'module'      => 'AUTH',
                'action'      => 'LOGIN_FAILED',
                'description' => 'Intento fallido para el correo: ' . $email . '. Motivo: ' . ($result['message'] ?? 'Credenciales inválidas'),
                'event_type'  => 'SECURITY' // Rojo en consola
            ]);

            $_SESSION['error'] = $result['message'];
            $this->redirect('/');
            return;
        }

        $u = $result['user'];

        // GUARDAMOS LA SESIÓN PRIMERO
        $_SESSION['user'] = [
            'id'        => $u['id'],
            'name'      => trim($u['first_name'] . ' ' . $u['last_name']),
            'email'     => $u['email'],
            'user_type' => $u['user_type'],
            'role'      => strtoupper($u['role']), 
            'status'    => $u['status'],
        ];

        // --- CASO 2: LOGIN EXITOSO ---
        // Al estar ya la sesión guardada, el log capturará correctamente el user_id
        AuditService::log([
            'module'      => 'AUTH',
            'action'      => 'LOGIN',
            'description' => 'Inicio de sesión exitoso: ' . $_SESSION['user']['name'],
            'event_type'  => 'SUCCESS' // Verde en consola
        ]);

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!empty($_SESSION['user']['id'])) {
            AuditService::log([
                'module'      => 'AUTH',
                'action'      => 'LOGOUT',
                'description' => 'Cierre de sesión manual: ' . ($_SESSION['user']['name'] ?? 'Usuario'),
                'event_type'  => 'NORMAL'
            ]);
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        $this->redirect('/');
    }
}