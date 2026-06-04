<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/AcademicController.php
 * Propósito: Registro de entrada al módulo.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuditService;

class AcademicController extends Controller
{
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header('Location: /diplomatic/public/login');
            exit;
        }

        AuditService::log([
            'module' => 'ACADEMIC_MANAGEMENT',
            'action' => 'ACCESS',
            'description' => 'Entró al panel de Gestión Académica.'
        ]);

        $this->view('academic/index');
    }
}