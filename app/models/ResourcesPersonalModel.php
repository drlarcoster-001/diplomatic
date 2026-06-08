<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * ARCHIVO: app/models/ResourcesPersonalModel.php
 * PROPÓSITO: Capa de persistencia para el catálogo de personal operativo del programa.
 * VERSIÓN: 1.2.0 - Soporte para expediente automático, cv_path y siglas de tipo.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ResourcesPersonalModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(string $search = ''): array
    {
        $sql = "SELECT p.*, t.nombre as tipo_nombre, t.siglas
                FROM tbl_personal p
                JOIN tbl_personal_tipos t ON p.tipo_personal_id = t.id
                WHERE p.is_active = 1
                AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.document_id LIKE ? OR p.expediente LIKE ? OR t.nombre LIKE ?)
                ORDER BY p.last_name ASC, p.first_name ASC";

        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term, $term, $term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT p.*, t.nombre as tipo_nombre, t.siglas FROM tbl_personal p JOIN tbl_personal_tipos t ON p.tipo_personal_id = t.id WHERE p.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $persona = $stmt->fetch(PDO::FETCH_ASSOC);
        return $persona ?: null;
    }

    public function getDetails(int $id): ?array
    {
        return $this->getById($id);
    }

    public function getTipos(): array
    {
        $sql = "SELECT id, nombre, siglas FROM tbl_personal_tipos WHERE is_active = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertBasic(array $data, int $userId): int
    {
        $stmtTipo = $this->db->prepare("SELECT siglas FROM tbl_personal_tipos WHERE id = ? LIMIT 1");
        $stmtTipo->execute([(int)$data['tipo_personal_id']]);
        $siglas = $stmtTipo->fetchColumn() ?: 'GEN';

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM tbl_personal WHERE tipo_personal_id = ?");
        $stmtCount->execute([(int)$data['tipo_personal_id']]);
        $count       = (int)($stmtCount->fetchColumn() ?? 0);
        $correlativo = str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);

        $expediente = 'DIP-' . $siglas . '-' . trim($data['document_id']) . '-' . $correlativo;

        $sql = "INSERT INTO tbl_personal 
                (expediente, first_name, last_name, document_id, tipo_personal_id, fecha_inicio, is_active, created_by)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?)";

        $this->db->prepare($sql)->execute([
            $expediente,
            trim($data['first_name']),
            trim($data['last_name']),
            trim($data['document_id']),
            (int)$data['tipo_personal_id'],
            !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null,
            $userId
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateBasic(int $id, array $data, int $userId, ?string $fotoPath = null, ?string $cvPath = null): bool
    {
        $fotoSql = $fotoPath ? ", foto = ?" : "";
        $cvSql   = $cvPath   ? ", cv_path = ?" : "";

        $sql = "UPDATE tbl_personal SET
                first_name           = ?,
                last_name            = ?,
                document_id          = ?,
                fecha_nacimiento     = ?,
                direccion            = ?,
                estado_civil         = ?,
                email                = ?,
                telefono_local       = ?,
                telefono_celular     = ?,
                grado_instruccion    = ?,
                estudios_adicionales = ?,
                tipo_personal_id     = ?,
                fecha_inicio         = ?,
                fecha_fin            = ?,
                updated_by           = ?,
                updated_at           = NOW()
                $fotoSql
                $cvSql
                WHERE id = ?";

        $params = [
            trim($data['first_name']),
            trim($data['last_name']),
            trim($data['document_id']),
            !empty($data['fecha_nacimiento'])     ? $data['fecha_nacimiento']     : null,
            !empty($data['direccion'])            ? $data['direccion']            : null,
            !empty($data['estado_civil'])         ? $data['estado_civil']         : null,
            !empty($data['email'])                ? $data['email']                : null,
            !empty($data['telefono_local'])       ? $data['telefono_local']       : null,
            !empty($data['telefono_celular'])     ? $data['telefono_celular']     : null,
            !empty($data['grado_instruccion'])    ? $data['grado_instruccion']    : null,
            !empty($data['estudios_adicionales']) ? $data['estudios_adicionales'] : null,
            (int)$data['tipo_personal_id'],
            !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null,
            !empty($data['fecha_fin'])    ? $data['fecha_fin']    : null,
            $userId
        ];

        if ($fotoPath) $params[] = $fotoPath;
        if ($cvPath)   $params[] = $cvPath;
        $params[] = $id;

        return $this->db->prepare($sql)->execute($params);
    }

    public function deletePhysical(int $id): bool
    {
        return $this->db->prepare("DELETE FROM tbl_personal WHERE id = ?")
                        ->execute([$id]);
    }

    public function smartDelete(int $id, int $userId): bool
    {
        return $this->db->prepare("UPDATE tbl_personal SET is_active = 0, updated_by = ?, updated_at = NOW() WHERE id = ?")
                        ->execute([$userId, $id]);
    }
}