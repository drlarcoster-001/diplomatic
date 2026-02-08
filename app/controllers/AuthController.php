<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/controllers/AuthController.php
 * Propósito: Controlador de login/logout para la autenticación del sistema.
 * Nota: Delegación al Model para la validación de credenciales.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;

final class AuthController extends Controller
{
  /**
   * Muestra la vista de login si el usuario no está autenticado
   */
  public function showLogin(): void
  {
    // Si ya está autenticado, lo mandamos al dashboard
    if (!empty($_SESSION['user']['id'])) {
      $this->redirect('/dashboard');
    }

    $this->view('auth/login');
  }

  /**
   * Procesa el login con las credenciales del usuario
   */
  public function doLogin(): void
  {
    $email = (string)($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    // Instanciamos el modelo de usuario
    $model = new UserModel();

    /**
     * La validación de credenciales y el estado del usuario
     * se realiza en el modelo (UserModel).
     */
    $result = $model->verifyLogin($email, $password);

    if (!$result['ok']) {
      $_SESSION['error'] = $result['message'];
      $this->redirect('/');
    }

    // Recuperamos los datos del usuario
    $u = $result['user'];

    // Almacenamos los datos del usuario en la sesión
    $_SESSION['user'] = [
      'id' => $u['id'],
      'name' => trim($u['first_name'] . ' ' . $u['last_name']),
      'email' => $u['email'],
      'user_type' => $u['user_type'],
      'status' => $u['status'],
    ];

    // Redirigimos al dashboard
    $this->redirect('/dashboard');
  }

  /**
   * Función para cerrar sesión
   */
  public function logout(): void
  {
    // Limpiar los datos de sesión del usuario
    unset($_SESSION['user']);
    
    // Regenerar el ID de sesión para evitar el uso de IDs antiguos
    session_regenerate_id(true);

    // Redirigir al login
    $this->redirect('/');
  }
}
