<?php
/**
 * MÓDULO: CONFIGURACIÓN / SEGURIDAD
 * ARCHIVO: app/models/SettingsSecurityModel.php
 * PROPÓSITO: Gestión de pre-users y tokens vencidos para limpieza del sistema.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class SettingsSecurityModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene pre-users con su token asociado y si ya existe como user real.
     */
    public function getPreUsers(array $filters = []): array
    {
        $params = [];
        $sql = "SELECT 
                    pu.id,
                    pu.first_name,
                    pu.last_name,
                    pu.email,
                    pu.phone,
                    pu.document_id,
                    pu.status,
                    pu.email_verified,
                    pu.created_at,
                    -- Token más reciente
                    t.expires_at as token_expires_at,
                    t.used_at as token_used_at,
                    t.created_at as token_created_at,
                    -- Si el token está vencido
                    CASE WHEN t.expires_at < NOW() AND t.used_at IS NULL THEN 1 ELSE 0 END as token_expired,
                    -- Si ya existe como usuario real
                    CASE WHEN u.id IS NOT NULL THEN 1 ELSE 0 END as is_registered_user,
                    u.id as user_id
                FROM tbl_pre_users pu
                LEFT JOIN tbl_pre_user_tokens t ON t.pre_user_id = pu.id
                    AND t.id = (SELECT MAX(id) FROM tbl_pre_user_tokens WHERE pre_user_id = pu.id)
                LEFT JOIN tbl_users u ON u.email = pu.email";

        $where = [];

        if (!empty($filters['status'])) {
            $where[] = "pu.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['text'])) {
            $where[] = "(pu.first_name LIKE ? OR pu.last_name LIKE ? OR pu.email LIKE ? OR pu.document_id LIKE ?)";
            $search = "%" . $filters['text'] . "%";
            array_push($params, $search, $search, $search, $search);
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY pu.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina un pre-user y sus tokens asociados.
     */
    public function deletePreUser(int $preUserId): bool
    {
        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();

            // Primero eliminamos los tokens
            $this->db->prepare("DELETE FROM tbl_pre_user_tokens WHERE pre_user_id = ?")
                     ->execute([$preUserId]);

            // Luego eliminamos el pre-user
            $this->db->prepare("DELETE FROM tbl_pre_users WHERE id = ?")
                     ->execute([$preUserId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Error deletePreUser: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina todos los tokens vencidos y no usados, y los pre-users PENDING sin actividad.
     */
    public function cleanExpiredTokens(): array
    {
        try {
            if (!$this->db->inTransaction()) $this->db->beginTransaction();

            // 1. Contar tokens vencidos no usados
            $countTokens = (int)$this->db->query(
                "SELECT COUNT(*) FROM tbl_pre_user_tokens WHERE expires_at < NOW() AND used_at IS NULL"
            )->fetchColumn();

            // 2. Obtener IDs de pre-users PENDING con token vencido y sin user real
            $stmtIds = $this->db->query(
                "SELECT pu.id FROM tbl_pre_users pu
                 INNER JOIN tbl_pre_user_tokens t ON t.pre_user_id = pu.id
                 LEFT JOIN tbl_users u ON u.email = pu.email
                 WHERE pu.status = 'PENDING'
                 AND t.expires_at < NOW()
                 AND t.used_at IS NULL
                 AND u.id IS NULL"
            );
            $ids = $stmtIds->fetchAll(PDO::FETCH_COLUMN);
            $countPreUsers = count($ids);

            // 3. Eliminar tokens vencidos no usados
            $this->db->exec("DELETE FROM tbl_pre_user_tokens WHERE expires_at < NOW() AND used_at IS NULL");

            // 4. Eliminar pre-users PENDING sin user real (sus tokens ya fueron eliminados)
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $this->db->prepare("DELETE FROM tbl_pre_users WHERE id IN ($placeholders) AND status = 'PENDING'")
                         ->execute($ids);
            }

            $this->db->commit();
            return [
                'tokens_eliminados' => $countTokens,
                'pre_users_eliminados' => $countPreUsers
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Error cleanExpiredTokens: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Resumen de estadísticas para las tarjetas del panel.
     */
    public function getSummary(): array
    {
        return [
            'total_pre_users'   => (int)$this->db->query("SELECT COUNT(*) FROM tbl_pre_users")->fetchColumn(),
            'pending'           => (int)$this->db->query("SELECT COUNT(*) FROM tbl_pre_users WHERE status = 'PENDING'")->fetchColumn(),
            'verified'          => (int)$this->db->query("SELECT COUNT(*) FROM tbl_pre_users WHERE status = 'VERIFIED'")->fetchColumn(),
            'tokens_vencidos'   => (int)$this->db->query("SELECT COUNT(*) FROM tbl_pre_user_tokens WHERE expires_at < NOW() AND used_at IS NULL")->fetchColumn(),
        ];
    }
}