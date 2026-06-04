<?php
/**
 * MÓDULO: CONFIGURACIÓN - AUDITORÍA
 * Archivo: app/controllers/SettingsEventsController.php
 * Propósito: Controlador para la visualización y filtrado de logs de auditoría.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService;
use Throwable;
use PDO;

final class SettingsEventsController extends Controller
{
    private $db;

    public function __construct() {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $this->db = (new Database())->getConnection();
        } catch (Throwable $e) { $this->sendError($e->getMessage()); }
    }

    public function index(): void {
        try {
            $stmt = $this->db->query("SELECT * FROM tbl_audit_logs ORDER BY created_at DESC LIMIT 100");
            $this->view('settings/eventos', ['logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Throwable $e) { $this->view('settings/eventos', ['logs' => []]); }
    }

    public function filter(): void {
        header('Content-Type: application/json');
        try {
            $search = $_GET['search'] ?? '';
            $from = $_GET['date_from'] ?? '';
            $to = $_GET['date_to'] ?? '';

            // Corregido: parámetros únicos para evitar SQLSTATE[HY093]
            $sql = "SELECT * FROM tbl_audit_logs WHERE (user_id LIKE :u OR action LIKE :a OR description LIKE :d OR module LIKE :m OR ip_address LIKE :i)";
            
            $term = "%$search%";
            $params = [':u' => $term, ':a' => $term, ':d' => $term, ':m' => $term, ':i' => $term];

            if (!empty($from)) { $sql .= " AND created_at >= :from"; $params[':from'] = $from . ' 00:00:00'; }
            if (!empty($to)) { $sql .= " AND created_at <= :to"; $params[':to'] = $to . ' 23:59:59'; }

            $sql .= " ORDER BY created_at DESC LIMIT 200";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    private function sendError($msg) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => $msg]);
        exit;
    }
}