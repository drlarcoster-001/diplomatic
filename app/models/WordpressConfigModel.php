<?php
/**
 * MÓDULO: CONFIGURACIÓN / WORDPRESS
 * ARCHIVO: app/Models/WordpressConfigModel.php
 * PROPÓSITO: Gestión de lectura y escritura de credenciales del Bridge de WP.
 * VERSIÓN: 1.1.6 - Estandarización de retornos seguros y optimización de UPSERT.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

final class WordpressConfigModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene la configuración actual de WordPress.
     * @return array Array asociativo garantizado con wp_url, wp_user y wp_pass
     */
    public function getConfig(): array
    {
        try {
            $sql = "SELECT wp_url, wp_user, wp_pass 
                    FROM tbl_settings_wordpress 
                    WHERE id = 1 
                    LIMIT 1";
            
            $stmt = $this->db->query($sql);
            
            if (!$stmt) {
                throw new Exception("Fallo en la consulta o la tabla no existe.");
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return [
                    'wp_url'  => '',
                    'wp_user' => '',
                    'wp_pass' => ''
                ];
            }

            return $result;

        } catch (Exception $e) {
            error_log("❌ Error al cargar config WP: " . $e->getMessage());
            return [
                'wp_url'  => '', 
                'wp_user' => '', 
                'wp_pass' => ''
            ]; 
        }
    }

    /**
     * Guarda o actualiza las credenciales en la base de datos (Lógica UPSERT).
     */
    public function saveConfig(string $url, string $user, string $pass): bool
    {
        try {
            $sql = "INSERT INTO tbl_settings_wordpress (id, wp_url, wp_user, wp_pass) 
                    VALUES (1, :url_insert, :user_insert, :pass_insert) 
                    ON DUPLICATE KEY UPDATE 
                        wp_url = :url_update,
                        wp_user = :user_update,
                        wp_pass = :pass_update";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':url_insert'  => $url,
                ':user_insert' => $user,
                ':pass_insert' => $pass,
                ':url_update'  => $url,
                ':user_update' => $user,
                ':pass_update' => $pass
            ]);

            return true;

        } catch (Exception $e) {
            error_log("❌ Error SQL al guardar config WP: " . $e->getMessage());
            throw new Exception("Error en la base de datos al guardar la configuración.");
        }
    }
}