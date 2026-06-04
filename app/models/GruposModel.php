<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/models/GruposModel.php
 * Propósito: CRUD maestro de grupos con validación de dependencias y borrado físico.
 * Version: 1.2.0 - Implementación de hasDependencies y deletePhysical.
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

    /**
     * Obtiene todos los grupos activos.
     * Nota: Se mantiene is_active por compatibilidad, pero la eliminación ahora es física si no hay uso.
     */
    public function getAll(string $search = ''): array
    {
        $sql = "SELECT g.* FROM tbl_grupos g 
                WHERE g.is_active = 1";
        
        if (!empty($search)) {
            $sql .= " AND (g.name LIKE ? OR g.modality LIKE ?)";
        }
                
        $sql .= " ORDER BY g.name ASC";
        $stmt = $this->db->prepare($sql);
        
        if (!empty($search)) {
            $term = "%$search%";
            $stmt->execute([$term, $term]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_grupos WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si el grupo tiene registros asociados en el sistema.
     * IMPORTANTE: Ajustar los nombres de tablas según su evolución (ej: tbl_inscripciones).
     */
    public function hasDependencies(int $id): bool 
    {
        // Revisamos si el grupo existe en alguna tabla de relación académica
        // Por ahora se deja una estructura extensible para futuras tablas
        $sql = "SELECT 
                (SELECT COUNT(*) FROM tbl_cohortes WHERE id = -1) as total"; 
                // Nota: Reemplazar 'id = -1' por la columna real cuando existan tablas dependientes
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Elimina físicamente el registro de la base de datos.
     */
    public function deletePhysical(int $id): bool 
    {
        $sql = "DELETE FROM tbl_grupos WHERE id = :id";
        return $this->db->prepare($sql)->execute([':id' => $id]);
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

    /**
     * Inactivación lógica (en desuso por requerimiento de borrado físico, se mantiene por legado).
     */
    public function setInactive(int $id, int $userId): bool
    {
        return $this->db->prepare("UPDATE tbl_grupos SET is_active = 0, updated_by = ? WHERE id = ?")
                        ->execute([$userId, $id]);
    }
}