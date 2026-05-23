<?php
/**
 * MÓDULO: GESTIÓN GENERAL / GERENCIAL
 * ARCHIVO: app/controllers/ManagerialController.php
 * PROPÓSITO: Controlador maestro de alto nivel. Solo gestiona el index del panel.
 * VERSIÓN: 1.0.3 - CLEANUP: Se eliminan stubs de reportes delegados a controladores especializados.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class ManagerialController extends Controller
{
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user']) || strtoupper($_SESSION['user']['role']) !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit();
        }
    }

    public function index(): void {
        while (ob_get_level() > 0) ob_end_clean();
        $this->view('managerial/index', ['title' => 'Gestión General']);
    }
    
    // NO AGREGAR MÁS MÉTODOS AQUÍ. Los reportes tienen sus propios controladores.
}