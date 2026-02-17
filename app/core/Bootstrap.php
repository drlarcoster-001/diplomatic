<?php
/**
 * MÓDULO: NÚCLEO
 * Archivo: app/core/Bootstrap.php
 * Propósito: Definición central de rutas e inicialización de persistencia de usuario (Remember Me).
 */

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UsersController;
use App\Controllers\SettingsController;
use App\Controllers\SettingsWhatsappController;
use App\Controllers\SettingsCompanyController;
use App\Controllers\SettingsEventsController;
use App\Controllers\ProfileController;
use App\Controllers\RegisterController;
use App\Controllers\SettingsEmailController;
use PDO;

final class Bootstrap
{
    public function run(): void
    {
        // --- 1. INICIALIZACIÓN DE PERSISTENCIA (RECORDARME) ---
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Si NO hay sesión pero SÍ hay cookie, intentamos loguear automáticamente
        if (empty($_SESSION['user']['id']) && isset($_COOKIE['remember_me'])) {
            try {
                $db = (new \App\Core\Database())->getConnection();
                $hash = hash('sha256', $_COOKIE['remember_me']);

                // Buscamos el token en la base de datos
                $sql = "SELECT u.* FROM tbl_users u 
                        JOIN tbl_user_remember_tokens t ON u.id = t.user_id 
                        WHERE t.token_hash = ? AND t.expires_at > NOW() AND u.status = 'ACTIVE' LIMIT 1";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([$hash]);
                $u = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($u) {
                    // Restauramos la sesión institucional con los datos del usuario
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
                // Falla silenciosa: si hay error en DB, el usuario simplemente deberá loguearse manual
            }
        }

        // --- 2. DEFINICIÓN DE RUTAS ---
        $router = new Router();

        // --- ACCESO Y DASHBOARD ---
        $router->get('/', [AuthController::class, 'showLogin']);
        $router->post('/login', [AuthController::class, 'doLogin']);
        $router->get('/logout', [AuthController::class, 'logout']);
        $router->get('/dashboard', [DashboardController::class, 'index']);
        
        // --- PERFIL DE USUARIO ---
        $router->get('/profile', [ProfileController::class, 'index']);
        $router->post('/profile/update', [ProfileController::class, 'update']);
        $router->get('/profile/security', [ProfileController::class, 'security']);
        $router->post('/profile/change-password', [ProfileController::class, 'changePassword']);

        // --- CONFIGURACIONES (GENERAL, CORREO, EMPRESA) ---
        $router->get('/settings', [SettingsController::class, 'index']);
        $router->get('/settings/correo', [SettingsEmailController::class, 'index']);
        $router->post('/settings/save-correo', [SettingsEmailController::class, 'save']);
        $router->post('/settings/test-correo', [SettingsEmailController::class, 'test']);
        
        $router->get('/settings/empresa', [SettingsCompanyController::class, 'index']);
        $router->post('/settings/empresa/save', [SettingsCompanyController::class, 'save']);
        
        // --- CONFIGURACIONES (WHATSAPP Y EVENTOS) ---
        $router->get('/settings/whatsapp', [SettingsWhatsappController::class, 'index']);
        $router->post('/settings/whatsapp/save-template', [SettingsWhatsappController::class, 'saveTemplate']);
        $router->post('/settings/whatsapp/log', [SettingsWhatsappController::class, 'logSend']);
        
        $router->get('/settings/eventos', [SettingsEventsController::class, 'index']);
        $router->get('/settings/eventos/filter', [SettingsEventsController::class, 'filter']);

        // --- GESTIÓN DE USUARIOS (MÓDULO ADMINISTRATIVO) ---
        $router->get('/users', [UsersController::class, 'index']);
        $router->post('/users/save', [UsersController::class, 'save']);
        $router->post('/users/delete', [UsersController::class, 'delete']);

        // --- FLUJO DE REGISTRO (AUTOSERVICIO) ---
        $router->get('/register', [RegisterController::class, 'index']);
        $router->post('/register/submit', [RegisterController::class, 'submit']);
        $router->get('/register/validate', [RegisterController::class, 'validateToken']);
        $router->post('/register/create-password', [RegisterController::class, 'createPassword']);
        $router->get('/register/complete', [RegisterController::class, 'completeProfile']);

        // --- FLUJO DE RECUPERACIÓN (OLVIDÉ MI CONTRASEÑA) ---
        $router->get('/forgot-password', [RegisterController::class, 'forgotPasswordIndex']);
        $router->post('/forgot-password/submit', [RegisterController::class, 'forgotPasswordSubmit']);
        $router->get('/forgot-password/validate', [RegisterController::class, 'validateToken']);

        $router->dispatch();
    }
}