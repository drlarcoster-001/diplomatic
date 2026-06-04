<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / ESTADOS DE CUENTA BANCARIOS
 * ARCHIVO: app/controllers/FinancialBankStatementsController.php
 * PROPÓSITO: Controlador para la gestión y carga de estados de cuenta bancarios.
 *            Maneja dos tipos de archivo: T-Pago (tbl_financial_bank_transactions_mobile)
 *            y Movimientos Mercantil (tbl_financial_bank_transactions_account).
 * VERSIÓN: 1.0.0 - Creación inicial del módulo con soporte dual de archivos bancarios.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialBankStatementsModel;
use App\Services\AuditService;
use Shuchkin\SimpleXLSX;
use Exception;

/**
 * CARGA MANUAL DE SimpleXLSX
 */
$xlsxLib = dirname(__DIR__, 2) . '/app/core/libs/SimpleXLSX.php';
if (file_exists($xlsxLib)) {
    require_once $xlsxLib;
}

class FinancialBankStatementsController extends Controller
{
    private FinancialBankStatementsModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;
        $allowedRoles = ['ADMIN', 'FINANZAS', 'SUPERADMIN'];

        $accessGranted = (
            $user &&
            $user['user_type'] === 'INTERNAL' &&
            isset($user['role']) &&
            in_array(strtoupper($user['role']), $allowedRoles)
        );

        if (!$accessGranted) {
            $this->redirect('/dashboard');
            exit;
        }

