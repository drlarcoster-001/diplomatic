<?php
/**
 * MÓDULO: NÚCLEO
 * Archivo: app/core/Bootstrap.php
 * Propósito: Define las rutas del sistema.
 */

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\UsersController; // <--- ASEGÚRATE DE QUE ESTA LÍNEA EXISTA

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

    // -- RUTAS DE USUARIOS (NUEVAS) --
    $router->get('/users', [UsersController::class, 'index']);
    $router->get('/users/create', [UsersController::class, 'create']); // Para el botón nuevo

    $router->dispatch();
  }
}