<?php
/**
 * MÓDULO: FINANCIERO / TESORERÍA
 * ARCHIVO: app/controllers/FinancialTesoreriaController.php
 * PROPÓSITO: index() lista todos los pagos por procesar. manage() detalle +
 *            formulario de pago (campos condicionados por medio de pago) +
 *            botón Reversar. pagar() procesa el pago, sube el comprobante y
 *            registra el egreso en tbl_libro_egresos. reversarPago() anula
 *            un pago ya PAGADO registrando la reversa en el libro.
 * VERSIÓN: 1.1.0 - pagar() llama registrarEgreso(). Nuevo método reversarPago().
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\FinancialTesoreriaController;
 *   $router->get('/financial/tesoreria',                [FinancialTesoreriaController::class, 'index']);
 *   $router->get('/financial/tesoreria/manage',         [FinancialTesoreriaController::class, 'manage']);
 *   $router->post('/financial/tesoreria/pagar',         [FinancialTesoreriaController::class, 'pagar']);
 *   $router->post('/financial/tesoreria/reversar',      [FinancialTesoreriaController::class, 'reversar']);
 *   $router->post('/financial/tesoreria/reversar-pago', [FinancialTesoreriaController::class, 'reversarPago']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialTesoreriaModel;
use App\Services\AuditService;
use Throwable;

class FinancialTesoreriaController extends Controller
{
    private FinancialTesoreriaModel $model;
    private const UPLOAD_DIR = '/var/www/diplomatic/public/uploads/tesoreria';
    private const UPLOAD_URL = '/uploads/tesoreria';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new FinancialTesoreriaModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $search  = trim($_GET['search'] ?? '');
        $estado  = $_GET['estado'] ?? '';
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;

        if (!in_array($estado, ['PENDIENTE', 'PAGADO', 'ANULADO'], true)) $estado = '';

        $total      = $this->model->countPagos($search, $estado);
        $totalPages = (int) ceil($total / $perPage);
        $pagos      = $this->model->getPagos($search, $estado, $page, $perPage);

        $this->view('financial/tesoreria/index', [
            'pagos' => $pagos, 'search' => $search, 'estado' => $estado,
            'page' => $page, 'total' => $total, 'totalPages' => $totalPages,
        ]);
    }

    // =========================================================================
    // MANAGE
    // =========================================================================

    public function manage(): void
    {
        $id   = (int) ($_GET['id'] ?? 0);
        $pago = $id ? $this->model->getPagoById($id) : null;

        if (!$pago) {
            header('Location: /diplomatic/public/financial/tesoreria?error=notfound');
            exit;
        }

        $this->view('financial/tesoreria/manage', ['pago' => $pago]);
    }

    // =========================================================================
    // PAGAR — registra el pago y genera el egreso en el libro
    // =========================================================================

    public function pagar(): void
    {
        try {
            $id          = (int) ($_POST['id'] ?? 0);
            $ordenPagoId = (int) ($_POST['orden_pago_id'] ?? 0);
            $pago        = $id ? $this->model->getPagoById($id) : null;

            if (!$pago) {
                $this->jsonFinal(['success' => false, 'message' => 'Registro no encontrado.'], 404);
                return;
            }
            if ($pago['estado'] !== 'PENDIENTE') {
                $this->jsonFinal(['success' => false, 'message' => 'Este pago ya fue procesado.'], 422);
                return;
            }

            $medioPago = $_POST['medio_pago'] ?? '';
            if (!in_array($medioPago, ['EFECTIVO', 'TRANSFERENCIA', 'PAGO_MOVIL'], true)) {
                $this->jsonFinal(['success' => false, 'message' => 'Selecciona un medio de pago válido.'], 422);
                return;
            }

            $datos = [
                'medio_pago' => $medioPago, 'moneda_efectivo' => null, 'arqueo_detalle' => null,
                'banco' => null, 'cuenta' => null, 'telefono' => null,
                'nombre_destinatario' => null, 'referencia' => null, 'comprobante_path' => null,
            ];

            if ($medioPago === 'EFECTIVO') {
                $moneda = $_POST['moneda_efectivo'] ?? '';
                if (!in_array($moneda, ['USD', 'BS'], true)) {
                    $this->jsonFinal(['success' => false, 'message' => 'Selecciona la moneda del efectivo.'], 422);
                    return;
                }
                $arqueo = trim($_POST['arqueo_detalle'] ?? '');
                if ($arqueo === '') {
                    $this->jsonFinal(['success' => false, 'message' => 'Debes registrar el arqueo de billetes.'], 422);
                    return;
                }
                $datos['moneda_efectivo'] = $moneda;
                $datos['arqueo_detalle']  = $arqueo;
            } else {
                $banco        = trim($_POST['banco'] ?? '');
                $destinatario = trim($_POST['nombre_destinatario'] ?? '');
                $referencia   = trim($_POST['referencia'] ?? '');

                if ($banco === '' || $destinatario === '' || $referencia === '') {
                    $this->jsonFinal(['success' => false, 'message' => 'Completa banco, destinatario y referencia.'], 422);
                    return;
                }

                $datos['banco']               = $banco;
                $datos['nombre_destinatario'] = $destinatario;
                $datos['referencia']          = $referencia;

                if ($medioPago === 'TRANSFERENCIA') {
                    $cuenta = trim($_POST['cuenta'] ?? '');
                    if ($cuenta === '') { $this->jsonFinal(['success' => false, 'message' => 'Indica el número de cuenta.'], 422); return; }
                    $datos['cuenta'] = $cuenta;
                } else {
                    $telefono = trim($_POST['telefono'] ?? '');
                    if ($telefono === '') { $this->jsonFinal(['success' => false, 'message' => 'Indica el teléfono.'], 422); return; }
                    $datos['telefono'] = $telefono;
                }

                if (empty($_FILES['comprobante']['name'])) {
                    $this->jsonFinal(['success' => false, 'message' => 'Debes adjuntar la captura del comprobante.'], 422);
                    return;
                }
                $rutaComprobante = $this->subirComprobante($_FILES['comprobante'], $pago['numero_orden']);
                if ($rutaComprobante === null) {
                    $this->jsonFinal(['success' => false, 'message' => 'El archivo debe ser una imagen (jpg, png) o PDF, máximo 5MB.'], 422);
                    return;
                }
                $datos['comprobante_path'] = $rutaComprobante;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->marcarComoPagado($id, $ordenPagoId, $datos, $userId);

            // Registrar egreso en el libro
            $this->model->registrarEgreso($ordenPagoId, $userId);

            AuditService::log($userId, 'Tesoreria', 'PAGAR',
                "Marcó como pagada la orden {$pago['numero_orden']} vía {$medioPago}", $id);

            $this->jsonFinal(['success' => true, 'message' => 'Pago registrado correctamente.']);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // REVERSAR A ÓRDENES DE PAGO (desde PENDIENTE — antes de pagar)
    // =========================================================================

    public function reversar(): void
    {
        try {
            $id          = (int) ($_POST['id'] ?? 0);
            $ordenPagoId = (int) ($_POST['orden_pago_id'] ?? 0);
            $pago        = $id ? $this->model->getPagoById($id) : null;

            if (!$pago) {
                $this->jsonFinal(['success' => false, 'message' => 'Registro no encontrado.'], 404);
                return;
            }
            if ($pago['estado'] !== 'PENDIENTE') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se puede reversar mientras está PENDIENTE de pago.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->reversarAOrdenPago($id, $ordenPagoId);

            AuditService::log($userId, 'Tesoreria', 'REVERSAR',
                "Reversó a Órdenes de Pago la orden {$pago['numero_orden']}", $id);

            $this->jsonFinal(['success' => true, 'message' => 'Se devolvió a Órdenes de Pago para corrección.']);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // REVERSAR PAGO REALIZADO (desde PAGADO — anula el pago ya ejecutado)
    // =========================================================================

    public function reversarPago(): void
    {
        try {
            $id          = (int) ($_POST['id'] ?? 0);
            $ordenPagoId = (int) ($_POST['orden_pago_id'] ?? 0);
            $pago        = $id ? $this->model->getPagoById($id) : null;

            if (!$pago) {
                $this->jsonFinal(['success' => false, 'message' => 'Registro no encontrado.'], 404);
                return;
            }
            if ($pago['estado'] !== 'PAGADO') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se puede reversar un pago en estado PAGADO.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->reversarPagoRealizado($id, $ordenPagoId, $userId);

            AuditService::log($userId, 'Tesoreria', 'REVERSAR_PAGO',
                "Reversó pago PAGADO de la orden {$pago['numero_orden']}", $id);

            $this->jsonFinal(['success' => true, 'message' => 'Pago reversado. La orden quedó disponible para volver a pagarse.']);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function subirComprobante(array $file, string $numeroOrden): ?string
    {
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!isset($allowed[$ext]) || $file['size'] > 5 * 1024 * 1024 || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if (!is_dir(self::UPLOAD_DIR)) mkdir(self::UPLOAD_DIR, 0755, true);

        $nombreSeguro = preg_replace('/[^A-Za-z0-9_-]/', '_', $numeroOrden);
        $filename = $nombreSeguro . '_' . time() . '.' . $ext;
        $destino  = self::UPLOAD_DIR . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destino)) return null;

        return self::UPLOAD_URL . '/' . $filename;
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