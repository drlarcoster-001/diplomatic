<?php
/**
 * MÓDULO - app/controllers/SettingsEventsController.php
 * Controlador de auditoría y monitoreo de eventos.
 * Administra la trazabilidad de acciones, logs de seguridad y actividad de usuarios.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;

final class SettingsEventsController extends Controller
{
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Seguridad: Solo Administradores
        $userRole = strtoupper(trim($_SESSION['user']['role'] ?? ''));
        if ($userRole !== 'ADMIN') {
            $projectRoot = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            header("Location: " . $projectRoot . "/dashboard");
            exit;
        }

        $this->db = (new Database())->getConnection();
    }

    /**
     * Pantalla principal de la Consola de Auditoría
     */
    public function index(): void
    {
        // Por ahora enviamos datos de ejemplo para ver el Front
        $mockLogs = [
            ['fecha' => date('Y-m-d H:i:s'), 'usuario' => 'Admin', 'accion' => 'LOGIN', 'tipo' => 'success', 'desc' => 'Inicio de sesión exitoso'],
            ['fecha' => date('Y-m-d H:i:s'), 'usuario' => 'Soporte', 'accion' => 'UPDATE', 'tipo' => 'warning', 'desc' => 'Cambio de configuración SMTP'],
            ['fecha' => date('Y-m-d H:i:s'), 'usuario' => 'Unknown', 'accion' => 'AUTH', 'tipo' => 'danger', 'desc' => 'Intento de acceso fallido IP 192.168.1.1'],
        ];

        $this->view('settings/eventos', [
            'title' => 'Consola de Auditoría',
            'logs' => $mockLogs
        ]);
    }
}