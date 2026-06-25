<?php
/**
 * MÓDULO: FINANCIERO / DASHBOARD
 * ARCHIVO: app/controllers/FinancialDashboardController.php
 * PROPÓSITO: Muestra el dashboard financiero con indicadores clave,
 *            gráfica de ingresos vs egresos de los últimos 6 meses
 *            y los últimos movimientos del libro de egresos.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\FinancialDashboardController;
 *   $router->get('/financial/dashboard', [FinancialDashboardController::class, 'index']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialDashboardModel;

class FinancialDashboardController extends Controller
{
    private FinancialDashboardModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $allowed = ['ADMIN', 'FINANZAS'];
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed, true)) {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new FinancialDashboardModel();
    }

    public function index(): void
    {
        $indicadores   = $this->model->getIndicadores();
        $graficaMensual = $this->model->getGraficaMensual();
        $ultimosEgresos = $this->model->getUltimosEgresos();

        $this->view('financial/dashboard/index', [
            'indicadores'    => $indicadores,
            'graficaMensual' => $graficaMensual,
            'ultimosEgresos' => $ultimosEgresos,
        ]);
    }
}