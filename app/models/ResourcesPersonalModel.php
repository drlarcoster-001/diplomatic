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
        $sql = "SELECT p.*, t.nombre as tipo_nombre, t.siglas,
                pr.photo_path as profesor_foto
                FROM tbl_personal p
                JOIN tbl_personal_tipos t ON p.tipo_personal_id = t.id
                LEFT JOIN tbl_professors pr ON p.profesor_id = pr.id
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
    $stmt = $this->db->prepare("SELECT p.*, t.nombre as tipo_nombre, t.siglas,
    pr.photo_path as profesor_foto, pr.biography as profesor_bio,
    pr.professor_type as profesor_type,
    COALESCE(p.email, u.email) as email,
    COALESCE(p.telefono_celular, u.phone) as telefono_celular
    FROM tbl_personal p 
    JOIN tbl_personal_tipos t ON p.tipo_personal_id = t.id 
    LEFT JOIN tbl_professors pr ON p.profesor_id = pr.id
    LEFT JOIN tbl_users u ON u.id = pr.user_id
    WHERE p.id = ? LIMIT 1");


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
                (expediente, first_name, last_name, document_id, tipo_personal_id, fecha_inicio, profesor_id, is_active, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)";

        $this->db->prepare($sql)->execute([
            $expediente,
            trim($data['first_name']),
            trim($data['last_name']),
            trim($data['document_id']),
            (int)$data['tipo_personal_id'],
            !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null,
            !empty($data['profesor_id']) ? (int)$data['profesor_id'] : null,
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
                banco                = ?,
                tipo_cuenta          = ?,
                numero_cuenta        = ?,
                titular_cuenta       = ?,
                telefono_pago_movil  = ?,
                banco_pago_movil     = ?,
                cedula_pago_movil    = ?,
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
            !empty($data['fecha_fin'])          ? $data['fecha_fin']          : null,
            !empty($data['banco'])              ? $data['banco']              : null,
            !empty($data['tipo_cuenta'])        ? $data['tipo_cuenta']        : null,
            !empty($data['numero_cuenta'])      ? $data['numero_cuenta']      : null,
            !empty($data['titular_cuenta'])     ? $data['titular_cuenta']     : null,
            !empty($data['telefono_pago_movil'])? $data['telefono_pago_movil']: null,
            !empty($data['banco_pago_movil'])    ? $data['banco_pago_movil']    : null,
            !empty($data['cedula_pago_movil'])   ? $data['cedula_pago_movil']   : null,
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

    public function buscarProfesorPorCedula(string $cedula): ?array
{
    $stmt = $this->db->prepare(
    "SELECT p.id, p.first_name, p.last_name, p.photo_path, p.professor_type,
            u.email, u.phone
     FROM tbl_professors p
     LEFT JOIN tbl_users u ON u.id = p.user_id
     WHERE p.identification = ? AND p.is_active = 1 LIMIT 1"
);
    $stmt->execute([trim($cedula)]);
    $profesor = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $profesor ?: null;
}

public function countSesionesProgramadas(int $personalId): int
{
    $stmt = $this->db->prepare(
        "SELECT COUNT(*) FROM tbl_sesiones
         WHERE personal_id = ? AND estado = 'PROGRAMADA' AND is_active = 1"
    );
    $stmt->execute([$personalId]);
    return (int) $stmt->fetchColumn();
}
}