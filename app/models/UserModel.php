<?php
/**
 * MÓDULO: USUARIOS
 * Archivo: app/models/UserModel.php
 * Propósito: Gestionar la lógica de datos de tbl_users, incluyendo perfil extendido y avatar.
 * Cambio: Integración de campos address, provenance, undergraduate_degree y avatar.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class UserModel
{
    /**
     * Verifica credenciales y devuelve datos básicos del usuario.
     */
    public function verifyLogin(string $email, string $password): array
    {
        $pdo = Database::getConnection();
        $email = trim($email);
        
        $sql = "SELECT id, first_name, last_name, email, password_hash, role, user_type, status, avatar 
                FROM tbl_users WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return ['ok' => false, 'message' => 'Credenciales incorrectas.'];
        if (($user['status'] ?? '') !== 'ACTIVE') return ['ok' => false, 'message' => 'Usuario inactivo.'];
        if (!password_verify($password, (string)$user['password_hash'])) return ['ok' => false, 'message' => 'Credenciales incorrectas.'];

        return ['ok' => true, 'user' => $user];
    }

    /**
     * Obtiene todos los usuarios activos con sus datos extendidos.
     */
    public function getAll(): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT id, first_name, last_name, document_id as cedula, phone, email, role, status, 
                       address, provenance, undergraduate_degree, avatar, created_at 
                FROM tbl_users WHERE status != 'INACTIVE' ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Valida duplicados de Email o Cédula (ignora el ID actual al editar).
     */
    public function exists(string $email, string $cedula, int $excludeId = 0): bool
    {
        $pdo = Database::getConnection();
        $sql = "SELECT id FROM tbl_users WHERE (email = :email OR document_id = :cedula) AND id != :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email, ':cedula' => $cedula, ':id' => $excludeId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Inserta un nuevo usuario con perfil de preinscripción.
     */
    public function create(array $data): bool
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO tbl_users (
                    first_name, last_name, document_id, phone, email, password_hash, 
                    role, user_type, status, address, provenance, undergraduate_degree, avatar, created_at
                ) VALUES (
                    :fname, :lname, :cedula, :phone, :email, :pass, 
                    :role, 'INTERNAL', 'ACTIVE', :address, :provenance, :degree, :avatar, NOW()
                )";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':fname'      => $data['first_name'],
            ':lname'      => $data['last_name'],
            ':cedula'     => $data['document_id'],
            ':phone'      => $data['phone'],
            ':email'      => $data['email'],
            ':pass'       => $data['password'],
            ':role'       => $data['role'],
            ':address'    => $data['address'] ?? null,
            ':provenance' => $data['provenance'] ?? null,
            ':degree'     => $data['undergraduate_degree'] ?? null,
            ':avatar'     => $data['avatar'] ?? 'default_avatar.png'
        ]);
    }

    /**
     * Actualiza los datos del usuario. La contraseña no se toca en este flujo.
     */
    public function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();
        
        $sql = "UPDATE tbl_users SET 
                first_name = :fname, 
                last_name = :lname, 
                document_id = :cedula, 
                phone = :phone, 
                email = :email, 
                role = :role,
                address = :address,
                provenance = :provenance,
                undergraduate_degree = :degree,
                avatar = :avatar 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            ':id'       => $id,
            ':fname'    => $data['first_name'],
            ':lname'    => $data['last_name'],
            ':cedula'   => $data['document_id'],
            ':phone'    => $data['phone'],
            ':email'    => $data['email'],
            ':role'     => $data['role'],
            ':address'  => $data['address'] ?? null,
            ':provenance'=> $data['provenance'] ?? null,
            ':degree'   => $data['undergraduate_degree'] ?? null,
            ':avatar'   => $data['avatar'] ?? 'default_avatar.png'
        ]);
    }

    /**
     * Desactivación lógica de usuario (Soft Delete).
     */
    public function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE tbl_users SET status = 'INACTIVE' WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}