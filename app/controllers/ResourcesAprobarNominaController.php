<?php
/**
 * MÓDULO: RECURSOS HUMANOS / APROBAR NÓMINAS
 * ARCHIVO: app/controllers/ResourcesAprobarNominaController.php
 * PROPÓSITO: index() lista nóminas PROCESADA pendientes de aprobación. manage()
 *            muestra el detalle de solo lectura. aprobar() genera las órdenes
 *            de pago y pasa la nómina a APROBADA. Solo rol ADMIN.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ResourcesAprobarNominaController;
 *   $router->get('/resources/aprobar-nomina',          [ResourcesAprobarNominaController::class, 'index']);
 *   $router->get('/resources/aprobar-nomina/manage',   [ResourcesAprobarNominaController::class, 'manage']);
 *   $router->post('/resources/aprobar-nomina/aprobar', [ResourcesAprobarNominaController::class, 'aprobar']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ResourcesAprobarNominaModel;
use App\Services\AuditService;
use Throwable;

class ResourcesAprobarNominaController extends Controller
{
    private ResourcesAprobarNominaModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new ResourcesAprobarNominaModel();
    }

    public function index(): void
    {
        $tab        = ($_GET['tab'] ?? 'pendientes') === 'aprobadas' ? 'aprobadas' : 'pendientes';
        $search     = trim($_GET['search'] ?? '');
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $perPage    = 25;

        if ($tab === 'aprobadas') {
            $total      = $this->model->countNominasAprobadas($search);
            $totalPages = (int) ceil($total / $perPage);
            $nominas    = $this->model->getNominasAprobadas($search, $page, $perPage);
        } else {
            $total      = $this->model->countNominasProcesadas($search);
            $totalPages = (int) ceil($total / $perPage);
            $nominas    = $this->model->getNominasProcesadas($search, $page, $perPage);
        }

        $this->view('resources/aprobar_nomina/index', [
            'tab'        => $tab,
            'nominas'    => $nominas,
            'search'     => $search,
            'page'       => $page,
            'total'      => $total,
            'totalPages' => $totalPages,
            'perPage'    => $perPage,
        ]);
    }

    public function manage(): void
    {
        $nominaId = (int) ($_GET['id'] ?? 0);
        $nomina   = $nominaId ? $this->model->getNominaById($nominaId) : null;

        if (!$nomina || $nomina['estado'] !== 'PROCESADA') {
            header('Location: /diplomatic/public/resources/aprobar-nomina?error=notfound');
            exit;
        }

        $personal = $this->model->getPersonalEnNomina($nominaId);

        $this->view('resources/aprobar_nomina/manage', [
            'nomina'   => $nomina,
            'nominaId' => $nominaId,
            'personal' => $personal,
        ]);
    }

    public function aprobar(): void
    {
        try {
            $nominaId = (int) ($_POST['nomina_id'] ?? 0);
            $nomina   = $nominaId ? $this->model->getNominaById($nominaId) : null;

            if (!$nomina) {
                $this->jsonFinal(['success' => false, 'message' => 'Nómina no encontrada.'], 404);
                return;
            }
            if ($nomina['estado'] !== 'PROCESADA') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden aprobar nóminas en estado PROCESADA.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $generadas = $this->model->aprobarNomina($nominaId, $userId);

            AuditService::log($userId, 'Nómina', 'APROBAR',
                "Aprobó nómina '{$nomina['nombre']}' — {$generadas} órdenes de pago generadas", $nominaId);

            $this->jsonFinal([
                'success' => true,
                'message' => "Nómina aprobada. Se generaron {$generadas} órdenes de pago.",
            ]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function reversar(): void
    {
        try {
            $nominaId = (int) ($_POST['nomina_id'] ?? 0);
            $nomina   = $nominaId ? $this->model->getNominaById($nominaId) : null;

            if (!$nomina) {
                $this->jsonFinal(['success' => false, 'message' => 'Nómina no encontrada.'], 404);
                return;
            }
            if ($nomina['estado'] !== 'APROBADA') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden reversar nóminas APROBADAS.'], 422);
                return;
            }
            if ($this->model->countOrdenesPagadas($nominaId) > 0) {
                $this->jsonFinal(['success' => false, 'message' => 'Esta nómina tiene órdenes de pago ya PAGADAS. Debes reversar esos pagos primero.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->reversarAprobacion($nominaId, $userId);

            AuditService::log($userId, 'Nómina', 'REVERSAR_APROBACION',
                "Reversó la aprobación de nómina '{$nomina['nombre']}'", $nominaId);

            $this->jsonFinal(['success' => true, 'message' => 'Aprobación reversada. La nómina volvió a estado PROCESADA y sus órdenes de pago fueron eliminadas.']);

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