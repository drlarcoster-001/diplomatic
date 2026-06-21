<?php
/**
 * MÓDULO: FINANCIERO / PAGOS A PROVEEDORES
 * ARCHIVO: app/controllers/FinancialPagosProveedoresController.php
 * PROPÓSITO: index() lista pagos. create()/save() crea un pago BORRADOR.
 *            manage() edita ítems/ajustes mientras está en BORRADOR.
 *            procesar()/descartar()/reversar() controlan el flujo de estados.
 *            (La aprobación vive en un módulo separado: Aprobar Pagos a Proveedores)
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\FinancialPagosProveedoresController;
 *   $router->get('/financial/pagos-proveedores',                  [FinancialPagosProveedoresController::class, 'index']);
 *   $router->get('/financial/pagos-proveedores/create',            [FinancialPagosProveedoresController::class, 'create']);
 *   $router->post('/financial/pagos-proveedores/save',             [FinancialPagosProveedoresController::class, 'save']);
 *   $router->get('/financial/pagos-proveedores/manage',            [FinancialPagosProveedoresController::class, 'manage']);
 *   $router->post('/financial/pagos-proveedores/addItem',          [FinancialPagosProveedoresController::class, 'addItem']);
 *   $router->post('/financial/pagos-proveedores/removeItem',       [FinancialPagosProveedoresController::class, 'removeItem']);
 *   $router->post('/financial/pagos-proveedores/addAjuste',        [FinancialPagosProveedoresController::class, 'addAjuste']);
 *   $router->post('/financial/pagos-proveedores/removeAjuste',     [FinancialPagosProveedoresController::class, 'removeAjuste']);
 *   $router->post('/financial/pagos-proveedores/procesar',         [FinancialPagosProveedoresController::class, 'procesar']);
 *   $router->post('/financial/pagos-proveedores/descartar',        [FinancialPagosProveedoresController::class, 'descartar']);
 *   $router->post('/financial/pagos-proveedores/reversar',         [FinancialPagosProveedoresController::class, 'reversar']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialPagosProveedoresModel;
use App\Services\AuditService;
use Throwable;

class FinancialPagosProveedoresController extends Controller
{
    private FinancialPagosProveedoresModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new FinancialPagosProveedoresModel();
    }

    public function index(): void
    {
        $search     = trim($_GET['search'] ?? '');
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $perPage    = 25;
        $total      = $this->model->countPagos($search);
        $totalPages = (int) ceil($total / $perPage);
        $pagos      = $this->model->getPagos($search, $page, $perPage);

        $this->view('financial/pagos_proveedores/index', [
            'pagos' => $pagos, 'search' => $search, 'page' => $page,
            'total' => $total, 'totalPages' => $totalPages, 'perPage' => $perPage,
        ]);
    }

    public function create(): void
    {
        $proveedores = $this->model->getProveedoresActivos();
        $this->view('financial/pagos_proveedores/create', ['proveedores' => $proveedores]);
    }

    public function save(): void
    {
        try {
            $proveedorId = (int) ($_POST['proveedor_id'] ?? 0);
            $fechaPago   = $_POST['fecha_pago'] ?? '';

            if (!$proveedorId || !$fechaPago) {
                header('Location: /diplomatic/public/financial/pagos-proveedores/create?error=incompleto');
                exit;
            }

            $userId = $_SESSION['user']['id'];
            $pagoId = $this->model->crearPago($proveedorId, $fechaPago, $userId);

            AuditService::log($userId, 'PagosProveedores', 'CREAR', "Creó pago a proveedor ID {$proveedorId}", $pagoId);

            header("Location: /diplomatic/public/financial/pagos-proveedores/manage?id={$pagoId}");
            exit;

        } catch (Throwable $e) {
            header('Location: /diplomatic/public/financial/pagos-proveedores/create?error=db');
            exit;
        }
    }

    public function manage(): void
    {
        $pagoId = (int) ($_GET['id'] ?? 0);
        $pago   = $pagoId ? $this->model->getPagoById($pagoId) : null;

        if (!$pago) {
            header('Location: /diplomatic/public/financial/pagos-proveedores?error=notfound');
            exit;
        }

        $items       = $this->model->getItems($pagoId);
        $ajustes     = $this->model->getAjustes($pagoId);
        $proveedores = $this->model->getProveedoresActivos();

        $this->view('financial/pagos_proveedores/manage', [
            'pago' => $pago, 'pagoId' => $pagoId, 'items' => $items, 'ajustes' => $ajustes,
            'proveedores' => $proveedores,
        ]);
    }

    public function addItem(): void
    {
        try {
            $pagoId = (int) ($_POST['pago_id'] ?? 0);
            $pago   = $pagoId ? $this->model->getPagoById($pagoId) : null;

            if (!$pago || $pago['estado'] !== 'BORRADOR') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden editar pagos en BORRADOR.'], 422);
                return;
            }

            $descripcion = trim($_POST['descripcion'] ?? '');
            $cantidad    = (float) ($_POST['cantidad'] ?? 0);
            $precio      = (float) ($_POST['precio_unitario'] ?? 0);

            if ($descripcion === '' || $cantidad <= 0 || $precio < 0) {
                $this->jsonFinal(['success' => false, 'message' => 'Datos del ítem inválidos.'], 422);
                return;
            }

            $this->model->addItem($pagoId, $descripcion, $cantidad, $precio);

            $this->jsonFinal([
                'success' => true, 'message' => 'Ítem agregado.',
                'items' => $this->model->getItems($pagoId),
                'ajustes' => $this->model->getAjustes($pagoId),
                'pago' => $this->model->getPagoById($pagoId),
            ]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function removeItem(): void
    {
        try {
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $pagoId = (int) ($_POST['pago_id'] ?? 0);
            $pago   = $pagoId ? $this->model->getPagoById($pagoId) : null;

            if (!$pago || $pago['estado'] !== 'BORRADOR') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden editar pagos en BORRADOR.'], 422);
                return;
            }

            $this->model->removeItem($itemId, $pagoId);

            $this->jsonFinal([
                'success' => true, 'items' => $this->model->getItems($pagoId),
                'ajustes' => $this->model->getAjustes($pagoId),
                'pago' => $this->model->getPagoById($pagoId),
            ]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function addAjuste(): void
    {
        try {
            $pagoId = (int) ($_POST['pago_id'] ?? 0);
            $pago   = $pagoId ? $this->model->getPagoById($pagoId) : null;

            if (!$pago || $pago['estado'] !== 'BORRADOR') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden editar pagos en BORRADOR.'], 422);
                return;
            }

            $nombre    = trim($_POST['nombre'] ?? '');
            $tipo      = $_POST['tipo'] ?? '';
            $direccion = $_POST['direccion'] ?? '';
            $valor     = (float) ($_POST['valor'] ?? 0);

            if ($nombre === '' || !in_array($tipo, ['PORCENTAJE', 'MONTO_FIJO'], true)
                || !in_array($direccion, ['SUMA', 'RESTA'], true) || $valor <= 0) {
                $this->jsonFinal(['success' => false, 'message' => 'Datos del ajuste inválidos.'], 422);
                return;
            }

            $this->model->addAjuste($pagoId, $nombre, $tipo, $direccion, $valor);

            $this->jsonFinal([
                'success' => true, 'message' => 'Ajuste agregado.',
                'ajustes' => $this->model->getAjustes($pagoId), 'pago' => $this->model->getPagoById($pagoId),
            ]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function removeAjuste(): void
    {
        try {
            $ajusteId = (int) ($_POST['ajuste_id'] ?? 0);
            $pagoId   = (int) ($_POST['pago_id'] ?? 0);
            $pago     = $pagoId ? $this->model->getPagoById($pagoId) : null;

            if (!$pago || $pago['estado'] !== 'BORRADOR') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden editar pagos en BORRADOR.'], 422);
                return;
            }

            $this->model->removeAjuste($ajusteId, $pagoId);

            $this->jsonFinal([
                'success' => true, 'ajustes' => $this->model->getAjustes($pagoId),
                'pago' => $this->model->getPagoById($pagoId),
            ]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function procesar(): void
    {
        try {
            $pagoId = (int) ($_POST['pago_id'] ?? 0);
            $pago   = $pagoId ? $this->model->getPagoById($pagoId) : null;

            if (!$pago) {
                $this->jsonFinal(['success' => false, 'message' => 'Pago no encontrado.'], 404);
                return;
            }
            if ($pago['estado'] !== 'BORRADOR') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden procesar pagos en BORRADOR.'], 422);
                return;
            }
            if ((float) $pago['total_usd'] <= 0) {
                $this->jsonFinal(['success' => false, 'message' => 'El pago debe tener al menos un ítem con monto mayor a 0.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->procesarPago($pagoId, $userId);

            AuditService::log($userId, 'PagosProveedores', 'PROCESAR', "Procesó pago {$pago['numero_pago']}", $pagoId);

            $this->jsonFinal(['success' => true, 'message' => 'Pago procesado correctamente.']);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function descartar(): void
    {
        try {
            $pagoId = (int) ($_POST['pago_id'] ?? 0);
            $pago   = $pagoId ? $this->model->getPagoById($pagoId) : null;

            if (!$pago) {
                $this->jsonFinal(['success' => false, 'message' => 'Pago no encontrado.'], 404);
                return;
            }
            if ($pago['estado'] !== 'BORRADOR') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden descartar pagos en BORRADOR.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->descartarPago($pagoId);

            AuditService::log($userId, 'PagosProveedores', 'DESCARTAR', "Descartó pago {$pago['numero_pago']}", $pagoId);

            $this->jsonFinal(['success' => true, 'message' => 'Pago descartado.']);
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
            if (!in_array($pago['estado'], ['PROCESADA', 'APROBADA'], true)) {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden reversar pagos PROCESADOS o APROBADOS.'], 422);
                return;
            }
            if ($pago['estado'] === 'APROBADA' && $this->model->countOrdenesPagadas($pagoId) > 0) {
                $this->jsonFinal(['success' => false, 'message' => 'Esta orden de pago ya fue PAGADA. Debes reversar el pago primero.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->reversarPago($pagoId, $userId);

            $mensaje = $pago['estado'] === 'APROBADA'
                ? 'Aprobación reversada. La orden de pago generada fue eliminada.'
                : 'Pago reversado a borrador.';

            AuditService::log($userId, 'PagosProveedores', 'REVERSAR', "Reversó pago {$pago['numero_pago']} desde {$pago['estado']}", $pagoId);

            $this->jsonFinal(['success' => true, 'message' => $mensaje]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cambiarProveedor(): void
    {
        try {
            $pagoId       = (int) ($_POST['pago_id'] ?? 0);
            $proveedorId  = (int) ($_POST['proveedor_id'] ?? 0);
            $pago         = $pagoId ? $this->model->getPagoById($pagoId) : null;

            if (!$pago) {
                $this->jsonFinal(['success' => false, 'message' => 'Pago no encontrado.'], 404);
                return;
            }
            if ($pago['estado'] !== 'BORRADOR') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se puede cambiar el proveedor mientras está en BORRADOR.'], 422);
                return;
            }
            if (!$proveedorId) {
                $this->jsonFinal(['success' => false, 'message' => 'Selecciona un proveedor.'], 422);
                return;
            }

            $this->model->cambiarProveedor($pagoId, $proveedorId);

            $userId = $_SESSION['user']['id'];
            AuditService::log($userId, 'PagosProveedores', 'CAMBIAR_PROVEEDOR',
                "Cambió el proveedor del pago {$pago['numero_pago']}", $pagoId);

            $this->jsonFinal(['success' => true, 'message' => 'Proveedor actualizado.']);
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