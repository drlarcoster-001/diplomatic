<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * ARCHIVO: app/models/ResourcesTiposPersonalModel.php
 * PROPÓSITO: Persistencia del catálogo de tipos de personal con borrado inteligente.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ResourcesTiposPersonalModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "SELECT t.*,
                (SELECT COUNT(*) FROM tbl_personal p WHERE p.tipo_personal_id = t.id AND p.is_active = 1) as total_personal
                FROM tbl_personal_tipos t
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
        $stmt = $this->db->prepare("SELECT * FROM tbl_personal_tipos WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $tipo = $stmt->fetch(PDO::FETCH_ASSOC);
        return $tipo ?: null;
    }

    public function insert(array $data, int $userId): int
    {
        $sql = "INSERT INTO tbl_personal_tipos (nombre, siglas, descripcion, is_active, created_by)
                VALUES (?, ?, ?, 1, ?)";

        $this->db->prepare($sql)->execute([
            trim($data['nombre']),
            strtoupper(trim($data['siglas'])),
            !empty($data['descripcion']) ? $data['descripcion'] : null,
            $userId
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data, int $userId): bool
    {
        $sql = "UPDATE tbl_personal_tipos SET
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
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_personal WHERE tipo_personal_id = ?");
        $stmt->execute([$id]);
        $count = (int)$stmt->fetchColumn();

        if ($count > 0) return 'referenced';

        $res = $this->db->prepare("DELETE FROM tbl_personal_tipos WHERE id = ?")->execute([$id]);
        return $res ? 'deleted' : 'error';
    }
}