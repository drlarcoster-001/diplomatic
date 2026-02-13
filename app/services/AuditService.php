<?php
/**
 * MODULE - app/services/AuditService.php
 * Final Version: Professional IP Tracking with Public IP Fallback.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class AuditService
{
    public static function log(array $data): void
    {
        try {
            $db = (new Database())->getConnection();
            $ipAddress = self::getPublicIP(); // <--- Ahora obtenemos la IP Real

            $sql = "INSERT INTO tbl_audit_logs (
                        user_id, session_id, ip_address, user_agent, device, os,
                        module, action, description, entity, entity_id, db_action,
                        data_before, data_after, endpoint, http_method, event_type,
                        request_id, environment
                    ) VALUES (
                        :user_id, :session_id, :ip_addr, :ua, :dev, :os,
                        :mod, :act, :desc, :ent, :ent_id, :db_act,
                        :d_bef, :d_aft, :endp, :meth, :ev_type,
                        :req_id, :env
                    )";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':user_id'     => $_SESSION['user']['id'] ?? null,
                ':session_id'  => session_id(),
                ':ip_addr'     => $ipAddress,
                ':ua'          => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                ':dev'         => self::getDevice($_SERVER['HTTP_USER_AGENT'] ?? ''),
                ':os'          => self::getOS($_SERVER['HTTP_USER_AGENT'] ?? ''),
                ':mod'         => $data['module'] ?? 'GENERAL',
                ':act'         => strtoupper($data['action'] ?? 'ACCESS'),
                ':desc'        => $data['description'] ?? '',
                ':ent'         => $data['entity'] ?? null,
                ':ent_id'      => $data['entity_id'] ?? null,
                ':db_act'      => $data['db_action'] ?? null,
                ':d_bef'       => isset($data['data_before']) ? json_encode($data['data_before']) : null,
                ':d_aft'       => isset($data['data_after']) ? json_encode($data['data_after']) : null,
                ':endp'        => $_SERVER['REQUEST_URI'] ?? '',
                ':meth'        => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                ':ev_type'     => $data['event_type'] ?? 'NORMAL',
                ':req_id'      => bin2hex(random_bytes(8)),
                ':env'         => 'DEV'
            ]);
        } catch (\Exception $e) {
            error_log("Audit Error: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la IP Pública incluso en entorno local (XAMPP).
     */
    private static function getPublicIP(): string
    {
        // 1. Intentamos obtener la IP del servidor de forma normal
        $external_ip = '0.0.0.0';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $external_ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $external_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $external_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        // 2. Si es una IP local (::1 o 127.0.0.1), consultamos a una API externa
        if ($external_ip === '::1' || $external_ip === '127.0.0.1' || strpos($external_ip, '192.168.') === 0) {
            try {
                // Consultamos a ipify para obtener la IP pública real del router
                $ctx = stream_context_create(['http' => ['timeout' => 2]]);
                $public_ip = @file_get_contents('https://api.ipify.org', false, $ctx);
                if ($public_ip !== false) {
                    return $public_ip;
                }
            } catch (\Exception $e) {
                return 'LOCAL_DEV';
            }
        }

        return $external_ip;
    }

    private static function getDevice(string $ua): string { return 'desktop'; }
    private static function getOS(string $ua): string { return 'Windows'; }
}