        $this->model = new FinancialBankStatementsModel();
    }

    private function clearOutputBuffer(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
    }

    private function setJsonHeaders(): void
    {
        $this->clearOutputBuffer();
        header('Content-Type: application/json; charset=utf-8');
    }

    // =========================================================
    // VISTAS
    // =========================================================

    /**
     * Vista principal: índice con las dos tarjetas
     */
    public function index(): void
    {
        $this->clearOutputBuffer();
        $this->view('financial/bank_statements/index', []);
    }

    /**
     * Vista T-Pago: grid de tbl_financial_bank_transactions_mobile
     */
    public function tpago(): void
    {
        $this->clearOutputBuffer();
        $this->view('financial/bank_statements/tpago/index', []);
    }

    /**
     * Vista Movimientos: grid de tbl_financial_bank_transactions_account
     */
    public function movimientos(): void
    {
        $this->clearOutputBuffer();
        $this->view('financial/bank_statements/movimientos/index', []);
    }

    // =========================================================
    // API: T-PAGO (tbl_financial_bank_transactions_mobile)
    // =========================================================

    /**
     * API: Obtener transacciones T-Pago paginadas y filtradas
     */
    public function getTpagoTransactions(): void
    {
        $this->setJsonHeaders();

        try {
            $filters = [
                'date'      => trim($_GET['date']      ?? ''),
                'reference' => trim($_GET['reference'] ?? ''),
                'amount'    => trim($_GET['amount']    ?? ''),
                'phone'     => trim($_GET['phone']     ?? ''),
                'text'      => trim($_GET['text']      ?? ''),
            ];

            $limit  = 25;
            $page   = max(1, (int)($_GET['page'] ?? 1));
            $offset = ($page - 1) * $limit;

            $total = $this->model->getTotalTpagoTransactions($filters);
            $data  = $this->model->getTpagoTransactions($filters, $limit, $offset);

            echo json_encode([
                'ok'   => true,
                'data' => $data,
                'pagination' => [
                    'current_page'  => $page,
                    'total_pages'   => (int)ceil($total / $limit),
                    'total_records' => $total,
                    'limit'         => $limit
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Cargar archivo Excel T-Pago
     * Estructura: encabezado fila 5, datos desde fila 6
     * Columnas: Tipo | Fecha | Referencia | Teléfono | Banco | Monto
     */
    public function uploadTpagoFile(): void
    {
        $this->setJsonHeaders();

        try {
            if (!isset($_FILES['excelFile'])) {
                throw new Exception("No se recibió ningún archivo.");
            }

            if ($xlsx = \Shuchkin\SimpleXLSX::parse($_FILES['excelFile']['tmp_name'])) {
                $rows = $xlsx->rows();

                if (count($rows) <= 5) {
                    throw new Exception("El archivo no contiene suficientes datos.");
                }

                $dataToSave         = [];
                $registrosProcesados = 0;

                foreach ($rows as $index => $row) {
                    if ($index <= 4) continue; // Saltar encabezados (filas 1-5)

                    $tipo = strtoupper(trim((string)($row[0] ?? '')));
                    if ($tipo !== 'NC') continue;

                    if (empty(trim((string)($row[2] ?? '')))) continue;

                    $registrosProcesados++;

                    // Monto
                    $val        = $row[5] ?? 0;
                    $montoFinal = 0.0;
                    if (is_numeric($val)) {
                        $montoFinal = (float)$val;
                    } else {
                        $montoRaw   = trim((string)$val);
                        $temp       = str_replace('.', '', $montoRaw);
                        $temp       = str_replace(',', '.', $temp);
                        $montoFinal = (float)$temp;
                    }

                    // Referencia
                    $refRaw = $row[2] ?? '';
                    if (is_float($refRaw) || is_int($refRaw)) {
                        $referenciaLimpia = number_format((float)$refRaw, 0, '', '');
                    } else {
                        $referenciaLimpia = ltrim(trim((string)$refRaw), '0');
                    }

                    // Teléfono
                    $phoneRaw         = trim((string)($row[3] ?? ''));
                    $phoneOnlyNumbers = preg_replace('/\D/', '', $phoneRaw);
                    $phoneFinal       = substr($phoneOnlyNumbers, -10);

                    // Fecha
                    $fechaRaw = $row[1] ?? '';
                    if ($fechaRaw instanceof \DateTime) {
                        $fechaRaw = $fechaRaw->format('d/m/Y');
                    } else {
                        $fechaRaw = trim((string)$fechaRaw);
                    }

                    $dataToSave[] = [
                        'op_type'      => $tipo,
                        'date_tran'    => $this->formatExcelDate($fechaRaw),
                        'reference'    => $referenciaLimpia,
                        'phone_source' => $phoneFinal,
                        'bank_source'  => trim((string)($row[4] ?? '')),
                        'amount_bs'    => $montoFinal
                    ];
                }

                if (empty($dataToSave)) {
                    throw new Exception("No se encontraron abonos (NC) válidos.");
                }

                $adminId      = (int)$_SESSION['user']['id'];
                $insertedCount = $this->model->saveTpagoBatch($dataToSave, $adminId);

                if ($insertedCount > 0) {
                    AuditService::log([
                        'module'      => 'BANK_STATEMENTS_TPAGO',
                        'action'      => 'UPLOAD_EXCEL',
                        'description' => "Archivo T-Pago procesado. Nuevos registros: $insertedCount",
                        'event_type'  => 'NORMAL'
                    ]);
                }

                echo json_encode([
                    'ok'      => true,
                    'message' => "Se procesaron {$registrosProcesados} registros. Guardados: {$insertedCount}"
                ]);

            } else {
                throw new Exception("Error al leer el Excel: " . \Shuchkin\SimpleXLSX::parseError());
            }

        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // =========================================================
    // API: MOVIMIENTOS MERCANTIL (tbl_financial_bank_transactions_account)
    // =========================================================

    /**
     * API: Obtener movimientos Mercantil paginados y filtrados
     */
    public function getMovimientosTransactions(): void
    {
        $this->setJsonHeaders();

        try {
            $filters = [
                'date'      => trim($_GET['date']      ?? ''),
                'reference' => trim($_GET['reference'] ?? ''),
                'amount'    => trim($_GET['amount']    ?? ''),
                'text'      => trim($_GET['text']      ?? ''),
            ];

            $limit  = 25;
            $page   = max(1, (int)($_GET['page'] ?? 1));
            $offset = ($page - 1) * $limit;

            $total = $this->model->getTotalMovimientosTransactions($filters);
            $data  = $this->model->getMovimientosTransactions($filters, $limit, $offset);

            echo json_encode([
                'ok'   => true,
                'data' => $data,
                'pagination' => [
                    'current_page'  => $page,
                    'total_pages'   => (int)ceil($total / $limit),
                    'total_records' => $total,
                    'limit'         => $limit
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Cargar archivo Excel Movimientos Mercantil
     * Estructura: encabezado fila 9, fila 10 = SALDO FINAL (saltar), datos desde fila 11
     * Columnas: Tipo | Fecha | Referencia | Descripción | Monto Bs.
     */
    public function uploadMovimientosFile(): void
    {
        $this->setJsonHeaders();

        try {
            if (!isset($_FILES['excelFile'])) {
                throw new Exception("No se recibió ningún archivo.");
            }

            if ($xlsx = \Shuchkin\SimpleXLSX::parse($_FILES['excelFile']['tmp_name'])) {
                $rows = $xlsx->rows();

                if (count($rows) <= 10) {
                    throw new Exception("El archivo no contiene suficientes datos.");
                }

                $dataToSave          = [];
                $registrosProcesados = 0;

                foreach ($rows as $index => $row) {
                    // Encabezado en fila 9 (index 8), fila 10 (index 9) = SALDO FINAL
                    if ($index <= 9) continue; // Saltar filas 1-10

                    $tipo = strtoupper(trim((string)($row[0] ?? '')));
                    if ($tipo !== 'NC') continue;

                    // Saltar SALDO INICIAL y SALDO FINAL por descripción
                    $desc = strtoupper(trim((string)($row[3] ?? '')));
                    if (in_array($desc, ['SALDO FINAL', 'SALDO INICIAL'])) continue;

                    if (empty(trim((string)($row[2] ?? '')))) continue;

                    $registrosProcesados++;

                    // Monto
                    $val        = $row[4] ?? 0;
                    $montoFinal = 0.0;
                    if (is_numeric($val)) {
                        $montoFinal = (float)$val;
                    } else {
                        $montoRaw   = trim((string)$val);
                        $temp       = str_replace('.', '', $montoRaw);
                        $temp       = str_replace(',', '.', $temp);
                        $montoFinal = (float)$temp;
                    }

                    if ($montoFinal <= 0) continue;

                    // Referencia
                    $refRaw = $row[2] ?? '';
                    if (is_float($refRaw) || is_int($refRaw)) {
                        $referenciaLimpia = number_format((float)$refRaw, 0, '', '');
                    } else {
                        $referenciaLimpia = ltrim(trim((string)$refRaw), '0');
                    }

                    // Fecha
                    $fechaRaw = $row[1] ?? '';
                    if ($fechaRaw instanceof \DateTime) {
                        $fechaRaw = $fechaRaw->format('d/m/Y');
                    } else {
                        $fechaRaw = trim((string)$fechaRaw);
                    }

                    $dataToSave[] = [
                        'op_type'     => $tipo,
                        'date_tran'   => $this->formatExcelDate($fechaRaw),
                        'reference'   => $referenciaLimpia,
                        'description' => $desc,
                        'amount_bs'   => $montoFinal
                    ];
                }

                if (empty($dataToSave)) {
                    throw new Exception("No se encontraron abonos (NC) válidos.");
                }

                $adminId       = (int)$_SESSION['user']['id'];
                $insertedCount = $this->model->saveMovimientosBatch($dataToSave, $adminId);

                if ($insertedCount > 0) {
                    AuditService::log([
                        'module'      => 'BANK_STATEMENTS_MOVIMIENTOS',
                        'action'      => 'UPLOAD_EXCEL',
                        'description' => "Archivo Movimientos Mercantil procesado. Nuevos registros: $insertedCount",
                        'event_type'  => 'NORMAL'
                    ]);
                }

                echo json_encode([
                    'ok'      => true,
                    'message' => "Se procesaron {$registrosProcesados} registros. Guardados: {$insertedCount}"
                ]);

            } else {
                throw new Exception("Error al leer el Excel: " . \Shuchkin\SimpleXLSX::parseError());
            }

        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Convierte fecha DD/MM/YYYY a YYYY-MM-DD
     */
    private function formatExcelDate(string $rawDate): ?string
    {
        if (strpos($rawDate, '/') !== false) {
            $p = explode('/', $rawDate);
            if (count($p) === 3) return "{$p[2]}-{$p[1]}-{$p[0]}";
        }
        $ts = strtotime($rawDate);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}