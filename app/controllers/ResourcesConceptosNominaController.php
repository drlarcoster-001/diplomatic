<?php
/**
 * MÓDULO: RECURSOS HUMANOS / CONCEPTOS DE NÓMINA
 * ARCHIVO: app/controllers/ResourcesConceptosNominaController.php
 * PROPÓSITO: CRUD de asignaciones y deducciones vía AJAX. Una sola vista con
 *            dos pestañas. Solo rol ADMIN.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ResourcesConceptosNominaController;
 *   $router->get('/resources/conceptos-nomina',                  [ResourcesConceptosNominaController::class, 'index']);
 *   $router->post('/resources/conceptos-nomina/saveAsignacion',  [ResourcesConceptosNominaController::class, 'saveAsignacion']);
 *   $router->post('/resources/conceptos-nomina/updateAsignacion',[ResourcesConceptosNominaController::class, 'updateAsignacion']);
 *   $router->post('/resources/conceptos-nomina/deleteAsignacion',[ResourcesConceptosNominaController::class, 'deleteAsignacion']);
 *   $router->post('/resources/conceptos-nomina/saveDeduccion',   [ResourcesConceptosNominaController::class, 'saveDeduccion']);
 *   $router->post('/resources/conceptos-nomina/updateDeduccion', [ResourcesConceptosNominaController::class, 'updateDeduccion']);
 *   $router->post('/resources/conceptos-nomina/deleteDeduccion', [ResourcesConceptosNominaController::class, 'deleteDeduccion']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ResourcesConceptosNominaModel;
use App\Services\AuditService;
use Throwable;

class ResourcesConceptosNominaController extends Controller
{
    private ResourcesConceptosNominaModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new ResourcesConceptosNominaModel();
    }

    public function index(): void
    {
        $this->view('resources/conceptos_nomina/index', [
            'asignaciones' => $this->model->getAsignaciones(),
            'deducciones'  => $this->model->getDeducciones(),
        ]);
    }

    // =========================================================================
    // ASIGNACIONES
    // =========================================================================

    public function saveAsignacion(): void
    {
        try {
            $data = $this->extractConceptoData();

            if ($this->model->asignacionNombreExists($data['nombre'])) {
                $this->jsonFinal(['success' => false, 'message' => "Ya existe una asignación llamada '{$data['nombre']}'."], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $id = $this->model->createAsignacion($data, $userId);
            AuditService::log($userId, 'Conceptos Nómina', 'CREAR', "Creó asignación '{$data['nombre']}'", $id);

            $this->jsonFinal([
                'success'      => true,
                'message'      => 'Asignación creada correctamente.',
                'asignaciones' => $this->model->getAsignaciones(),
            ]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateAsignacion(): void
    {
        try {
            $id   = (int) ($_POST['id'] ?? 0);
            $data = $this->extractConceptoData();

            if ($this->model->asignacionNombreExists($data['nombre'], $id)) {
                $this->jsonFinal(['success' => false, 'message' => "Ya existe otra asignación con ese nombre."], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->updateAsignacion($id, $data, $userId);
            AuditService::log($userId, 'Conceptos Nómina', 'EDITAR', "Editó asignación ID {$id}", $id);

            $this->jsonFinal([
                'success'      => true,
                'message'      => 'Asignación actualizada.',
                'asignaciones' => $this->model->getAsignaciones(),
            ]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteAsignacion(): void
    {
        try {
            $id     = (int) ($_POST['id'] ?? 0);
            $item   = $id ? $this->model->getAsignacionById($id) : null;
            if (!$item) { $this->jsonFinal(['success' => false, 'message' => 'No encontrada.'], 404); return; }

            $userId = $_SESSION['user']['id'];
            $accion = $this->model->deleteAsignacion($id, $userId);
            AuditService::log($userId, 'Conceptos Nómina', strtoupper($accion), "Asignación '{$item['nombre']}' {$accion}", $id);

            $msg = $accion === 'deleted' ? 'Asignación eliminada.' : 'Asignación inactivada (está en uso en nóminas).';
            $this->jsonFinal(['success' => true, 'message' => $msg, 'asignaciones' => $this->model->getAsignaciones()]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // DEDUCCIONES
    // =========================================================================

    public function saveDeduccion(): void
    {
        try {
            $data = $this->extractConceptoData(false);

            if ($this->model->deduccionNombreExists($data['nombre'])) {
                $this->jsonFinal(['success' => false, 'message' => "Ya existe una deducción llamada '{$data['nombre']}'."], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $id = $this->model->createDeduccion($data, $userId);
            AuditService::log($userId, 'Conceptos Nómina', 'CREAR', "Creó deducción '{$data['nombre']}'", $id);

            $this->jsonFinal([
                'success'     => true,
                'message'     => 'Deducción creada correctamente.',
                'deducciones' => $this->model->getDeducciones(),
            ]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateDeduccion(): void
    {
        try {
            $id   = (int) ($_POST['id'] ?? 0);
            $data = $this->extractConceptoData(false);

            if ($this->model->deduccionNombreExists($data['nombre'], $id)) {
                $this->jsonFinal(['success' => false, 'message' => "Ya existe otra deducción con ese nombre."], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->updateDeduccion($id, $data, $userId);
            AuditService::log($userId, 'Conceptos Nómina', 'EDITAR', "Editó deducción ID {$id}", $id);

            $this->jsonFinal([
                'success'     => true,
                'message'     => 'Deducción actualizada.',
                'deducciones' => $this->model->getDeducciones(),
            ]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteDeduccion(): void
    {
        try {
            $id   = (int) ($_POST['id'] ?? 0);
            $item = $id ? $this->model->getDeduccionById($id) : null;
            if (!$item) { $this->jsonFinal(['success' => false, 'message' => 'No encontrada.'], 404); return; }

            $userId = $_SESSION['user']['id'];
            $accion = $this->model->deleteDeduccion($id, $userId);
            AuditService::log($userId, 'Conceptos Nómina', strtoupper($accion), "Deducción '{$item['nombre']}' {$accion}", $id);

            $msg = $accion === 'deleted' ? 'Deducción eliminada.' : 'Deducción inactivada (está en uso en nóminas).';
            $this->jsonFinal(['success' => true, 'message' => $msg, 'deducciones' => $this->model->getDeducciones()]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function extractConceptoData(bool $esAsignacion = true): array
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $tipo   = trim($_POST['tipo']   ?? '');
        $desc   = trim($_POST['descripcion'] ?? '') ?: null;

        if ($nombre === '') throw new \InvalidArgumentException('El nombre es obligatorio.');

        $tiposValidos = $esAsignacion
            ? ['SALARIO_BASE', 'MONTO_FIJO', 'FORMULA']
            : ['MONTO_FIJO', 'FORMULA'];

        if (!in_array($tipo, $tiposValidos, true)) {
            throw new \InvalidArgumentException('Tipo inválido.');
        }

        $valor   = null;
        $formula = null;

        if ($tipo === 'MONTO_FIJO') {
            $valor = (float) ($_POST['valor'] ?? 0);
            if ($valor <= 0) throw new \InvalidArgumentException('El monto debe ser mayor a 0.');
        } elseif ($tipo === 'FORMULA') {
            $formula = trim($_POST['formula'] ?? '');
            if ($formula === '') throw new \InvalidArgumentException('La fórmula es obligatoria.');
        }

        return compact('nombre', 'tipo', 'valor', 'formula') + ['descripcion' => $desc];
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