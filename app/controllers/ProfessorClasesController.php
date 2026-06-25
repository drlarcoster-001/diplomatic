<?php
/**
 * MÓDULO: PORTAL DOCENTE / MIS CLASES
 * ARCHIVO: app/controllers/ProfessorClasesController.php
 * PROPÓSITO: Lista TODAS las clases asignadas al profesor de sesión,
 *            separadas en 3 pestañas (Teórica/Práctica/Virtual). Pantalla
 *            de solo lectura — desde aquí no se edita nada.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ProfessorClasesController;
 *   $router->get('/professor/clases', [ProfessorClasesController::class, 'index']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ProfessorModel;
use App\Models\ProfessorClasesModel;

class ProfessorClasesController extends Controller
{
    protected array $profesor;
    private ProfessorClasesModel $clasesModel;

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
            header('Location: /diplomatic/public/dashboard?error=profesor_sin_expediente');
            exit;
        }

        $this->profesor    = $profesor;
        $this->clasesModel = new ProfessorClasesModel();
    }

    public function index(): void
    {
        $professorId = (int) $this->profesor['id'];

        $this->view('professor/clases/index', [
            'profesor'  => $this->profesor,
            'teoricas'  => $this->clasesModel->getClasesTeoricas($professorId),
            'practicas' => $this->clasesModel->getClasesPracticas($professorId),
            'virtuales' => $this->clasesModel->getClasesVirtuales($professorId),
        ]);
    }
}