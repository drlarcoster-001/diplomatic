<?php
/**
 * MÓDULO: GESTIÓN OPERATIVA / PROFESSORS
 * ARCHIVO: app/Controllers/OperationalController.php
 * PROPÓSITO: Gestión de sincronización de staff docente con WordPress.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class OperationalController extends Controller
{
    public function __construct() 
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $user = $_SESSION['user'] ?? null;
        
        // Limpiamos y convertimos a MAYÚSCULAS para evitar errores de tipeo
        $role = ($user) ? strtoupper(trim((string)$user['role'])) : '';

        // DEBUG TEMPORAL: Descomenta la línea de abajo si sigue fallando para ver qué rol tienes
        // die("Tu rol es: " . $role); 

    if (!$user || !in_array($role, ['ADMIN', 'OPERATOR'])) {
        header("Location: /diplomatic/public/dashboard");
        exit;
    }
    }

    public function index(): void 
    {
        $this->view('operational/index', ['title' => 'Panel Operativo']);
    }

    public function professors(): void 
    {
        $this->view('operational/professors/index');
    }

    public function news(): void 
    {
        $this->view('operational/news/index');
    }
}