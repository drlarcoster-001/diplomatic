<?php
/**
 * MÓDULO: FINANCIERO / ÓRDENES DE PAGO
 * ARCHIVO: app/controllers/FinancialOrdenesPagoController.php
 * PROPÓSITO: index() lista con buscador + filtro tipo + filtro estado +
 *            paginación 25. manage() detalle con Aprobar/Rechazar/Anular/
 *            Reversar.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\FinancialOrdenesPagoController;
 *   $router->get('/financial/ordenes-pago',           [FinancialOrdenesPagoController::class, 'index']);
 *   $router->get('/financial/ordenes-pago/manage',    [FinancialOrdenesPagoController::class, 'manage']);
 *   $router->post('/financial/ordenes-pago/aprobar',  [FinancialOrdenesPagoController::class, 'aprobar']);
 *   $router->post('/financial/ordenes-pago/rechazar', [FinancialOrdenesPagoController::class, 'rechazar']);
 *   $router->post('/financial/ordenes-pago/anular',   [FinancialOrdenesPagoController::class, 'anular']);
 *   $router->post('/financial/ordenes-pago/reversar', [FinancialOrdenesPagoController::class, 'reversar']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialOrdenesPagoModel;
use App\Services\AuditService;
use Throwable;

class FinancialOrdenesPagoController extends Controller
{
    private FinancialOrdenesPagoModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new FinancialOrdenesPagoModel();
    }

    public function index(): void
    {
        $search  = trim($_GET['search'] ?? '');
        $tipo    = $_GET['tipo']   ?? '';
        $estado  = $_GET['estado'] ?? '';
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;

        if (!in_array($tipo, ['NOMINA', 'PROVEEDOR', 'DIRECTA'], true)) $tipo = '';
        if (!in_array($estado, ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'ANULADA', 'PAGADA'], true)) $estado = '';

        $total      = $this->model->countOrdenes($search, $tipo, $estado);
        $totalPages = (int) ceil($total / $perPage);
        $ordenes    = $this->model->getOrdenes($search, $tipo, $estado, $page, $perPage);

        $this->view('financial/ordenes_pago/index', [
            'ordenes' => $ordenes, 'search' => $search, 'tipo' => $tipo, 'estado' => $estado,
            'page' => $page, 'total' => $total, 'totalPages' => $totalPages,
        ]);
    }

    public function create(): void
    {
        $proveedores = $this->model->getProveedoresActivos();
        $this->view('financial/ordenes_pago/create', ['proveedores' => $proveedores]);
    }

    public function save(): void
    {
        try {
            $proveedorId = (int) ($_POST['proveedor_id'] ?? 0);
            $concepto    = trim($_POST['concepto'] ?? '');
            $monto       = (float) ($_POST['monto'] ?? 0);
            $fechaPago   = $_POST['fecha_pago'] ?? '';

            if (!$proveedorId || $concepto === '' || $monto <= 0 || !$fechaPago) {
                header('Location: /diplomatic/public/financial/ordenes-pago/create?error=incompleto');
                exit;
            }

            $userId = $_SESSION['user']['id'];
            $numero = $this->model->crearOrdenDirecta($proveedorId, $concepto, $monto, $fechaPago, $userId);

            AuditService::log($userId, 'OrdenesPago', 'CREAR_DIRECTA', "Creó la orden directa {$numero}", null);

            header('Location: /diplomatic/public/financial/ordenes-pago?estado=PENDIENTE');
            exit;
        } catch (Throwable $e) {
            header('Location: /diplomatic/public/financial/ordenes-pago/create?error=db');
            exit;
        }
    }

    public function manage(): void
    {
        $id    = (int) ($_GET['id'] ?? 0);
        $orden = $id ? $this->model->getOrdenById($id) : null;

        if (!$orden) {
            header('Location: /diplomatic/public/financial/ordenes-pago?error=notfound');
            exit;
        }

        $this->view('financial/ordenes_pago/manage', ['orden' => $orden]);
    }

    public function aprobar(): void
    {
        try {
            $id    = (int) ($_POST['id'] ?? 0);
            $orden = $id ? $this->model->getOrdenById($id) : null;

            if (!$orden) {
                $this->jsonFinal(['success' => false, 'message' => 'Orden no encontrada.'], 404);
                return;
            }
            if ($orden['estado'] !== 'PENDIENTE') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden aprobar órdenes en estado PENDIENTE.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->aprobarOrden($id, $userId);

            AuditService::log($userId, 'OrdenesPago', 'APROBAR', "Aprobó la orden {$orden['numero_orden']}", $id);

            $this->jsonFinal(['success' => true, 'message' => 'Orden de pago aprobada.']);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function rechazar(): void
    {
        try {
            $id     = (int) ($_POST['id'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? '');
            $orden  = $id ? $this->model->getOrdenById($id) : null;

            if (!$orden) {
                $this->jsonFinal(['success' => false, 'message' => 'Orden no encontrada.'], 404);
                return;
            }
            if ($orden['estado'] !== 'PENDIENTE') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden rechazar órdenes en estado PENDIENTE.'], 422);
                return;
            }
            if ($motivo === '') {
                $this->jsonFinal(['success' => false, 'message' => 'Debes indicar el motivo del rechazo.'], 422);
                return;
            }
            if ($orden['tipo'] === 'NOMINA' && $orden['nomina_id']) {
                $hermanas = $this->model->countOrdenesHermanasAvanzadas((int) $orden['nomina_id'], $id);
                if ($hermanas > 0) {
                    $this->jsonFinal([
                        'success' => false,
                        'message' => 'No se puede rechazar: otro profesor de la misma nómina ya tiene su pago aprobado o pagado. Coordina primero con Tesorería.',
                    ], 422);
                    return;
                }
            }

            $userId = $_SESSION['user']['id'];
            $this->model->rechazarOrden($id, $userId, $motivo, $orden['tipo'], $orden['nomina_id'], $orden['pago_proveedor_id']);

            $cascadaMsg = $orden['tipo'] !== 'DIRECTA'
                ? ' El registro de origen volvió a Pendientes de Aprobar.'
                : '';

            AuditService::log($userId, 'OrdenesPago', 'RECHAZAR', "Rechazó la orden {$orden['numero_orden']}: {$motivo}", $id);

            $this->jsonFinal(['success' => true, 'message' => 'Orden de pago rechazada.' . $cascadaMsg]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function anular(): void
    {
        try {
            $id       = (int) ($_POST['id'] ?? 0);
            $password = (string) ($_POST['password'] ?? '');
            $orden    = $id ? $this->model->getOrdenById($id) : null;

            if (!$orden) {
                $this->jsonFinal(['success' => false, 'message' => 'Orden no encontrada.'], 404);
                return;
            }
            if (!in_array($orden['estado'], ['PENDIENTE', 'APROBADA'], true)) {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden anular órdenes PENDIENTES o APROBADAS.'], 422);
                return;
            }
            if ($password === '') {
                $this->jsonFinal(['success' => false, 'message' => 'Debes ingresar tu contraseña.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            if (!$this->model->verificarPassword($userId, $password)) {
                $this->jsonFinal(['success' => false, 'message' => 'Contraseña incorrecta.'], 403);
                return;
            }

            $this->model->anularOrden($id, $userId);

            AuditService::log($userId, 'OrdenesPago', 'ANULAR', "Anuló la orden {$orden['numero_orden']}", $id);

            $this->jsonFinal(['success' => true, 'message' => 'Orden de pago anulada. Para rehacer este pago, ve al módulo original y usa "Reversar".']);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function reversar(): void
    {
        try {
            $id    = (int) ($_POST['id'] ?? 0);
            $orden = $id ? $this->model->getOrdenById($id) : null;

            if (!$orden) {
                $this->jsonFinal(['success' => false, 'message' => 'Orden no encontrada.'], 404);
                return;
            }
            if (!in_array($orden['estado'], ['APROBADA', 'RECHAZADA'], true)) {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden reversar órdenes APROBADAS o RECHAZADAS.'], 422);
                return;
            }
            if ($orden['estado'] === 'RECHAZADA' && $orden['tipo'] !== 'DIRECTA') {
                $this->jsonFinal(['success' => false, 'message' => 'Esta orden ya regresó a su módulo de origen. No se puede reversar desde aquí.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->reversarOrden($id);

            AuditService::log($userId, 'OrdenesPago', 'REVERSAR', "Reversó la orden {$orden['numero_orden']} desde {$orden['estado']}", $id);

            $this->jsonFinal(['success' => true, 'message' => 'Orden de pago reversada a PENDIENTE.']);
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