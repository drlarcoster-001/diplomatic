<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / CONTROLADORES
 * ARCHIVO: app/controllers/StudentsPaymentHistoryController.php
 * PROPÓSITO: Orquestador del historial de pagos del estudiante en sesión.
 * VERSIÓN: 1.0.0 - Creación inicial del módulo de historial de pagos estudiantil.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\StudentsPaymentHistoryModel;

class StudentsPaymentHistoryController extends Controller
{
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user']['id'])) {
            header('Location: /diplomatic/public/login');
            exit;
        }
    }

    public function index(): void
    {
        $userId = (int)$_SESSION['user']['id'];
        $model = new StudentsPaymentHistoryModel();
        $pagos = $model->getPaymentHistory($userId);

        $this->view('students/payment_history/index', [
            'title' => 'Mis Pagos',
            'pagos' => $pagos
        ]);
    }
}