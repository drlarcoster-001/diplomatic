<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS (BINANCE)
 * ARCHIVO: app/Controllers/FinancialPaymentValidationsBinanceController.php
 * PROPÓSITO: Controlador operativo para la validación de cuotas vía Binance Pay (USDT).
 * VERSIÓN: 1.0.3 - Fix: Error de constante indefinida y limpieza de caracteres.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialPaymentValidationsBinanceModel;
use App\Services\AuditService;
use Exception;

class FinancialPaymentValidationsBinanceController extends Controller
{
    private FinancialPaymentValidationsBinanceModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Validación de Seguridad Estricta
        $user = $_SESSION['user'] ?? null;
        $authorizedRoles = ['ADMIN', 'FINANZAS']; 
        
        $accessGranted = (
            $user && 
            $user['user_type'] === 'INTERNAL' && 
            isset($user['role']) && 
            in_array(strtoupper($user['role']), $authorizedRoles)
        );

        if (!$accessGranted) {
            // Corregido: Redirección limpia al dashboard
            header('Location: /dashboard');
            exit;
        }

        $this->model = new FinancialPaymentValidationsBinanceModel();
    }

    /**
     * Vista principal
     */
    public function index(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        $this->view('financial/payment_validations/binance/index', [
            'title' => 'Validación de Cuotas: Binance Pay (USDT)'
        ]);
    }

    /**
     * API: Listado de pagos pendientes
     */
    public function getPendingPayments(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $filters = [
                'text'      => $_GET['text'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to'   => $_GET['date_to'] ?? ''
            ];
            
            $data = $this->model->getPendingBinancePayments($filters);
            echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Proceso de Aprobación y Cascada
     */
    public function validatePayment(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $adminId   = (int)$_SESSION['user']['id'];

            if ($paymentId <= 0) throw new Exception("ID de pago no válido.");

            // Ejecuta la aprobación y la cascada en el Ledger
            if ($this->model->approveBinancePayment($paymentId, $adminId)) {
                
                // Registro en Auditoría
                AuditService::log([
                    'module'      => 'FINANCIAL_VALIDATIONS_BINANCE',
                    'action'      => 'APPROVE_PAYMENT',
                    'description' => "Pago Binance aprobado y aplicado a Ledger. ID Pago: $paymentId",
                    'event_type'  => 'SUCCESS'
                ]);

                echo json_encode([
                    'ok' => true, 
                    'message' => "Validación exitosa. Se ha actualizado el saldo del estudiante."
                ]);
            } else {
                throw new Exception("No se pudo procesar la aprobación.");
            }
            
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Proceso de Rechazo
     */
    public function rejectPayment(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0);

            if ($paymentId <= 0) throw new Exception("ID de pago no válido para rechazo.");

            if ($this->model->rejectBinancePayment($paymentId)) {
                AuditService::log([
                    'module'      => 'FINANCIAL_VALIDATIONS_BINANCE',
                    'action'      => 'REJECT_PAYMENT',
                    'description' => "Pago Binance rechazado. ID Pago: $paymentId",
                    'event_type'  => 'WARNING'
                ]);
                echo json_encode(['ok' => true, 'message' => "La transacción ha sido rechazada."]);
            } else {
                throw new Exception("No se pudo completar la acción de rechazo.");
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}