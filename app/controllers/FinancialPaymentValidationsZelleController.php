<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS (ZELLE)
 * ARCHIVO: app/controllers/FinancialPaymentValidationsZelleController.php
 * PROPÓSITO: Controlador operativo para la validación de cuotas reportadas vía Zelle (USD).
 * VERSIÓN: 1.0.5 - Código limpio, seguro y con función de rechazo.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialPaymentValidationsZelleModel;
use Exception;

class FinancialPaymentValidationsZelleController extends Controller
{
    private FinancialPaymentValidationsZelleModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Seguridad básica: Si no hay usuario en sesión, no puede usar este controlador
        if (empty($_SESSION['user']['id'])) {
            header('Location: /');
            exit;
        }

        $this->model = new FinancialPaymentValidationsZelleModel();
    }

    public function index(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        $this->view('financial/payment_validations/zelle/index', [
            'title' => 'Validación Zelle | Finanzas'
        ]);
    }

    public function getPendingPayments(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = $this->model->getPendingZellePayments(['text' => $_GET['text'] ?? '']);
            echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function validatePayment(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pid = (int)($_POST['payment_id'] ?? 0);
            $adminId = (int)$_SESSION['user']['id'];
            
            if ($pid <= 0) {
                throw new Exception("ID de pago inválido.");
            }

            // Llamamos a la cascada financiera del modelo
            if ($this->model->approveZellePayment($pid, $adminId)) {
                echo json_encode([
                    'ok' => true, 
                    'message' => "Pago aprobado exitosamente. El saldo del estudiante ha sido actualizado."
                ]);
            } else {
                throw new Exception("Ocurrió un error al intentar aprobar el pago.");
            }
        } catch (Exception $e) { 
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]); 
        }
        exit;
    }

    public function rejectPayment(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pid = (int)($_POST['payment_id'] ?? 0);
            
            if ($pid <= 0) {
                throw new Exception("ID de pago inválido.");
            }

            if ($this->model->rejectZellePayment($pid)) {
                echo json_encode([
                    'ok' => true, 
                    'message' => "El pago ha sido rechazado y devuelto."
                ]);
            } else {
                throw new Exception("No se pudo rechazar el pago.");
            }
        } catch (Exception $e) { 
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]); 
        }
        exit;
    }
}