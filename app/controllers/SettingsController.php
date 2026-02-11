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
        // Validación estricta de sesión y ROL
        $userRole = strtoupper(trim($_SESSION['user']['role'] ?? ''));

        if (empty($_SESSION['user']['id']) || $userRole !== 'ADMIN') {
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
}