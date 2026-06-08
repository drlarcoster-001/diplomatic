<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * ARCHIVO: app/models/PersonalModel.php
 * PROPÓSITO: Capa de persistencia para el catálogo de personal operativo del programa. Gestiona el CRUD del expediente, tipos de personal y validaciones de integridad.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class PersonalModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene todo el personal activo con su tipo.
     */
    public function getAll(string $search = ''): array
    {
        $sql = "SELECT p.*, t.nombre as tipo_nombre
                FROM tbl_personal p
                JOIN tbl_personal_tipos t ON p.tipo_personal_id = t.id
                WHERE p.is_active = 1
                AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.document_id LIKE ? OR t.nombre LIKE ?)
                ORDER BY p.last_name ASC, p.first_name ASC";

        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term, $term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un registro por ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT p.*, t.nombre as tipo_nombre FROM tbl_personal p JOIN tbl_personal_tipos t ON p.tipo_personal_id = t.id WHERE p.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $persona = $stmt->fetch(PDO::FETCH_ASSOC);
        return $persona ?: null;
    }

    /**
     * Obtiene el expediente completo para la vista de edición.
     */
    public function getDetails(int $id): ?array
    {
        return $this->getById($id);
    }

    /**
     * Lista de tipos de personal activos para selectores.
     */
    public function getTipos(): array
    {
        $sql = "SELECT id, nombre FROM tbl_personal_tipos WHERE is_active = 1 ORDER BY nombre ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inserción inicial del registro de personal.
     */
    public function insertBasic(array $data, int $userId): int
    {
        $sql = "INSERT INTO tbl_personal 
                (first_name, last_name, document_id, tipo_personal_id, fecha_inicio, is_active, created_by)
                VALUES (?, ?, ?, ?, ?, 1, ?)";

        $this->db->prepare($sql)->execute([
            trim($data['first_name']),
            trim($data['last_name']),
            trim($data['document_id']),
            (int)$data['tipo_personal_id'],
            !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null,
            $userId
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza el expediente completo del personal.
     */
    public function updateBasic(int $id, array $data, int $userId, ?string $fotoPath = null): bool
    {
        $fotoSql = $fotoPath ? ", foto = ?" : "";

        $sql = "UPDATE tbl_personal SET
                first_name        = ?,
                last_name         = ?,
                document_id       = ?,
                fecha_nacimiento  = ?,
                direccion         = ?,
                estado_civil      = ?,
                email             = ?,
                telefono_local    = ?,
                telefono_celular  = ?,
                grado_instruccion = ?,
                estudios_adicionales = ?,
                tipo_personal_id  = ?,
                fecha_inicio      = ?,
                fecha_fin         = ?,
                updated_by        = ?,
                updated_at        = NOW()
                $fotoSql
                WHERE id = ?";

        $params = [
            trim($data['first_name']),
            trim($data['last_name']),
            trim($data['document_id']),
            !empty($data['fecha_nacimiento'])  ? $data['fecha_nacimiento']  : null,
            !empty($data['direccion'])         ? $data['direccion']         : null,
            !empty($data['estado_civil'])      ? $data['estado_civil']      : null,
            !empty($data['email'])             ? $data['email']             : null,
            !empty($data['telefono_local'])    ? $data['telefono_local']    : null,
            !empty($data['telefono_celular'])  ? $data['telefono_celular']  : null,
            !empty($data['grado_instruccion']) ? $data['grado_instruccion'] : null,
            !empty($data['estudios_adicionales']) ? $data['estudios_adicionales'] : null,
            (int)$data['tipo_personal_id'],
            !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null,
            !empty($data['fecha_fin'])    ? $data['fecha_fin']    : null,
            $userId
        ];

        if ($fotoPath) $params[] = $fotoPath;
        $params[] = $id;

        return $this->db->prepare($sql)->execute($params);
    }

    /**
     * Inactivación lógica del personal.
     */
    public function smartDelete(int $id, int $userId): bool
    {
        return $this->db->prepare("UPDATE tbl_personal SET is_active = 0, updated_by = ?, updated_at = NOW() WHERE id = ?")
                        ->execute([$userId, $id]);
    }
}