<?php
/**
 * MÓDULO: ADMINISTRATIVO / DOCUMENTOS RECHAZADOS
 * ARCHIVO: app/controllers/AdministrativeRejectedController.php
 * PROPÓSITO: Controlador maestro para la auditoría y reversión de estatus.
 * VERSIÓN: 1.3.5 - Fix: Blindaje de búfer para evitar errores de token y soporte multirol ADMIN/ACADEMIC.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdministrativeRejectedModel;

class AdministrativeRejectedController extends Controller
{
    private AdministrativeRejectedModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $allowedRoles = ['ADMIN', 'ACADEMIC'];
        $userRole = $_SESSION['user']['role'] ?? '';

        if (!isset($_SESSION['user']) || !in_array(strtoupper($userRole), $allowedRoles)) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }
        $this->model = new AdministrativeRejectedModel();
    }

    public function index(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        $this->view('administrative/rejected/index', [
            'rejected' => $this->model->getRejectedList()
        ]);
    }

    public function changeStatus(): void 
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');
        
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $this->model->revertStatus($id)) {
            echo json_encode(['ok' => true, 'message' => 'Estatus actualizado correctamente.']);
        } else {
            echo json_encode(['ok' => false, 'message' => 'Error al procesar la actualización.']);
        }
        exit;
    }
}