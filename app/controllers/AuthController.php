<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/controllers/AuthController.php
 * Propósito: Controlador de login/logout.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;

final class AuthController extends Controller
{
  public function showLogin(): void
  {
    if (!empty($_SESSION['user']['id'])) {
      $this->redirect('/dashboard');
    }

    $this->view('auth/login');
  }

  public function doLogin(): void
  {
    $email = (string)($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    $model = new UserModel();
    $result = $model->verifyLogin($email, $password);

    if (!$result['ok']) {
      $_SESSION['error'] = $result['message'];
      $this->redirect('/');
    }

    $u = $result['user'];

    // AQUÍ GUARDAMOS EL ROL EN LA SESIÓN
    $_SESSION['user'] = [
      'id' => $u['id'],
      'name' => trim($u['first_name'] . ' ' . $u['last_name']),
      'email' => $u['email'],
      'user_type' => $u['user_type'],
      'role' => $u['role'], // <--- ESTA LÍNEA ES VITAL
      'status' => $u['status'],
    ];

    $this->redirect('/dashboard');
  }

  public function logout(): void
  {
    // Borramos todo rastro de sesión
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
    
    // Redirigir al login
    $this->redirect('/');
  }
}