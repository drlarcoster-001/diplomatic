<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/models/CampusesModel.php
 * Propósito: Clase encargada de las operaciones CRUD de la tabla tbl_campuses con sistema de borrado inteligente (híbrido físico/lógico).
 * Version: 1.1.3 - Versión Maestra Estable. Implementación de eliminación física para registros sin uso y baja lógica para registros con dependencias.
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class CampusesModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene el listado completo de sedes activas con filtro de búsqueda opcional.
     */
    public function getAll(string $search = ''): array
    {
        $sql = "SELECT * FROM tbl_campuses 
                WHERE is_active = 1 AND name LIKE ? 
                ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los datos de una sede específica por su ID.
     */
    public function getById(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_campuses WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Inserta una nueva sede en el sistema.
     */
    public function insert(array $data): int
    {
        $sql = "INSERT INTO tbl_campuses (name) VALUES (?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$data['name']]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza el nombre de una sede existente.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE tbl_campuses SET name = ? WHERE id = ?";
        return $this->db->prepare($sql)->execute([$data['name'], $id]);
    }

    /**
     * BORRADO INTELIGENTE:
     * 1. Verifica si la sede ha sido usada en cohortes.
     * 2. Si no tiene uso, DELETE físico.
     * 3. Si tiene uso, UPDATE is_active = 0 (Lógico).
     */
    public function smartDelete(int $id): bool
    {
        // Consultar dependencias en la tabla intermedia de cohortes
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_cohort_campuses WHERE campus_id = ?");
        $stmt->execute([$id]);
        $usageCount = (int)$stmt->fetchColumn();

        if ($usageCount === 0) {
            // No se ha usado nunca: Borrado físico para limpiar la base de datos
            return $this->db->prepare("DELETE FROM tbl_campuses WHERE id = ?")
                            ->execute([$id]);
        } else {
            // Ya tiene historial: Baja lógica para no romper la integridad de datos
            return $this->db->prepare("UPDATE tbl_campuses SET is_active = 0 WHERE id = ?")
                            ->execute([$id]);
        }
    }
}