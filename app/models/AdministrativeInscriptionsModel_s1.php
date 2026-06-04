<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/Models/AdministrativeInscriptionsModel_s1.php
 * PROPÓSITO: Lógica de BD para el catálogo de ofertas y validación temprana (Paso 1).
 * VERSIÓN: 2.1.1 - Asegurando visibilidad pública de métodos para el controlador.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class AdministrativeInscriptionsModel_s1
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Método que el controlador llama en la línea 39.
     */
    public function getOpenOfferings(): array
    {
        $sql = "SELECT o.id, o.total_capacity, o.enrolled_count, o.registration_end, 
                       d.name as diploma_name, c.name as cohort_name
                FROM tbl_academic_offerings o
                INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                INNER JOIN tbl_cohortes c ON o.cohort_id = c.id
                WHERE o.status = 'ABIERTA' AND o.is_active = 1
                ORDER BY o.registration_end ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchParticipants(string $term): array
    {
        $sql = "SELECT id, document_id, first_name, last_name, email, 
                       IFNULL(avatar, 'default.png') as avatar, 
                       undergraduate_degree, provenance 
                FROM tbl_users 
                WHERE (document_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)
                AND user_type = 'PARTICIPANT' AND status = 'ACTIVE'
                LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $t = "%$term%";
        $stmt->execute([$t, $t, $t, $t]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkExistingEnrollment(int $userId, int $offeringId): bool
    {
        $sql = "SELECT COUNT(*) FROM tbl_administrative_enrollments 
                WHERE user_id = ? AND offering_id = ? AND status != 'ANULADA'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $offeringId]);
        
        return ((int)$stmt->fetchColumn()) > 0;
    }
}
