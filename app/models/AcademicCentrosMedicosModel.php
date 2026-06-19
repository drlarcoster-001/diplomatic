<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/models/AcademicCentrosMedicosModel.php
 * PROPÓSITO: Acceso a datos de tbl_centros_medicos. Incluye búsqueda por nombre/dirección,
 *            validación de nombre duplicado y borrado inteligente (smartDelete) según
 *            referencias activas en tbl_horarios_practicas.
 * VERSIÓN: 1.1.0 - Fix de conexión: ya no extiende una clase Model inexistente, ahora
 *           construye su propia conexión PDO vía App\Core\Database, igual que el Bootstrap.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class AcademicCentrosMedicosModel
{
    private PDO $db;
    protected string $table = 'tbl_centros_medicos';

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "SELECT id, nombre, direccion, is_active, created_at, updated_at
                FROM {$this->table}
                WHERE is_active = 1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (nombre LIKE :search OR direccion LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql .= " ORDER BY nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function nameExists(string $nombre, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE nombre = :nombre";
        $params = [':nombre' => $nombre];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (nombre, direccion, is_active, created_by)
             VALUES (:nombre, :direccion, 1, :created_by)"
        );
        $stmt->execute([
            ':nombre'     => $data['nombre'],
            ':direccion'  => $data['direccion'] ?? null,
            ':created_by' => $userId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET nombre = :nombre, direccion = :direccion, updated_by = :updated_by
             WHERE id = :id"
        );
        return $stmt->execute([
            ':nombre'     => $data['nombre'],
            ':direccion'  => $data['direccion'] ?? null,
            ':updated_by' => $userId,
            ':id'         => $id,
        ]);
    }

    public function hasReferences(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_horarios_practicas WHERE centro_medico_id = :id"
        );
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function smartDelete(int $id, int $userId): string
    {
        if ($this->hasReferences($id)) {
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} SET is_active = 0, updated_by = :uid WHERE id = :id"
            );
            $stmt->execute([':uid' => $userId, ':id' => $id]);
            return 'inactivated';
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return 'deleted';
    }
}