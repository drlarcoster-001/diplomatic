<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/models/CohortesModel.php
 * Propósito: Clase encargada de las operaciones CRUD de la tabla tbl_cohortes.
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class CohortesModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "SELECT c.* FROM tbl_cohortes c 
                WHERE c.is_active = 1 AND (c.name LIKE ? OR c.cohort_code LIKE ?)
                ORDER BY c.start_date DESC";
        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_cohortes WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert(array $data): int
    {
        $sql = "INSERT INTO tbl_cohortes (cohort_code, name, start_date, end_date, enrollment_start, enrollment_end, description, base_campus, cohort_status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        $defaultStatus = 'Planificada'; 

        $stmt->execute([
            $data['cohort_code'],
            $data['name'],
            $data['start_date'],
            $data['end_date'],
            !empty($data['enrollment_start']) ? $data['enrollment_start'] : null,
            !empty($data['enrollment_end']) ? $data['enrollment_end'] : null,
            !empty($data['description']) ? $data['description'] : null,
            !empty($data['base_campus']) ? $data['base_campus'] : null,
            $defaultStatus,
            $data['created_by']
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE tbl_cohortes SET 
                cohort_code = ?, 
                name = ?, 
                start_date = ?, 
                end_date = ?, 
                enrollment_start = ?, 
                enrollment_end = ?, 
                description = ?, 
                base_campus = ?, 
                updated_by = ? 
                WHERE id = ?";
        
        return $this->db->prepare($sql)->execute([
            $data['cohort_code'],
            $data['name'],
            $data['start_date'],
            $data['end_date'],
            !empty($data['enrollment_start']) ? $data['enrollment_start'] : null,
            !empty($data['enrollment_end']) ? $data['enrollment_end'] : null,
            !empty($data['description']) ? $data['description'] : null,
            !empty($data['base_campus']) ? $data['base_campus'] : null,
            $data['updated_by'],
            $id
        ]);
    }

    public function updateStatus(int $id, string $status, int $userId): bool
    {
        return $this->db->prepare("UPDATE tbl_cohortes SET cohort_status = ?, updated_by = ? WHERE id = ?")
                        ->execute([$status, $userId, $id]);
    }

    public function setInactive(int $id, int $userId): bool
    {
        return $this->db->prepare("UPDATE tbl_cohortes SET is_active = 0, updated_by = ? WHERE id = ?")
                        ->execute([$userId, $id]);
    }
}