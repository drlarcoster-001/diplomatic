<?php
/**
 * MÓDULO: SEGURIDAD DE USUARIOS
 * Archivo: app/models/UserSecurityModel.php
 * Propósito: Consultas directas a tbl_users para gestión de credenciales, estados y eliminación física.
 */

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class UserSecurityModel {
    private $db;

    public function __construct() {
        // Instancia de conexión a la base de datos
        $database = new Database(); 
        $this->db = $database->getConnection();
    }

    /**
     * Obtiene todos los usuarios con datos básicos para la cuadrícula de seguridad.
     */
    public function getAllUsersForSecurity() {
        $sql = "SELECT id, avatar, first_name, last_name, email, user_type, role, status FROM tbl_users";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza el hash de la contraseña de un usuario.
     */
    public function setNewPassword($id, $hash) {
        $sql = "UPDATE tbl_users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->prepare($sql)->execute([$hash, $id]);
    }

    /**
     * Cambia el estado de acceso de un usuario (ACTIVE/INACTIVE).
     */
    public function setNewStatus($id, $status) {
        $sql = "UPDATE tbl_users SET status = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->prepare($sql)->execute([$status, $id]);
    }

    /**
     * Intenta eliminar físicamente el registro del usuario.
     * Captura errores de restricción de llave foránea si el usuario tiene asociaciones.
     */
    public function deleteUserPhysically($id) {
        try {
            $sql = "DELETE FROM tbl_users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);

            return [
                'success' => true,
                'message' => 'Usuario eliminado correctamente.'
            ];
        } catch (PDOException $e) {
            // Código SQLSTATE 23000: Error de integridad referencial (asociaciones en otras tablas)
            if ($e->getCode() === '23000') {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar: El usuario tiene datos vinculados (inscripciones, pagos o registros académicos).'
                ];
            }

            return [
                'success' => false,
                'message' => 'Error técnico al intentar eliminar: ' . $e->getMessage()
            ];
        }
    }
}