<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/controllers/PeriodosController.php
 * PROPÓSITO: Orquestar las operaciones administrativas de los períodos institucionales. Actúa como mediador entre la interfaz de usuario y el modelo de datos, gestionando validaciones de entrada, flujos de redirección y el registro de auditoría narrativa de eventos.
 * NOTA: Un período institucional agrupa múltiples cohortes académicas bajo un mismo contexto operativo (ej: 2026-COHORTE-15). Hasta tres períodos pueden estar activos simultáneamente en distintas fases: inscripciones, ejecución académica y cierre.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PeriodosModel;
use App\Services\AuditService;

final class PeriodosController extends Controller
{
    private $model;
    private $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;

        // Verificación de seguridad: Acceso restringido a roles administrativos y académicos
        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR', 'ACADEMIC'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model  = new PeriodosModel();
        $this->userId = (int)($_SESSION['user']['id'] ?? 0);
    }

    /**
     * Muestra la grid principal con el listado de períodos institucionales.
     */
    public function index(): void
    {
        AuditService::log([
            'module'      => 'PERIODOS',
            'action'      => 'ACCESS_INDEX',
            'description' => 'El usuario ingresó al panel maestro de períodos institucionales.'
        ]);

        $search = $_GET['search'] ?? '';

        $this->view('academic/periodos/index', [
            'periodos' => $this->model->getAll($search),
            'search'   => $search
        ]);
    }

    /**
     * Registra eventos de interacción manual desde la interfaz para trazabilidad.
     */
    public function logAccess(): void
    {
        $action = $_GET['action'] ?? 'UNKNOWN';
        $id     = (int)($_GET['id'] ?? 0);
        $periodo = ($id > 0) ? $this->model->getById($id) : null;

        $identificador = $periodo
            ? "[{$periodo['periodo_code']}] {$periodo['nombre']}"
            : 'NUEVO REGISTRO';

        $desc = match($action) {
            'VIEW_DETAILS'   => "Visualizó la ficha técnica del período: $identificador",
            'CREATE_FORM'    => "Abrió el formulario para crear un nuevo período institucional.",
            'EDIT_FORM'      => "Abrió el formulario de edición para: $identificador",
            'DELETE_ATTEMPT' => "Inició proceso de inactivación para: $identificador",
            default          => "Interacción con el módulo de períodos: $action"
        };

        AuditService::log([
            'module'      => 'PERIODOS',
            'action'      => $action,
            'description' => $desc,
            'entity_id'   => $id ?: null
        ]);

        header('Content-Type: application/json');
        echo json_encode(['logged' => true]);
        exit();
    }

    /**
     * Procesa el guardado de un nuevo período institucional.
     */
    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (!$this->validateDates($_POST)) {
            header('Location: /diplomatic/public/academic/periodos?error=invalid_dates');
            exit();
        }

        $_POST['created_by'] = $this->userId;

        try {
            $id = $this->model->insert($_POST);

            AuditService::log([
                'module'      => 'PERIODOS',
                'action'      => 'CREATE_SUCCESS',
                'description' => "Creó el período institucional: [{$_POST['periodo_code']}] {$_POST['nombre']}",
                'entity_id'   => $id
            ]);

            header('Location: /diplomatic/public/academic/periodos?created=1');
            exit();
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/academic/periodos?error=db');
            exit();
        }
    }

    /**
     * Actualiza un período institucional validando su estado operativo actual.
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];

        $dataBefore = $this->model->getById($id);

        if (strtolower(trim($dataBefore['estado'] ?? '')) === 'finalizado') {
            header('Location: /diplomatic/public/academic/periodos?error=restriction_finalizado');
            exit();
        }

        if (!$this->validateDates($_POST)) {
            header('Location: /diplomatic/public/academic/periodos?error=invalid_dates');
            exit();
        }

        $_POST['updated_by'] = $this->userId;

        if ($this->model->update($id, $_POST)) {
            AuditService::log([
                'module'      => 'PERIODOS',
                'action'      => 'UPDATE_SUCCESS',
                'description' => "Actualizó datos del período institucional: {$dataBefore['periodo_code']}",
                'entity_id'   => $id
            ]);
        }

        header('Location: /diplomatic/public/academic/periodos?updated=1');
        exit();
    }

    /**
     * PROCESO DE INACTIVACIÓN CON BLINDAJE DE INTEGRIDAD REFERENCIAL.
     * Un período no puede inactivarse si tiene cohortes activas vinculadas.
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id  = (int)$_POST['id'];
        $res = $this->model->smartDelete($id, $this->userId);

        if ($res === 'referenced') {
            // BLOQUEO: No se puede inactivar si tiene cohortes vinculadas
            AuditService::log([
                'module'      => 'PERIODOS',
                'action'      => 'INACTIVATION_BLOCKED',
                'description' => "Se bloqueó la inactivación del período ID: #$id. Motivo: Registro vinculado a cohortes activas.",
                'event_type'  => 'WARNING'
            ]);
            header('Location: /diplomatic/public/academic/periodos?error=in_use');
            exit();
        }

        if ($res === 'inactivated') {
            AuditService::log([
                'module'      => 'PERIODOS',
                'action'      => 'INACTIVATE',
                'entity_id'   => $id,
                'description' => "Inactivación lógica del período ID: #$id tras validar ausencia de cohortes vinculadas.",
                'event_type'  => 'NORMAL'
            ]);
            header('Location: /diplomatic/public/academic/periodos?success=inactivated');
        } else {
            header('Location: /diplomatic/public/academic/periodos?error=db');
        }
        exit();
    }

    /**
     * Actualiza el estado del ciclo de vida (Planificado -> Activo -> Finalizado).
     */
    public function changeStatus(): void
    {
        $id     = (int)($_GET['id'] ?? 0);
        $status = $_GET['status'] ?? '';

        if ($id > 0 && !empty($status)) {
            $periodo = $this->model->getById($id);
            if ($periodo) {
                $this->model->updateStatus($id, $status, $this->userId);

                AuditService::log([
                    'module'      => 'PERIODOS',
                    'action'      => 'STATUS_CHANGE',
                    'description' => "Cambió el estado del período [{$periodo['periodo_code']}] {$periodo['nombre']} a: " . strtoupper($status),
                    'entity_id'   => $id
                ]);
            }
        }

        header('Location: /diplomatic/public/academic/periodos?updated=1');
        exit();
    }

    /**
     * Provee datos detallados para modales y visualizaciones rápidas.
     */
    public function getDetails(): void
    {
        $id      = (int)($_GET['id'] ?? 0);
        $periodo = $this->model->getDetails($id);

        if ($periodo) {
            AuditService::log([
                'module'      => 'PERIODOS',
                'action'      => 'VIEW_DETAILS',
                'description' => "Visualizó la Ficha Técnica del período: [{$periodo['periodo_code']}] {$periodo['nombre']}",
                'entity_id'   => $id
            ]);
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => ($periodo ? true : false), 'periodo' => $periodo]);
        exit();
    }

    /**
     * Validación cronológica de fechas del período e inscripciones.
     */
    private function validateDates(array $d): bool
    {
        if (strtotime($d['fecha_fin']) <= strtotime($d['fecha_inicio'])) return false;

        if (!empty($d['apertura_inscripcion']) && !empty($d['cierre_inscripcion'])) {
            if (strtotime($d['cierre_inscripcion']) <= strtotime($d['apertura_inscripcion'])) return false;
        }

        return true;
    }
}