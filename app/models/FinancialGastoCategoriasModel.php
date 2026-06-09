<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / EGRESOS
 * ARCHIVO: app/models/FinancialGastoCategoriasModel.php
 * PROPÓSITO: Persistencia del catálogo de categorías de gasto institucional.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class FinancialGastoCategoriasModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM tbl_gasto_conceptos g WHERE g.categoria_id = c.id AND g.is_active = 1) as total_conceptos
                FROM tbl_gasto_categorias c
                WHERE c.is_active = 1
                AND (c.codigo LIKE ? OR c.nombre LIKE ?)
                ORDER BY c.codigo ASC";

        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_gasto_categorias WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cat ?: null;
    }

    public function getActivasParaSelector(): array
    {
        $sql = "SELECT id, codigo, nombre FROM tbl_gasto_categorias WHERE is_active = 1 ORDER BY codigo ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(array $data, int $userId): int
    {
        $sql = "INSERT INTO tbl_gasto_categorias (codigo, nombre, descripcion, is_active, created_by)
                VALUES (?, ?, ?, 1, ?)";

        $this->db->prepare($sql)->execute([
            strtoupper(trim($data['codigo'])),
            trim($data['nombre']),
            !empty($data['descripcion']) ? $data['descripcion'] : null,
            $userId
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE tbl_gasto_categorias SET
                codigo      = ?,
                nombre      = ?,
                descripcion = ?
                WHERE id = ?";

        return $this->db->prepare($sql)->execute([
            strtoupper(trim($data['codigo'])),
            trim($data['nombre']),
            !empty($data['descripcion']) ? $data['descripcion'] : null,
            $id
        ]);
    }

    public function smartDelete(int $id): string
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_gasto_conceptos WHERE categoria_id = ? AND is_active = 1");
        $stmt->execute([$id]);
        $count = (int)$stmt->fetchColumn();

        if ($count > 0) return 'referenced';

        $res = $this->db->prepare("DELETE FROM tbl_gasto_categorias WHERE id = ?")->execute([$id]);
        return $res ? 'deleted' : 'error';
    }
}