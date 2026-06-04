<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS
 * ARCHIVO: app/controllers/FinancialPaymentValidationsController.php
 * VERSIÓN: 3.0.0 - Unificación de lógica Pago Móvil y Limpieza de Interceptores.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialPaymentValidationsModel;
use App\Models\FinancialPaymentValidationsPagomovilModel;
use Exception;

class FinancialPaymentValidationsController extends Controller
{
    private FinancialPaymentValidationsModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->model = new FinancialPaymentValidationsModel();
    }

    /**
     * Dashboard principal de validaciones (Inbox)
     */
    public function index(): void
    {
        if (ob_get_length()) ob_clean();
        $counts = $this->model->getPendingCounts();
        $this->view('financial/payment_validations/index', ['counts' => $counts]);
    }

    /**
     * Endpoint AJAX para contadores globales
     */
    public function getPendingCounts(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $counts = $this->model->getPendingCounts();
            echo json_encode(['ok' => true, 'data' => $counts]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // =========================================================================================
    // MÓDULO PAGO MÓVIL (UNIFICADO)
    // =========================================================================================

    public function pagomovil(): void
    {
        // INTERCEPTOR AJAX: Captura todas las acciones de la vista Pago Móvil
        $ajaxAction = $_POST['ajax_action'] ?? $_GET['ajax_action'] ?? null;
        
        if ($ajaxAction) {
            if (ob_get_level() > 0) ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            
            $pmModel = new FinancialPaymentValidationsPagomovilModel();
            $adminId = $_SESSION['user']['id'] ?? 0;

            try {
                // 1. CARGAR TABLA
                if ($ajaxAction === 'getPendingPayments') {
                    $data = $pmModel->getPendingPayments([]);
                    echo json_encode(['ok' => true, 'data' => $data]);
                    exit;
                }
                
                // 2. PROCESAR EXCEL (EL MOTOR REAL)
                if ($ajaxAction === 'uploadFile') {
                    if (!isset($_FILES['excelFile'])) throw new Exception("Archivo no recibido.");

                    $xlsxLib = dirname(__DIR__, 2) . '/app/core/libs/SimpleXLSX.php';
                    if (!file_exists($xlsxLib)) throw new Exception("Librería Excel no encontrada.");
                    require_once $xlsxLib;

                    if ($xlsx = \Shuchkin\SimpleXLSX::parse($_FILES['excelFile']['tmp_name'])) {
                        $rows = $xlsx->rows();
                        $dataToSave = [];
                        $registrosProcesados = 0;

                        foreach ($rows as $index => $row) {
                            if ($index <= 4) continue; 
                            if (strtoupper(trim((string)($row[0] ?? ''))) !== 'NC') continue; 

                            $registrosProcesados++;

                            // Formateo de fecha DD/MM/YYYY -> YYYY-MM-DD
                            $rawDate = trim((string)($row[1] ?? ''));
                            $formattedDate = null;
                            if (strpos($rawDate, '/') !== false) {
                                $p = explode('/', $rawDate);
                                if (count($p) === 3) $formattedDate = "{$p[2]}-{$p[1]}-{$p[0]}";
                            }

                            $dataToSave[] = [
                                'date_tran'    => $formattedDate,
                                'reference'    => trim((string)($row[2] ?? '')),
                                'phone_source' => trim((string)($row[3] ?? '')),
                                'bank_source'  => trim((string)($row[4] ?? '')),
                                'amount_bs'    => (float)str_replace(['.', ','], ['', '.'], (string)($row[5] ?? '0'))
                            ];
                        }

                        $inserted = $pmModel->saveStatementBatch($dataToSave, (int)$adminId);
                        echo json_encode([
                            'ok' => true, 
                            'message' => "Se han procesado {$registrosProcesados} registros y se han guardado {$inserted} nuevos en la base de datos."
                        ]);
                    } else {
                        throw new Exception("Error al leer Excel: " . \Shuchkin\SimpleXLSX::parseError());
                    }
                    exit;
                }

                // 3. APROBAR PAGO (CASCADA)
                if ($ajaxAction === 'validatePayment') {
                    $id = (int)($_POST['payment_id'] ?? 0);
                    if ($id <= 0) throw new Exception("ID de pago no válido.");

                    $res = $pmModel->approvePaymentCascade($id, (int)$adminId);
                    if (!$res) throw new Exception("Error interno al procesar la cascada de abonos.");

                    echo json_encode(['ok' => true, 'message' => '¡Pago Aprobado! Se aplicó el abono en cascada satisfactoriamente.']);
                    exit;
                }

                // 4. RECHAZAR PAGO
                if ($ajaxAction === 'rejectPayment') {
                    $id = (int)($_POST['payment_id'] ?? 0);
                    if ($id <= 0) throw new Exception("ID de pago no válido.");

                    $pmModel->rejectPayment($id, (int)$adminId);
                    echo json_encode(['ok' => true, 'message' => 'El pago ha sido rechazado correctamente.']);
                    exit;
                }

                // 5. APROBACIÓN MASIVA
                if ($ajaxAction === 'approveMassivePayments') {
                    $payments = $_POST['payments'] ?? [];
                    if (empty($payments)) throw new Exception("No hay pagos para procesar.");
                    
                    $count = 0;
                    foreach ($payments as $p) {
                        if (isset($p['id'])) {
                            $pmModel->approvePaymentCascade((int)$p['id'], (int)$adminId);
                            $count++;
                        }
                    }
                    echo json_encode(['ok' => true, 'message' => "Se aprobaron $count pagos conciliados con éxito."]);
                    exit;
                }
                
            } catch (Exception $e) {
                echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
                exit;
            }
            exit;
        }

        // RENDERIZADO NORMAL DE LA VISTA
        if (ob_get_level() > 0) ob_end_clean();
        $pmModel = new FinancialPaymentValidationsPagomovilModel();
        $this->view('financial/payment_validations/pagomovil/index', [
            'last_rate' => $pmModel->getLastGlobalRate() 
        ]);
    }

    // =========================================================================================
    // OTRAS BANDEJAS (Pendientes de implementar lógica similar)
    // =========================================================================================

    public function binance(): void
    {
        if (ob_get_length()) ob_clean();
        $this->view('financial/payment_validations/binance/index');
    }

    public function zelle(): void
    {
        if (ob_get_length()) ob_clean();
        $this->view('financial/payment_validations/zelle/index');
    }

    public function efectivo(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        
        // Aquí podrías cargar datos iniciales si la vista los necesita
        $this->view('financial/payment_validations/efectivo/index', [
            'title' => 'Validación de Efectivo'
        ]);
    }



}