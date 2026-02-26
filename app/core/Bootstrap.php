<?php
/**
 * MÓDULO: NÚCLEO
 * Archivo: app/core/Bootstrap.php
 * Propósito: Definición central de rutas e inicialización de persistencia.
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
use App\Controllers\AcademicController;
use App\Controllers\DiplomadosController;
use App\Controllers\CohortesController;
use App\Controllers\CohortesConfigController; 
use App\Controllers\GruposController;
use App\Controllers\ProfesoresController;
use App\Controllers\SettingsWordpressController;
use App\Controllers\ExportController;
use PDO;

final class Bootstrap
{
    public function run(): void
    {
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
            } catch (\Throwable $e) {}
        }

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

        // --- INTEGRACIÓN WORDPRESS ---
        $router->get('/settings/wordpress', [SettingsWordpressController::class, 'index']);
        $router->post('/settings/wordpress/save', [SettingsWordpressController::class, 'saveConfig']);
        $router->post('/settings/wordpress/test', [SettingsWordpressController::class, 'testConnection']);
        $router->post('/settings/wordpress/sync-prof', [SettingsWordpressController::class, 'syncProfessor']);
        $router->post('/settings/wordpress/unsync-prof', [SettingsWordpressController::class, 'unsyncProfessor']);

        // --- GESTIÓN ACADÉMICA (DIPLOMADOS) ---
        $router->get('/academic', [AcademicController::class, 'index']);
        $router->get('/academic/diplomados', [DiplomadosController::class, 'index']);
        $router->get('/academic/diplomados/create', [DiplomadosController::class, 'create']);
        $router->post('/academic/diplomados/save', [DiplomadosController::class, 'save']);
        $router->get('/academic/diplomados/edit', [DiplomadosController::class, 'edit']); 
        $router->post('/academic/diplomados/update', [DiplomadosController::class, 'update']);
        $router->post('/academic/diplomados/delete', [DiplomadosController::class, 'delete']);
        $router->get('/academic/diplomados/getDetails', [DiplomadosController::class, 'getDetails']);
        $router->get('/academic/diplomados/export', [\App\Controllers\ExportController::class, 'pdf']);

        // --- GESTIÓN ACADÉMICA (COHORTES) ---
        $router->get('/academic/cohortes', [CohortesController::class, 'index']);
        $router->get('/academic/cohortes/create', [CohortesController::class, 'create']);
        $router->post('/academic/cohortes/save', [CohortesController::class, 'save']);
        $router->post('/academic/cohortes/update', [CohortesController::class, 'update']);
        $router->post('/academic/cohortes/delete', [CohortesController::class, 'delete']);
        $router->get('/academic/cohortes/getDetails', [CohortesController::class, 'getDetails']);
        $router->get('/academic/cohortes/changeStatus', [CohortesController::class, 'changeStatus']);
        $router->get('/academic/cohortes/logAccess', [CohortesController::class, 'logAccess']); 
        
        // --- GESTIÓN ACADÉMICA (CONFIGURACIÓN AVANZADA COHORTES) ---
        $router->get('/academic/cohortes-config', [CohortesConfigController::class, 'index']);
        $router->get('/academic/cohortes-config/getDetails', [CohortesConfigController::class, 'getDetails']);
        $router->post('/academic/cohortes-config/updateStatus', [CohortesConfigController::class, 'updateStatus']);
        $router->post('/academic/cohortes-config/hardDelete', [CohortesConfigController::class, 'hardDelete']);

        // --- GESTIÓN ACADÉMICA (GRUPOS) ---
        $router->get('/academic/grupos', [GruposController::class, 'index']);
        $router->post('/academic/grupos/save', [GruposController::class, 'save']);
        $router->post('/academic/grupos/update', [GruposController::class, 'update']);
        $router->post('/academic/grupos/delete', [GruposController::class, 'delete']);
        $router->get('/academic/grupos/getDetails', [GruposController::class, 'getDetails']);
        $router->get('/academic/grupos/logAccess', [GruposController::class, 'logAccess']);

        // --- GESTIÓN ACADÉMICA (PROFESORES) ---
        $router->get('/academic/profesores', [ProfesoresController::class, 'index']);
        $router->get('/academic/profesores/create', [ProfesoresController::class, 'create']);
        $router->post('/academic/profesores/save', [ProfesoresController::class, 'save']);
        $router->get('/academic/profesores/edit', [ProfesoresController::class, 'edit']);
        $router->post('/academic/profesores/updateBase', [ProfesoresController::class, 'updateBase']);
        $router->post('/academic/profesores/delete', [ProfesoresController::class, 'delete']);
        $router->get('/academic/profesores/getDetails', [ProfesoresController::class, 'getDetails']);
        $router->get('/academic/profesores/logAccess', [ProfesoresController::class, 'logAccess']);
        
        // Rutas Multitabla Profesores
        $router->post('/academic/profesores/saveFormation', [ProfesoresController::class, 'saveFormation']);
        $router->post('/academic/profesores/deleteFormation', [ProfesoresController::class, 'deleteFormation']);
        $router->post('/academic/profesores/saveWork', [ProfesoresController::class, 'saveWork']);
        $router->post('/academic/profesores/deleteWork', [ProfesoresController::class, 'deleteWork']);
        $router->post('/academic/profesores/saveSpecialty', [ProfesoresController::class, 'saveSpecialty']);
        $router->post('/academic/profesores/deleteSpecialty', [ProfesoresController::class, 'deleteSpecialty']);
        $router->post('/academic/profesores/uploadDocument', [ProfesoresController::class, 'uploadDocument']);
        $router->post('/academic/profesores/deleteDocument', [ProfesoresController::class, 'deleteDocument']);
        $router->post('/academic/profesores/uploadPhoto', [ProfesoresController::class, 'uploadPhoto']);

        // --- SEGURIDAD Y USUARIOS ---
        $router->get('/UserSecurity', [UserSecurityController::class, 'index']);
        
        // CORRECCIÓN: Rutas de seguridad sincronizadas con el JS y el controlador
        $router->post('/UserSecurity/updatePassword', [UserSecurityController::class, 'updatePassword']);
        $router->post('/UserSecurity/updateStatus', [UserSecurityController::class, 'updateStatus']);
        $router->post('/UserSecurity/deletePhysical', [UserSecurityController::class, 'deletePhysical']);
        
        $router->get('/users', [UsersController::class, 'index']);
        $router->post('/users/save', [UsersController::class, 'save']);
        $router->post('/users/delete', [UsersController::class, 'delete']);

        // --- REGISTRO Y RECUPERACIÓN ---
        $router->get('/register', [RegisterController::class, 'index']);
        $router->post('/register/submit', [RegisterController::class, 'submit']);
        $router->get('/register/validate', [RegisterController::class, 'validateToken']);
        $router->post('/register/create-password', [RegisterController::class, 'createPassword']);
        $router->get('/register/complete', [RegisterController::class, 'completeProfile']);
        $router->get('/forgot-password', [RegisterController::class, 'forgotPasswordIndex']);
        $router->post('/forgot-password/submit', [RegisterController::class, 'forgotPasswordSubmit']);

        $router->dispatch();
    }
}