<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/Models/AdministrativeInscriptionsModel_s2.php
 * PROPÓSITO: Lógica de persistencia de borradores temporales del Wizard.
 * VERSIÓN: 2.0.0 - División modular y Fix HY093 en parámetros de DUPLICATE KEY.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class AdministrativeInscriptionsModel_s2
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function saveDraft(int $adminId, int $offeringId, array $data): bool
    {
        $sql = "INSERT INTO tbl_administrative_enrollments_draft (admin_id, offering_id, draft_data) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE draft_data = ?, updated_at = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($sql);
        
        $jsonDraft = json_encode($data);
        return $stmt->execute([$adminId, $offeringId, $jsonDraft, $jsonDraft]);
    }

    public function getDraft(int $adminId, int $offeringId): ?array
    {
        $sql = "SELECT draft_data FROM tbl_administrative_enrollments_draft WHERE admin_id = ? AND offering_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$adminId, $offeringId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? json_decode($res['draft_data'], true) : null;
    }
}