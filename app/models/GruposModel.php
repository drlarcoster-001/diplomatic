<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/models/GruposModel.php
 * Propósito: Clase encargada de las operaciones CRUD de la tabla maestra tbl_grupos.
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class GruposModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "SELECT g.* FROM tbl_grupos g 
                WHERE g.is_active = 1 AND (g.name LIKE ? OR g.modality LIKE ?)
                ORDER BY g.name ASC";
        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_grupos WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert(array $data): int
    {
        $sql = "INSERT INTO tbl_grupos (name, modality, description, created_by) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['modality'],
            !empty($data['description']) ? $data['description'] : null,
            $data['created_by']
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE tbl_grupos SET 
                name = ?, 
                modality = ?, 
                description = ?, 
                updated_by = ? 
                WHERE id = ?";
        return $this->db->prepare($sql)->execute([
            $data['name'],
            $data['modality'],
            !empty($data['description']) ? $data['description'] : null,
            $data['updated_by'],
            $id
        ]);
    }

    public function setInactive(int $id, int $userId): bool
    {
        return $this->db->prepare("UPDATE tbl_grupos SET is_active = 0, updated_by = ? WHERE id = ?")
                        ->execute([$userId, $id]);
    }
}