<?php
/**
 * MÓDULO: USUARIOS
 * Archivo: app/models/UserModel.php
 * Propósito: Gestión de persistencia para la tabla tbl_users.
 * Integra la lógica de autenticación y el perfil completo del usuario.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class UserModel 
{
    private $db;

    public function __construct() 
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Verifica las credenciales de acceso.
     */
    public function verifyLogin(string $email, string $password): array|bool 
    {
        $sql = "SELECT * FROM tbl_users WHERE email = :email AND status = 'ACTIVE' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            $this->db->prepare("UPDATE tbl_users SET last_login_at = NOW() WHERE id = ?")->execute([$user['id']]);
            return $user;
        }
        return false;
    }

    /**
     * Obtiene usuarios activos y suspendidos únicamente.
     * Excluye los marcados como INACTIVE.
     */
    public function getAll(): array 
    {
        $sql = "SELECT * FROM tbl_users WHERE status != 'INACTIVE' ORDER BY created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool 
    {
        $sql = "INSERT INTO tbl_users (
                    user_type, status, first_name, last_name, email, role, 
                    phone, document_id, address, provenance, 
                    undergraduate_degree, avatar, password_hash
                ) VALUES (
                    :user_type, :status, :first_name, :last_name, :email, :role, 
                    :phone, :document_id, :address, :provenance, 
                    :undergraduate_degree, :avatar, :password_hash
                )";
        return $this->db->prepare($sql)->execute($data);
    }

    public function update(int $id, array $data): bool 
    {
        $sql = "UPDATE tbl_users SET 
                    user_type = :user_type, status = :status, first_name = :first_name, 
                    last_name = :last_name, email = :email, role = :role, 
                    phone = :phone, document_id = :document_id, address = :address, 
                    provenance = :provenance, undergraduate_degree = :undergraduate_degree, 
                    avatar = :avatar 
                WHERE id = :id";
        $data['id'] = $id;
        return $this->db->prepare($sql)->execute($data);
    }

    /**
     * Baja lógica: Cambia el estado a INACTIVE.
     */
    public function delete(int $id): bool 
    {
        $sql = "UPDATE tbl_users SET status = 'INACTIVE' WHERE id = :id";
        return $this->db->prepare($sql)->execute([':id' => $id]);
    }
}