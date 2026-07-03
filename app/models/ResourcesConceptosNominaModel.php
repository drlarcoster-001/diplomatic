<?php
/**
 * MÓDULO: RECURSOS HUMANOS / CONCEPTOS DE NÓMINA
 * ARCHIVO: app/models/ResourcesConceptosNominaModel.php
 * PROPÓSITO: CRUD de asignaciones y deducciones del catálogo de nómina.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ResourcesConceptosNominaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // ASIGNACIONES
    // =========================================================================

    public function getAsignaciones(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tbl_asignaciones ORDER BY tipo ASC, nombre ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAsignacionById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_asignaciones WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function asignacionNombreExists(string $nombre, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM tbl_asignaciones WHERE nombre = :nombre";
        $params = [':nombre' => $nombre];
        if ($excludeId) { $sql .= " AND id != :id"; $params[':id'] = $excludeId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function createAsignacion(array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_asignaciones (nombre, tipo, valor, formula, descripcion, is_active, created_by)
             VALUES (:nombre, :tipo, :valor, :formula, :desc, 1, :uid)"
        );
        $stmt->execute([
            ':nombre'  => $data['nombre'],
            ':tipo'    => $data['tipo'],
            ':valor'   => $data['valor']   ?? null,
            ':formula' => $data['formula'] ?? null,
            ':desc'    => $data['descripcion'] ?? null,
            ':uid'     => $userId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateAsignacion(int $id, array $data, int $userId): void
    {
        $this->db->prepare(
            "UPDATE tbl_asignaciones
             SET nombre = :nombre, tipo = :tipo, valor = :valor,
                 formula = :formula, descripcion = :desc, updated_by = :uid
             WHERE id = :id"
        )->execute([
            ':nombre'  => $data['nombre'],
            ':tipo'    => $data['tipo'],
            ':valor'   => $data['valor']   ?? null,
            ':formula' => $data['formula'] ?? null,
            ':desc'    => $data['descripcion'] ?? null,
            ':uid'     => $userId,
            ':id'      => $id,
        ]);
    }

    public function deleteAsignacion(int $id, int $userId): string
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_nomina_personal_asignaciones WHERE asignacion_id = :id"
        );
        $stmt->execute([':id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->db->prepare(
                "UPDATE tbl_asignaciones SET is_active = 0, updated_by = :uid WHERE id = :id"
            )->execute([':uid' => $userId, ':id' => $id]);
            return 'inactivated';
        }
        $this->db->prepare("DELETE FROM tbl_asignaciones WHERE id = :id")->execute([':id' => $id]);
        return 'deleted';
    }

    // =========================================================================
    // DEDUCCIONES
    // =========================================================================

    public function getDeducciones(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tbl_deducciones ORDER BY tipo ASC, nombre ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDeduccionById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_deducciones WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function deduccionNombreExists(string $nombre, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM tbl_deducciones WHERE nombre = :nombre";
        $params = [':nombre' => $nombre];
        if ($excludeId) { $sql .= " AND id != :id"; $params[':id'] = $excludeId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function createDeduccion(array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_deducciones (nombre, tipo, valor, formula, descripcion, is_active, created_by)
             VALUES (:nombre, :tipo, :valor, :formula, :desc, 1, :uid)"
        );
        $stmt->execute([
            ':nombre'  => $data['nombre'],
            ':tipo'    => $data['tipo'],
            ':valor'   => $data['valor']   ?? null,
            ':formula' => $data['formula'] ?? null,
            ':desc'    => $data['descripcion'] ?? null,
            ':uid'     => $userId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateDeduccion(int $id, array $data, int $userId): void
    {
        $this->db->prepare(
            "UPDATE tbl_deducciones
             SET nombre = :nombre, tipo = :tipo, valor = :valor,
                 formula = :formula, descripcion = :desc, updated_by = :uid
             WHERE id = :id"
        )->execute([
            ':nombre'  => $data['nombre'],
            ':tipo'    => $data['tipo'],
            ':valor'   => $data['valor']   ?? null,
            ':formula' => $data['formula'] ?? null,
            ':desc'    => $data['descripcion'] ?? null,
            ':uid'     => $userId,
            ':id'      => $id,
        ]);
    }

    public function deleteDeduccion(int $id, int $userId): string
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_nomina_personal_deducciones WHERE deduccion_id = :id"
        );
        $stmt->execute([':id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->db->prepare(
                "UPDATE tbl_deducciones SET is_active = 0, updated_by = :uid WHERE id = :id"
            )->execute([':uid' => $userId, ':id' => $id]);
            return 'inactivated';
        }
        $this->db->prepare("DELETE FROM tbl_deducciones WHERE id = :id")->execute([':id' => $id]);
        return 'deleted';
    }
}