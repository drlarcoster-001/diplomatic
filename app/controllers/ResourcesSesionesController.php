<?php
/**
 * MÓDULO: RECURSOS HUMANOS / SESIONES
 * ARCHIVO: app/controllers/ResourcesSesionesController.php
 * PROPÓSITO: index() lista ofertas activas. manage() abre la gestión de sesiones
 *            de una oferta con dos pestañas (Teóricos/Prácticos). Todas las
 *            mutaciones responden JSON vía AJAX.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS para Bootstrap.php:
 *   use App\Controllers\ResourcesSesionesController;
 *   $router->get('/resources/sesiones',                [ResourcesSesionesController::class, 'index']);
 *   $router->get('/resources/sesiones/manage',         [ResourcesSesionesController::class, 'manage']);
 *   $router->get('/resources/sesiones/getPersonal',    [ResourcesSesionesController::class, 'getPersonal']);
 *   $router->get('/resources/sesiones/getSesiones',    [ResourcesSesionesController::class, 'getSesiones']);
 *   $router->post('/resources/sesiones/save',          [ResourcesSesionesController::class, 'save']);
 *   $router->post('/resources/sesiones/delete',        [ResourcesSesionesController::class, 'delete']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ResourcesSesionesModel;
use App\Services\AuditService;
use Throwable;

class ResourcesSesionesController extends Controller
{
    private ResourcesSesionesModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $allowed = ['ADMIN', 'OPERATOR', 'ACADEMIC'];
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed, true)) {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new ResourcesSesionesModel();
    }

    public function index(): void
    {
$periodoId  = (int) ($_GET['periodo_id'] ?? 0);
    $diplomaId  = (int) ($_GET['diploma_id'] ?? 0);
    $filters = [
        'periodo_id' => $periodoId ?: null,
        'search'     => $diplomaId ? '' : trim($_GET['search'] ?? ''),
        'diploma_id' => $diplomaId ?: null,
    ];
    $page       = max(1, (int) ($_GET['page'] ?? 1));
    $perPage    = 25;
    $total      = $this->model->countOfertas($filters);
    $totalPages = (int) ceil($total / $perPage);
    $ofertas    = $this->model->getOfertasActivas($filters, $page, $perPage);

    $this->view('resources/sesiones/index', [
        'ofertas'    => $ofertas,
        'page'       => $page,
        'filters'    => $filters,
        'periodos'   => $this->model->getPeriodos(),
        'diplomados' => $this->model->getDiplomadosPorPeriodo($periodoId),
        'periodoId'  => $periodoId,
        'diplomaId'  => $diplomaId,
        'total'      => $total,
        'totalPages' => $totalPages,
        'perPage'    => $perPage,
    ]);
    }

    public function manage(): void
    {
        $offeringId = (int) ($_GET['offering_id'] ?? 0);
        $oferta     = $offeringId ? $this->model->getOferta($offeringId) : null;

        if (!$oferta) {
            header('Location: /diplomatic/public/resources/sesiones?error=notfound');
            exit;
        }

        $personal        = $this->model->getPersonalDocente();
        $horariosTeoricos = $this->model->getHorariosTeoricos($offeringId);
        $horariosPracticos = $this->model->getHorariosPracticos($offeringId);

        $this->view('resources/sesiones/manage', [
            'oferta'             => $oferta,
            'offeringId'         => $offeringId,
            'personal'           => $personal,
            'horariosTeoricos'   => $horariosTeoricos,
            'horariosPracticos'  => $horariosPracticos,
        ]);
    }

    public function getPersonal(): void
    {
        try {
            $data = $this->model->getPersonalDocente();
            $this->jsonFinal(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getSesiones(): void
    {
        try {
            $personalId = (int) ($_GET['personal_id']  ?? 0);
            $offeringId = (int) ($_GET['offering_id']  ?? 0);
            $data = $this->model->getSesionesByPersonal($personalId, $offeringId);
            $this->jsonFinal(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function save(): void
    {
        try {
            $tipo       = trim($_POST['tipo_horario'] ?? '');
            $horarioId  = (int) ($_POST['horario_id']  ?? 0);
            $personalId = (int) ($_POST['personal_id'] ?? 0);
            $offeringId = (int) ($_POST['offering_id'] ?? 0);
            $fechasRaw  = $_POST['fechas'] ?? '';
            $fechas     = array_filter(array_map('trim', explode(',', $fechasRaw)));

            if (!$tipo || !$horarioId || !$personalId || empty($fechas)) {
                $this->jsonFinal(['success' => false, 'message' => 'Faltan datos requeridos.'], 422);
                return;
            }
            if (!$this->model->personalPerteneceOferta($personalId, $offeringId)) {
                $this->jsonFinal(['success' => false, 'message' => 'Este profesor no está asignado a esta oferta académica.'], 422);
                return;
            }

            $userId   = $_SESSION['user']['id'];
            $creadas  = 0;
            $duplicadas = 0;

            foreach ($fechas as $fecha) {
                if ($this->model->sesionExists($tipo, $horarioId, $fecha, $personalId)) {
                    $duplicadas++;
                    continue;
                }
                $this->model->createSesion([
                    'tipo_horario' => $tipo,
                    'horario_id'   => $horarioId,
                    'personal_id'  => $personalId,
                    'fecha'        => $fecha,
                ], $userId);
                $creadas++;
            }

            AuditService::log($userId, 'Sesiones', 'CREAR',
                "Programó {$creadas} sesión(es) tipo {$tipo}, horario {$horarioId}", $horarioId);

            $msg = "{$creadas} sesión(es) programada(s).";
            if ($duplicadas > 0) $msg .= " {$duplicadas} ya existían y se omitieron.";

            $sesiones = $this->model->getSesionesByPersonal($personalId, $offeringId);
            $this->jsonFinal(['success' => true, 'message' => $msg, 'sesiones' => $sesiones]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete(): void
    {
        try {
            $id         = (int) ($_POST['id']          ?? 0);
            $personalId = (int) ($_POST['personal_id'] ?? 0);
            $offeringId = (int) ($_POST['offering_id'] ?? 0);
            $sesion     = $id ? $this->model->getSesionById($id) : null;

            if (!$sesion) {
                $this->jsonFinal(['success' => false, 'message' => 'Sesión no encontrada.'], 404);
                return;
            }
            if ($sesion['estado'] === 'DICTADA') {
                $this->jsonFinal(['success' => false, 'message' => 'No se puede eliminar una sesión ya dictada.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->deleteSesion($id, $userId);
            AuditService::log($userId, 'Sesiones', 'ELIMINAR', "Eliminó sesión ID {$id}", $id);

            $sesiones = $this->model->getSesionesByPersonal($personalId, $offeringId);
            $this->jsonFinal(['success' => true, 'message' => 'Sesión eliminada.', 'sesiones' => $sesiones]);

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