<?php
/**
 * MODULE: USERS, ROLES & ACCESS
 * File: app/controllers/AuthController.php
 * Propósito: Gestión de acceso con auditoría, carga de avatar y funcionalidad "Recordarme".
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Services\AuditService;
use App\Core\Database; // Necesario para gestionar los tokens persistentes
use PDO;

final class AuthController extends Controller
{
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function showLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Antes de mostrar el login, verificamos si existe una cookie de "Recordarme"
        if (empty($_SESSION['user']['id'])) {
            $this->checkRememberMe();
        }

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
        $remember = isset($_POST['remember']); // Capturamos el checkbox del formulario

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

        $this->redirect('/dashboard');
    }

    /**
     * Crea la sesión estándar del usuario
     */
    private function createSession(array $u): void {
        $_SESSION['user'] = [
            'id'        => $u['id'],
            'name'      => trim($u['first_name'] . ' ' . $u['last_name']),
            'email'     => $u['email'],
            'user_type' => $u['user_type'],
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

        // Guardar en base de datos
        $stmt = $this->db->prepare("INSERT INTO tbl_user_remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $hash, $expires]);

        // Crear Cookie segura (HttpOnly)
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

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // 1. Limpiar token en la BD si existe cookie
        if (isset($_COOKIE['remember_me'])) {
            $hash = hash('sha256', $_COOKIE['remember_me']);
            $this->db->prepare("DELETE FROM tbl_user_remember_tokens WHERE token_hash = ?")->execute([$hash]);
            // Eliminar la cookie físicamente
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