<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / PROVEEDORES
 * ARCHIVO: app/models/FinancialProveedoresModel.php
 * PROPÓSITO: Persistencia del catálogo de proveedores externos del programa.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class FinancialProveedoresModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "SELECT * FROM tbl_proveedores
                WHERE is_active = 1
                AND (nombre LIKE ? OR rif_cedula LIKE ? OR email LIKE ?)
                ORDER BY nombre ASC";

        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_proveedores WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $prov = $stmt->fetch(PDO::FETCH_ASSOC);
        return $prov ?: null;
    }

    public function getActivosParaSelector(): array
    {
        $sql = "SELECT id, nombre, rif_cedula, tipo FROM tbl_proveedores WHERE is_active = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(array $data, int $userId): int
    {
        $sql = "INSERT INTO tbl_proveedores
                (nombre, rif_cedula, tipo, email, telefono, direccion,
                 banco, tipo_cuenta, numero_cuenta, titular_cuenta,
                 telefono_pago_movil, banco_pago_movil, cedula_pago_movil,
                 is_active, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";

        $this->db->prepare($sql)->execute([
            trim($data['nombre']),
            trim($data['rif_cedula']),
            $data['tipo'] ?? 'Persona Natural',
            !empty($data['email'])               ? $data['email']               : null,
            !empty($data['telefono'])            ? $data['telefono']            : null,
            !empty($data['direccion'])           ? $data['direccion']           : null,
            !empty($data['banco'])               ? $data['banco']               : null,
            !empty($data['tipo_cuenta'])         ? $data['tipo_cuenta']         : null,
            !empty($data['numero_cuenta'])       ? $data['numero_cuenta']       : null,
            !empty($data['titular_cuenta'])      ? $data['titular_cuenta']      : null,
            !empty($data['telefono_pago_movil']) ? $data['telefono_pago_movil'] : null,
            !empty($data['banco_pago_movil'])    ? $data['banco_pago_movil']    : null,
            !empty($data['cedula_pago_movil'])   ? $data['cedula_pago_movil']   : null,
            $userId
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data, int $userId): bool
    {
        $sql = "UPDATE tbl_proveedores SET
                nombre               = ?,
                rif_cedula           = ?,
                tipo                 = ?,
                email                = ?,
                telefono             = ?,
                direccion            = ?,
                banco                = ?,
                tipo_cuenta          = ?,
                numero_cuenta        = ?,
                titular_cuenta       = ?,
                telefono_pago_movil  = ?,
                banco_pago_movil     = ?,
                cedula_pago_movil    = ?,
                updated_by           = ?,
                updated_at           = NOW()
                WHERE id = ?";

        return $this->db->prepare($sql)->execute([
            trim($data['nombre']),
            trim($data['rif_cedula']),
            $data['tipo'] ?? 'Persona Natural',
            !empty($data['email'])               ? $data['email']               : null,
            !empty($data['telefono'])            ? $data['telefono']            : null,
            !empty($data['direccion'])           ? $data['direccion']           : null,
            !empty($data['banco'])               ? $data['banco']               : null,
            !empty($data['tipo_cuenta'])         ? $data['tipo_cuenta']         : null,
            !empty($data['numero_cuenta'])       ? $data['numero_cuenta']       : null,
            !empty($data['titular_cuenta'])      ? $data['titular_cuenta']      : null,
            !empty($data['telefono_pago_movil']) ? $data['telefono_pago_movil'] : null,
            !empty($data['banco_pago_movil'])    ? $data['banco_pago_movil']    : null,
            !empty($data['cedula_pago_movil'])   ? $data['cedula_pago_movil']   : null,
            $userId,
            $id
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->db->prepare("DELETE FROM tbl_proveedores WHERE id = ?")
                        ->execute([$id]);
    }
}