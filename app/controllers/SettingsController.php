<?php
/**
 * MÓDULO: CONFIGURACIÓN
 * Archivo: app/controllers/SettingsController.php
 * Propósito: Panel central de ajustes globales para el administrador.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class SettingsController extends Controller
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId   = $_SESSION['user']['id']   ?? null;
        $userRole = strtoupper(trim($_SESSION['user']['role'] ?? ''));

        // Permitir acceso si el usuario está autenticado y es ADMIN
        if (!$userId || $userRole !== 'ADMIN') {
            $projectRoot = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            header("Location: " . $projectRoot . "/dashboard");
            exit;
        }
    }

    public function index(): void
    {
        $this->view('settings/index', [
            'title' => 'Configuración del Sistema'
        ]);
    }

    public function correo(): void
    {
        $this->view('settings/mail', [
            'title' => 'Configuración de Correo'
        ]);
    }
}
