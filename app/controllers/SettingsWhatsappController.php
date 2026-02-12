<?php
/**
 * MÓDULO - app/controllers/SettingsWhatsappController.php
 * Controlador de gestión de notificaciones WhatsApp (Modo Manual).
 * Administra plantillas, eventos y registro de trazabilidad.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

final class SettingsWhatsappController extends Controller
{
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->getConnection();
    }

    public function index(): void
    {
        $stmt = $this->db->query("SELECT * FROM tbl_wa_manual_templates");
        $templatesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $templates = [];
        foreach ($templatesRaw as $t) { $templates[$t['evento']] = $t; }

        $stmtLogs = $this->db->query("SELECT * FROM tbl_wa_manual_logs ORDER BY fecha_envio DESC LIMIT 15");
        $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

        $this->view('settings/whatsapp', [
            'title' => 'Configuración WhatsApp',
            'templates' => $templates,
            'logs' => $logs
        ]);
    }

    public function saveTemplate(): void
    {
        header('Content-Type: application/json');
        try {
            $sql = "UPDATE tbl_wa_manual_templates SET mensaje = :msj, activo = :act WHERE evento = :ev";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':msj' => $_POST['mensaje'] ?? '',
                ':act' => isset($_POST['activo']) ? 1 : 0,
                ':ev'  => $_POST['evento']
            ]);
            echo json_encode(['ok' => true, 'msg' => 'Plantilla actualizada']);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    public function logSend(): void
    {
        header('Content-Type: application/json');
        try {
            $stmt = $this->db->prepare("INSERT INTO tbl_wa_manual_logs (estudiante, evento, telefono) VALUES (:est, :ev, :tel)");
            $stmt->execute([
                ':est' => $_POST['estudiante'] ?? 'N/A',
                ':ev'  => $_POST['evento'] ?? 'MANUAL',
                ':tel' => $_POST['telefono'] ?? ''
            ]);
            echo json_encode(['ok' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false]);
        }
        exit;
    }
}