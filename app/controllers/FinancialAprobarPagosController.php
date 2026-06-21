<?php
/**
 * MÓDULO: FINANCIERO / APROBAR PAGOS A PROVEEDORES
 * ARCHIVO: app/controllers/FinancialAprobarPagosController.php
 * PROPÓSITO: index() con dos pestañas (Pendientes/Aprobadas). manage() vista
 *            de solo lectura con botón Aprobar. aprobar() genera la orden de
 *            pago. reversar() (desde el index, pestaña Aprobadas) la elimina.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\FinancialAprobarPagosController;
 *   $router->get('/financial/aprobar-pagos',          [FinancialAprobarPagosController::class, 'index']);
 *   $router->get('/financial/aprobar-pagos/manage',   [FinancialAprobarPagosController::class, 'manage']);
 *   $router->post('/financial/aprobar-pagos/aprobar', [FinancialAprobarPagosController::class, 'aprobar']);
 *   $router->post('/financial/aprobar-pagos/reversar',[FinancialAprobarPagosController::class, 'reversar']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialAprobarPagosModel;
use App\Services\AuditService;
use Throwable;

class FinancialAprobarPagosController extends Controller
{
    private FinancialAprobarPagosModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new FinancialAprobarPagosModel();
    }

    public function index(): void
    {
        $tab     = ($_GET['tab'] ?? 'pendientes') === 'aprobadas' ? 'aprobadas' : 'pendientes';
        $search  = trim($_GET['search'] ?? '');
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;

        if ($tab === 'aprobadas') {
            $total      = $this->model->countPagosAprobados($search);
            $totalPages = (int) ceil($total / $perPage);
            $pagos      = $this->model->getPagosAprobados($search, $page, $perPage);
        } else {
            $total      = $this->model->countPagosProcesados($search);
            $totalPages = (int) ceil($total / $perPage);
            $pagos      = $this->model->getPagosProcesados($search, $page, $perPage);
        }

        $this->view('financial/aprobar_pagos/index', [
            'tab' => $tab, 'pagos' => $pagos, 'search' => $search,
            'page' => $page, 'total' => $total, 'totalPages' => $totalPages,
        ]);
    }

    public function manage(): void
    {
        $pagoId = (int) ($_GET['id'] ?? 0);
        $pago   = $pagoId ? $this->model->getPagoById($pagoId) : null;

        if (!$pago || $pago['estado'] !== 'PROCESADA') {
            header('Location: /diplomatic/public/financial/aprobar-pagos?error=notfound');
            exit;
        }

        $items   = $this->model->getItems($pagoId);
        $ajustes = $this->model->getAjustes($pagoId);

        $this->view('financial/aprobar_pagos/manage', [
            'pago' => $pago, 'pagoId' => $pagoId, 'items' => $items, 'ajustes' => $ajustes,
        ]);
    }

    public function aprobar(): void
    {
        try {
            $pagoId = (int) ($_POST['pago_id'] ?? 0);
            $pago   = $pagoId ? $this->model->getPagoById($pagoId) : null;

            if (!$pago) {
                $this->jsonFinal(['success' => false, 'message' => 'Pago no encontrado.'], 404);
                return;
            }
            if ($pago['estado'] !== 'PROCESADA') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden aprobar pagos en estado PROCESADA.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $numero = $this->model->aprobarPago($pagoId, $userId);

            AuditService::log($userId, 'PagosProveedores', 'APROBAR',
                "Aprobó pago {$pago['numero_pago']} — orden {$numero} generada", $pagoId);

            $this->jsonFinal(['success' => true, 'message' => "Pago aprobado. Orden de pago {$numero} generada."]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function reversar(): void
    {
        try {
            $pagoId = (int) ($_POST['pago_id'] ?? 0);
            $pago   = $pagoId ? $this->model->getPagoById($pagoId) : null;

            if (!$pago) {
                $this->jsonFinal(['success' => false, 'message' => 'Pago no encontrado.'], 404);
                return;
            }
            if ($pago['estado'] !== 'APROBADA') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden reversar pagos APROBADOS.'], 422);
                return;
            }
            if ($this->model->countOrdenesPagadas($pagoId) > 0) {
                $this->jsonFinal(['success' => false, 'message' => 'Esta orden de pago ya fue PAGADA. Debes reversar el pago primero.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->reversarAprobacion($pagoId, $userId);

            AuditService::log($userId, 'PagosProveedores', 'REVERSAR_APROBACION',
                "Reversó la aprobación del pago {$pago['numero_pago']}", $pagoId);

            $this->jsonFinal(['success' => true, 'message' => 'Aprobación reversada. La orden de pago fue eliminada.']);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function jsonFinal(array $payload, int $code = 200): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        try { echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); }
        catch (Throwable $e) { echo json_encode(['success' => false, 'message' => 'Error JSON.']); }
        exit;
    }
}