<?php
/**
 * MÓDULO: CONFIGURACIÓN / MÉTODOS DE PAGO
 * ARCHIVO: app/models/MetodosModel.php
 * PROPÓSITO: Gestión de persistencia sobre tbl_settings_payment_methods.
 * VERSIÓN: 1.6.0 - Mapeo de columnas SQL y gestión de tipos.
 */

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class MetodosModel {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(): array {
        $sql = "SELECT * FROM tbl_settings_payment_methods ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function update(int $id, array $data): array {
        try {
            $sql = "UPDATE tbl_settings_payment_methods SET 
                    method_name = ?, titular = ?, identifier = ?, 
                    identification = ?, extra_info = ?, qr_path = ?, 
                    description = ?, status = ?, updated_at = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['method_name'], $data['titular'], $data['identifier'],
                $data['identification'], $data['extra_info'], $data['qr_path'],
                $data['description'], $data['status'], $id
            ]);

            return ['status' => 'success'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function updateStatus(int $id, int $status): bool {
        $sql = "UPDATE tbl_settings_payment_methods SET status = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->prepare($sql)->execute([$status, $id]);
    }
}