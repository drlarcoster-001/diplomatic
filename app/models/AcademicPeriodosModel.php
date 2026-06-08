<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/models/AcademicPeriodosModel.php
 * PROPÓSITO: Centralizar la persistencia y reglas de negocio de los períodos institucionales.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class AcademicPeriodosModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "SELECT p.*,
                (SELECT COUNT(*) FROM tbl_cohortes c WHERE c.periodo_id = p.id AND c.is_active = 1) as total_cohortes
                FROM tbl_periodos_cohorte p
                WHERE p.is_active = 1
                AND (p.nombre LIKE ? OR p.periodo_code LIKE ?)
                ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_periodos_cohorte WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $periodo = $stmt->fetch(PDO::FETCH_ASSOC);
        return $periodo ?: null;
    }

    public function getDetails(int $id): ?array
    {
        $periodo = $this->getById($id);
        if (!$periodo) return null;

        $stmt = $this->db->prepare("SELECT id, cohort_code, name, cohort_status FROM tbl_cohortes WHERE periodo_id = ? AND is_active = 1 ORDER BY id ASC");
        $stmt->execute([$id]);
        $periodo['cohortes']       = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $periodo['total_cohortes'] = count($periodo['cohortes']);

        return $periodo;
    }

    public function getActivosParaSelector(): array
    {
        $sql = "SELECT id, periodo_code, nombre, estado FROM tbl_periodos_cohorte WHERE is_active = 1 AND estado IN ('Planificado', 'Activo') ORDER BY id DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(array $data): int
    {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO tbl_periodos_cohorte 
                    (periodo_code, nombre, fecha_inicio, fecha_fin, apertura_inscripcion, cierre_inscripcion, descripcion, estado, is_active, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Planificado', 1, ?)";

            $this->db->prepare($sql)->execute([
                $data['periodo_code'],
                $data['nombre'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                !empty($data['apertura_inscripcion']) ? $data['apertura_inscripcion'] : null,
                !empty($data['cierre_inscripcion'])   ? $data['cierre_inscripcion']   : null,
                !empty($data['descripcion'])           ? $data['descripcion']           : null,
                $data['created_by']
            ]);

            $periodoId = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $periodoId;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return 0;
        }
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE tbl_periodos_cohorte SET
                periodo_code         = ?,
                nombre               = ?,
                fecha_inicio         = ?,
                fecha_fin            = ?,
                apertura_inscripcion = ?,
                cierre_inscripcion   = ?,
                descripcion          = ?,
                updated_by           = ?,
                updated_at           = NOW()
                WHERE id = ?";

        return $this->db->prepare($sql)->execute([
            $data['periodo_code'],
            $data['nombre'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            !empty($data['apertura_inscripcion']) ? $data['apertura_inscripcion'] : null,
            !empty($data['cierre_inscripcion'])   ? $data['cierre_inscripcion']   : null,
            !empty($data['descripcion'])           ? $data['descripcion']           : null,
            $data['updated_by'],
            $id
        ]);
    }

    public function updateStatus(int $id, string $status, int $userId): bool
    {
        $res = $this->db->prepare("UPDATE tbl_periodos_cohorte SET estado = ?, updated_by = ?, updated_at = NOW() WHERE id = ?")
                        ->execute([$status, $userId, $id]);

        if ($res && $status === 'Finalizado') {
            $this->db->prepare("UPDATE tbl_cohortes SET is_active = 0, updated_by = ?, updated_at = NOW() WHERE periodo_id = ?")
                     ->execute([$userId, $id]);
        }

        return $res;
    }

    public function smartDelete(int $id, int $userId): string
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_cohortes WHERE periodo_id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $usageCount = (int)$stmt->fetchColumn();

        if ($usageCount > 0) return 'referenced';

        $sql  = "UPDATE tbl_periodos_cohorte SET is_active = 0, updated_by = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $res  = $stmt->execute([$userId, $id]);

        return $res ? 'inactivated' : 'error';
    }

    public function getPeriodoConInscripcionesAbiertas(): ?array
    {
        $sql = "SELECT * FROM tbl_periodos_cohorte 
                WHERE is_active = 1 AND estado = 'Activo'
                AND apertura_inscripcion <= CURDATE()
                AND cierre_inscripcion   >= CURDATE()
                LIMIT 1";
        $stmt   = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}