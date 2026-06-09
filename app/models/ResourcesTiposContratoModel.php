<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * ARCHIVO: app/models/ResourcesTiposContratoModel.php
 * PROPÓSITO: Persistencia del catálogo de tipos de contrato con sus siglas institucionales.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ResourcesTiposContratoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "SELECT t.*,
                (SELECT COUNT(*) FROM tbl_contract_templates p WHERE p.tipo_contrato_id = t.id AND p.is_active = 1) as total_plantillas
                FROM tbl_contract_types t
                WHERE t.is_active = 1
                AND (t.nombre LIKE ? OR t.siglas LIKE ?)
                ORDER BY t.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_contract_types WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $tipo = $stmt->fetch(PDO::FETCH_ASSOC);
        return $tipo ?: null;
    }

    public function getActivosParaSelector(): array
    {
        $sql = "SELECT id, nombre, siglas FROM tbl_contract_types WHERE is_active = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(array $data, int $userId): int
    {
        $sql = "INSERT INTO tbl_contract_types (nombre, siglas, descripcion, is_active, created_by)
                VALUES (?, ?, ?, 1, ?)";

        $this->db->prepare($sql)->execute([
            trim($data['nombre']),
            strtoupper(trim($data['siglas'])),
            !empty($data['descripcion']) ? $data['descripcion'] : null,
            $userId
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE tbl_contract_types SET
                nombre      = ?,
                siglas      = ?,
                descripcion = ?
                WHERE id = ?";

        return $this->db->prepare($sql)->execute([
            trim($data['nombre']),
            strtoupper(trim($data['siglas'])),
            !empty($data['descripcion']) ? $data['descripcion'] : null,
            $id
        ]);
    }

    public function smartDelete(int $id): string
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_contract_templates WHERE tipo_contrato_id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $count = (int)$stmt->fetchColumn();

        if ($count > 0) return 'referenced';

        $res = $this->db->prepare("DELETE FROM tbl_contract_types WHERE id = ?")->execute([$id]);
        return $res ? 'deleted' : 'error';
    }
}