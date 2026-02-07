<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/models/UserModel.php
 * Propósito: operaciones de usuario para autenticación (login) de forma segura.
 * Nota: consultas SQL permanecen aquí (capa Model), nunca en controladores ni vistas.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class UserModel
{
  /**
   * Busca un usuario por su correo electrónico.
   * 
   * @param string $email Correo electrónico del usuario
   * @return array|null Datos del usuario, o null si no existe
   */
  public function findByEmail(string $email): ?array
  {
    $pdo = Database::getConnection();

    // Consulta SQL para buscar el usuario por email
    $sql = "SELECT id, user_type, status, first_name, last_name, email, password_hash
            FROM tbl_users
            WHERE email = :email
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  /**
   * Verifica las credenciales del usuario (correo y contraseña).
   * 
   * @param string $email Correo electrónico del usuario
   * @param string $password Contraseña proporcionada
   * @return array Resultado de la validación con mensaje y datos del usuario si es válido
   */
  public function verifyLogin(string $email, string $password): array
  {
    $email = trim($email);
    $password = (string)$password;

    // Verifica si los campos no están vacíos
    if ($email === '' || $password === '') {
      return [
        'ok' => false,
        'message' => 'Debe ingresar correo y contraseña.',
        'user' => null,
      ];
    }

    // Obtiene los datos del usuario a través del correo
    $user = $this->findByEmail($email);

    // Si el usuario no existe, no revelamos detalles de qué salió mal
    if (!$user) {
      return [
        'ok' => false,
        'message' => 'Credenciales inválidas.',
        'user' => null,
      ];
    }

    // Si el usuario no está activo, no se le permite el login
    if (($user['status'] ?? '') !== 'ACTIVE') {
      return [
        'ok' => false,
        'message' => 'Usuario inactivo o suspendido. Contacte al administrador.',
        'user' => null,
      ];
    }

    /**
     * Usamos password_verify() para verificar la contraseña de forma segura
     * contra el hash almacenado en la base de datos.
     */
    $hash = (string)($user['password_hash'] ?? '');
    if (!password_verify($password, $hash)) {
      return [
        'ok' => false,
        'message' => 'Credenciales inválidas.',
        'user' => null,
      ];
    }

    // Si todo es correcto, retornamos el resultado exitoso con los datos del usuario
    return [
      'ok' => true,
      'message' => 'Acceso concedido.',
      'user' => [
        'id' => (int)$user['id'],
        'first_name' => (string)$user['first_name'],
        'last_name' => (string)$user['last_name'],
        'email' => (string)$user['email'],
        'user_type' => (string)$user['user_type'],
        'status' => (string)$user['status'],
      ],
    ];
  }
}
