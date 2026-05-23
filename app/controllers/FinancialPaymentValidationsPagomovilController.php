<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS
 * ARCHIVO: app/controllers/FinancialPaymentValidationsPagomovilController.php
 * PROPÓSITO: Controlador operativo para conciliación de Pago Móvil con Lógica de Cascada.
 * VERSIÓN: 3.0.0 - FIX: Conteo detallado (X vs Y) y Aprobación en Cascada.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialPaymentValidationsPagomovilModel;
use App\Services\AuditService;
use Shuchkin\SimpleXLSX;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PDO;

/**
 * CARGA MANUAL DE PHPMailer
 */
$phpmailerPath = realpath(__DIR__ . '/../../tools/phpmailer/');
if ($phpmailerPath) {
    require_once $phpmailerPath . '/Exception.php';
    require_once $phpmailerPath . '/PHPMailer.php';
    require_once $phpmailerPath . '/SMTP.php';
}

/**
 * CARGA MANUAL DE SimpleXLSX
 */
$xlsxLib = dirname(__DIR__, 2) . '/app/core/libs/SimpleXLSX.php';
if (file_exists($xlsxLib)) {
    require_once $xlsxLib;
}

final class FinancialPaymentValidationsPagomovilController extends Controller
{
    private FinancialPaymentValidationsPagomovilModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificación de seguridad
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['user_type'] !== 'INTERNAL') {
            $this->redirect('/dashboard');
            exit;
        }

        $this->model = new FinancialPaymentValidationsPagomovilModel();
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


    public function index(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        $rate = $this->model->getLastGlobalRate(); // <        $rate = $this->model->getLastGlobalRate();
        $this->view('financial/payment_validations/pagomovil/index', [
            'last_rate' => $rate 
        ]);
    }

/**
 * API: Obtener pagos pendientes (Paginado y Filtrado)
 */
