<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / EGRESOS
 * ARCHIVO: app/models/FinancialGastoConceptosModel.php
 * PROPÓSITO: Persistencia del catálogo de conceptos de gasto institucional.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class FinancialGastoConceptosModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "SELECT g.*, c.nombre as categoria_nombre, c.codigo as categoria_codigo
                FROM tbl_gasto_conceptos g
                JOIN tbl_gasto_categorias c ON g.categoria_id = c.id
                WHERE g.is_active = 1
                AND (g.codigo LIKE ? OR g.nombre LIKE ? OR c.nombre LIKE ?)
                ORDER BY g.codigo ASC";

        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT g.*, c.nombre as categoria_nombre, c.codigo as categoria_codigo
                FROM tbl_gasto_conceptos g
                JOIN tbl_gasto_categorias c ON g.categoria_id = c.id
                WHERE g.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $concepto = $stmt->fetch(PDO::FETCH_ASSOC);
        return $concepto ?: null;
    }

    public function getCategorias(): array
    {
        $sql = "SELECT id, codigo, nombre FROM tbl_gasto_categorias WHERE is_active = 1 ORDER BY codigo ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActivosParaSelector(): array
    {
        $sql = "SELECT g.id, g.codigo, g.nombre, c.nombre as categoria_nombre
                FROM tbl_gasto_conceptos g
                JOIN tbl_gasto_categorias c ON g.categoria_id = c.id
                WHERE g.is_active = 1
                ORDER BY g.codigo ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(array $data, int $userId): int
    {
        $sql = "INSERT INTO tbl_gasto_conceptos (codigo, nombre, categoria_id, descripcion, is_active, created_by)
                VALUES (?, ?, ?, ?, 1, ?)";

        $this->db->prepare($sql)->execute([
            strtoupper(trim($data['codigo'])),
            trim($data['nombre']),
            (int)$data['categoria_id'],
            !empty($data['descripcion']) ? $data['descripcion'] : null,
            $userId
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE tbl_gasto_conceptos SET
                codigo       = ?,
                nombre       = ?,
                categoria_id = ?,
                descripcion  = ?
                WHERE id = ?";

        return $this->db->prepare($sql)->execute([
            strtoupper(trim($data['codigo'])),
            trim($data['nombre']),
            (int)$data['categoria_id'],
            !empty($data['descripcion']) ? $data['descripcion'] : null,
            $id
        ]);
    }

    public function smartDelete(int $id): string
    {
        $res = $this->db->prepare("DELETE FROM tbl_gasto_conceptos WHERE id = ?")->execute([$id]);
        return $res ? 'deleted' : 'error';
    }
}