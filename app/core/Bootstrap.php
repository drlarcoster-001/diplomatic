<?php
/**
 * MÓDULO: NÚCLEO
 * Archivo: app/core/Bootstrap.php
 * Propósito: Definición central de rutas del sistema.
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

final class Bootstrap
{
  public function run(): void
  {
    $router = new Router();

    // -- RUTAS DE ACCESO --
    $router->get('/', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'doLogin']);
    $router->get('/logout', [AuthController::class, 'logout']);

    // -- RUTAS DEL PANEL --
    $router->get('/dashboard', [DashboardController::class, 'index']);
    
    // -- RUTAS DE CONFIGURACIÓN --
    $router->get('/settings', [SettingsController::class, 'index']);
    $router->get('/settings/correo', [SettingsController::class, 'correo']);
    $router->post('/settings/save-correo', [SettingsController::class, 'saveCorreo']);
    $router->post('/settings/test-correo', [SettingsController::class, 'testCorreo']);
    
    // Empresa
    $router->get('/settings/empresa', [SettingsCompanyController::class, 'index']);
    $router->post('/settings/empresa/save', [SettingsCompanyController::class, 'save']);
    
    // WhatsApp (Verificadas e intactas)
    $router->get('/settings/whatsapp', [SettingsWhatsappController::class, 'index']);
    $router->post('/settings/whatsapp/save-template', [SettingsWhatsappController::class, 'saveTemplate']);
    $router->post('/settings/whatsapp/log', [SettingsWhatsappController::class, 'logSend']);
    
    // Consola de Auditoría (Eventos)
    $router->get('/settings/eventos', [SettingsEventsController::class, 'index']);
    $router->get('/settings/eventos/filter', [SettingsEventsController::class, 'filter']);

    // -- RUTAS DE USUARIOS --
    $router->get('/users', [UsersController::class, 'index']);
    $router->post('/users/save', [UsersController::class, 'save']);
    $router->post('/users/delete', [UsersController::class, 'delete']);

    $router->dispatch();
  }
}