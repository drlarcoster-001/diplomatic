<?php
/**
 * MÓDULO: ESTUDIANTES
 * ARCHIVO: app/Controllers/StudentsController.php
 * PROPÓSITO: Controlador principal del panel de autogestión estudiantil. Orquesta las vistas de opciones e inscripciones.
 * VERSIÓN: 1.1.0 - Inclusión del método inscriptions() y conexión con StudentModel. Blindaje de búfer para evitar el error "Unexpected token <".
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuditService;
use App\Models\StudentModel;

final class StudentsController extends Controller
{
    private StudentModel $studentModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // 1. Verificación de sesión activa
        if (empty($_SESSION['user']['id'])) {
            $this->redirect('/login');
        }

        // 2. Validación estricta de Estudiante (Rol, Tipo y Estatus)
        $userRole = strtoupper(trim($_SESSION['user']['role'] ?? ''));
        $userType = strtoupper(trim($_SESSION['user']['user_type'] ?? ''));
        $status   = strtoupper(trim($_SESSION['user']['status'] ?? 'ACTIVE'));

        if ($userRole !== 'PARTICIPANT' || $userType !== 'PARTICIPANT' || $status !== 'ACTIVE') {
            AuditService::log([
                'module'      => 'STUDENT_PANEL', 
                'action'      => 'ACCESS_DENIED',
                'description' => "Intento de acceso denegado al Panel Estudiantil. Rol: $userRole, Tipo: $userType, Estatus: $status", 
                'event_type'  => 'WARNING'
            ]);
            
            $this->redirect('/dashboard');
        }

        // 3. Inicializamos el modelo de estudiantes
        $this->studentModel = new StudentModel();
    }

    /**
     * Muestra el panel principal con las 4 tarjetas de opciones.
     */
    public function index(): void {
        if (ob_get_length()) ob_clean();

        AuditService::log([
            'module'      => 'STUDENT_PANEL', 
            'action'      => 'VIEW_INDEX',
            'description' => "Acceso autorizado al Panel Estudiantil central.", 
            'event_type'  => 'NORMAL'
        ]);
        
        $this->view('students/index', [
            'title' => 'Panel Estudiantil'
        ]);
    }

    /**
     * Muestra la pantalla de inscripciones (Ofertas disponibles y estado de solicitudes).
     */
    public function inscriptions(): void {
        if (ob_get_length()) ob_clean();

        // Extraemos las ofertas que tienen estatus ABIERTA
        $openOfferings = $this->studentModel->getOpenOfferings();

        // Auditoría: Registramos que el alumno entró a ver la oferta académica
        AuditService::log([
            'module'      => 'STUDENT_INSCRIPTIONS', 
            'action'      => 'VIEW_OFFERINGS',
            'description' => "El estudiante consultó la oferta académica. Ofertas mostradas: " . count($openOfferings), 
            'event_type'  => 'NORMAL'
        ]);

        $this->view('students/inscriptions', [
            'title' => 'Gestión de Inscripciones',
            'openOfferings' => $openOfferings
        ]);
    }
}