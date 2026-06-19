<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/controllers/AcademicHorariosTeoricosController.php
 * PROPÓSITO: index() muestra el listado de ofertas con conteo de horarios.
 *            manage() abre la grilla interactiva scoped a una oferta específica.
 *            save/update/delete responden JSON puro vía AJAX.
 *            Blindaje de búfer en jsonFinal() para evitar "Unexpected token <".
 * VERSIÓN: 3.0.0 - Rediseño de flujo: index=listado de ofertas, manage=grilla por oferta.
 *
 * RUTAS a registrar en Bootstrap.php:
 *   use App\Controllers\AcademicHorariosTeoricosController;
 *   $router->get('/academic/horarios-teoricos',          [AcademicHorariosTeoricosController::class, 'index']);
 *   $router->get('/academic/horarios-teoricos/manage',   [AcademicHorariosTeoricosController::class, 'manage']);
 *   $router->post('/academic/horarios-teoricos/save',    [AcademicHorariosTeoricosController::class, 'save']);
 *   $router->post('/academic/horarios-teoricos/update',  [AcademicHorariosTeoricosController::class, 'update']);
 *   $router->post('/academic/horarios-teoricos/delete',  [AcademicHorariosTeoricosController::class, 'delete']);
 *
 * ELIMINAR de Bootstrap.php si existían:
 *   /academic/horarios-teoricos/create
 *   /academic/horarios-teoricos/edit
 *   /academic/horarios-teoricos/getAll
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AcademicHorariosTeoricosModel;
use App\Services\AuditService;
use Throwable;

class AcademicHorariosTeoricosController extends Controller
{
    private AcademicHorariosTeoricosModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $allowed = ['ADMIN', 'OPERATOR', 'ACADEMIC'];
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed, true)) {
            header('Location: /diplomatic/public/login');
            exit;
        }

        $this->model = new AcademicHorariosTeoricosModel();
    }

    /**
     * Lista todas las ofertas con conteo de horarios teóricos.
     */
    public function index(): void
    {
        $ofertas = $this->model->getOfertasConHorarios();
        $this->view('academic/horarios_teoricos/index', ['ofertas' => $ofertas]);
    }

    /**
     * Grilla interactiva scoped a una oferta específica.
     * GET /academic/horarios-teoricos/manage?offering_id=X
     */
    public function manage(): void
    {
        $offeringId = (int) ($_GET['offering_id'] ?? 0);
        $oferta     = $offeringId ? $this->model->getOferta($offeringId) : null;

        if (!$oferta) {
            header('Location: /diplomatic/public/academic/horarios-teoricos?error=notfound');
            exit;
        }

        $horarios = $this->model->getByOffering($offeringId);

        $this->view('academic/horarios_teoricos/manage', [
            'oferta'     => $oferta,
            'horarios'   => $horarios,
            'offeringId' => $offeringId,
        ]);
    }

    /**
     * Crea un nuevo horario teórico. Responde JSON.
     */
    public function save(): void
    {
        try {
            $offeringId = (int)  ($_POST['offering_id'] ?? 0);
            $diaSemana  = trim(($_POST['dia_semana']   ?? ''));
            $horaInicio = trim(($_POST['hora_inicio']  ?? ''));
            $horaFin    = trim(($_POST['hora_fin']     ?? ''));

            if (!$offeringId || !$diaSemana || !$horaInicio || !$horaFin) {
                $this->jsonFinal(['success' => false, 'message' => 'Todos los campos son obligatorios.'], 422);
                return;
            }
            if ($horaFin <= $horaInicio) {
                $this->jsonFinal(['success' => false, 'message' => 'La hora de fin debe ser posterior a la de inicio.'], 422);
                return;
            }
            if ($this->model->exists($offeringId, $diaSemana, $horaInicio)) {
                $this->jsonFinal(['success' => false, 'message' => 'Ya existe un horario para ese día y hora de inicio.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $id = $this->model->create([
                'offering_id' => $offeringId,
                'dia_semana'  => $diaSemana,
                'hora_inicio' => $horaInicio,
                'hora_fin'    => $horaFin,
            ], $userId);

            AuditService::log($userId, 'Horarios Teóricos', 'CREAR',
                "Creó horario teórico ID {$id} (offering {$offeringId}, {$diaSemana} {$horaInicio}-{$horaFin})", $id);

            $horario = $this->model->getById($id);
            $this->jsonFinal(['success' => true, 'message' => 'Horario creado correctamente.', 'horario' => $horario]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualiza un horario existente. Responde JSON.
     * Nota: offering_id no cambia en update (el horario pertenece a la oferta original).
     */
    public function update(): void
    {
        try {
            $id         = (int)  ($_POST['id']          ?? 0);
            $offeringId = (int)  ($_POST['offering_id'] ?? 0);
            $diaSemana  = trim(($_POST['dia_semana']   ?? ''));
            $horaInicio = trim(($_POST['hora_inicio']  ?? ''));
            $horaFin    = trim(($_POST['hora_fin']     ?? ''));

            if (!$id || !$diaSemana || !$horaInicio || !$horaFin) {
                $this->jsonFinal(['success' => false, 'message' => 'Todos los campos son obligatorios.'], 422);
                return;
            }
            if ($horaFin <= $horaInicio) {
                $this->jsonFinal(['success' => false, 'message' => 'La hora de fin debe ser posterior a la de inicio.'], 422);
                return;
            }
            if ($this->model->exists($offeringId, $diaSemana, $horaInicio, $id)) {
                $this->jsonFinal(['success' => false, 'message' => 'Ya existe otro horario con ese día y hora de inicio.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->update($id, [
                'dia_semana'  => $diaSemana,
                'hora_inicio' => $horaInicio,
                'hora_fin'    => $horaFin,
            ], $userId);

            AuditService::log($userId, 'Horarios Teóricos', 'EDITAR',
                "Editó horario teórico ID {$id} ({$diaSemana} {$horaInicio}-{$horaFin})", $id);

            $horario = $this->model->getById($id);
            $this->jsonFinal(['success' => true, 'message' => 'Horario actualizado correctamente.', 'horario' => $horario]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Elimina o inactiva un horario. Responde JSON.
     */
    public function delete(): void
    {
        try {
            $id      = (int) ($_POST['id'] ?? 0);
            $horario = $id ? $this->model->getById($id) : null;

            if (!$horario) {
                $this->jsonFinal(['success' => false, 'message' => 'Horario no encontrado.'], 404);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $accion = $this->model->smartDelete($id, $userId);

            $msg = $accion === 'deleted'
                ? 'Horario eliminado correctamente.'
                : 'El horario tiene sesiones vinculadas, se inactivó en lugar de eliminarse.';

            AuditService::log($userId, 'Horarios Teóricos', strtoupper($accion),
                "{$msg} ID {$id} ({$horario['dia_semana']} {$horario['hora_inicio']})", $id);

            $this->jsonFinal(['success' => true, 'message' => $msg, 'action' => $accion]);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function jsonFinal(array $payload, int $code = 200): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        try {
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error de codificación JSON.']);
        }
        exit;
    }
}