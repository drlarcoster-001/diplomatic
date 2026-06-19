<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/controllers/AcademicHorariosPracticasController.php
 * PROPÓSITO: Gestión de horarios de práctica con grupos, estudiantes, centros médicos
 *            y fechas específicas. Todas las mutaciones responden JSON vía AJAX.
 * VERSIÓN: 2.0.0 - Agrega endpoints para fechas por horario de práctica.
 *
 * RUTAS para Bootstrap.php:
 *   use App\Controllers\AcademicHorariosPracticasController;
 *   $router->get('/academic/horarios-practicas',                   [AcademicHorariosPracticasController::class, 'index']);
 *   $router->get('/academic/horarios-practicas/manage',            [AcademicHorariosPracticasController::class, 'manage']);
 *   $router->post('/academic/horarios-practicas/saveGrupo',        [AcademicHorariosPracticasController::class, 'saveGrupo']);
 *   $router->post('/academic/horarios-practicas/deleteGrupo',      [AcademicHorariosPracticasController::class, 'deleteGrupo']);
 *   $router->post('/academic/horarios-practicas/saveHorario',      [AcademicHorariosPracticasController::class, 'saveHorario']);
 *   $router->post('/academic/horarios-practicas/deleteHorario',    [AcademicHorariosPracticasController::class, 'deleteHorario']);
 *   $router->post('/academic/horarios-practicas/saveFechas',       [AcademicHorariosPracticasController::class, 'saveFechas']);
 *   $router->get('/academic/horarios-practicas/getFechas',         [AcademicHorariosPracticasController::class, 'getFechas']);
 *   $router->post('/academic/horarios-practicas/saveEstudiante',   [AcademicHorariosPracticasController::class, 'saveEstudiante']);
 *   $router->post('/academic/horarios-practicas/deleteEstudiante', [AcademicHorariosPracticasController::class, 'deleteEstudiante']);
 *   $router->get('/academic/horarios-practicas/getEstudiantes',    [AcademicHorariosPracticasController::class, 'getEstudiantes']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AcademicHorariosPracticasModel;
use App\Services\AuditService;
use Throwable;

class AcademicHorariosPracticasController extends Controller
{
    private AcademicHorariosPracticasModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $allowed = ['ADMIN', 'OPERATOR', 'ACADEMIC'];
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed, true)) {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new AcademicHorariosPracticasModel();
    }

    public function index(): void
    {
        $search     = trim($_GET['search'] ?? '');
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $perPage    = 25;
        $total      = $this->model->countOfertas($search);
        $totalPages = (int) ceil($total / $perPage);
        $ofertas    = $this->model->getOfertasConHorarios($search, $page, $perPage);

        $this->view('academic/horarios_practicas/index', [
            'ofertas'    => $ofertas,
            'search'     => $search,
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
        ]);
    }

    public function manage(): void
    {
        $offeringId = (int) ($_GET['offering_id'] ?? 0);
        $oferta     = $offeringId ? $this->model->getOferta($offeringId) : null;

        if (!$oferta) {
            header('Location: /diplomatic/public/academic/horarios-practicas?error=notfound');
            exit;
        }

        $grupos         = $this->model->getGruposByOffering($offeringId);
        $horarios       = $this->model->getHorariosByOffering($offeringId);
        $centrosMedicos = $this->model->getCentrosMedicos();
        $fechas         = $this->model->getFechasByOffering($offeringId);

        $this->view('academic/horarios_practicas/manage', [
            'oferta'         => $oferta,
            'offeringId'     => $offeringId,
            'grupos'         => $grupos,
            'horarios'       => $horarios,
            'centrosMedicos' => $centrosMedicos,
            'fechas'         => $fechas,
        ]);
    }

    // ===== GRUPOS =====

    public function saveGrupo(): void
    {
        try {
            $offeringId = (int)  ($_POST['offering_id'] ?? 0);
            $nombre     = trim(($_POST['nombre']        ?? ''));

            if (!$offeringId || $nombre === '') {
                $this->jsonFinal(['success' => false, 'message' => 'El nombre del grupo es obligatorio.'], 422);
                return;
            }
            if ($this->model->grupoNombreExists($offeringId, $nombre)) {
                $this->jsonFinal(['success' => false, 'message' => "Ya existe el grupo '{$nombre}' en esta oferta."], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $id     = $this->model->createGrupo($offeringId, $nombre, $userId);
            AuditService::log($userId, 'Horarios Prácticas', 'CREAR_GRUPO', "Creó grupo '{$nombre}' (oferta {$offeringId})", $id);

            $grupos = $this->model->getGruposByOffering($offeringId);
            $this->jsonFinal(['success' => true, 'message' => "Grupo '{$nombre}' creado.", 'grupos' => $grupos]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteGrupo(): void
    {
        try {
            $id    = (int) ($_POST['id'] ?? 0);
            $grupo = $id ? $this->model->getGrupoById($id) : null;
            if (!$grupo) { $this->jsonFinal(['success' => false, 'message' => 'Grupo no encontrado.'], 404); return; }

            $userId = $_SESSION['user']['id'];
            $accion = $this->model->deleteGrupo($id, $userId);
            AuditService::log($userId, 'Horarios Prácticas', 'ELIMINAR_GRUPO', "Grupo '{$grupo['nombre']}' {$accion}", $id);

            $grupos = $this->model->getGruposByOffering($grupo['offering_id']);
            $msg    = $accion === 'deleted' ? 'Grupo eliminado.' : 'Grupo inactivado (tiene estudiantes asignados).';
            $this->jsonFinal(['success' => true, 'message' => $msg, 'grupos' => $grupos]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ===== ESTUDIANTES =====

    public function getEstudiantes(): void
    {
        try {
            $grupoId    = (int) ($_GET['grupo_id']    ?? 0);
            $offeringId = (int) ($_GET['offering_id'] ?? 0);
            $tipo       = $_GET['tipo'] ?? 'asignados';
            $data = $tipo === 'sin_grupo'
                ? $this->model->getEstudiantesSinGrupo($offeringId)
                : $this->model->getEstudiantesDelGrupo($grupoId);
            $this->jsonFinal(['success' => true, 'data' => $data]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function saveEstudiante(): void
    {
        try {
            $grupoId      = (int) ($_POST['grupo_id']      ?? 0);
            $enrollmentId = (int) ($_POST['enrollment_id'] ?? 0);
            $offeringId   = (int) ($_POST['offering_id']   ?? 0);
            if (!$grupoId || !$enrollmentId) {
                $this->jsonFinal(['success' => false, 'message' => 'Datos incompletos.'], 422); return;
            }
            $userId = $_SESSION['user']['id'];
            $ok = $this->model->saveEstudiante($grupoId, $enrollmentId, $userId);
            if (!$ok) {
                $this->jsonFinal(['success' => false, 'message' => 'El estudiante ya está asignado a un grupo de esta oferta.'], 422); return;
            }
            AuditService::log($userId, 'Horarios Prácticas', 'ASIGNAR_ESTUDIANTE', "Asignó enrollment {$enrollmentId} a grupo {$grupoId}", $grupoId);
            $asignados = $this->model->getEstudiantesDelGrupo($grupoId);
            $grupos    = $this->model->getGruposByOffering($offeringId);
            $this->jsonFinal(['success' => true, 'message' => 'Estudiante asignado.', 'asignados' => $asignados, 'grupos' => $grupos]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteEstudiante(): void
    {
        try {
            $asignacionId = (int) ($_POST['asignacion_id'] ?? 0);
            $grupoId      = (int) ($_POST['grupo_id']      ?? 0);
            $offeringId   = (int) ($_POST['offering_id']   ?? 0);
            if (!$asignacionId) { $this->jsonFinal(['success' => false, 'message' => 'ID inválido.'], 422); return; }
            $userId = $_SESSION['user']['id'];
            $this->model->deleteEstudiante($asignacionId);
            AuditService::log($userId, 'Horarios Prácticas', 'QUITAR_ESTUDIANTE', "Quitó asignación {$asignacionId} del grupo {$grupoId}", $grupoId);
            $asignados = $this->model->getEstudiantesDelGrupo($grupoId);
            $grupos    = $this->model->getGruposByOffering($offeringId);
            $this->jsonFinal(['success' => true, 'message' => 'Estudiante removido.', 'asignados' => $asignados, 'grupos' => $grupos]);
        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ===== HORARIOS DE PRÁCTICA =====

    public function saveHorario(): void
    {
        try {
            $offeringId = (int) ($_POST['offering_id']      ?? 0);
            $grupoId    = (int) ($_POST['grupo_id']         ?? 0);
            $centroId   = (int) ($_POST['centro_medico_id'] ?? 0);

            if (!$offeringId || !$grupoId || !$centroId) {
                $this->jsonFinal(['success' => false, 'message' => 'Todos los campos son obligatorios.'], 422); return;
            }
            if ($this->model->horarioExists($offeringId, $grupoId, $centroId)) {
                $this->jsonFinal(['success' => false, 'message' => 'Ese grupo ya tiene asignado ese centro médico.'], 422); return;
            }

            $userId = $_SESSION['user']['id'];
            $id = $this->model->saveHorario(['offering_id' => $offeringId, 'grupo_id' => $grupoId, 'centro_medico_id' => $centroId], $userId);
            AuditService::log($userId, 'Horarios Prácticas', 'CREAR_HORARIO', "Creó horario práctica ID {$id} (grupo {$grupoId} → centro {$centroId})", $id);

            $horarios = $this->model->getHorariosByOffering($offeringId);
            $fechas   = $this->model->getFechasByOffering($offeringId);
            $this->jsonFinal(['success' => true, 'message' => 'Asignación creada.', 'horario_id' => $id, 'horarios' => $horarios, 'fechas' => $fechas]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteHorario(): void
    {
        try {
            $id         = (int) ($_POST['id']          ?? 0);
            $offeringId = (int) ($_POST['offering_id'] ?? 0);
            $horario    = $id ? $this->model->getHorarioById($id) : null;
            if (!$horario) { $this->jsonFinal(['success' => false, 'message' => 'Horario no encontrado.'], 404); return; }

            $userId = $_SESSION['user']['id'];
            $accion = $this->model->deleteHorario($id, $userId);
            AuditService::log($userId, 'Horarios Prácticas', strtoupper($accion), "Horario práctica ID {$id} {$accion}", $id);

            $horarios = $this->model->getHorariosByOffering($offeringId);
            $fechas   = $this->model->getFechasByOffering($offeringId);
            $msg      = $accion === 'deleted' ? 'Asignación eliminada.' : 'Asignación inactivada (tiene sesiones vinculadas).';
            $this->jsonFinal(['success' => true, 'message' => $msg, 'horarios' => $horarios, 'fechas' => $fechas]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ===== FECHAS =====

    public function saveFechas(): void
    {
        try {
            $horarioId  = (int) ($_POST['horario_practica_id'] ?? 0);
            $offeringId = (int) ($_POST['offering_id']         ?? 0);
            $fechasRaw  = $_POST['fechas'] ?? '';
            $fechas     = array_filter(array_map('trim', explode(',', $fechasRaw)));

            if (!$horarioId) {
                $this->jsonFinal(['success' => false, 'message' => 'Horario no especificado.'], 422); return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->saveFechas($horarioId, $fechas, $userId);
            AuditService::log($userId, 'Horarios Prácticas', 'GUARDAR_FECHAS',
                "Guardó " . count($fechas) . " fechas para horario práctica {$horarioId}", $horarioId);

            $horarios = $this->model->getHorariosByOffering($offeringId);
            $fechasAll = $this->model->getFechasByOffering($offeringId);
            $this->jsonFinal(['success' => true, 'message' => count($fechas) . ' fecha(s) guardadas.', 'horarios' => $horarios, 'fechas' => $fechasAll]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getFechas(): void
    {
        try {
            $horarioId = (int) ($_GET['horario_practica_id'] ?? 0);
            $data = $horarioId ? $this->model->getFechasByHorario($horarioId) : [];
            $this->jsonFinal(['success' => true, 'data' => $data]);
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