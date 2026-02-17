<?php
/**
 * MÓDULO: SEGURIDAD DE USUARIOS
 * Archivo: app/models/UserSecurityModel.php
 * Propósito: Consultas directas a tbl_users para gestión de credenciales y estados.
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class UserSecurityModel {
    private $db;

    public function __construct() {
        // Solución al error P1013: Instanciamos la clase Database directamente
        $database = new Database(); 
        $this->db = $database->getConnection();
    }

    public function getAllUsersForSecurity() {
        $sql = "SELECT id, avatar, first_name, last_name, email, user_type, role, status FROM tbl_users";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setNewPassword($id, $hash) {
        $sql = "UPDATE tbl_users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->prepare($sql)->execute([$hash, $id]);
    }

    public function setNewStatus($id, $status) {
        $sql = "UPDATE tbl_users SET status = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->prepare($sql)->execute([$status, $id]);
    }
}