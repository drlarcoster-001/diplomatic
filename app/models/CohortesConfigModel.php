<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/models/CohortesConfigModel.php
 * Propósito: Clase encargada de las operaciones avanzadas (restauración y borrado físico) de tbl_cohortes.
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class CohortesConfigModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene TODAS las cohortes (Incluso las inactivas/eliminadas lógicamente).
     */
    public function getAll(string $search = ''): array
    {
        // Nota: NO filtramos por is_active = 1 para poder ver la papelera
        $sql = "SELECT c.* FROM tbl_cohortes c 
                WHERE (c.name LIKE ? OR c.cohort_code LIKE ?)
                ORDER BY c.id DESC";
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

    /**
     * Forzar estatus y restablecer de la papelera (is_active = 1)
     */
    public function forceUpdateStatus(int $id, string $status, int $userId): bool
    {
        $sql = "UPDATE tbl_cohortes SET 
                cohort_status = ?, 
                is_active = 1, 
                updated_by = ? 
                WHERE id = ?";
        return $this->db->prepare($sql)->execute([$status, $userId, $id]);
    }

    /**
     * BORRADO FÍSICO DEFINITIVO
     * (Solo funcionará si no hay restricciones de Foreign Key activas)
     */
    public function deletePhysically(int $id): bool
    {
        $sql = "DELETE FROM tbl_cohortes WHERE id = ?";
        return $this->db->prepare($sql)->execute([$id]);
    }
}