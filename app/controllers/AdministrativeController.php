<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/Controllers/AdministrativeController.php
 * PROPÓSITO: Controlador maestro para el Panel Administrativo y sus sub-módulos.
 * VERSIÓN: 1.0.2 - Adición de métodos para Inscripciones, Matrícula, Estudiantes y Constancias con Auditoría.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuditService;

final class AdministrativeController extends Controller
{
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user']['id'])) $this->redirect('/login');
        
        $role = strtoupper($_SESSION['user']['role'] ?? '');
        if (!in_array($role, ['ADMIN', 'SUPERADMIN', 'ADMINISTRADOR', 'OPERATOR'])) {
            $this->redirect('/dashboard');
        }
    }

    public function index(): void {
        AuditService::log([
            'module' => 'ADMINISTRATIVE_PANEL', 'action' => 'VIEW_INDEX',
            'description' => "Acceso al Dashboard Administrativo.", 'event_type' => 'NORMAL'
        ]);
        $this->view('administrative/index');
    }

    public function inscriptions(): void {
        AuditService::log([
            'module' => 'ADMINISTRATIVE_INSCRIPTIONS', 'action' => 'VIEW_LIST',
            'description' => "Acceso al módulo de Inscripciones.", 'event_type' => 'NORMAL'
        ]);
        $this->view('administrative/inscriptions/index');
    }

    public function enrollment(): void {
        AuditService::log([
            'module' => 'ADMINISTRATIVE_ENROLLMENT', 'action' => 'VIEW_LIST',
            'description' => "Acceso al módulo de Matrícula Estudiantil.", 'event_type' => 'NORMAL'
        ]);
        $this->view('administrative/enrollment/index');
    }

    public function students(): void {
        AuditService::log([
            'module' => 'ADMINISTRATIVE_STUDENTS', 'action' => 'VIEW_LIST',
            'description' => "Acceso al listado de Estudiantes (Vía Administrativa).", 'event_type' => 'NORMAL'
        ]);
        $this->view('administrative/students/index');
    }

    public function certificates(): void {
        AuditService::log([
            'module' => 'ADMINISTRATIVE_CERTIFICATES', 'action' => 'VIEW_PANEL',
            'description' => "Acceso al panel de generación de Constancias.", 'event_type' => 'NORMAL'
        ]);
        $this->view('administrative/certificates/index');
    }
}