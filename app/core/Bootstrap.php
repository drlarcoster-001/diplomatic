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
use App\Controllers\UserSecurityController; 
use App\Controllers\AcademicController; // Nuevo
use App\Controllers\DiplomadosController; // Nuevo
use App\Controllers\CohortesController; // Nuevo
use App\Controllers\GruposController; // Nuevo
use App\Controllers\ProfesoresController; // Nuevo
use PDO;

final class Bootstrap
{
    public function run(): void
    {
        // --- 1. INICIALIZACIÓN DE PERSISTENCIA (RECORDARME) ---
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (empty($_SESSION['user']['id']) && isset($_COOKIE['remember_me'])) {
            try {
                $db = (new \App\Core\Database())->getConnection();
                $hash = hash('sha256', $_COOKIE['remember_me']);

                $sql = "SELECT u.* FROM tbl_users u 
                        JOIN tbl_user_remember_tokens t ON u.id = t.user_id 
                        WHERE t.token_hash = ? AND t.expires_at > NOW() AND u.status = 'ACTIVE' LIMIT 1";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([$hash]);
                $u = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($u) {
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
                // Falla silenciosa
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

        // --- CONFIGURACIONES ---
        $router->get('/settings', [SettingsController::class, 'index']);
        $router->get('/settings/correo', [SettingsEmailController::class, 'index']);
        $router->post('/settings/save-correo', [SettingsEmailController::class, 'save']);
        $router->post('/settings/test-correo', [SettingsEmailController::class, 'test']);
        $router->get('/settings/empresa', [SettingsCompanyController::class, 'index']);
        $router->post('/settings/empresa/save', [SettingsCompanyController::class, 'save']);
        $router->get('/settings/whatsapp', [SettingsWhatsappController::class, 'index']);
        $router->post('/settings/whatsapp/save-template', [SettingsWhatsappController::class, 'saveTemplate']);
        $router->post('/settings/whatsapp/log', [SettingsWhatsappController::class, 'logSend']);
        $router->get('/settings/eventos', [SettingsEventsController::class, 'index']);
        $router->get('/settings/eventos/filter', [SettingsEventsController::class, 'filter']);

        // --- GESTIÓN ACADÉMICA (MÓDULOS INDEPENDIENTES) ---
        $router->get('/academic', [AcademicController::class, 'index']);
        $router->get('/academic/diplomados', [DiplomadosController::class, 'index']);
        $router->get('/academic/cohortes', [CohortesController::class, 'index']);
        $router->get('/academic/grupos', [GruposController::class, 'index']);
        $router->get('/academic/profesores', [ProfesoresController::class, 'index']);

        // --- SEGURIDAD Y USUARIOS ---
        $router->get('/UserSecurity', [UserSecurityController::class, 'index']);
        $router->post('/UserSecurityController/updatePassword', [UserSecurityController::class, 'changePassword']);
        $router->post('/UserSecurityController/updateStatus', [UserSecurityController::class, 'changeStatus']);
        $router->get('/users', [UsersController::class, 'index']);
        $router->post('/users/save', [UsersController::class, 'save']);
        $router->post('/users/delete', [UsersController::class, 'delete']);

        // --- REGISTRO Y RECUPERACIÓN ---
        $router->get('/register', [RegisterController::class, 'index']);
        $router->post('/register/submit', [RegisterController::class, 'submit']);
        $router->get('/forgot-password', [RegisterController::class, 'forgotPasswordIndex']);

        $router->dispatch();
    }
}