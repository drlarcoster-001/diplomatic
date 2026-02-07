<?php
/**
 * MÓDULO 1 - app/controllers/DashboardController.php
 * Controlador para mostrar el dashboard una vez autenticado.
 */

namespace App\Controllers;

use App\Core\Controller;

final class DashboardController extends Controller
{
  // Muestra el contenido del dashboard
  public function index(): void
  {
    // Verifica si el usuario está autenticado
    if (!isset($_SESSION['user'])) {
      $this->redirect('/login');  // Si no está autenticado, redirige al login
    }

    // Si está autenticado, muestra el dashboard
    $this->view('dashboard/index');
  }
}
