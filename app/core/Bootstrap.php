<?php
/**
 * MÓDULO: NÚCLEO
 * Archivo: app/core/Bootstrap.php
 * Propósito: Definición central de rutas del sistema.
 */

declare(strict_types=1);

namespace App\Core;

// Importamos las clases para que el Router las reconozca por su nombre completo
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UsersController;

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

    // -- RUTAS DE USUARIOS (El orden importa) --
    // Aseguramos que el path sea exactamente '/users'
    $router->get('/users', [UsersController::class, 'index']);
    $router->post('/users/save', [UsersController::class, 'save']);
    $router->post('/users/delete', [UsersController::class, 'delete']);

    $router->dispatch();
  }
}