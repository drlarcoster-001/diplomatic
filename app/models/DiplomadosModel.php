<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/models/DiplomadosModel.php
 * Propósito: Gestión de persistencia con auditoría y validación de dependencias.
 * Version: 1.4.6 - Ampliación de hasDependencies para incluir tbl_academic_offerings.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class DiplomadosModel 
{
    private $db;

    public function __construct() 
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene diplomados visibles (que no estén marcados como INACTIVOS).
     */
    public function getAll(string $search = ''): array 
    {
        $sql = "SELECT id, code, name, total_hours, status 
                FROM tbl_diplomados 
                WHERE status != 'INACTIVO'";
        
        if ($search !== '') {
            $sql .= " AND (name LIKE ? OR code LIKE ?)";
        }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        
        if ($search !== '') {
            $term = "%$search%";
            $stmt->execute([$term, $term]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si el diplomado ya está siendo usado en el sistema académico.
     * CORRECCIÓN: Ahora también revisa la tabla de Ofertas Académicas.
     */
    public function hasDependencies(int $id): bool 
    {
        // Añadimos la consulta a tbl_academic_offerings (diploma_id)
        $sql = "SELECT 
                (SELECT COUNT(*) FROM tbl_cohortes WHERE diplomado_id = :id1) +
                (SELECT COUNT(*) FROM tbl_grupos WHERE diplomado_id = :id2) +
                (SELECT COUNT(*) FROM tbl_academic_offerings WHERE diploma_id = :id3) as total";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id1' => $id, 
            ':id2' => $id,
            ':id3' => $id
        ]);
        
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Elimina físicamente el registro de la base de datos.
     */
    public function deletePhysical(int $id): bool 
    {
        $sql = "DELETE FROM tbl_diplomados WHERE id = :id";
        return $this->db->prepare($sql)->execute([':id' => $id]);
    }

    public function isCodeDuplicate(string $code, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM tbl_diplomados WHERE code = ? AND id != ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code, $excludeId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Inserción con soporte para auditoría de creación.
     */
    public function insert(array $data): int 
    {
        $sql = "INSERT INTO tbl_diplomados (
                    code, name, total_hours, description, directed_to, 
                    status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['code'],
            $data['name'] ?? 'Borrador: ' . $data['code'],
            (int)($data['total_hours'] ?? 0),
            $data['description'] ?? null,
            $data['directed_to'] ?? null,
            $data['status'] ?? 'BORRADOR',
            $data['created_by']
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualización con soporte para auditoría de modificación.
     */
    public function update(int $id, array $data): bool 
    {
        $sql = "UPDATE tbl_diplomados SET 
                    code = ?, name = ?, total_hours = ?, 
                    description = ?, directed_to = ?, 
                    status = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ?";
        
        return $this->db->prepare($sql)->execute([
            $data['code'], 
            $data['name'], 
            (int)$data['total_hours'],
            $data['description'] ?? null, 
            $data['directed_to'] ?? null,
            $data['status'], 
            $data['updated_by'], 
            $id
        ]);
    }

    public function getById(int $id): ?array 
    {
        $sql = "SELECT * FROM tbl_diplomados WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function updateStatus(int $id, string $status): bool 
    {
        $sql = "UPDATE tbl_diplomados SET status = :status WHERE id = :id";
        return $this->db->prepare($sql)->execute([':status' => $status, ':id' => $id]);
    }

    // --- MÉTODOS PARA REQUISITOS Y CONDICIONES ---

    public function saveRequirements(int $id, array $items): void 
    {
        $this->db->prepare("DELETE FROM tbl_diplomados_requirements WHERE diplomado_id = ?")->execute([$id]);
        $sql = "INSERT INTO tbl_diplomados_requirements (diplomado_id, requirement_text) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        foreach ($items as $text) {
            if (!empty(trim((string)$text))) $stmt->execute([$id, $text]);
        }
    }

    public function saveConditions(int $id, array $items): void 
    {
        $this->db->prepare("DELETE FROM tbl_diplomados_conditions WHERE diplomado_id = ?")->execute([$id]);
        $sql = "INSERT INTO tbl_diplomados_conditions (diplomado_id, condition_text) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        foreach ($items as $text) {
            if (!empty(trim((string)$text))) $stmt->execute([$id, $text]);
        }
    }

    public function getRequirements(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM tbl_diplomados_requirements WHERE diplomado_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getConditions(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM tbl_diplomados_conditions WHERE diplomado_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}