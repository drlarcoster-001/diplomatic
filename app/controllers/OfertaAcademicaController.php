<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/Controllers/OfertaAcademicaController.php
 * PROPÓSITO: Controlador maestro encargado de orquestar el ciclo de vida completo de las convocatorias (Oferta Académica).
 * VERSIÓN: 3.44.0 - Integración de captura de Fecha de Vencimiento para el Esquema de Pagos.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OfertaAcademicaModel;
use App\Models\UserModel;
use App\Services\AuditService;

final class OfertaAcademicaController extends Controller
{
    private OfertaAcademicaModel $model;
    private int $userId;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Blindaje contra "Unexpected token <": Si no hay sesión y es AJAX, devolver JSON, no redirigir a HTML.
        if (empty($_SESSION['user']['id'])) {
            $isFetch = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
            if ($isFetch) {
                if (ob_get_level()) ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'msg' => 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.']);
                exit;
            }
            $this->redirect('/login');
        }
        
        $this->model = new OfertaAcademicaModel(); 
        $this->userId = (int)$_SESSION['user']['id'];
    }

    public function index(): void {
        AuditService::log([
            'module'      => 'ACADEMIC_OFFERING',
            'action'      => 'VIEW_INDEX',
            'description' => "El usuario " . $_SESSION['user']['name'] . " accedió al panel de Oferta Académica.",
            'event_type'  => 'NORMAL'
        ]);

        $filters = [
            'diploma_id' => $_GET['diploma_id'] ?? null, 
            'cohort_id'  => $_GET['cohort_id'] ?? null, 
            'status'     => $_GET['status'] ?? null
        ];

        $this->view('academic/oferta/index', [
            'ofertas'    => $this->model->getAll($filters), 
            'diplomados' => $this->model->getActiveDiplomas(), 
            'cohortes'   => $this->model->getOperableCohorts(), 
            'filters'    => $filters
        ]);
    }

    public function create(): void {
        $this->view('academic/oferta/create', [
            'diplomados' => $this->model->getActiveDiplomas(), 
            'cohortes'   => $this->model->getOperableCohorts(), 
            'grupos'     => $this->model->getActiveGroups(), 
            'professors' => $this->model->getActiveProfessors(),
            'oferta'     => ['description' => '']
        ]);
    }

    /**
     * Procesa la creación de una nueva oferta y retorna JSON blindado con Try-Catch.
     */
    public function save(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido.');
            }
            
            if (empty($_POST['campuses']) || empty($_POST['groups_check']) || empty($_POST['payment_concept'])) {
                throw new \Exception('Faltan campos obligatorios de la oferta (Sedes, Grupos o Pagos).');
            }

            $data = $this->prepareData($_POST);
            $newId = $this->model->insert($data, $this->userId);
            
            if ($newId) {
                AuditService::log([
                    'module'      => 'ACADEMIC_OFFERING', 
                    'action'      => 'CREATE',
                    'description' => "Nueva oferta creada #$newId en estado BORRADOR.",
                    'event_type'  => 'SUCCESS'
                ]);
                echo json_encode(['ok' => true, 'msg' => 'La oferta ha sido creada exitosamente como Borrador.']);
            } else {
                throw new \Exception('Error crítico al registrar la oferta en la base de datos.');
            }
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        $oferta = $this->model->getById($id);
        
        if (!$oferta) $this->redirect('/academic/oferta?error=not_found');

        $oferta['description'] = $oferta['description'] ?? '';
        
        $this->view('academic/oferta/edit', [
            'oferta'     => $oferta, 
            'diplomados' => $this->model->getActiveDiplomas(), 
            'cohortes'   => $this->model->getOperableCohorts(), 
            'grupos'     => $this->model->getActiveGroups(), 
            'professors' => $this->model->getActiveProfessors(), 
            'campuses'   => $this->model->getCampusesByCohortId((int)$oferta['cohort_id'])
        ]);
    }

    /**
     * Procesa la actualización de la oferta y retorna JSON blindado con Try-Catch.
     */
    public function update(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido.');
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new \Exception('ID de oferta inválido.');

            $data = $this->prepareData($_POST);

            if ($this->model->update($id, $data, $this->userId)) {
                AuditService::log([
                    'module'      => 'ACADEMIC_OFFERING', 
                    'action'      => 'UPDATE',
                    'description' => "Se actualizó la oferta #$id (Ciclo de Venta).", 
                    'event_type'  => 'SUCCESS'
                ]);
                echo json_encode(['ok' => true, 'msg' => 'La oferta ha sido actualizada exitosamente.']);
            } else {
                throw new \Exception('Error al procesar la actualización en la base de datos.');
            }
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    public function executeOpen(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        
        $id = (int)($_POST['id'] ?? 0);
        
        if ($this->model->updateStatus($id, 'ABIERTA', $this->userId)) {
            echo json_encode(['ok' => true]);
        } else { 
            echo json_encode(['ok' => false]); 
        }
        exit;
    }

    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        
        if ($this->model->delete($id, $this->userId)) {
            $this->redirect('/academic/oferta?success=deleted');
        } else { 
            $this->redirect('/academic/oferta?error=delete_failed'); 
        }
    }

    public function getCohortCampuses(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($this->model->getCampusesByCohortId((int)($_GET['cohort_id'] ?? 0)));
        exit;
    }

    public function logSummaryPopup(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        AuditService::log([
            'module' => 'ACADEMIC_OFFERING', 'action' => 'VIEW_SUMMARY_POPUP', 
            'description' => "Visualización de resumen para oferta #$id.", 'event_type' => 'NORMAL'
        ]);
        echo json_encode(['ok' => true]);
        exit;
    }

    public function logLockPopup(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        AuditService::log([
            'module' => 'ACADEMIC_OFFERING', 'action' => 'VIEW_UNLOCK_POPUP', 
            'description' => "Intento de desbloqueo administrativo para oferta #$id.", 'event_type' => 'NORMAL'
        ]);
        echo json_encode(['ok' => true]);
        exit;
    }

    public function verifyAdmin(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $userModel = new UserModel();
        $result = $userModel->verifyLogin($email, $password);

        if ($result['ok'] && in_array(strtoupper($result['user']['role']), ['ADMIN', 'SUPERADMIN', 'ADMINISTRADOR'])) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Credenciales insuficientes o incorrectas.']);
        }
        exit;
    }

    public function changeStatusAdmin(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        
        if ($this->model->updateStatus($id, $newStatus, $this->userId)) {
            AuditService::log([
                'module' => 'ACADEMIC_OFFERING', 'action' => 'STATUS_CHANGE_ADMIN', 
                'description' => "Cambio de estatus forzado por Admin a: $newStatus en oferta #$id.", 'event_type' => 'WARNING'
            ]);
            echo json_encode(['ok' => true]);
        } else { 
            echo json_encode(['ok' => false]); 
        }
        exit;
    }

    /**
     * --- MAPEO DE DATOS ACTUALIZADO ---
     */
    private function prepareData(array $p): array {
        $cap = (int)($p['total_capacity'] ?? 0);
        if ($cap > 399) $cap = 399; 
        
        return [
            'diploma_id'          => (int)$p['diploma_id'], 
            'cohort_id'           => (int)$p['cohort_id'], 
            'total_capacity'      => $cap,
            'registration_start'  => $p['registration_start'], 
            'registration_end'    => $p['registration_end'],
            'class_start'         => $p['class_start'], 
            'class_end'           => $p['class_end'],
            'general_modality'    => $p['general_modality'], 
            'total_cost'          => (float)$p['total_cost'],
            'description'         => trim($p['description'] ?? ''), 
            'campuses'            => $p['campuses'] ?? [],
            'groups'              => $p['groups_check'] ?? [], 
            'professor_id'        => $p['professor_id'] ?? [],
            'professor_role'      => $p['professor_role'] ?? [], 
            
            // Bloque de pagos sincronizado con la nueva Grid del JS
            'payment_concept'     => $p['payment_concept'] ?? [],
            'payment_amount'      => $p['payment_amount'] ?? [], 
            'payment_due_date'    => $p['payment_due_date'] ?? [], // <--- NUEVO CAMPO CAPTURADO
            'payment_description' => $p['payment_description'] ?? []
        ];
    }
}