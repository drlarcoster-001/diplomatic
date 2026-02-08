<?php
/**
 * MÓDULO: USUARIOS, ROLES Y ACCESO
 * Archivo: app/models/UserModel.php
 * Propósito: Lógica de base de datos para usuarios.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class UserModel
{
    /**
     * Busca un usuario por email para el login.
     * Incluye el campo 'role' y 'password_hash'.
     */
    public function findByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        // IMPORTANTE: Aquí seleccionamos 'role'
        $sql = "SELECT id, user_type, status, first_name, last_name, email, password_hash, role
                FROM tbl_users
                WHERE email = :email LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Obtiene todos los usuarios para el listado.
     */
    public function getAllUsers(): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT id, user_type, status, first_name, last_name, email, role, created_at
                FROM tbl_users
                WHERE status != 'INACTIVE' 
                ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Crea un nuevo usuario (usado por el Admin).
     */
    public function create(array $data): bool
    {
        $pdo = Database::getConnection();

        // 1. Verificar duplicados
        $check = $this->findByEmail($data['email']);
        if ($check) {
            return false; // El correo ya existe
        }

        // 2. Insertar nuevo registro
        $sql = "INSERT INTO tbl_users (first_name, last_name, email, password_hash, role, user_type, status, created_at) 
                VALUES (:fname, :lname, :email, :pass, :role, :utype, :status, NOW())";

        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            ':fname'  => $data['first_name'],
            ':lname'  => $data['last_name'],
            ':email'  => $data['email'],
            ':pass'   => $data['password'],
            ':role'   => $data['role'],
            ':utype'  => $data['user_type'],
            ':status' => $data['status']
        ]);
    }

    /**
     * Verifica credenciales y devuelve los datos del usuario + ROL.
     */
    public function verifyLogin(string $email, string $password): array
    {
        $email = trim($email);
        
        if ($email === '' || $password === '') {
            return ['ok' => false, 'message' => 'Debe ingresar correo y contraseña.', 'user' => null];
        }

        $user = $this->findByEmail($email);

        if (!$user) {
            return ['ok' => false, 'message' => 'Credenciales inválidas.', 'user' => null];
        }

        if (($user['status'] ?? '') !== 'ACTIVE') {
            return ['ok' => false, 'message' => 'Usuario inactivo o suspendido.', 'user' => null];
        }

        if (!password_verify($password, (string)$user['password_hash'])) {
            return ['ok' => false, 'message' => 'Credenciales inválidas.', 'user' => null];
        }

        // Retornamos los datos limpios, incluyendo el ROL
        return [
            'ok' => true,
            'message' => 'Acceso concedido.',
            'user' => [
                'id' => (int)$user['id'],
                'first_name' => (string)$user['first_name'],
                'last_name' => (string)$user['last_name'],
                'email' => (string)$user['email'],
                'user_type' => (string)$user['user_type'],
                'role' => (string)$user['role'], // <--- CRÍTICO
                'status' => (string)$user['status'],
            ],
        ];
    }
}