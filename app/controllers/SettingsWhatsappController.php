<?php
/**
 * MÓDULO - app/controllers/SettingsWhatsappController.php
 * Controlador de gestión de notificaciones WhatsApp (Modo Manual).
 * Administra plantillas, eventos y registro de trazabilidad.
 * Integrado con AuditService para auditoría en tiempo real.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Services\AuditService; // Importado para la auditoría
use PDO;
use Throwable;

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
        // Registro de Acceso al módulo
        AuditService::log([
            'module'      => 'WHATSAPP',
            'action'      => 'ACCESS',
            'description' => 'El usuario accedió al panel de configuración de WhatsApp Manual',
            'event_type'  => 'NORMAL'
        ]);

        $stmt = $this->db->query("SELECT * FROM tbl_wa_manual_templates");
        $templatesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $templates = [];
        foreach ($templatesRaw as $t) { $templates[$t['evento']] = $t; }

        $stmtLogs = $this->db->query("SELECT * FROM tbl_wa_manual_logs ORDER BY fecha_envio DESC LIMIT 15");
        $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

        $this->view('settings/whatsapp', [
            'title'     => 'Configuración WhatsApp',
            'templates' => $templates,
            'logs'      => $logs
        ]);
    }

    public function saveTemplate(): void
    {
        header('Content-Type: application/json');
        try {
            $evento  = $_POST['evento'] ?? 'N/A';
            $mensaje = $_POST['mensaje'] ?? '';
            $activo  = isset($_POST['activo']) ? 1 : 0;

            $sql = "UPDATE tbl_wa_manual_templates SET mensaje = :msj, activo = :act WHERE evento = :ev";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':msj' => $mensaje,
                ':act' => $activo,
                ':ev'  => $evento
            ]);

            // Registro de Auditoría: Cambio de Plantilla
            AuditService::log([
                'module'      => 'WHATSAPP',
                'action'      => 'UPDATE_TEMPLATE',
                'description' => "Se actualizó la plantilla del evento: {$evento} (Estado: " . ($activo ? 'Activo' : 'Inactivo') . ")",
                'event_type'  => 'WARNING' // Resalta en color ámbar/amarillo
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Plantilla actualizada']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    public function logSend(): void
    {
        header('Content-Type: application/json');
        try {
            $estudiante = $_POST['estudiante'] ?? 'N/A';
            $evento     = $_POST['evento'] ?? 'MANUAL';
            $telefono   = $_POST['telefono'] ?? '';

            $stmt = $this->db->prepare("INSERT INTO tbl_wa_manual_logs (estudiante, evento, telefono) VALUES (:est, :ev, :tel)");
            $stmt->execute([
                ':est' => $estudiante,
                ':ev'  => $evento,
                ':tel' => $telefono
            ]);

            // Registro de Auditoría: Notificación enviada
            AuditService::log([
                'module'      => 'WHATSAPP',
                'action'      => 'SEND_LOG',
                'description' => "Notificación enviada a {$estudiante} ({$telefono}) - Evento: {$evento}",
                'event_type'  => 'SUCCESS' // Resalta en color verde
            ]);

            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false]);
        }
        exit;
    }
}