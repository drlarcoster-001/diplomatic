<?php
/**
 * MÓDULO: USUARIOS
 * Archivo: app/models/UserModel.php
 * Cambio: La función update YA NO toca la contraseña.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class UserModel
{
    // --- LOGIN ---
    public function verifyLogin(string $email, string $password): array
    {
        $pdo = Database::getConnection();
        $email = trim($email);
        
        $sql = "SELECT id, first_name, last_name, email, password_hash, role, user_type, status 
                FROM tbl_users WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return ['ok' => false, 'message' => 'Credenciales incorrectas.'];
        if (($user['status'] ?? '') !== 'ACTIVE') return ['ok' => false, 'message' => 'Usuario inactivo.'];
        if (!password_verify($password, (string)$user['password_hash'])) return ['ok' => false, 'message' => 'Credenciales incorrectas.'];

        return ['ok' => true, 'user' => $user];
    }

    // --- LISTAR ---
    public function getAll(): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT id, first_name, last_name, document_id as cedula, phone, email, role, status, created_at 
                FROM tbl_users WHERE status != 'INACTIVE' ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // --- VALIDAR DUPLICADOS (Inteligente) ---
    // Si enviamos $excludeId, ignora ese usuario (vital para editar sin errores)
    public function exists(string $email, string $cedula, int $excludeId = 0): bool
    {
        $pdo = Database::getConnection();
        $sql = "SELECT id FROM tbl_users WHERE (email = :email OR document_id = :cedula) AND id != :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email, ':cedula' => $cedula, ':id' => $excludeId]);
        return (bool)$stmt->fetch();
    }

    // --- CREAR (Requiere Password) ---
    public function create(array $data): bool
    {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO tbl_users (first_name, last_name, document_id, phone, email, password_hash, role, user_type, status, created_at) 
                VALUES (:fname, :lname, :cedula, :phone, :email, :pass, :role, 'INTERNAL', 'ACTIVE', NOW())";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':fname' => $data['first_name'], ':lname' => $data['last_name'],
            ':cedula' => $data['document_id'], ':phone' => $data['phone'],
            ':email' => $data['email'], ':pass' => $data['password'],
            ':role' => $data['role']
        ]);
    }

    // --- ACTUALIZAR (SIN PASSWORD) ---
    public function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();
        
        // Solo actualizamos datos personales y rol. La contraseña se ignora aquí.
        $sql = "UPDATE tbl_users SET 
                first_name = :fname, 
                last_name = :lname, 
                document_id = :cedula, 
                phone = :phone, 
                email = :email, 
                role = :role 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            ':id'    => $id,
            ':fname' => $data['first_name'],
            ':lname' => $data['last_name'],
            ':cedula'=> $data['document_id'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':role'  => $data['role']
        ]);
    }

    // --- ELIMINAR ---
    public function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE tbl_users SET status = 'INACTIVE' WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}