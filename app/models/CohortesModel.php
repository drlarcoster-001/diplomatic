<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/models/CohortesModel.php
 * PROPÓSITO: Centralizar la persistencia y reglas de negocio de los periodos académicos (Cohortes). Gestiona el ciclo de vida de cada periodo, su vinculación geográfica con sedes físicas (tbl_campuses) y garantiza la integridad referencial del sistema.
 * ACTUALIZACIÓN: Reforzamiento del método smartDelete() para la Grid Principal. Se ha restringido la operación exclusivamente a una inactivación lógica (is_active = 0). Se implementó el validador de dependencias que retorna 'referenced' si el registro posee vínculos en 'tbl_academic_offerings', impidiendo que la cohorte sea dada de baja mientras sea operativa.
 * VERSIÓN: 2.19.0
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class CohortesModel
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene todas las cohortes activas, incluyendo los nombres de las sedes vinculadas.
     */
    public function getAll(string $search = ''): array
    {
        $sql = "SELECT c.*, 
                (SELECT GROUP_CONCAT(cp.name SEPARATOR ', ') 
                 FROM tbl_campuses cp 
                 JOIN tbl_cohort_campuses cc ON cp.id = cc.campus_id 
                 WHERE cc.cohort_id = c.id) as campus_names
                FROM tbl_cohortes c 
                WHERE c.is_active = 1 AND (c.name LIKE ? OR c.cohort_code LIKE ?)
                ORDER BY c.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una cohorte por su ID primario.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_cohortes WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $cohort = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cohort ?: null;
    }

    /**
     * Obtiene el detalle completo de una cohorte incluyendo los IDs y nombres de sus sedes.
     */
    public function getDetails(int $id): ?array
    {
        $cohort = $this->getById($id);
        if (!$cohort) return null;

        // IDs de sedes para los selectores (Select2)
        $stmtIds = $this->db->prepare("SELECT campus_id FROM tbl_cohort_campuses WHERE cohort_id = ?");
        $stmtIds->execute([$id]);
        $cohort['campus_ids'] = $stmtIds->fetchAll(PDO::FETCH_COLUMN);

        // Nombres de sedes concatenados para visualización
        $stmtNames = $this->db->prepare("
            SELECT name FROM tbl_campuses c 
            JOIN tbl_cohort_campuses cc ON c.id = cc.campus_id 
            WHERE cc.cohort_id = ?
        ");
        $stmtNames->execute([$id]);
        $names = $stmtNames->fetchAll(PDO::FETCH_COLUMN);
        $cohort['campus_names'] = !empty($names) ? implode(', ', $names) : 'No definida';

        return $cohort;
    }

    /**
     * Lista maestra de sedes activas para selectores.
     */
    public function getActiveCampuses(): array
    {
        $sql = "SELECT id, name FROM tbl_campuses WHERE is_active = 1 ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserta una nueva cohorte en estado 'Planificada' por defecto.
     */
    public function insert(array $data): int
    {
        try {
            $this->db->beginTransaction();
            $sql = "INSERT INTO tbl_cohortes (cohort_code, name, start_date, end_date, enrollment_start, enrollment_end, description, base_campus, cohort_status, created_by, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Planificada', ?, 1)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['cohort_code'], $data['name'], $data['start_date'], $data['end_date'],
                !empty($data['enrollment_start']) ? $data['enrollment_start'] : null,
                !empty($data['enrollment_end']) ? $data['enrollment_end'] : null,
                !empty($data['description']) ? $data['description'] : null,
                !empty($data['base_campus']) ? $data['base_campus'] : null,
                $data['created_by']
            ]);
            $cohortId = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $cohortId;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return 0;
        }
    }

    /**
     * Actualiza la información de una cohorte existente.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE tbl_cohortes SET 
                cohort_code = ?, name = ?, start_date = ?, end_date = ?, 
                enrollment_start = ?, enrollment_end = ?, description = ?, 
                base_campus = ?, updated_by = ?, updated_at = NOW() 
                WHERE id = ?";
        
        return $this->db->prepare($sql)->execute([
            $data['cohort_code'], $data['name'], $data['start_date'], $data['end_date'],
            !empty($data['enrollment_start']) ? $data['enrollment_start'] : null,
            !empty($data['enrollment_end']) ? $data['enrollment_end'] : null,
            !empty($data['description']) ? $data['description'] : null,
            !empty($data['base_campus']) ? $data['base_campus'] : null,
            $data['updated_by'], $id
        ]);
    }

    /**
     * Sincroniza las sedes asociadas a una cohorte en la tabla relacional.
     */
    public function syncCampuses(int $cohortId, array $campusIds): void
    {
        $this->db->prepare("DELETE FROM tbl_cohort_campuses WHERE cohort_id = ?")->execute([$cohortId]);
        if (!empty($campusIds)) {
            $stmt = $this->db->prepare("INSERT INTO tbl_cohort_campuses (cohort_id, campus_id) VALUES (?, ?)");
            foreach ($campusIds as $campusId) {
                $stmt->execute([$cohortId, $campusId]);
            }
        }
    }

    /**
     * Actualiza el estatus operativo de la cohorte.
     */
    public function updateStatus(int $id, string $status, int $userId): bool
    {
        return $this->db->prepare("UPDATE tbl_cohortes SET cohort_status = ?, updated_by = ? WHERE id = ?")
                        ->execute([$status, $userId, $id]);
    }

    /**
     * INACTIVACIÓN LÓGICA CON VALIDACIÓN DE REGISTROS RELACIONADOS:
     * 1. Si está en uso en Oferta Académica -> Retorna 'referenced' (No se toca).
     * 2. Si no tiene uso -> Actualiza is_active = 0 (Inactivación).
     */
    public function smartDelete(int $id, int $userId): string
    {
        // 1. Verificación preventiva de integridad en Oferta Académica
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_academic_offerings WHERE cohort_id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $usageCount = (int)$stmt->fetchColumn();

        if ($usageCount > 0) {
            // BLOQUEO: Posee ofertas activas vinculadas
            return 'referenced';
        }

        // 2. Ejecución de baja lógica (Ocultar registro)
        $sql = "UPDATE tbl_cohortes SET is_active = 0, updated_by = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $res = $stmt->execute([$userId, $id]);

        return $res ? 'inactivated' : 'error';
    }
}