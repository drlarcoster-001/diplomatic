<?php
/**
 * MÓDULO: CONFIGURACIONES
 * Archivo: app/controllers/SettingsWordpressController.php
 * Propósito: Gestión de integración con WordPress vía JWT Authentication y rastro de auditoría.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProfesoresModel;
use App\Services\AuditService;

class SettingsWordpressController extends Controller
{
    private $profesoresModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user'])) {
            header('Location: /diplomatic/public/');
            exit();
        }
        $this->profesoresModel = new ProfesoresModel();
    }

    public function index(): void
    {
        AuditService::log([
            'module' => 'SETTINGS_WP',
            'action' => 'ACCESS_INDEX',
            'description' => 'Ingresó al panel de configuración de WordPress (JWT).'
        ]);

        $this->view('settings/wordpress', [
            'profesores' => $this->profesoresModel->getAll()
        ]);
    }

    public function saveConfig(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        AuditService::log([
            'module' => 'SETTINGS_WP',
            'action' => 'SAVE_CONFIG',
            'description' => 'Actualizó credenciales de WordPress en el sistema local.'
        ]);

        header('Location: /diplomatic/public/settings/wordpress?updated=1');
        exit();
    }

    public function testConnection(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $url = rtrim($_POST['wp_url'] ?? '', '/') . '/wp-json/jwt-auth/v1/token';
        $username = $_POST['wp_user'] ?? '';
        $password = $_POST['wp_pass'] ?? '';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'username' => $username,
            'password' => $password
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        $status = ($httpCode === 200 && isset($data['token']));

        AuditService::log([
            'module' => 'SETTINGS_WP',
            'action' => 'TEST_CONNECTION',
            'description' => 'Prueba conexión JWT: ' . ($status ? 'EXITOSO' : 'FALLIDO ('.$httpCode.')')
        ]);

        echo json_encode([
            'ok' => $status, 
            'code' => $httpCode, 
            'message' => $data['message'] ?? 'Respuesta inválida del servidor WordPress.'
        ]);
        exit();
    }

    public function syncProfessor(): void { 
        AuditService::log(['module' => 'SETTINGS_WP', 'action' => 'SYNC_PROF_DEV', 'entity_id' => $_POST['id'], 'description' => 'Intento de subir profesor (Módulo en desarrollo).']);
        header('Location: /diplomatic/public/settings/wordpress?dev=1'); exit(); 
    }
    public function unsyncProfessor(): void { 
        AuditService::log(['module' => 'SETTINGS_WP', 'action' => 'UNSYNC_PROF_DEV', 'entity_id' => $_POST['id'], 'description' => 'Intento de quitar profesor (Módulo en desarrollo).']);
        header('Location: /diplomatic/public/settings/wordpress?dev=1'); exit(); 
    }
}