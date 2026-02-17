<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/AcademicController.php
 * Propósito: Dashboard central para la administración de la estructura académica.
 */

namespace App\Controllers;

use App\Core\Controller;

class AcademicController extends Controller
{
    /**
     * Valida que el usuario tenga acceso administrativo u operativo antes de cargar el panel.
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Solo ADMIN y OPERATOR pueden acceder a la gestión académica
        $role = $_SESSION['user']['role'] ?? '';
        if ($role !== 'ADMIN' && $role !== 'OPERATOR') {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }
    }

    /**
     * Muestra la vista principal con las tarjetas de módulos académicos.
     */
    public function index(): void
    {
        $this->view('academic/index');
    }
}