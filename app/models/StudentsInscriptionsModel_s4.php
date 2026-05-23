<?php
namespace App\Models;
use App\Core\Database;
use PDO;

final class StudentsInscriptionsModel_s4 {
    private PDO $db;
    public function __construct() { $this->db = (new Database())->getConnection(); }

    public function registerInitialPayment(array $data): bool {
        $sql = "INSERT INTO tbl_payments (inscription_id, method, amount, metadata, status) 
                VALUES (:ins, :meth, :amount, :meta, 'PENDING')";
        return $this->db->prepare($sql)->execute([
            'ins'    => $data['inscription_id'],
            'meth'   => $data['method'],
            'amount' => $data['amount'],
            'meta'   => json_encode($data['metadata'])
        ]);
    }
}