public function getPendingPayments(): void
{
    $this->setJsonHeaders();
    
    try {
        // 1. Forzar la captura limpia de los parámetros GET
        $text = isset($_GET['text']) ? trim((string)$_GET['text']) : '';
        $date = isset($_GET['date']) ? trim((string)$_GET['date']) : '';

        // 2. Construir el array EXACTO que espera el modelo
        $filters = [
            'text'      => $text,
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to']   ?? ''),
            'order'     => trim($_GET['order']      ?? 'DESC'),
        ];
        
        // Logs de respaldo para verificar en tiempo real
        error_log("CONTROLADOR - Text: '$text', Date: '$date'");

        $limit = 25;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        // 3. Pasar los filtros al conteo y a la data
        $totalRecords = $this->model->getTotalPendingPayments($filters);
        $data = $this->model->getPendingPayments($filters, $limit, $offset);
        
        foreach ($data as &$row) {
            $row['referencia_corta'] = $row['referencia']; // Ya viene con 4 dígitos del SQL
        }
        unset($row);
        
        $totalPages = ceil($totalRecords / $limit);

        echo json_encode([
            'ok' => true, 
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'limit' => $limit
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    }
    exit;
}


/**
     * Procesa el archivo Excel del banco (Fix Definitivo de Montos)
     */
    public function uploadFile(): void
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

                $dataToSave = [];
                $registrosProcesados = 0;

                foreach ($rows as $index => $row) {
                    if ($index <= 4) continue; // Saltamos encabezados

                    // --- VALIDACIÓN 1: Solo Notas de Crédito ---
                    $tipo = strtoupper(trim((string)($row[0] ?? '')));
                    if ($tipo !== 'NC') continue;

                    // --- VALIDACIÓN 2: Referencia obligatoria ---
                    if (empty(trim((string)($row[2] ?? '')))) continue;

                    $registrosProcesados++;

                    // --- FIX DEFINITIVO DE MONTO ---
                    $val = $row[5] ?? 0;
                    $montoFinal = 0.0;

                    if (is_numeric($val)) {
                        // Si la librería ya lo detecta como número limpio (ej. 602.74 o 60274)
                        $montoFinal = (float)$val;
                    } else {
                        // Si viene como texto con separadores (ej: "602,74" o "1.250,00")
                        $montoRaw = trim((string)$val);
                        $temp = str_replace('.', '', $montoRaw); // Quitamos puntos de miles
                        $temp = str_replace(',', '.', $temp);    // Coma a punto decimal
                        $montoFinal = (float)$temp;
                    }


                    // --- NORMALIZACIÓN DE DATOS ---
                    $refRaw = $row[2] ?? '';
                    if (is_float($refRaw) || is_int($refRaw)) {
                        $referenciaLimpia = number_format((float)$refRaw, 0, '', '');
                    } else {
                        $referenciaLimpia = ltrim(trim((string)$refRaw), '0');
                    }

                    $phoneRaw = trim((string)($row[3] ?? ''));
                    $phoneOnlyNumbers = preg_replace('/\D/', '', $phoneRaw);
                    $phoneFinal = substr($phoneOnlyNumbers, -10);

                    $fechaRaw = $row[1] ?? '';
                    if ($fechaRaw instanceof \DateTime) {
                        $fechaRaw = $fechaRaw->format('d/m/Y');
                    } else {
                        $fechaRaw = trim((string)$fechaRaw);
                    }

                    $dataToSave[] = [
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

                $adminId = (int)$_SESSION['user']['id'];
                $insertedCount = $this->model->saveStatementBatch($dataToSave, $adminId);

                echo json_encode([
                    'ok' => true,
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

    /**
     * APROBAR PAGO (Llamada a la Lógica de Cascada)
     */
public function validatePayment(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $adminId = (int)$_SESSION['user']['id'];

            if ($paymentId <= 0) throw new Exception("ID de pago inválido.");

            // --- 1. Obtener detalles para el correo ---
            $db = (new \App\Core\Database())->getConnection();
            $sqlInfo = "SELECT u.email, CONCAT(u.first_name, ' ', u.last_name) as full_name, 
                               d.name as diploma_name, fp.amount, fp.currency, fp.method, fp.reference_id,
                               DATE_FORMAT(NOW(), '%d/%m/%Y %h:%i %p') as fecha_validacion
                        FROM tbl_financial_payments fp
                        JOIN tbl_students s ON fp.student_id = s.id
                        JOIN tbl_users u ON s.user_id = u.id
                        JOIN tbl_enrollments e ON s.enrollment_id = e.id
                        JOIN tbl_academic_offerings o ON e.offering_id = o.id
                        JOIN tbl_diplomados d ON o.diploma_id = d.id
                        WHERE fp.id = ? LIMIT 1";
            
            $stmt = $db->prepare($sqlInfo);
            $stmt->execute([$paymentId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            // --- 2. Ejecutar Lógica de Cascada ---
            if ($this->model->approvePaymentCascade($paymentId, $adminId)) {
                
                // --- 3. Enviar Correo si se aprobó con éxito ---
                if ($userData) {
                    $this->sendValidationEmail(
                        $userData['email'], 
                        $userData['full_name'], 
                        $userData['diploma_name'], 
                        $userData['reference_id'] ?? 'S/R',
                        $userData['amount'],
                        $userData['currency'],
                        "PAGO MÓVIL",
                        $userData['fecha_validacion']
                    );
                }

                echo json_encode(['ok' => true, 'message' => "Pago aprobado y comprobante enviado al estudiante."]);
            } else {
                throw new Exception("Error interno al procesar los abonos del estudiante.");
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }


    /**
     * RECHAZAR PAGO
     */
    public function rejectPayment(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $adminId = (int)$_SESSION['user']['id'];

            if ($this->model->rejectPayment($paymentId, $adminId)) {
                echo json_encode(['ok' => true, 'message' => "El reporte de pago ha sido rechazado."]);
            } else {
                throw new Exception("No se pudo procesar el rechazo.");
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * APROBACIÓN MASIVA (También usa Cascada)
     */
    public function approveMassivePayments(): void
    {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $payments = $_POST['payments'] ?? [];
            if (empty($payments)) throw new Exception("No hay registros para procesar.");

            $adminId = (int)$_SESSION['user']['id'];
            $successCount = 0;

            foreach ($payments as $p) {
                if (isset($p['id'])) {
                    $result = $this->model->approvePaymentCascade((int)$p['id'], $adminId);
                    error_log("Pago ID {$p['id']}: " . ($result ? 'OK' : 'FAIL'));
                    if ($result) $successCount++;
                }
            }

            echo json_encode([
                'ok' => true, 
                'message' => "Se procesaron exitosamente $successCount pagos en cascada."
            ]);

        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Helper: Formatear fecha de Excel DD/MM/YYYY a YYYY-MM-DD
     */
    private function formatExcelDate(string $rawDate): ?string
    {
        if (strpos($rawDate, '/') !== false) {
            $p = explode('/', $rawDate);
            if (count($p) === 3) return "{$p[2]}-{$p[1]}-{$p[0]}";
        }
        return date('Y-m-d', strtotime($rawDate)) ?: null;
    }

    private function sendValidationEmail($email, $fullName, $diplomaName, $reference, $amount, $currency, $method, $date): void
    {
        try {
            $db = (new \App\Core\Database())->getConnection();
            $stmt = $db->prepare("SELECT * FROM tbl_email_settings WHERE tipo_correo = 'INSCRIPCION' LIMIT 1");
            $stmt->execute();
            $conf = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$conf) return;

            $nombreInstitucion = "Coordinación Académica DIPLOMATIC"; 
            $successColor = '#198754';
            $montoFormateado = number_format((float)$amount, 2, ',', '.') . ' ' . $currency;

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Host = $conf['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $conf['smtp_user'];
            $mail->Password = $conf['smtp_password'];
            $mail->Port = (int)$conf['smtp_port'];
            $mail->SMTPSecure = (strtoupper($conf['smtp_security']) === 'SSL') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom($conf['from_email'], $conf['from_name']);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "✅ Cuota Validada: $diplomaName - Ref: $reference";

            $mail->Body = "
            <div style='background-color: #f4f7f6; padding: 30px 10px; font-family: sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0;'>
                    <div style='background-color: $successColor; padding: 20px; text-align: center;'>
                        <h2 style='color: #ffffff; margin: 0; font-size: 20px;'>NOTIFICACIÓN DE PAGO RECIBIDO</h2>
                    </div>

                    <div style='padding: 30px; color: #444444;'>
                        <p style='font-size: 16px;'>Hola <strong>$fullName</strong>,</p>
                        <p>Tu reporte de <strong>Pago Móvil</strong> ha sido conciliado con nuestro estado de cuenta bancario exitosamente.</p>
                        
                        <table style='width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #fcfcfc;'>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Programa:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold;'>$diplomaName</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Referencia Bancaria:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold; color: $successColor;'>$reference</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Monto Conciliado:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; font-weight: bold; font-size: 18px;'>$montoFormateado</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Método:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee;'>$method</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eee; color: #666;'>Fecha Validación:</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eee;'>$date</td>
                            </tr>
                        </table>

                        <div style='background-color: #e9f7ef; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; text-align: center;'>
                            <strong>¡Pago Aplicado!</strong> Los fondos han sido distribuidos en tus cuotas pendientes según la prioridad de vencimiento.
                        </div>
                    </div>

                    <div style='background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                        <p style='margin: 0;'>Atentamente,</p>
                        <p style='margin: 5px 0 0 0; font-weight: bold; color: $successColor; font-size: 14px;'>$nombreInstitucion</p>
                    </div>
                </div>
            </div>";

            $mail->send();
        } catch (\Throwable $e) {
            error_log("Error de correo Pago Móvil Cuotas: " . $e->getMessage());
        }
    }
}