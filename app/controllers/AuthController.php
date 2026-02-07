<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/controllers/AuthController.php
 * Propósito: control de login/logout (sin SQL aquí). Delegación al Model.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;

final class AuthController extends Controller
{
  public function showLogin(): void
  {
    // Si ya está autenticado, lo mandamos al dashboard
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

    /**
     * Comentario importante:
     * La validación de credenciales, estado y hash se realiza en el Model.
     * Aquí solo se administra el flujo (sesión y redirecciones).
     */
    $result = $model->verifyLogin($email, $password);

    if (!$result['ok']) {
      $_SESSION['error'] = $result['message'];
      $this->redirect('/');
    }

    $u = $result['user'];

    // Sesión mínima (sin roles/permisos aún)
    $_SESSION['user'] = [
      'id' => $u['id'],
      'name' => trim($u['first_name'] . ' ' . $u['last_name']),
      'email' => $u['email'],
      'user_type' => $u['user_type'],
      'status' => $u['status'],
    ];

    $this->redirect('/dashboard');
  }

  public function logout(): void
  {
    unset($_SESSION['user']);
    session_regenerate_id(true);

    $this->redirect('/');
  }
}
