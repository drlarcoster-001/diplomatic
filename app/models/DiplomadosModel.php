<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/models/DiplomadosModel.php
 * Propósito: Gestión de persistencia para la tabla tbl_diplomados y sus tablas relacionadas.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class DiplomadosModel 
{
    private $db;

    public function __construct() 
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene diplomados para el listado principal con filtro opcional.
     */
    public function getAll(string $search = ''): array 
    {
        if ($search !== '') {
            $sql = "SELECT id, code, name, total_hours, status 
                    FROM tbl_diplomados 
                    WHERE (name LIKE ? OR code LIKE ?) AND status != 'INACTIVE' 
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $term = "%$search%";
            $stmt->execute([$term, $term]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $sql = "SELECT id, code, name, total_hours, status 
                FROM tbl_diplomados 
                WHERE status != 'INACTIVE' 
                ORDER BY created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un registro maestro por ID.
     */
    public function getById(int $id): ?array 
    {
        $sql = "SELECT * FROM tbl_diplomados WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene la lista de requisitos asociados a un diplomado.
     */
    public function getRequirements(int $diplomadoId): array 
    {
        $sql = "SELECT * FROM tbl_diplomados_requirements WHERE diplomado_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $diplomadoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene la lista de condiciones (contrato) asociadas a un diplomado.
     */
    public function getConditions(int $diplomadoId): array 
    {
        $sql = "SELECT * FROM tbl_diplomados_conditions WHERE diplomado_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $diplomadoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inactivación lógica del diplomado (Cambio de estado).
     */
    public function updateStatus(int $id, string $status): bool 
    {
        $sql = "UPDATE tbl_diplomados SET status = :status WHERE id = :id";
        return $this->db->prepare($sql)->execute([
            ':status' => $status,
            ':id'     => $id
        ]);
    }
}