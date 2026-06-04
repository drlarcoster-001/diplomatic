<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / RECHAZOS DE PAGO
 * ARCHIVO: app/Controllers/FinancialPaymentRejectionController.php
 * PROPÓSITO: Controlador maestro para la reactivación y eliminación física de pagos rechazados.
 * VERSIÓN: 1.0.0 - Blindaje de búfer implementado (ob_end_clean) para evitar el error "Unexpected token <" en respuestas AJAX.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialPaymentRejectionModel;
use Exception;

final class FinancialPaymentRejectionController extends Controller
{
    private FinancialPaymentRejectionModel $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $user = $_SESSION['user'] ?? null;
        $allowedRoles = ['ADMIN', 'FINANCIAL'];
        
        if (!$user || $user['user_type'] !== 'INTERNAL' || !in_array(strtoupper($user['role'] ?? ''), $allowedRoles, true)) {
            if ($this->isAjax()) {
                $this->executeJsonSafely(fn() => throw new Exception("Acceso denegado."));
            }
            $this->redirect('/dashboard');
            exit;
        }
        $this->model = new FinancialPaymentRejectionModel();
    }

    public function index(): void {
        $this->view('financial/payment_rejection/index', ['title' => 'Gestión de Rechazos de Pago']);
    }

    /* --- INSCRIPCIONES --- */
    public function search_inscripciones(): void {
        $this->executeJsonSafely(fn() => $this->model->searchInscripciones(trim($_POST['search'] ?? '')));
    }

    public function incorporar_inscripcion(): void {
        $this->executeJsonSafely(function() {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
            if ($paymentId <= 0 || $enrollmentId <= 0) throw new Exception("IDs inválidos.");
            if (!$this->model->incorporarInscripcion($paymentId, $enrollmentId)) throw new Exception("No se pudo incorporar la inscripción.");
            return null;
        }, "Inscripción y pago reactivados correctamente para revisión.");
    }

    public function eliminar_inscripcion(): void {
        $this->executeJsonSafely(function() {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
            if ($paymentId <= 0 || $enrollmentId <= 0) throw new Exception("IDs inválidos.");
            if (!$this->model->eliminarInscripcion($paymentId, $enrollmentId)) throw new Exception("Error al eliminar los registros de inscripción.");
            return null;
        }, "Registros eliminados y cupo devuelto a la oferta académica.");
    }

    /* --- REGULARES --- */
    public function search_regulares(): void {
        $this->executeJsonSafely(fn() => $this->model->searchRegulares(trim($_POST['search'] ?? '')));
    }

    public function incorporar_regular(): void {
        $this->executeJsonSafely(function() {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            if ($paymentId <= 0) throw new Exception("ID de pago inválido.");
            if (!$this->model->incorporarRegular($paymentId)) throw new Exception("No se pudo reactivar el pago.");
            return null;
        }, "El pago ha sido devuelto a PENDIENTE para su validación.");
    }

    public function eliminar_regular(): void {
        $this->executeJsonSafely(function() {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            if ($paymentId <= 0) throw new Exception("ID de pago inválido.");
            if (!$this->model->eliminarRegular($paymentId)) throw new Exception("No se pudo eliminar el pago.");
            return null;
        }, "El registro de pago regular ha sido eliminado físicamente.");
    }

    private function isAjax(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    private function executeJsonSafely(callable $operation, string $successMsg = ''): void {
        while (ob_get_level() > 0) ob_end_clean();
        ob_start();
        try {
            $data = $operation();
            ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'data' => $data, 'message' => $successMsg], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            while (ob_get_level() > 0) ob_end_clean();
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}