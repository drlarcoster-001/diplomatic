<?php
/**
 * MÓDULO: RECURSOS HUMANOS / NÓMINA
 * ARCHIVO: app/controllers/ResourcesNominaController.php
 * PROPÓSITO: index() lista nóminas. manage() permite crear/gestionar una nómina:
 *            buscar y agregar personal, copiar monto del contrato, aplicar
 *            asignaciones/deducciones del catálogo, calcular con tasa BCV,
 *            y procesar generando el snapshot final. Solo rol ADMIN.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\ResourcesNominaController;
 *   $router->get('/resources/nomina',                       [ResourcesNominaController::class, 'index']);
 *   $router->get('/resources/nomina/create',                [ResourcesNominaController::class, 'create']);
 *   $router->post('/resources/nomina/store',                [ResourcesNominaController::class, 'store']);
 *   $router->get('/resources/nomina/manage',                [ResourcesNominaController::class, 'manage']);
 *   $router->get('/resources/nomina/buscarPersonal',         [ResourcesNominaController::class, 'buscarPersonal']);
 *   $router->get('/resources/nomina/getMontoContrato',       [ResourcesNominaController::class, 'getMontoContrato']);
 *   $router->post('/resources/nomina/addPersonal',           [ResourcesNominaController::class, 'addPersonal']);
 *   $router->post('/resources/nomina/removePersonal',        [ResourcesNominaController::class, 'removePersonal']);
 *   $router->get('/resources/nomina/getCatalogos',           [ResourcesNominaController::class, 'getCatalogos']);
 *   $router->post('/resources/nomina/addAsignacion',         [ResourcesNominaController::class, 'addAsignacion']);
 *   $router->post('/resources/nomina/addDeduccion',          [ResourcesNominaController::class, 'addDeduccion']);
 *   $router->post('/resources/nomina/deleteAsignacionItem',  [ResourcesNominaController::class, 'deleteAsignacionItem']);
 *   $router->post('/resources/nomina/deleteDeduccionItem',   [ResourcesNominaController::class, 'deleteDeduccionItem']);
 *   $router->post('/resources/nomina/procesar',              [ResourcesNominaController::class, 'procesar']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ResourcesNominaModel;
use App\Services\AuditService;
use Throwable;

class ResourcesNominaController extends Controller
{
    private ResourcesNominaModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new ResourcesNominaModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $search     = trim($_GET['search'] ?? '');
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $perPage    = 25;
        $total      = $this->model->countNominas($search);
        $totalPages = (int) ceil($total / $perPage);
        $nominas    = $this->model->getNominas($search, $page, $perPage);

        $this->view('resources/nomina/index', [
            'nominas'    => $nominas,
            'search'     => $search,
            'page'       => $page,
            'total'      => $total,
            'totalPages' => $totalPages,
            'perPage'    => $perPage,
        ]);
    }

    // =========================================================================
    // CREAR NÓMINA (formulario inicial)
    // =========================================================================

    public function create(): void
    {
        $this->view('resources/nomina/create', [
            'sesionesPendientesCount' => $this->model->countSesionesPendientesGlobal(),
        ]);
    }

    public function store(): void
    {
        try {
            $tipo      = trim($_POST['tipo'] ?? '');
            $fechaPago = trim($_POST['fecha_pago'] ?? '');

            if (!in_array($tipo, ['QUINCENAL', 'POR_DIA', 'POR_SESION'], true)) {
                $this->jsonFinal(['success' => false, 'message' => 'Tipo de nómina inválido.'], 422);
                return;
            }
            if ($fechaPago === '') {
                $this->jsonFinal(['success' => false, 'message' => 'La fecha de pago es obligatoria.'], 422);
                return;
            }

            $nombre = $this->model->generarNombre($tipo);
            $userId = $_SESSION['user']['id'];
            $id     = $this->model->createNomina(['nombre' => $nombre, 'tipo' => $tipo, 'fecha_pago' => $fechaPago], $userId);

            AuditService::log($userId, 'Nómina', 'CREAR', "Creó nómina '{$nombre}' tipo {$tipo}", $id);

            $this->jsonFinal(['success' => true, 'message' => 'Nómina creada.', 'nomina_id' => $id]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // MANAGE (gestión de personal, asignaciones, deducciones)
    // =========================================================================

    public function manage(): void
    {
        $nominaId = (int) ($_GET['id'] ?? 0);
        $nomina   = $nominaId ? $this->model->getNominaById($nominaId) : null;

        if (!$nomina) {
            header('Location: /diplomatic/public/resources/nomina?error=notfound');
            exit;
        }

        $personal      = $this->model->getPersonalEnNomina($nominaId);
        $asignaciones  = $this->model->getCatalogoAsignaciones();
        $deducciones   = $this->model->getCatalogoDeducciones();
        $tasaBcv       = $this->model->getTasaBcvActual();

        $this->view('resources/nomina/manage', [
            'nomina'        => $nomina,
            'nominaId'      => $nominaId,
            'personal'      => $personal,
            'asignaciones'  => $asignaciones,
            'deducciones'   => $deducciones,
            'tasaBcv'       => $tasaBcv,
        ]);
    }

    // =========================================================================
    // BUSCAR PERSONAL (AJAX)
    // =========================================================================

    public function buscarPersonal(): void
    {
        try {
            $nominaId = (int) ($_GET['nomina_id'] ?? 0);
            $tipo     = trim($_GET['tipo'] ?? '');
            $search   = trim($_GET['search'] ?? '');

            $data = $this->model->buscarPersonalPorTipo($tipo, $search, $nominaId);
            $this->jsonFinal(['success' => true, 'data' => $data]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getMontoContrato(): void
    {
        try {
            $personalId = (int) ($_GET['personal_id'] ?? 0);
            $monto = $this->model->getMontoContrato($personalId);
            $this->jsonFinal(['success' => true, 'monto' => $monto]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getColaSesiones(): void
    {
        try {
            $nominaId = (int) ($_GET['nomina_id'] ?? 0);
            $nomina   = $nominaId ? $this->model->getNominaById($nominaId) : null;

            if (!$nomina || $nomina['tipo'] !== 'POR_SESION') {
                $this->jsonFinal(['success' => true, 'data' => []]);
                return;
            }

            $data = $this->model->getProfesoresConSesionesPendientes($nominaId);

            foreach ($data as &$p) {
                $p['detalle'] = $this->model->getSesionesPendientesDetalle((int) $p['id']);
            }

            $this->jsonFinal(['success' => true, 'data' => $data]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDetalleSesionesPendientes(): void
    {
        try {
            $personalId = (int) ($_GET['personal_id'] ?? 0);
            $detalle = $this->model->getSesionesPendientesDetalle($personalId);
            $this->jsonFinal(['success' => true, 'data' => $detalle]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // AGREGAR / QUITAR PERSONAL
    // =========================================================================

    public function addPersonal(): void
    {
        try {
            $nominaId    = (int)   ($_POST['nomina_id']   ?? 0);
            $personalId  = (int)   ($_POST['personal_id'] ?? 0);
            $salarioBase = (float) ($_POST['salario_base'] ?? 0);

            if (!$nominaId || !$personalId) {
                $this->jsonFinal(['success' => false, 'message' => 'Datos incompletos.'], 422);
                return;
            }
            if ($salarioBase <= 0) {
                $this->jsonFinal(['success' => false, 'message' => 'El salario base debe ser mayor a 0.'], 422);
                return;
            }

            $tasaBcv = $this->model->getTasaBcvActual();
            if ($tasaBcv <= 0) {
                $this->jsonFinal(['success' => false, 'message' => 'No hay una tasa BCV activa registrada.'], 422);
                return;
            }

            $nomina = $this->model->getNominaById($nominaId);
            $userId = $_SESSION['user']['id'];
            $npId   = $this->model->addPersonalToNomina($nominaId, $personalId, $salarioBase, $tasaBcv, $userId);

            // Si es nómina POR_SESION, vincular automáticamente las sesiones pendientes
            if ($nomina && $nomina['tipo'] === 'POR_SESION') {
                $sesionIds = $this->model->getSesionesPendientesIds($personalId);
                $this->model->linkSesionesToNominaPersonal($npId, $sesionIds);
            }

            AuditService::log($userId, 'Nómina', 'AGREGAR_PERSONAL',
                "Agregó personal {$personalId} a nómina {$nominaId} con salario {$salarioBase}", $nominaId);

            $personal = $this->model->getPersonalEnNomina($nominaId);
            $this->jsonFinal(['success' => true, 'message' => 'Personal agregado.', 'personal' => $personal]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function removePersonal(): void
    {
        try {
            $nominaPersonalId = (int) ($_POST['id']         ?? 0);
            $nominaId         = (int) ($_POST['nomina_id']  ?? 0);

            if (!$nominaPersonalId) {
                $this->jsonFinal(['success' => false, 'message' => 'ID inválido.'], 422);
                return;
            }

            $this->model->removePersonalFromNomina($nominaPersonalId);

            $userId = $_SESSION['user']['id'];
            AuditService::log($userId, 'Nómina', 'QUITAR_PERSONAL', "Quitó nomina_personal {$nominaPersonalId}", $nominaId);

            $personal = $this->model->getPersonalEnNomina($nominaId);
            $this->jsonFinal(['success' => true, 'message' => 'Personal removido.', 'personal' => $personal]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // CATÁLOGOS
    // =========================================================================

    public function getCatalogos(): void
    {
        try {
            $this->jsonFinal([
                'success'      => true,
                'asignaciones' => $this->model->getCatalogoAsignaciones(),
                'deducciones'  => $this->model->getCatalogoDeducciones(),
            ]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ASIGNACIONES / DEDUCCIONES POR PERSONA
    // =========================================================================

    public function addAsignacion(): void
    {
        try {
            $nominaPersonalId = (int) ($_POST['nomina_personal_id'] ?? 0);
            $nominaId         = (int) ($_POST['nomina_id']          ?? 0);
            $asignacionId     = (int) ($_POST['asignacion_id']      ?? 0) ?: null;
            $nombre           = trim($_POST['nombre'] ?? '');
            $monto            = (float) ($_POST['monto'] ?? 0);

            if (!$nominaPersonalId || $nombre === '' || $monto <= 0) {
                $this->jsonFinal(['success' => false, 'message' => 'Datos incompletos.'], 422);
                return;
            }

            $this->model->addAsignacionToPersonal($nominaPersonalId, $asignacionId, $nombre, $monto);

            $personal = $this->model->getPersonalEnNomina($nominaId);
            $this->jsonFinal(['success' => true, 'message' => 'Asignación agregada.', 'personal' => $personal]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function addDeduccion(): void
    {
        try {
            $nominaPersonalId = (int) ($_POST['nomina_personal_id'] ?? 0);
            $nominaId         = (int) ($_POST['nomina_id']          ?? 0);
            $deduccionId      = (int) ($_POST['deduccion_id']       ?? 0) ?: null;
            $nombre           = trim($_POST['nombre'] ?? '');
            $monto            = (float) ($_POST['monto'] ?? 0);

            if (!$nominaPersonalId || $nombre === '' || $monto <= 0) {
                $this->jsonFinal(['success' => false, 'message' => 'Datos incompletos.'], 422);
                return;
            }

            $this->model->addDeduccionToPersonal($nominaPersonalId, $deduccionId, $nombre, $monto);

            $personal = $this->model->getPersonalEnNomina($nominaId);
            $this->jsonFinal(['success' => true, 'message' => 'Deducción agregada.', 'personal' => $personal]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteAsignacionItem(): void
    {
        try {
            $id               = (int) ($_POST['id'] ?? 0);
            $nominaPersonalId = (int) ($_POST['nomina_personal_id'] ?? 0);
            $nominaId         = (int) ($_POST['nomina_id'] ?? 0);

            $this->model->deleteAsignacionConcepto($id, $nominaPersonalId);

            $personal = $this->model->getPersonalEnNomina($nominaId);
            $this->jsonFinal(['success' => true, 'message' => 'Concepto eliminado.', 'personal' => $personal]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteDeduccionItem(): void
    {
        try {
            $id               = (int) ($_POST['id'] ?? 0);
            $nominaPersonalId = (int) ($_POST['nomina_personal_id'] ?? 0);
            $nominaId         = (int) ($_POST['nomina_id'] ?? 0);

            $this->model->deleteDeduccionConcepto($id, $nominaPersonalId);

            $personal = $this->model->getPersonalEnNomina($nominaId);
            $this->jsonFinal(['success' => true, 'message' => 'Concepto eliminado.', 'personal' => $personal]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PROCESAR NÓMINA
    // =========================================================================

    public function procesar(): void
    {
        try {
            $nominaId = (int) ($_POST['nomina_id'] ?? 0);
            $nomina   = $nominaId ? $this->model->getNominaById($nominaId) : null;

            if (!$nomina) {
                $this->jsonFinal(['success' => false, 'message' => 'Nómina no encontrada.'], 404);
                return;
            }
            if ($nomina['estado'] !== 'BORRADOR') {
                $this->jsonFinal(['success' => false, 'message' => 'Esta nómina ya fue procesada.'], 422);
                return;
            }
            if ($this->model->countPersonalEnNomina($nominaId) === 0) {
                $this->jsonFinal(['success' => false, 'message' => 'Agrega al menos una persona antes de procesar.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->procesarNomina($nominaId, $userId);

            AuditService::log($userId, 'Nómina', 'PROCESAR', "Procesó nómina '{$nomina['nombre']}'", $nominaId);

            $this->jsonFinal(['success' => true, 'message' => 'Nómina procesada correctamente.']);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // DESCARTAR / REVERSAR
    // =========================================================================

    public function descartar(): void
    {
        try {
            $nominaId = (int) ($_POST['nomina_id'] ?? 0);
            $nomina   = $nominaId ? $this->model->getNominaById($nominaId) : null;

            if (!$nomina) {
                $this->jsonFinal(['success' => false, 'message' => 'Nómina no encontrada.'], 404);
                return;
            }
            if ($nomina['estado'] !== 'BORRADOR') {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden descartar nóminas en BORRADOR.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->descartarNomina($nominaId);

            AuditService::log($userId, 'Nómina', 'DESCARTAR', "Descartó nómina '{$nomina['nombre']}'", $nominaId);

            $this->jsonFinal(['success' => true, 'message' => 'Nómina descartada correctamente.']);

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
            if (!in_array($nomina['estado'], ['PROCESADA', 'APROBADA'], true)) {
                $this->jsonFinal(['success' => false, 'message' => 'Solo se pueden reversar nóminas PROCESADAS o APROBADAS (no pagadas).'], 422);
                return;
            }
            if ($nomina['estado'] === 'APROBADA' && $this->model->countOrdenesPagadas($nominaId) > 0) {
                $this->jsonFinal(['success' => false, 'message' => 'Esta nómina tiene órdenes de pago ya PAGADAS. Debes reversar esos pagos primero.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->reversarNomina($nominaId, $userId);

            $mensaje = $nomina['estado'] === 'APROBADA'
                ? 'Nómina reversada a PROCESADA. Las órdenes de pago generadas fueron eliminadas.'
                : 'Nómina reversada a borrador.';

            AuditService::log($userId, 'Nómina', 'REVERSAR', "Reversó nómina '{$nomina['nombre']}' desde {$nomina['estado']}", $nominaId);

            $this->jsonFinal(['success' => true, 'message' => $mensaje]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // EDITAR SALARIO BASE DE UNA PERSONA YA AGREGADA
    // =========================================================================

    public function updateSalario(): void
    {
        try {
            $nominaPersonalId = (int)   ($_POST['nomina_personal_id'] ?? 0);
            $nominaId          = (int)   ($_POST['nomina_id']          ?? 0);
            $nuevoSalario      = (float) ($_POST['salario_base']       ?? 0);

            if (!$nominaPersonalId || $nuevoSalario <= 0) {
                $this->jsonFinal(['success' => false, 'message' => 'El salario debe ser mayor a 0.'], 422);
                return;
            }

            $this->model->updateSalarioPersonal($nominaPersonalId, $nuevoSalario);

            $userId = $_SESSION['user']['id'];
            AuditService::log($userId, 'Nómina', 'EDITAR_SALARIO',
                "Actualizó salario base a {$nuevoSalario} en nomina_personal {$nominaPersonalId}", $nominaId);

            $personal = $this->model->getPersonalEnNomina($nominaId);
            $this->jsonFinal(['success' => true, 'message' => 'Salario actualizado.', 'personal' => $personal]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // JSON
    // =========================================================================

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