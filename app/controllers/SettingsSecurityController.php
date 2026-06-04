<?php
/**
 * MÓDULO: CONFIGURACIÓN / SEGURIDAD
 * ARCHIVO: app/controllers/SettingsSecurityController.php
 * PROPÓSITO: Administración de pre-users y tokens vencidos del sistema de registro.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SettingsSecurityModel;
use App\Services\AuditService;
use Exception;

final class SettingsSecurityController extends Controller
{
    private SettingsSecurityModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $user = $_SESSION['user'] ?? null;
        $allowedRoles = ['ADMIN', 'SUPERADMIN'];

        if (!$user || !in_array(strtoupper($user['role'] ?? ''), $allowedRoles)) {
            $this->redirect('/dashboard');
            exit;
        }

        $this->model = new SettingsSecurityModel();
    }

    private function setJsonHeaders(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
    }

    public function index(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        $summary = $this->model->getSummary();
        $this->view('settings/seguridad/index', ['summary' => $summary]);
    }

    public function getPreUsers(): void
    {
        $this->setJsonHeaders();
        try {
            $filters = [
                'text'   => trim($_GET['text'] ?? ''),
                'status' => trim($_GET['status'] ?? '')
            ];
            $data = $this->model->getPreUsers($filters);
            echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function deletePreUser(): void
    {
        $this->setJsonHeaders();
        try {
            $preUserId = (int)($_POST['pre_user_id'] ?? 0);
            if ($preUserId <= 0) throw new Exception("ID inválido.");

            $result = $this->model->deletePreUser($preUserId);
            if (!$result) throw new Exception("No se pudo eliminar el registro.");

            AuditService::log([
                'module'      => 'SETTINGS_SECURITY',
                'action'      => 'DELETE_PRE_USER',
                'description' => "Pre-user ID $preUserId eliminado manualmente.",
                'event_type'  => 'WARNING'
            ]);

            echo json_encode(['ok' => true, 'message' => 'Pre-usuario eliminado correctamente.']);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function cleanExpiredTokens(): void
    {
        $this->setJsonHeaders();
        try {
            $result = $this->model->cleanExpiredTokens();

            AuditService::log([
                'module'      => 'SETTINGS_SECURITY',
                'action'      => 'CLEAN_EXPIRED_TOKENS',
                'description' => "Limpieza automática: {$result['tokens_eliminados']} tokens y {$result['pre_users_eliminados']} pre-users eliminados.",
                'event_type'  => 'NORMAL'
            ]);

            echo json_encode([
                'ok'      => true,
                'message' => "Se eliminaron {$result['tokens_eliminados']} tokens vencidos y {$result['pre_users_eliminados']} pre-usuarios sin activar.",
                'data'    => $result
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}