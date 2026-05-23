<?php
/**
 * MODULE: USERS, ROLES & ACCESS
 * File: app/controllers/AuthController.php
 * Propósito: Gestión de acceso con auditoría, "Recordarme" y redirección inteligente por rol.
 * VERSIÓN: 1.6.0 - Redirección directa para estudiantes a /students.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Services\AuditService;
use App\Core\Database;
use PDO;

final class AuthController extends Controller
{
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Muestra la vista de login o redirige si ya existe sesión
     */
    public function showLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Antes de mostrar el login, verificamos si existe una cookie de "Recordarme"
        if (empty($_SESSION['user']['id'])) {
            $this->checkRememberMe();
        }

        if (!empty($_SESSION['user']['id'])) {
            $this->redirectByRole($_SESSION['user']['user_type']);
        }
        $this->view('auth/login');
    }

    /**
     * Procesa el inicio de sesión
     */
    public function doLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $email = (string)($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $remember = isset($_POST['remember']); 

        $model = new UserModel();
        $result = $model->verifyLogin($email, $password);

        if (!$result['ok']) {
            AuditService::log([
                'module'      => 'AUTH',
                'action'      => 'LOGIN_FAILED',
                'description' => 'Intento fallido para: ' . $email,
                'event_type'  => 'SECURITY'
            ]);
            $_SESSION['error'] = $result['message'];
            $this->redirect('/');
            return;
        }

        $u = $result['user'];
        $this->createSession($u);

        // --- LÓGICA RECORDARME ---
        if ($remember) {
            $this->setRememberMe((int)$u['id']);
        }

        AuditService::log([
            'module'      => 'AUTH',
            'action'      => 'LOGIN',
            'description' => 'Inicio de sesión exitoso: ' . $_SESSION['user']['name'],
            'event_type'  => 'SUCCESS'
        ]);

        // --- REDIRECCIÓN INTELIGENTE SEGÚN TIPO DE USUARIO ---
        $this->redirectByRole($u['user_type']);
    }

    /**
     * Centraliza la lógica de redirección post-login
     */
    private function redirectByRole(string $type): void
    {
        if ($type === 'PARTICIPANT') {
            // Estudiantes van directo a su panel de autogestión
            $this->redirect('/students');
        } else {
            // Personal interno va al dashboard administrativo
            $this->redirect('/dashboard');
        }
    }

    /**
     * Crea la sesión estándar del usuario manteniendo el formato de array original
     */
    private function createSession(array $u): void {
        $_SESSION['user'] = [
            'id'        => $u['id'],
            'name'      => trim($u['first_name'] . ' ' . $u['last_name']),
            'first_name'=> $u['first_name'],
            'last_name' => $u['last_name'],
            'email'     => $u['email'],
            'user_type' => $u['user_type'], // 'INTERNAL' o 'PARTICIPANT'
            'role'      => strtoupper($u['role']), 
            'status'    => $u['status'],
            'avatar'    => $u['avatar'] ?? 'default_avatar.png',
        ];
    }

    /**
     * Genera token persistente y cookie de 30 días
     */
    private function setRememberMe(int $userId): void {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

        $stmt = $this->db->prepare("INSERT INTO tbl_user_remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $hash, $expires]);

        // HttpOnly para mayor seguridad
        setcookie('remember_me', $token, time() + (86400 * 30), "/", "", false, true);
    }

    /**
     * Valida si existe una cookie válida para iniciar sesión automáticamente
     */
    private function checkRememberMe(): void {
        $token = $_COOKIE['remember_me'] ?? '';
        if (empty($token)) return;

        $hash = hash('sha256', $token);
        $stmt = $this->db->prepare("SELECT u.* FROM tbl_users u 
                                    JOIN tbl_user_remember_tokens t ON u.id = t.user_id 
                                    WHERE t.token_hash = ? AND t.expires_at > NOW() AND u.status = 'ACTIVE' LIMIT 1");
        $stmt->execute([$hash]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $this->createSession($user);
            AuditService::log([
                'module' => 'AUTH', 'action' => 'LOGIN_REMEMBER', 
                'description' => 'Acceso persistente detectado: ' . $user['email'], 'event_type' => 'SUCCESS'
            ]);
        }
    }

    /**
     * Cierre de sesión completo y limpieza de tokens
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (isset($_COOKIE['remember_me'])) {
            $hash = hash('sha256', $_COOKIE['remember_me']);
            $this->db->prepare("DELETE FROM tbl_user_remember_tokens WHERE token_hash = ?")->execute([$hash]);
            setcookie('remember_me', '', time() - 3600, "/");
        }

        if (!empty($_SESSION['user']['id'])) {
            AuditService::log([
                'module' => 'AUTH', 'action' => 'LOGOUT',
                'description' => 'Cierre de sesión manual: ' . ($_SESSION['user']['name'] ?? 'Usuario'),
                'event_type' => 'NORMAL'
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