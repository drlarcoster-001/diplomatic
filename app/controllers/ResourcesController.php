<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * ARCHIVO: app/controllers/ResourcesController.php
 * PROPÓSITO: Controlador maestro para el Panel de Recursos Humanos del programa de diplomados.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuditService;

final class ResourcesController extends Controller
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user']['id'])) $this->redirect('/login');

        $role = strtoupper($_SESSION['user']['role'] ?? '');
        if (!in_array($role, ['ADMIN', 'OPERATOR'])) {
            $this->redirect('/dashboard');
        }
    }

    public function index(): void
    {
        AuditService::log([
            'module'      => 'RESOURCES_PANEL',
            'action'      => 'VIEW_INDEX',
            'description' => 'Acceso al Panel de Recursos.',
            'event_type'  => 'NORMAL'
        ]);
        $this->view('resources/index');
    }
}