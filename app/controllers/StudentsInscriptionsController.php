<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES / CONTROLADOR PRINCIPAL
 * ARCHIVO: app/controllers/StudentsInscriptionsController.php
 * PROPÓSITO: Orquestador del wizard de inscripción.
 * VERSIÓN: 3.2.0 - FIX: Inyección de Tasa BCV, Calculadora Inicial y Candado de Monto Mínimo.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuditService;
use App\Models\StudentsInscriptionsModel;
use App\Models\StudentsInscriptionsModel_s1;
use App\Models\StudentsInscriptionsModel_s5;
use App\Services\PaymentValidationService;
use App\Controllers\AdministrativeInscriptionsController_s6; 
use Exception;

final class StudentsInscriptionsController extends Controller
{
    private StudentsInscriptionsModel $inscriptionsModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (empty($_SESSION['user']['id'])) {
            $this->redirect('/login');
            exit;
        }

        $userRole = strtoupper(trim($_SESSION['user']['role'] ?? ''));
        if ($userRole !== 'PARTICIPANT' && $userRole !== 'ADMINISTRATOR') {
            $this->redirect('/dashboard');
            exit;
        }

        $this->inscriptionsModel = new StudentsInscriptionsModel();
    }

    public function sendEmail(): void 
    {
        (new AdministrativeInscriptionsController_s6())->sendEmail();
    }

    public function checkExisting(): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $userId = (int)$_SESSION['user']['id'];
            $offeringId = (int)($_GET['offering_id'] ?? 0);
            
            $modelS1 = new StudentsInscriptionsModel_s1();
            $exists = $modelS1->checkExistingEnrollment($userId, $offeringId);
            
            echo json_encode(['exists' => $exists]);
        } catch (Exception $e) {
            echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function index(): void {
        if (ob_get_level() > 0) ob_clean(); 
        $studentId = (int)$_SESSION['user']['id'];
        $this->view('students/inscriptions/index', [
            'title' => 'Gestión de Inscripciones',
            'openOfferings' => $this->inscriptionsModel->getAvailableOfferings(),
            'enrollmentStatus' => $this->inscriptionsModel->getStudentEnrollmentsStatus($studentId)
        ]);
    }

    /**
     * FIX PASO 4: El Mensajero
     * Ahora envía las cuotas, el mínimo inicial calculado y la Tasa BCV real.
     */
    public function getPaymentPlan(): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $offeringId = (int)($_GET['id'] ?? 0);
            if ($offeringId <= 0) throw new Exception("ID no válido.");
            
            $plan = $this->inscriptionsModel->getOfferingPaymentPlan($offeringId);
            $initialPayment = $this->inscriptionsModel->getInitialPaymentDetails($offeringId);
            
            // BUSCADOR INTELIGENTE: Busca la tasa de hoy o la última disponible hacia atrás
            $tasaData = PaymentValidationService::obtenerTasaCorrecta(date('Y-m-d'));
            
            // Si no hay tasa en absoluto, enviamos 0.00 para que el JS bloquee
            $tasaBcv = $tasaData ? round((float)$tasaData['dolar_bcv'], 2) : 0.00;

            echo json_encode([
                'success' => true, 
                'plan' => $plan,
                'initial_payment' => $initialPayment,
                'tasa_bcv' => $tasaBcv
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Endpoint para obtener la tasa BCV por fecha.
     * Se usa cuando el alumno cambia la fecha en la calculadora.
     */
    public function getRateByDate(): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $date = $_GET['date'] ?? '';
            if (empty($date)) {
                throw new Exception("Fecha no proporcionada.");
            }

            // Llamamos a nuestro buscador que viaja en el tiempo
            $rateData = PaymentValidationService::obtenerTasaCorrecta($date);

            if ($rateData) {
                echo json_encode([
                    'success'    => true,
                    'dolar_bcv'  => round((float)$rateData['dolar_bcv'], 2),
                    'euro_bcv'   => round((float)$rateData['euro_bcv'], 2),
                    'found_date' => $rateData['rate_date']
                ]);
            } else {
                throw new Exception("No hay tasas registradas recientes. Verifique los feriados.");
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function create(): void {
        if (ob_get_level() > 0) ob_clean();
        $offeringId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $userId = (int)$_SESSION['user']['id'];
        $offering = ($offeringId > 0) ? $this->inscriptionsModel->getOfferingById($offeringId) : null;
        if (!$offering) { $this->redirect('/students/inscriptions'); exit; }

        $this->view('students/inscriptions/create', [
            'title'    => 'Formulario de Inscripción',
            'offering' => $offering,
            'student'  => $this->inscriptionsModel->getStudentProfileData($userId),
            'step'     => 1
        ]);
    }

    
public function store(): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = (int)$_SESSION['user']['id']; 
            $data = $_POST;
            $files = $_FILES;

            // --- PROCESAMIENTO DE ARCHIVOS (Igual al original) ---
            $uploadBaseDir = "uploads/enrollments/{$userId}/";
            $paymentDir = $uploadBaseDir . "payment/";
            if (!is_dir($uploadBaseDir)) mkdir($uploadBaseDir, 0755, true);
            if (!is_dir($paymentDir)) mkdir($paymentDir, 0755, true);

            $uploadedPaths = [];
            $docTypes = ['doc_id' => 'file_doc_id', 'doc_degree' => 'file_doc_degree', 'doc_cv' => 'file_doc_cv', 'pay_screenshot' => 'pay_screenshot'];
            
            foreach ($docTypes as $typeKey => $htmlName) {
                if (!empty($files[$htmlName]) && $files[$htmlName]['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($files[$htmlName]['name'], PATHINFO_EXTENSION);
                    $newFileName = "DOC_{$userId}_{$typeKey}_" . time() . ".{$ext}";
                    $targetDir = ($typeKey === 'pay_screenshot') ? $paymentDir : $uploadBaseDir;
                    $destPath = $targetDir . $newFileName;
                    if (move_uploaded_file($files[$htmlName]['tmp_name'], $destPath)) {
                        $uploadedPaths[$typeKey] = $destPath;
                    }
                }
            }

            // --- LÓGICA FINANCIERA SERIA ---
            $payMethod = strtoupper(trim($data['payment_method_type'] ?? 'CASH'));
            $paymentData = $this->preparePaymentData($payMethod, $userId);
            $enrollStatus = ($payMethod === 'CASH') ? 'COMPROMISO' : 'REVISION';

            if ($payMethod !== 'CASH') {
                $rawMeta = json_decode($paymentData['metadata'], true);
                // Usamos la fecha que el alumno reportó en el comprobante
                $fechaComprobante = $rawMeta['detalles_transaccion']['fecha_comprobante'] ?? date('Y-m-d');
                
            // ANTI-DUPLICADOS
            if ($payMethod === 'PAGOMOVIL') {
                $referencia = $rawMeta['detalles_transaccion']['referencia'] ?? '';
                $montoBs    = (float)($rawMeta['detalles_transaccion']['monto_nativo'] ?? 0);
                $telefono   = $rawMeta['detalles_origen']['cuenta_correo_telf'] ?? '';

                $validacion = PaymentValidationService::verificarDuplicado(
                    $referencia, $montoBs, $fechaComprobante, $telefono, $payMethod
                );
                if ($validacion['duplicado']) {
                    throw new Exception($validacion['mensaje']);
                }
            }

            // TASA CORRECTA + REDONDEO EXACTO
            if ($payMethod === 'PAGOMOVIL') {
                $montoBs = (float)$paymentData['amount'];
                $calculo = PaymentValidationService::calcularMontoUsd($montoBs, $fechaComprobante);
                $montoEnviadoUsd = $calculo['monto_usd'];
                $tasaValida      = $calculo['tasa'];
            } else {
                $montoEnviadoUsd = round((float)$paymentData['amount'], 2);
                $tasaValida      = (float)($rawMeta['tasa_cambio'] ?? 1);
            }


                $rawMeta['monto_sistema_usd'] = number_format(round($montoEnviadoUsd, 2), 2, '.', '');
                $rawMeta['tasa_cambio']        = number_format(round($tasaValida, 2), 2, '.', '');

                $paymentData['metadata'] = json_encode($rawMeta, JSON_UNESCAPED_UNICODE);
            }

            // --- PERSISTENCIA ---
            $modelS5 = new StudentsInscriptionsModel_s5();
            $inscriptionParams = [
                'user_id' => $userId,
                'offering_id' => (int)$data['offering_id'],
                'undergraduate_degree' => $data['undergraduate_degree_s2'] ?? 'N/A',
                'provenance' => $data['provenance_s2'] ?? 'N/A',
                'doc_id_card' => $uploadedPaths['doc_id'] ?? null,
                'doc_degree' => $uploadedPaths['doc_degree'] ?? null,
                'doc_cv' => $uploadedPaths['doc_cv'] ?? null,
                'payment_method' => $payMethod,
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'reference_id' => $paymentData['reference'],
                'payment_metadata' => $paymentData['metadata'],
                'screenshot_path' => $uploadedPaths['pay_screenshot'] ?? null,
                'enrollment_status' => $enrollStatus, 
                'payment_status' => 'PENDING'
            ];

            $inscriptionId = $modelS5->saveCompleteInscription($inscriptionParams);
            
            if (!$inscriptionId) {
                throw new Exception("Error al guardar la inscripción.");
            }

            AuditService::log([
                'module' => 'STUDENT_ENROLLMENTS', 'action' => 'STORE_SUCCESS',
                'description' => "Inscripción #$inscriptionId creada por Alumno $userId.", 'event_type' => 'SUCCESS'
            ]);

            echo json_encode(['status' => 'success', 'enrollment_id' => $inscriptionId, 'message' => '¡Procesado exitosamente!']);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

private function preparePaymentData(string $method, int $userId): array
    {
        $rawMetadata = json_decode($_POST['payment_metadata'] ?? '{}', true);

        $clean = function($val) {
            if (is_null($val) || $val === '' || $val === 'N/A') return 0.0; 
            $val = str_replace(['$', ' '], '', (string)$val);
            if (strpos($val, '.') !== false && strpos($val, ',') !== false) {
                $val = str_replace('.', '', $val);
                $val = str_replace(',', '.', $val);
            } else if (strpos($val, ',') !== false) {
                $val = str_replace(',', '.', $val);
            }
            return (float)$val;
        };

        $banco   = 'N/A';
        $telf    = 'N/A';
        $titular = 'N/A';
        $moneda  = ($method === 'PAGOMOVIL') ? 'Bs' : 'USD';

        if ($method === 'PAGOMOVIL') {
            $banco   = $rawMetadata['detalles_origen']['banco_emisor'] ?? ($_POST['pm_bank'] ?? 'N/A');
            $prefix  = $_POST['pm_prefix'] ?? '';
            $num     = $_POST['pm_phone'] ?? '';
            $telf    = (!empty($prefix) && !empty($num)) ? "$prefix-$num" : ($rawMetadata['detalles_origen']['cuenta_correo_telf'] ?? 'N/A');
            $titular = 'NO_SUMINISTRADO';
        } elseif ($method === 'ZELLE') {
            $banco   = 'ZELLE';
            $telf    = $rawMetadata['detalles_origen']['cuenta_correo_telf'] ?? ($_POST['z_email'] ?? 'N/A');
            $titular = $rawMetadata['detalles_origen']['nombre_titular'] ?? ($_POST['z_issuer'] ?? 'N/A');
        } elseif ($method === 'BINANCE') {
            $banco   = 'BINANCE PAY';
            $telf    = $rawMetadata['detalles_origen']['cuenta_correo_telf'] ?? ($_POST['b_uid'] ?? 'N/A');
            $titular = 'BINANCE_USER';
        }

        $masterJson = [
            'metodo'            => $method,
            // Cambiamos floor por round a 2 decimales para no perder dinero
            'monto_sistema_usd' => number_format(round($clean($rawMetadata['monto_sistema_usd'] ?? 0), 2), 2, '.', ''),
            'tasa_cambio'       => number_format(round((float)($rawMetadata['tasa_cambio'] ?? 0), 2), 2, '.', ''),
            'detalles_origen'   => [
                'identificador'      => $rawMetadata['detalles_origen']['identificador'] ?? $userId,
                'cuenta_correo_telf' => $telf,
                'nombre_titular'     => $titular,
                'banco_emisor'       => $banco
            ],
            'detalles_transaccion' => [
                'referencia'        => $rawMetadata['detalles_transaccion']['referencia'] ?? ($_POST['pm_ref'] ?? ($_POST['z_ref'] ?? ($_POST['b_order'] ?? 'N/A'))),
                'fecha_comprobante' => date('Y-m-d', strtotime($rawMetadata['detalles_transaccion']['fecha_comprobante'] ?? date('Y-m-d'))),
                'monto_nativo'      => number_format(round($clean($rawMetadata['detalles_transaccion']['monto_nativo'] ?? 0), 2), 2, '.', ''),
                'moneda_nativa'     => $moneda
            ],

            'auditoria' => [
                'fecha_registro' => date('Y-m-d H:i:s'), 
                'agente'         => ($_SESSION['user']['id'] ?? '0') . " - " . ($_SESSION['user']['name'] ?? 'N/A')
            ]
        ];

        return [
            'amount'    => ($method === 'CASH') ? $masterJson['monto_sistema_usd'] : $masterJson['detalles_transaccion']['monto_nativo'],
            'currency'  => $moneda,
            'reference' => $masterJson['detalles_transaccion']['referencia'],
            'metadata'  => json_encode($masterJson, JSON_UNESCAPED_UNICODE)
        ];
    }

    protected function redirect(string $path): void {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $urlBase = (strpos($base, 'public') === false) ? $base . '/public' : $base;
        header("Location: " . $urlBase . $path);
        exit;
    }


}