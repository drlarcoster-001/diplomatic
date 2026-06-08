<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/controllers/CohortesConfigController.php
 * PROPÓSITO: Proporcionar control administrativo de nivel superior para la gestión técnica de las cohortes académicas. Permite realizar operaciones críticas como la restauración de registros inactivos, el forzado de estados operativos y la depuración física de la base de datos bajo estrictos protocolos de integridad.
 * ACTUALIZACIÓN: Refactorización de la lógica de seguridad en el método hardDelete(). Se ha vinculado la validación preventiva al motor de dependencias de Oferta Académica para impedir la eliminación física de registros con actividad vinculada. Se estandarizaron los parámetros de retorno (?error=has_movements) para la sincronización con las alertas del frontend y el registro de auditoría de seguridad.
 * VERSIÓN: 1.2.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CohortesConfigModel;
use App\Services\AuditService;

final class CohortesConfigController extends Controller
{
    private $model;
    private $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;
        
        // Bloqueo de seguridad: Acceso exclusivo para perfiles de alto nivel (ADMIN/OPERATOR)
        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR'])) {
            header('Location: /diplomatic/public/academic');
            exit();
        }
        
        $this->model = new CohortesConfigModel();
        $this->userId = (int)($_SESSION['user']['id'] ?? 0);
    }

    /**
     * Panel principal de configuración avanzada y mantenimiento de registros.
     */
    public function index(): void
    {
        AuditService::log([
            'module' => 'COHORTES_CONFIG', 
            'action' => 'ACCESS', 
            'description' => 'Ingreso al panel de configuración avanzada de cohortes.'
        ]);

        $search      = $_GET['search'] ?? '';
        $currentPage = max(1, (int)($_GET['page'] ?? 1));
        $perPage     = 25;
        $total       = $this->model->countAll($search);
        $totalPages  = (int)ceil($total / $perPage);

        $this->view('academic/cohortes_config/index', [
            'cohortes'    => $this->model->getAll($search, $currentPage, $perPage),
            'search'      => $search,
            'currentPage' => $currentPage,
            'totalPages'  => $totalPages,
        ]);
    }

    /**
     * Obtiene detalles técnicos por JSON y registra la visualización en auditoría.
     */
    public function getDetails(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $cohort = $this->model->getById($id);

        if ($cohort) {
            AuditService::log([
                'module' => 'COHORTES_CONFIG',
                'action' => 'VIEW_DETAILS',
                'description' => "Consultó detalles técnicos de la cohorte: [{$cohort['cohort_code']}] {$cohort['name']}",
                'entity_id' => $id
            ]);
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => ($cohort ? true : false), 'cohorte' => $cohort]);
        exit();
    }

    /**
     * Actualización forzada de estado y reactivación de registros (Papelera -> Activo).
     */
    public function updateStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = (int)$_POST['id'];
        $status = $_POST['cohort_status'] ?? '';
        $cohort = $this->model->getById($id);

        if ($cohort && !empty($status)) {
            $this->model->forceUpdateStatus($id, $status, $this->userId);
            AuditService::log([
                'module' => 'COHORTES_CONFIG', 
                'action' => 'STATUS_FORCED', 
                'description' => "Forzó el estado de la cohorte [{$cohort['cohort_code']}] a: $status (Registro restaurado a Activo).",
                'entity_id' => $id,
                'event_type' => 'WARNING'
            ]);
        }
        header('Location: /diplomatic/public/academic/cohortes-config?updated=1');
        exit();
    }

    /**
     * BORRADO FÍSICO PROTEGIDO:
     * Implementa validación cruzada con Oferta Académica antes de la eliminación irreversible.
     */
    public function hardDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $id = (int)$_POST['id'];
        $cohort = $this->model->getById($id);

        if (!$cohort) {
            header('Location: /diplomatic/public/academic/cohortes-config?error=not_found');
            exit();
        }

        // Ejecución de borrado mediante el modelo (valida dependencias internamente)
        $res = $this->model->deletePhysically($id);

        if ($res === 'referenced') {
            // BLOQUEO POR INTEGRIDAD: Existe uso en Oferta Académica
            AuditService::log([
                'module' => 'COHORTES_CONFIG', 
                'action' => 'HARD_DELETE_BLOCKED', 
                'description' => "Se bloqueó el borrado físico de [{$cohort['cohort_code']}]. El registro tiene ofertas académicas vinculadas.", 
                'entity_id' => $id,
                'event_type' => 'SECURITY'
            ]);
            header('Location: /diplomatic/public/academic/cohortes-config?error=has_movements');
            exit();
        }

        if ($res === 'deleted') {
            // ÉXITO: Eliminación definitiva
            AuditService::log([
                'module' => 'COHORTES_CONFIG', 
                'action' => 'HARD_DELETE_SUCCESS', 
                'description' => "Borrado FÍSICO definitivo de la cohorte: [{$cohort['cohort_code']}]", 
                'entity_id' => $id, 
                'event_type' => 'SECURITY'
            ]);
            header('Location: /diplomatic/public/academic/cohortes-config?deleted=1');
        } else {
            // ERROR: Fallo técnico en la transacción
            header('Location: /diplomatic/public/academic/cohortes-config?error=system_fail');
        }
        exit();
    }

    /**
 * Procesamiento masivo de reactivación o archivado de cohortes seleccionadas.
 */
public function massiveAction(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $ids    = $_POST['ids'] ?? [];
    $accion = $_POST['accion'] ?? '';

    if (empty($ids) || !in_array($accion, ['reactivar', 'archivar'])) {
        header('Location: /diplomatic/public/academic/cohortes-config?error=invalid');
        exit();
    }

    $ids = array_map('intval', $ids);
    $res = $this->model->massiveUpdateStatus($ids, $accion, $this->userId);

    if ($res) {
        $label = $accion === 'reactivar' ? 'reactivadas' : 'archivadas';
        AuditService::log([
            'module'      => 'COHORTES_CONFIG',
            'action'      => 'MASSIVE_' . strtoupper($accion),
            'description' => "Acción masiva: " . count($ids) . " cohortes $label.",
            'event_type'  => 'WARNING'
        ]);
        header('Location: /diplomatic/public/academic/cohortes-config?success=massive');
    } else {
        header('Location: /diplomatic/public/academic/cohortes-config?error=db');
    }
    exit();
}
}