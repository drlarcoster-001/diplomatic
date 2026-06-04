<?php
namespace App\Models;
use App\Core\Database;
use PDO;

final class StudentsInscriptionsModel_s3 {
    private PDO $db;
    public function __construct() { $this->db = (new Database())->getConnection(); }

    public function saveDocumentMetadata(int $inscriptionId, string $type, string $path): bool {
        $sql = "INSERT INTO tbl_inscription_documents (inscription_id, doc_type, file_path, uploaded_at) 
                VALUES (:ins, :type, :path, NOW())";
        return $this->db->prepare($sql)->execute(['ins' => $inscriptionId, 'type' => $type, 'path' => $path]);
    }
}