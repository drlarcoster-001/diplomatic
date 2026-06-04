<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/Models/OfertaAcademicaModel.php
 * PROPÓSITO: Centralizar la persistencia para Oferta Académica con ciclo de venta estricto.
 * VERSIÓN: 3.43.0 - Integración de persistencia de Fecha de Vencimiento (due_date) en planes de pago.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class OfertaAcademicaModel
{
    private PDO $db;

    /**
     * Ciclo de Venta Oficial (Estatus de Oferta)
     */
    public const STATUS_BORRADOR   = 'BORRADOR';   // En preparación, invisible
    public const STATUS_ABIERTA    = 'ABIERTA';    // Disponible para inscripciones
    public const STATUS_CERRADA    = 'CERRADA';    // Inscripciones terminadas/cupo lleno
    public const STATUS_SUSPENDIDA = 'SUSPENDIDA'; // Pausada temporalmente
    public const STATUS_CANCELADA  = 'CANCELADA';  // Anulada totalmente

    private const ALLOWED_STATUSES = [
        self::STATUS_BORRADOR,
        self::STATUS_ABIERTA,
        self::STATUS_CERRADA,
        self::STATUS_SUSPENDIDA,
        self::STATUS_CANCELADA
    ];

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

/**
     * Obtiene todas las ofertas con cálculo de disponibilidad y proyección de grupos.
     */
    public function getAll(array $filters = []): array
    {
        $params = [];
        $sql = "SELECT o.*, d.name as diplomado_name, c.cohort_code, c.name as cohort_name,
                       o.total_capacity as cupos_totales,
                       o.enrolled_count,
                       (o.total_capacity - o.enrolled_count) as cupos_disponibles,
                       (SELECT GROUP_CONCAT(g.name SEPARATOR ', ') 
                        FROM tbl_academic_offering_groups og 
                        INNER JOIN tbl_grupos g ON og.group_id = g.id 
                        WHERE og.offering_id = o.id) AS grupos_nombres
                FROM tbl_academic_offerings o
                JOIN tbl_diplomados d ON o.diploma_id = d.id
                JOIN tbl_cohortes c ON o.cohort_id = c.id
                WHERE o.is_active = 1";

        if (!empty($filters['diploma_id'])) {
            $sql .= " AND o.diploma_id = ?";
            $params[] = (int)$filters['diploma_id'];
        }

        if (!empty($filters['cohort_id'])) {
            $sql .= " AND o.cohort_id = ?";
            $params[] = (int)$filters['cohort_id'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], self::ALLOWED_STATUSES, true)) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY o.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getById(int $id): ?array
    {
        $sql = "SELECT o.*, d.name as diplomado_name, c.cohort_code, c.name as cohort_name,
                       (o.total_capacity - o.enrolled_count) as cupos_disponibles
                FROM tbl_academic_offerings o
                JOIN tbl_diplomados d ON o.diploma_id = d.id
                JOIN tbl_cohortes c ON o.cohort_id = c.id
                WHERE o.id = ? AND o.is_active = 1 LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $oferta = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($oferta) {
            $offeringId = (int)$oferta['id'];
            
            // Sedes vinculadas
            $stmt = $this->db->prepare("SELECT campus_id FROM tbl_academic_offering_campuses WHERE offering_id = ?");
            $stmt->execute([$offeringId]);
            $oferta['campuses'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Grupos asignados
            $stmt = $this->db->prepare("SELECT group_id FROM tbl_academic_offering_groups WHERE offering_id = ?");
            $stmt->execute([$offeringId]);
            $oferta['groups'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Profesores y sus roles
            $stmt = $this->db->prepare("SELECT professor_id, role FROM tbl_academic_offering_professors WHERE offering_id = ?");
            $stmt->execute([$offeringId]);
            $oferta['professors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Plan de Pagos detallado - INCLUYE DUE_DATE
            $stmt = $this->db->prepare("SELECT name, amount, due_date, notes FROM tbl_academic_offering_payment_plans WHERE offering_id = ? ORDER BY id ASC");
            $stmt->execute([$offeringId]);
            $oferta['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $oferta ?: null;
    }

    public function getCampusesByCohortId(int $cohortId): array 
    { 
        $sql = "SELECT c.id, c.name 
                FROM tbl_campuses c 
                JOIN tbl_cohort_campuses cc ON c.id = cc.campus_id 
                WHERE cc.cohort_id = ? AND c.is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cohortId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); 
    }

    public function insert(array $data, int $userId): int
    {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO tbl_academic_offerings 
                    (diploma_id, cohort_id, total_capacity, registration_start, registration_end, 
                     class_start, class_end, general_modality, total_cost, description, status, created_by, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['diploma_id'], $data['cohort_id'], $data['total_capacity'],
                $data['registration_start'], $data['registration_end'],
                $data['class_start'], $data['class_end'],
                $data['general_modality'], $data['total_cost'],
                $data['description'], self::STATUS_BORRADOR, $userId
            ]);

            $offeringId = (int)$this->db->lastInsertId();
            $this->saveRelations($offeringId, $data);

            $this->db->commit();
            return $offeringId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en OfertaAcademicaModel::insert -> " . $e->getMessage());
            return 0;
        }
    }

    public function update(int $id, array $data, int $userId): bool
    {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE tbl_academic_offerings SET 
                    total_capacity = ?, registration_start = ?, registration_end = ?, 
                    class_start = ?, class_end = ?, general_modality = ?, 
                    total_cost = ?, description = ?, updated_at = NOW(), updated_by = ?
                    WHERE id = ? AND is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['total_capacity'], $data['registration_start'], $data['registration_end'],
                $data['class_start'], $data['class_end'], $data['general_modality'],
                $data['total_cost'], $data['description'], $userId, $id
            ]);

            $stmtDel = $this->db->prepare("DELETE FROM tbl_academic_offering_campuses WHERE offering_id = ?");
            $stmtDel->execute([$id]);
            
            $stmtDel = $this->db->prepare("DELETE FROM tbl_academic_offering_groups WHERE offering_id = ?");
            $stmtDel->execute([$id]);

            $stmtDel = $this->db->prepare("DELETE FROM tbl_academic_offering_professors WHERE offering_id = ?");
            $stmtDel->execute([$id]);

            $stmtDel = $this->db->prepare("DELETE FROM tbl_academic_offering_payment_plans WHERE offering_id = ?");
            $stmtDel->execute([$id]);

            $this->saveRelations($id, $data);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en OfertaAcademicaModel::update -> " . $e->getMessage());
            return false;
        }
    }

    private function saveRelations(int $offeringId, array $data): void
    {
        if (!empty($data['campuses'])) {
            $stmt = $this->db->prepare("INSERT INTO tbl_academic_offering_campuses (offering_id, campus_id) VALUES (?, ?)");
            foreach ($data['campuses'] as $cid) {
                $stmt->execute([$offeringId, (int)$cid]);
            }
        }

        if (!empty($data['groups'])) {
            $stmt = $this->db->prepare("INSERT INTO tbl_academic_offering_groups (offering_id, group_id) VALUES (?, ?)");
            foreach ($data['groups'] as $gid) {
                $stmt->execute([$offeringId, (int)$gid]);
            }
        }

        if (!empty($data['professor_id'])) {
            $stmt = $this->db->prepare("INSERT INTO tbl_academic_offering_professors (offering_id, professor_id, role) VALUES (?, ?, ?)");
            foreach ($data['professor_id'] as $idx => $pid) {
                $role = $data['professor_role'][$idx] ?? 'PRINCIPAL';
                $stmt->execute([$offeringId, (int)$pid, $role]);
            }
        }

        /**
         * --- ACTUALIZACIÓN DE PERSISTENCIA DE PAGOS ---
         * Ahora incluye la columna 'due_date'
         */
        if (!empty($data['payment_concept'])) {
            $stmt = $this->db->prepare("INSERT INTO tbl_academic_offering_payment_plans (offering_id, name, amount, due_date, notes) VALUES (?, ?, ?, ?, ?)");
            foreach ($data['payment_concept'] as $idx => $concept) {
                $amount = $data['payment_amount'][$idx] ?? 0;
                $dueDate = $data['payment_due_date'][$idx] ?? null; // Recibido del Controlador
                $notes = $data['payment_description'][$idx] ?? null; 
                $stmt->execute([$offeringId, $concept, $amount, $dueDate, $notes]);
            }
        }
    }

    public function updateStatus(int $id, string $status, int $userId): bool
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) return false;

        $stmt = $this->db->prepare("UPDATE tbl_academic_offerings SET status = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
        return $stmt->execute([$status, $userId, $id]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE tbl_academic_offerings SET is_active = 0, status = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
        return $stmt->execute([self::STATUS_CANCELADA, $userId, $id]);
    }

    // --- Consultas Maestras ---
    public function getActiveDiplomas(): array 
    { 
        return $this->db->query("SELECT id, name FROM tbl_diplomados WHERE status = 'ACTIVO' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC); 
    }

    public function getOperableCohorts(): array 
    { 
        return $this->db->query("SELECT id, cohort_code, name, enrollment_start, enrollment_end, start_date, end_date FROM tbl_cohortes WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC); 
    }

    public function getActiveGroups(): array 
    { 
        return $this->db->query("SELECT id, name, modality, description FROM tbl_grupos WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC); 
    }

    public function getActiveProfessors(): array 
    { 
        return $this->db->query("SELECT id, full_name FROM tbl_professors WHERE is_active = 1 ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC); 
    }
}