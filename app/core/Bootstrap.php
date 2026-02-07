<?php
/**
 * MÓDULO 1 - app/core/Bootstrap.php
 * Inicializa las rutas mínimas: login, dashboard y logout.
 */

declare(strict_types=1);

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\DashboardController;

final class Bootstrap
{
  public function run(): void
  {
    // Router ahora será cargado por el Autoload automáticamente
    $router = new Router();

    // Rutas mínimas
    $router->get('/', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'doLogin']);
    $router->get('/logout', [AuthController::class, 'logout']);

    $router->get('/dashboard', [DashboardController::class, 'index']);

    $router->dispatch();
  }
}
