<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/Models/InscriptionModel.php
 * PROPÓSITO: Clase encargada de la persistencia y lógica de datos para Inscripciones (Aspirantes).
 * VERSIÓN: 1.0.0 - Implementación de CRUD con Auditoría integrada.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\AuditService;
use PDO;

final class InscriptionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene todos los aspirantes con el nombre del diplomado asociado.
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT i.*, d.name as diploma_name 
                FROM tbl_inscriptions i
                LEFT JOIN tbl_diplomados d ON i.diploma_id = d.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND i.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (i.first_name LIKE ? OR i.last_name LIKE ? OR i.id_card LIKE ?)";
            $term = "%" . $filters['search'] . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY i.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un nuevo aspirante en el sistema.
     */
    public function create(array $data): bool
    {
        try {
            $sql = "INSERT INTO tbl_inscriptions 
                    (first_name, last_name, id_card, email, phone, diploma_id, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'PENDIENTE', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['id_card'],
                $data['email'],
                $data['phone'],
                $data['diploma_id']
            ]);

            if ($result) {
                AuditService::log([
                    'module'      => 'ADMINISTRATIVE_INSCRIPTIONS',
                    'action'      => 'CREATE_RECORD',
                    'description' => "Nueva solicitud de inscripción creada para: " . $data['first_name'] . " " . $data['last_name'],
                    'event_type'  => 'NORMAL'
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            error_log("Error en InscriptionModel::create -> " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el estatus de una inscripción (Aprobación/Rechazo).
     */
    public function updateStatus(int $id, string $status): bool
    {
        $sql = "UPDATE tbl_inscriptions SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$status, $id]);

        if ($result) {
            AuditService::log([
                'module'      => 'ADMINISTRATIVE_INSCRIPTIONS',
                'action'      => 'UPDATE_STATUS',
                'description' => "Se cambió el estatus de la inscripción ID #$id a: $status",
                'event_type'  => 'WARNING'
            ]);
        }

        return $result;
    }

    /**
     * Elimina físicamente una solicitud de inscripción.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM tbl_inscriptions WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$id]);

        if ($result) {
            AuditService::log([
                'module'      => 'ADMINISTRATIVE_INSCRIPTIONS',
                'action'      => 'DELETE_RECORD',
                'description' => "Se eliminó la solicitud de inscripción ID #$id",
                'event_type'  => 'DANGER'
            ]);
        }

        return $result;
    }
}