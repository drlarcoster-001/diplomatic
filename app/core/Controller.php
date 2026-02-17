<?php
/**
 * MÓDULO 1 - app/core/Controller.php
 * Controlador base del MVC con soporte para persistencia de sesión ("Recordarme").
 */

declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use PDO;

abstract class Controller
{
    /**
     * Verifica si existe una cookie de persistencia para restaurar la sesión.
     */
    public function checkPersistence(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Si ya hay sesión activa, no hacemos nada
        if (!empty($_SESSION['user']['id'])) return;

        // Si no hay cookie de recordarme, no hay nada que validar
        $token = $_COOKIE['remember_me'] ?? '';
        if (empty($token)) return;

        try {
            $db = (new Database())->getConnection();
            $hash = hash('sha256', $token);

            // Buscamos al usuario vinculado al token activo y con estado activo
            $stmt = $db->prepare("SELECT u.* FROM tbl_users u 
                                  JOIN tbl_user_remember_tokens t ON u.id = t.user_id 
                                  WHERE t.token_hash = ? AND t.expires_at > NOW() AND u.status = 'ACTIVE' LIMIT 1");
            $stmt->execute([$hash]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($u) {
                // Re-establecemos la sesión institucional
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
        } catch (\Throwable $e) {
            // Error silencioso para no romper el flujo si la BD no responde
        }
    }

    protected function view(string $viewPath, array $data = []): void
    {
        View::render($viewPath, $data);
    }

    protected function redirect(string $path): void
    {
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        header('Location: ' . $basePath . $path);
        exit;
    }
}