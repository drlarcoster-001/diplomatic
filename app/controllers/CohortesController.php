<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/controllers/CohortesController.php
 * PROPÓSITO: Orquestar las operaciones administrativas de las cohortes académicas. Actúa como mediador entre la interfaz de usuario y el modelo de datos, gestionando validaciones de entrada, flujos de redirección y el registro de auditoría narrativa de eventos.
 * ACTUALIZACIÓN: Refactorización integral del método delete() para implementar la política de inactivación lógica protegida. Se ha sincronizado el flujo con el modelo v2.19.0 para capturar el estado 'referenced', bloqueando la desaparición del registro si posee ofertas académicas activas. En caso de éxito, se procesa como 'inactivated', manteniendo la integridad física de la base de datos y notificando al usuario mediante el parámetro 'success=inactivated'.
 * VERSIÓN: 2.15.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CohortesModel;
use App\Services\AuditService;

final class CohortesController extends Controller
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

        $this->model = new CohortesModel();
        $this->userId = (int)($_SESSION['user']['id'] ?? 0);
    }

    /**
     * Muestra la grid principal con el listado de cohortes.
     */
    public function index(): void
    {
        AuditService::log([
            'module' => 'COHORTES', 
            'action' => 'ACCESS_INDEX', 
            'description' => 'El usuario ingresó al panel maestro de cohortes.'
        ]);

        $search = $_GET['search'] ?? '';
        
        $this->view('academic/cohortes/index', [
            'cohortes' => $this->model->getAll($search),
            'campuses' => $this->model->getActiveCampuses(),
            'search'   => $search
        ]);
    }

    /**
     * Registra eventos de interacción manual desde la interfaz para trazabilidad.
     */
    public function logAccess(): void
    {
        $action = $_GET['action'] ?? 'UNKNOWN';
        $id = (int)($_GET['id'] ?? 0);
        $cohort = ($id > 0) ? $this->model->getById($id) : null;
        
        $identificador = $cohort ? "[{$cohort['cohort_code']}] {$cohort['name']}" : "NUEVO REGISTRO";
        
        $desc = match($action) {
            'VIEW_DETAILS'   => "Visualizó la ficha técnica de la cohorte: $identificador",
            'CREATE_FORM'    => "Abrió el formulario para crear una nueva cohorte.",
            'EDIT_FORM'      => "Abrió el formulario de edición para: $identificador",
            'DELETE_ATTEMPT' => "Inició proceso de inactivación para: $identificador",
            default          => "Interacción con el módulo de cohortes: $action"
        };

        AuditService::log([
            'module' => 'COHORTES', 
            'action' => $action, 
            'description' => $desc,
            'entity_id' => $id ?: null
        ]);
        
        header('Content-Type: application/json');
        echo json_encode(['logged' => true]);
        exit();
    }

    /**
     * Procesa el guardado de una nueva cohorte y sincroniza sus sedes.
     */
    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (!$this->validateDates($_POST)) {
            header('Location: /diplomatic/public/academic/cohortes?error=invalid_dates');
            exit();
        }

        $_POST['created_by'] = $this->userId;
        try {
            $id = $this->model->insert($_POST);
            
            if ($id > 0 && !empty($_POST['campus_ids'])) {
                $this->model->syncCampuses($id, $_POST['campus_ids']);
            }

            AuditService::log([
                'module' => 'COHORTES', 
                'action' => 'CREATE_SUCCESS', 
                'description' => "Creó la cohorte: [{$_POST['cohort_code']}] {$_POST['name']}", 
                'entity_id' => $id
            ]);

            header('Location: /diplomatic/public/academic/cohortes?created=1');
            exit();
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/academic/cohortes?error=db');
            exit();
        }
    }

    /**
     * Actualiza una cohorte validando el estado operativo actual.
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];

        $dataBefore = $this->model->getById($id);
        
        if (strtolower(trim($dataBefore['cohort_status'] ?? '')) !== 'planificada') {
            header('Location: /diplomatic/public/academic/cohortes?error=restriction_active');
            exit();
        }

        if (!$this->validateDates($_POST)) {
            header('Location: /diplomatic/public/academic/cohortes?error=invalid_dates');
            exit();
        }

        $_POST['updated_by'] = $this->userId;
        if ($this->model->update($id, $_POST)) {
            $this->model->syncCampuses($id, $_POST['campus_ids'] ?? []);

            AuditService::log([
                'module' => 'COHORTES', 
                'action' => 'UPDATE_SUCCESS', 
                'description' => "Actualizó datos de la cohorte: {$dataBefore['cohort_code']}", 
                'entity_id' => $id
            ]);
        }
        header('Location: /diplomatic/public/academic/cohortes?updated=1');
        exit();
    }

    /**
     * PROCESO DE INACTIVACIÓN CON BLINDAJE DE INTEGRIDAD REFERENCIAL.
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = (int)$_POST['id'];
        
        // Ejecuta la inactivación lógica validada en el modelo
        $res = $this->model->smartDelete($id, $this->userId);

        if ($res === 'referenced') {
            // BLOQUEO: No se puede inactivar si tiene ofertas académicas
            AuditService::log([
                'module' => 'COHORTES',
                'action' => 'INACTIVATION_BLOCKED',
                'description' => "Se bloqueó la inactivación de la cohorte ID: #$id. Motivo: Registro amarrado a Oferta Académica activa.",
                'event_type' => 'WARNING'
            ]);
            header('Location: /diplomatic/public/academic/cohortes?error=in_use');
            exit();
        }

        if ($res === 'inactivated') {
            // ÉXITO: El registro fue ocultado (is_active = 0)
            AuditService::log([
                'module' => 'COHORTES',
                'action' => 'INACTIVATE',
                'entity_id' => $id,
                'description' => "Inactivación lógica de la cohorte ID: #$id tras validar ausencia de dependencias.",
                'event_type' => 'NORMAL'
            ]);
            header('Location: /diplomatic/public/academic/cohortes?success=inactivated');
        } else {
            header('Location: /diplomatic/public/academic/cohortes?error=db');
        }
        exit();
    }

    /**
     * Actualiza el estado del ciclo de vida (Planificada -> En curso -> Finalizada).
     */
    public function changeStatus(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $status = $_GET['status'] ?? '';
        
        if ($id > 0 && !empty($status)) {
            $cohort = $this->model->getById($id);
            if ($cohort) {
                $this->model->updateStatus($id, $status, $this->userId);

                AuditService::log([
                    'module' => 'COHORTES',
                    'action' => 'STATUS_CHANGE',
                    'description' => "Cambió el estado de la cohorte [{$cohort['cohort_code']}] {$cohort['name']} a: " . strtoupper($status),
                    'entity_id' => $id
                ]);
            }
        }
        header('Location: /diplomatic/public/academic/cohortes?updated=1');
        exit();
    }

    /**
     * Provee datos detallados para modales y visualizaciones rápidas.
     */
    public function getDetails(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $cohort = $this->model->getDetails($id);
        
        if ($cohort) {
            AuditService::log([
                'module' => 'COHORTES',
                'action' => 'VIEW_DETAILS',
                'description' => "Visualizó la Ficha Técnica de la cohorte: [{$cohort['cohort_code']}] {$cohort['name']}",
                'entity_id' => $id
            ]);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['ok' => ($cohort ? true : false), 'cohorte' => $cohort]);
        exit();
    }

    /**
     * Validación cronológica de fechas académicas y administrativas.
     */
    private function validateDates(array $d): bool
    {
        if (strtotime($d['end_date']) <= strtotime($d['start_date'])) return false;

        if (!empty($d['enrollment_start']) && !empty($d['enrollment_end'])) {
            if (strtotime($d['enrollment_end']) <= strtotime($d['enrollment_start'])) return false;
        }
        return true;
    }
}