<?php
/**
 * MÓDULO: PORTAL DOCENTE
 * ARCHIVO: app/controllers/ProfessorMainController.php
 * PROPÓSITO: Dashboard de entrada del Portal Docente — bienvenida y
 *            accesos a las 4 secciones (Clases, Matrícula, Notas,
 *            Asistencia). Solo accesible con rol PROFESOR.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ProfessorMainController;
 *   $router->get('/professor', [ProfessorMainController::class, 'index']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProfessorModel;

class ProfessorMainController extends Controller
{
    protected array $profesor;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'PROFESOR') {
            header('Location: /diplomatic/public/login');
            exit;
        }

        $model    = new ProfessorModel();
        $profesor = $model->getProfessorByUserId((int) $_SESSION['user']['id']);

        if (!$profesor) {
            // Cuenta con rol Profesor pero sin expediente vinculado en tbl_professors.
            header('Location: /diplomatic/public/dashboard?error=profesor_sin_expediente');
            exit;
        }

        $this->profesor = $profesor;
    }

    public function index(): void
    {
        $this->view('professor/index', ['profesor' => $this->profesor]);
    }
}