<?php
/**
 * MÓDULO: NÚCLEO
 * Archivo: app/core/Bootstrap.php
 * Propósito: Definición central de rutas del sistema con soporte para Perfil y Registro.
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
// NUEVOS CONTROLADORES
use App\Controllers\ProfileController;
use App\Controllers\RegisterController;

final class Bootstrap
{
  public function run(): void
  {
    $router = new Router();

    // -- RUTAS PÚBLICAS Y ACCESO --
    $router->get('/', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'doLogin']);
    $router->get('/logout', [AuthController::class, 'logout']);
    
    // Flujo de Registro Externo (El Algoritmo)
    $router->get('/register', [RegisterController::class, 'showRegister']);
    $router->post('/register/step1', [RegisterController::class, 'submitRegistration']);
    $router->get('/register/validate', [RegisterController::class, 'validateToken']);
    $router->post('/register/step2', [RegisterController::class, 'createPassword']);

    // -- RUTAS DEL PANEL --
    $router->get('/dashboard', [DashboardController::class, 'index']);
    
    // -- PERFIL DE USUARIO (Acceso para todos, pero uso personal)
    $router->get('/profile', [ProfileController::class, 'index']);
    $router->post('/profile/update', [ProfileController::class, 'update']);
    $router->get('/profile/security', [ProfileController::class, 'security']); // Para el cambio de clave
    $router->post('/profile/change-password', [ProfileController::class, 'changePassword']);

    // -- RUTAS DE CONFIGURACIÓN (SOLO ADMIN)
    $router->get('/settings', [SettingsController::class, 'index']);
    $router->get('/settings/correo', [SettingsController::class, 'correo']);
    $router->post('/settings/save-correo', [SettingsController::class, 'saveCorreo']);
    $router->post('/settings/test-correo', [SettingsController::class, 'testCorreo']);
    
    // Empresa
    $router->get('/settings/empresa', [SettingsCompanyController::class, 'index']);
    $router->post('/settings/empresa/save', [SettingsCompanyController::class, 'save']);
    
    // WhatsApp
    $router->get('/settings/whatsapp', [SettingsWhatsappController::class, 'index']);
    $router->post('/settings/whatsapp/save-template', [SettingsWhatsappController::class, 'saveTemplate']);
    $router->post('/settings/whatsapp/log', [SettingsWhatsappController::class, 'logSend']);
    
    // Consola de Auditoría
    $router->get('/settings/eventos', [SettingsEventsController::class, 'index']);
    $router->get('/settings/eventos/filter', [SettingsEventsController::class, 'filter']);

    // -- GESTIÓN DE USUARIOS (SOLO ADMIN)
    $router->get('/users', [UsersController::class, 'index']);
    $router->post('/users/save', [UsersController::class, 'save']);
    $router->post('/users/delete', [UsersController::class, 'delete']);

    $router->dispatch();
  }
}