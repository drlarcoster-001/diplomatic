<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/controllers/AcademicPeriodosController.php
 * PROPÓSITO: Orquestar las operaciones administrativas de los períodos institucionales.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AcademicPeriodosModel;
use App\Services\AuditService;

final class AcademicPeriodosController extends Controller
{
    private AcademicPeriodosModel $model;
    private int $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;

        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR', 'ACADEMIC'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model  = new AcademicPeriodosModel();
        $this->userId = (int)($_SESSION['user']['id'] ?? 0);
    }

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

    public function logAccess(): void
    {
        $action  = $_GET['action'] ?? 'UNKNOWN';
        $id      = (int)($_GET['id'] ?? 0);
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

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id  = (int)$_POST['id'];
        $res = $this->model->smartDelete($id, $this->userId);

        if ($res === 'referenced') {
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
                'description' => "Inactivación lógica del período ID: #$id.",
                'event_type'  => 'NORMAL'
            ]);
            header('Location: /diplomatic/public/academic/periodos?success=inactivated');
        } else {
            header('Location: /diplomatic/public/academic/periodos?error=db');
        }
        exit();
    }

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
                    'description' => "Cambió el estado del período [{$periodo['periodo_code']}] a: " . strtoupper($status),
                    'entity_id'   => $id
                ]);
            }
        }

        header('Location: /diplomatic/public/academic/periodos?updated=1');
        exit();
    }

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

    private function validateDates(array $d): bool
    {
        if (strtotime($d['fecha_fin']) <= strtotime($d['fecha_inicio'])) return false;

        if (!empty($d['apertura_inscripcion']) && !empty($d['cierre_inscripcion'])) {
            if (strtotime($d['cierre_inscripcion']) <= strtotime($d['apertura_inscripcion'])) return false;
        }

        return true;
    }
}