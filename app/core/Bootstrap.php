<?php
/**
 * MÓDULO: NÚCLEO
 * Archivo: app/core/Bootstrap.php
 * Propósito: Definición central de rutas con soporte para el nuevo módulo de Email independiente.
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

final class Bootstrap
{
    public function run(): void
    {
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
        // forgotPasswordIndex: Muestra el formulario de Correo + Cédula
        $router->get('/forgot-password', [RegisterController::class, 'forgotPasswordIndex']);
        
        // forgotPasswordSubmit: Valida datos y envía el correo con la nueva tabla
        $router->post('/forgot-password/submit', [RegisterController::class, 'forgotPasswordSubmit']);
        
        // validateToken: Es el mismo validador del registro, ahora detecta recuperación automáticamente
        $router->get('/forgot-password/validate', [RegisterController::class, 'validateToken']);

        $router->dispatch();
    }
}