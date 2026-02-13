<?php
/**
 * MODULE: USERS, ROLES & ACCESS
 * File: app/controllers/AuthController.php
 * Updated: Integrated with AuditService for real-time monitoring.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Services\AuditService; // IMPORTANTE: Importación del servicio de auditoría

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (!empty($_SESSION['user']['id'])) {
            $this->redirect('/dashboard');
        }
        $this->view('auth/login');
    }

    public function doLogin(): void
    {
        $email = (string)($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $model = new UserModel();
        $result = $model->verifyLogin($email, $password);

        // --- CASO 1: FALLO DE AUTENTICACIÓN (ALERTA DE SEGURIDAD) ---
        if (!$result['ok']) {
            AuditService::log([
                'module'            => 'AUTH',
                'action'            => 'LOGIN_FAILED',
                'description'       => 'Unauthorized login attempt for email: ' . $email . '. Reason: ' . ($result['message'] ?? 'Invalid credentials'),
                'event_type'        => 'SECURITY', // Aparecerá en ROJO en la consola
                'is_failed_attempt' => 1           // Marca el evento como crítico en tbl_audit_logs
            ]);

            $_SESSION['error'] = $result['message'];
            $this->redirect('/');
        }

        $u = $result['user'];

        // GUARDAMOS LOS DATOS REALES EN LA SESIÓN
        $_SESSION['user'] = [
            'id'        => $u['id'],
            'name'      => trim($u['first_name'] . ' ' . $u['last_name']),
            'email'     => $u['email'],
            'user_type' => $u['user_type'],
            'role'      => strtoupper($u['role']), 
            'status'    => $u['status'],
        ];

        // --- CASO 2: LOGIN EXITOSO (EVENTO NORMAL) ---
        AuditService::log([
            'module'      => 'AUTH',
            'action'      => 'LOGIN',
            'description' => 'User session started successfully for: ' . $_SESSION['user']['name'],
            'event_type'  => 'NORMAL' // Aparecerá en VERDE/AZUL en la consola
        ]);

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        // --- CASO 3: CIERRE DE SESIÓN ---
        // Registramos ANTES de destruir la sesión para capturar el user_id
        if (!empty($_SESSION['user']['id'])) {
            AuditService::log([
                'module'      => 'AUTH',
                'action'      => 'LOGOUT',
                'description' => 'User session closed manually for: ' . ($_SESSION['user']['name'] ?? 'Unknown'),
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