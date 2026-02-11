<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/controllers/AuthController.php
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

    // Si el login falla según el nuevo formato del modelo
    if (!$result['ok']) {
      $_SESSION['error'] = $result['message'];
      $this->redirect('/');
    }

    $u = $result['user'];

    // GUARDAMOS LOS DATOS REALES EN LA SESIÓN
    $_SESSION['user'] = [
      'id'        => $u['id'],
      'name'      => trim($u['first_name'] . ' ' . $u['last_name']),
      'email'     => $u['email'],
      'user_type' => $u['user_type'],
      'role'      => strtoupper($u['role']), // Vital para las comparaciones en el Sidebar
      'status'    => $u['status'],
    ];

    $this->redirect('/dashboard');
  }

  public function logout(): void
  {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    $this->redirect('/');
  }
}