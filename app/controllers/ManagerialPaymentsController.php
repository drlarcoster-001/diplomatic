<?php
/**
 * MÓDULO: GESTIÓN GENERAL / GERENCIAL - PAGOS
 * ARCHIVO: app/controllers/ManagerialPaymentsController.php
 * PROPÓSITO: Controlador para la visualización de la interfaz del reporte de pagos (Modo Estático).
 * VERSIÓN: 1.1.2 - FRONT-ONLY: Se eliminan consultas a BD para evitar errores de columnas inexistentes.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class ManagerialPaymentsController extends Controller
{
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user']) || strtoupper($_SESSION['user']['role']) !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit();
        }
    }

    /**
     * Carga la interfaz con datos de ejemplo basados en el Excel Corte 12.
     */
    public function index(): void {
        while (ob_get_level() > 0) ob_end_clean();

        // Datos de ejemplo para que el front no esté vacío
        $dummyKpis = [
            'recaudado'  => 12850.50,
            'becados'    => 15,
            'activos'    => 145
        ];

        $dummyTable = [
            ['diplomado' => 'UCI', 'cohorte' => 'CORTE 12', 'inscritos' => 35, 'recaudado' => 4500.00],
            ['diplomado' => 'QX', 'cohorte' => 'CORTE 12', 'inscritos' => 28, 'recaudado' => 3200.00],
            ['diplomado' => 'FORENSE', 'cohorte' => 'CORTE 12', 'inscritos' => 42, 'recaudado' => 5150.50]
        ];

        $this->view('managerial/payments_report/index', [
            'title'     => 'Reporte General de Pagos',
            'kpis'      => $dummyKpis,
            'tableData' => $dummyTable
        ]);
    }
}