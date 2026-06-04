<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/models/CohortesConfigModel.php
 * PROPÓSITO: Proporcionar una capa de persistencia avanzada para la configuración técnica y el mantenimiento profundo de las cohortes. Se encarga de operaciones críticas como el restablecimiento de registros desde la papelera y el borrado físico transaccional, integrando salvaguardas de integridad referencial.
 * ACTUALIZACIÓN: Reconfiguración del motor de validación de dependencias en el método hasMovements(). Se migró la consulta desde 'tbl_grupos' hacia 'tbl_academic_offerings' para alinearse con el esquema real de la base de datos y evitar errores de columna inexistente. Ahora el borrado físico queda estrictamente bloqueado si la cohorte está vinculada a una oferta académica activa.
 * VERSIÓN: 1.2.0
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class CohortesConfigModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene TODAS las cohortes registradas, permitiendo auditoría sobre registros activos e inactivos.
     */
    public function getAll(string $search = ''): array
    {
        $sql = "SELECT c.* FROM tbl_cohortes c 
                WHERE (c.name LIKE ? OR c.cohort_code LIKE ?)
                ORDER BY c.id DESC";
        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una cohorte específica por ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_cohortes WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $cohort = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cohort ?: null;
    }

    /**
     * VALIDADOR DE INTEGRIDAD REFERENCIAL:
     * Verifica si existen dependencias en Oferta Académica.
     * Retorna TRUE si está en uso, bloqueando acciones destructivas.
     */
    public function hasMovements(int $id): bool
    {
        // CORRECCIÓN: La relación real está en tbl_academic_offerings (cohort_id)
        $sql = "SELECT COUNT(*) FROM tbl_academic_offerings WHERE cohort_id = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return ((int)$stmt->fetchColumn() > 0);
    }

    /**
     * Restablece una cohorte (is_active = 1) y permite forzar su estatus operativo.
     */
    public function forceUpdateStatus(int $id, string $status, int $userId): bool
    {
        $sql = "UPDATE tbl_cohortes SET 
                cohort_status = ?, 
                is_active = 1, 
                updated_by = ?, 
                updated_at = NOW() 
                WHERE id = ?";
        return $this->db->prepare($sql)->execute([$status, $userId, $id]);
    }

    /**
     * BORRADO FÍSICO DEFINITIVO CON VALIDACIÓN PREVIA:
     * Retorna 'referenced' si hay uso activo, 'deleted' si tuvo éxito, o 'error'.
     */
    public function deletePhysically(int $id): string
    {
        // 1. Verificación de seguridad antes de cualquier operación destructiva
        if ($this->hasMovements($id)) {
            return 'referenced';
        }

        try {
            $this->db->beginTransaction();

            // 1. Limpieza de relaciones en tablas puente
            $this->db->prepare("DELETE FROM tbl_cohort_campuses WHERE cohort_id = ?")->execute([$id]);

            // 2. Eliminación permanente del registro maestro
            $stmtMaster = $this->db->prepare("DELETE FROM tbl_cohortes WHERE id = ?");
            $res = $stmtMaster->execute([$id]);

            $this->db->commit();
            return $res ? 'deleted' : 'error';
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return 'error';
        }
    }
}