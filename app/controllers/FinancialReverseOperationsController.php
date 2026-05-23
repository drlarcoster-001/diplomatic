<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / REVERSO DE OPERACIONES
 * ARCHIVO: app/Controllers/FinancialReverseOperationsController.php
 * PROPÓSITO: Orquestador maestro para anulación y reverso de transacciones contables.
 * VERSIÓN: 5.0.0 - FIX: Sincronización con Reverso Quirúrgico y eliminación de la cascada.
 * REGLA DE EQUIPO: Este controlador centraliza la auditoría y respeta los bloqueos del Modelo.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuditService;
use App\Models\FinancialReverseOperationsModel;
use Exception;

final class FinancialReverseOperationsController extends Controller
{
    private FinancialReverseOperationsModel $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $user = $_SESSION['user'] ?? null;
        $allowedRoles = ['ADMIN', 'FINANCIAL'];
        $role = strtoupper($user['role'] ?? '');

        // --- SEGURIDAD: Solo personal interno con roles autorizados ---
        if (!$user || $user['user_type'] !== 'INTERNAL' || !in_array($role, $allowedRoles, true)) {
            
            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false, 
                    'message' => 'Acceso denegado: Se requieren permisos administrativos de finanzas.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $this->redirect('/dashboard');
            exit;
        }

        $this->model = new FinancialReverseOperationsModel();
    }

    /**
     * Carga la bandeja de entrada del módulo de reversos.
     */
    public function index(): void {
        $this->view('financial/reverse_operations/index', [
            'title' => 'Reverso de Operaciones'
        ]);
    }

    /**
     * AJAX: Localiza inscripciones que pueden ser reiniciadas.
     */
    public function search_inscripciones(): void {
        $this->executeJsonSafely(function() {
            $search = trim($_POST['search'] ?? '');
            return $this->model->searchInscripciones($search);
        });
    }

    /**
     * AJAX: Localiza pagos de cuotas que pueden ser reversados.
     */
    public function search_cuotas(): void {
        $this->executeJsonSafely(function() {
            $search = trim($_POST['search'] ?? '');
            return $this->model->searchCuotas($search); 
        });
    }

    /**
     * ACCIÓN CRÍTICA: Reverso de Inscripción (RESET).
     * Dispara la eliminación del Ledger (SOLO PARA INSCRIPCIONES).
     */
    public function reverse_inscripcion(): void {
        $this->executeJsonSafely(function() {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);

            if ($paymentId <= 0 || $enrollmentId <= 0) {
                throw new Exception("Identificadores de operación no válidos.");
            }

            // Ejecutamos el modelo. Si hay bloqueo (ej. estudiante activo), el modelo lanzará Exception.
            $this->model->executeReverseInscripcion($paymentId, $enrollmentId);

            // Auditoría de alto nivel (DANGER) por borrado de datos iniciales en Ledger
            AuditService::log([
                'module' => 'FINANCIAL_REVERSE',
                'action' => 'REVERSE_RESET_INSCRIPTION',
                'description' => "REINICIO TOTAL: El usuario {$_SESSION['user']['username']} reversó el pago inicial #{$paymentId} y ELIMINÓ el Ledger de la Inscripción #{$enrollmentId}.",
                'event_type' => 'DANGER'
            ]);

            return null;
        }, 'Inscripción reiniciada correctamente. El historial base ha sido limpiado.');
    }

    /**
     * ACCIÓN QUIRÚRGICA: Reverso de cuota regular (Pestaña 2).
     * Dispara el reset de cuotas específicas SIN BORRAR REGISTROS.
     */
    public function reverse_cuota(): void {
        $this->executeJsonSafely(function() {
            $paymentId = (int)($_POST['payment_id'] ?? 0);

            if ($paymentId <= 0) {
                throw new Exception("ID de pago no válido para esta operación.");
            }

            // Ejecutamos el modelo. La lógica quirúrgica ya no usa cascada.
            $this->model->executeReverseCuota($paymentId);

            // Auditoría de nivel medio (WARNING) ajustada al nuevo modelo
            AuditService::log([
                'module' => 'FINANCIAL_REVERSE',
                'action' => 'REVERSE_CUOTA_QUIRURGICO',
                'description' => "REVERSO QUIRÚRGICO: Pago de cuota #{$paymentId} anulado. Las cuotas afectadas volvieron a estado Pendiente.",
                'event_type' => 'WARNING'
            ]);

            return null;
        }, 'Pago de cuota revertido exitosamente. La deuda vuelve a estar pendiente en el estado de cuenta.');
    }

    /**
     * HELPER: Detectar peticiones AJAX mediante cabeceras HTTP.
     */
    private function isAjax(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    /**
     * MOTOR DE RESPUESTA UNIFICADA:
     * Gestiona el búfer de salida, cabeceras JSON y captura de excepciones.
     */
    private function executeJsonSafely(callable $operation, string $successMessage = ''): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        ob_start();

        try {
            $data = $operation();
            ob_end_clean();

            header('Content-Type: application/json; charset=utf-8');
            $response = ['ok' => true];
            
            if ($data !== null) $response['data'] = $data;
            if ($successMessage !== '') $response['message'] = $successMessage;

            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        } catch (Exception $e) {
            while (ob_get_level() > 0) ob_end_clean();
            
            http_response_code(400); 
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false, 
                'message' => $e->getMessage() // Aquí llega el mensaje del Modelo a la pantalla
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}