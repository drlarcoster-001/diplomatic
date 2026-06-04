<?php
/**
 * MÓDULO: NÚCLEO / SEGURIDAD
 * ARCHIVO: app/services/AuditService.php
 * PROPÓSITO: Servicio centralizado de auditoría con soporte polimórfico y fix de constante global mediante constant() para Intelephense.
 * VERSIÓN: 1.6.1 - Fix definitivo de error P1011 (Undefined constant) y mantenimiento de compatibilidad string/array.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class AuditService
{
    /**
     * Registra un evento en la tabla de auditoría.
     * Soporta tanto arreglos como cadenas de texto para no romper la compatibilidad con otros módulos.
     * @param mixed $data Información del evento (array o string)
     */
    public static function log($data): void
    {
        try {
            // NORMALIZACIÓN: Si el módulo envía un string (ej: desde Cohortes), lo convertimos al formato de array esperado
            if (is_string($data)) {
                $data = ['description' => $data];
            }

            $db = (new Database())->getConnection();
            $ipAddress = self::getPublicIP(); 

            /**
             * REGLA TÉCNICA: Verificación segura de constante ENVIRONMENT.
             * FIX P1011: Se usa constant() para evitar que el linter busque un símbolo inexistente en tiempo de análisis.
             */
            $env = defined('ENVIRONMENT') ? constant('ENVIRONMENT') : 'PROD';

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
            
            /**
             * INTEGRIDAD DE DATOS: 
             * tbl_users usa INT(10) UNSIGNED. Forzamos el casteo para asegurar compatibilidad exacta con la DB.
             */
            $userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

            $stmt->execute([
                ':user_id'     => $userId,
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
                ':env'         => $env
            ]);
        } catch (\Exception $e) {
            // Registro silencioso en log del servidor para evitar detener procesos críticos
            error_log("Critical Audit Failure: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la IP Pública incluso en entorno local (XAMPP).
     */
    private static function getPublicIP(): string
    {
        $external_ip = '0.0.0.0';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $external_ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $external_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $external_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        if ($external_ip === '::1' || $external_ip === '127.0.0.1' || strpos($external_ip, '192.168.') === 0) {
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 2]]);
                $public_ip = @file_get_contents('https://api.ipify.org', false, $ctx);
                if ($public_ip !== false) return $public_ip;
            } catch (\Exception $e) {
                return 'LOCAL_DEV';
            }
        }
        return $external_ip;
    }

    private static function getDevice(string $ua): string 
    { 
        if (preg_match('/(mobile|android|iphone|ipad)/i', $ua)) return 'mobile';
        return 'desktop'; 
    }

    private static function getOS(string $ua): string 
    { 
        if (preg_match('/windows/i', $ua)) return 'Windows';
        if (preg_match('/macintosh|mac os x/i', $ua)) return 'Mac OS';
        if (preg_match('/linux/i', $ua)) return 'Linux';
        if (preg_match('/android/i', $ua)) return 'Android';
        if (preg_match('/iphone|ipad/i', $ua)) return 'iOS';
        return 'Unknown'; 
    }
}