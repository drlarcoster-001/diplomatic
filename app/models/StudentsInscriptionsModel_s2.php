<?php
namespace App\Models;
use App\Core\Database;
use PDO;

final class StudentsInscriptionsModel_s2 {
    private PDO $db;
    public function __construct() { $this->db = (new Database())->getConnection(); }

    public function getStudentProfile(int $userId): ?array {
        $stmt = $this->db->prepare("SELECT undergraduate_degree, provenance FROM tbl_users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}