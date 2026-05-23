<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / PAGO MÓVIL
 * ARCHIVO: app/controllers/FinancialCashPagomovilController.php
 * PROPÓSITO: Controlador operativo para conciliación de Pago Móvil, auditoría de eventos y generación de Ledger.
 * VERSIÓN: 3.2.1 - FIX: Corrección de parámetros en rejectEnrollmentFull para evitar Fatal Error por strict_types.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialCashPagomovilModel;
use App\Models\AdministrativeInscriptionsModel;
use App\Services\AuditService;
use Shuchkin\SimpleXLSX;
use Exception;

// --- 1. AQUÍ ESTÁN LOS "USE" QUE ME PEDISTE ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PDO;

// Carga de la librería SimpleXLSX con soporte para la estructura de subcarpeta /public/
$xlsxLib = dirname(__DIR__, 2) . '/app/core/libs/SimpleXLSX.php';
if (file_exists($xlsxLib)) {
    require_once $xlsxLib;
}

// --- 2. AQUÍ COLOCAS LA CARGA MANUAL DE PHPMAILER ---
$phpmailerPath = realpath(__DIR__ . '/../../tools/phpmailer/');
if ($phpmailerPath) {
    require_once $phpmailerPath . '/Exception.php';
    require_once $phpmailerPath . '/PHPMailer.php';
    require_once $phpmailerPath . '/SMTP.php';
}

class FinancialCashPagomovilController extends Controller
{
    private FinancialCashPagomovilModel $model;

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

        $this->model = new FinancialCashPagomovilModel();
    }

    /**
     * Limpia el búfer de salida para evitar errores de JSON (Token '<')
     */
    private function clearOutputBuffer(): void
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Establece las cabeceras JSON estándar
     */
    private function setJsonHeaders(): void
    {
        $this->clearOutputBuffer();
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Carga la vista principal.
     */
    public function index(): void
    {
        $this->clearOutputBuffer();
        $lastRate = $this->model->getLastGlobalRate();

        $this->view('financial/cash_operations/pagomovil/index', [
            'last_rate' => $lastRate
        ]);
    }

    /**
     * API: Retorna pagos PENDING con equivalencia USD calculada en el modelo.
     */
    public function getPendingPayments(): void
    {
        $this->setJsonHeaders();

        try {
            $filters = [
                'text' => $_GET['text'] ?? '',
                'date' => $_GET['date'] ?? ''
            ];
            $data = $this->model->getPendingPayments($filters);
            echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Procesa el Excel bancario.
     */
    public function uploadFile(): void
    {
        $this->setJsonHeaders();

        try {
            if (!isset($_FILES['excelFile'])) {
                throw new Exception("No se recibió ningún archivo.");
            }

            if ($xlsx = SimpleXLSX::parse($_FILES['excelFile']['tmp_name'])) {
                $rows = $xlsx->rows();
                
                if (count($rows) <= 5) {
                    throw new Exception("El archivo no contiene suficientes datos (Fila 5 vacía).");
                }

                $dataToSave = [];
                foreach ($rows as $index => $row) {
                    if ($index <= 4) continue;

                    $tipo = strtoupper(trim((string)($row[0] ?? '')));
                    if ($tipo !== 'NC') continue;

                    if (empty(trim((string)($row[2] ?? '')))) continue;

                    $rawDate = trim((string)($row[1] ?? ''));
                    $formattedDate = null;
                    
                    if (!empty($rawDate)) {
                        if (strpos($rawDate, '/') !== false) {
                            $parts = explode('/', $rawDate);
                            if (count($parts) === 3) {
                                $formattedDate = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
                            }
                        } else {
                            $ts = strtotime($rawDate);
                            if ($ts) $formattedDate = date('Y-m-d', $ts);
                        }
                    }

                    $dataToSave[] = [
                        'op_type'      => $tipo,
                        'date_tran'    => $formattedDate,
                        'reference'    => trim((string)($row[2] ?? '')),
                        'phone_source' => trim((string)($row[3] ?? '')),
                        'bank_source'  => trim((string)($row[4] ?? '')),
                        'amount_bs'    => (float)str_replace(['.', ','], ['', '.'], (string)($row[5] ?? '0'))
                    ];
                }

                if (empty($dataToSave)) {
                    throw new Exception("No se encontraron registros válidos para procesar.");
                }

                $userId = (int)$_SESSION['user']['id'];
                $insertedCount = $this->model->saveStatementBatch($dataToSave, $userId);
                
                if ($insertedCount > 0) {
                    AuditService::log([
                        'module'      => 'FINANCIAL_PAGOMOVIL',
                        'action'      => 'UPLOAD_EXCEL',
                        'description' => "Procesado estado de cuenta. Nuevos registros: $insertedCount",
                        'event_type'  => 'NORMAL'
                    ]);
                }

                echo json_encode([
                    'ok' => true,
                    'message' => "Se procesaron " . count($dataToSave) . " registros. Guardados: " . $insertedCount
                ]);
            } else {
                throw new Exception("Error al leer el Excel: " . SimpleXLSX::parseError());
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Acción de validación. Dispara la creación del Ledger (Libro Mayor).
     */
public function validatePayment(): void
    {
        $this->setJsonHeaders();
        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $reference = trim((string)($_POST['reference'] ?? ''));
            $adminId = (int)$_SESSION['user']['id'];

            if ($paymentId <= 0) throw new Exception("ID de pago no válido.");

            // 1. Buscamos TODOS los detalles del pago para el comprobante
            $db = (new \App\Core\Database())->getConnection();
            $sqlInfo = "SELECT u.email, CONCAT(u.first_name, ' ', u.last_name) as full_name, 
                               d.name as diploma_name, ep.amount, ep.currency, ep.method, 
                               DATE_FORMAT(ep.created_at, '%d/%m/%Y %h:%i %p') as fecha_pago
                        FROM tbl_enrollments_payments ep
                        JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                        JOIN tbl_users u ON e.user_id = u.id
                        JOIN tbl_academic_offerings o ON e.offering_id = o.id
                        JOIN tbl_diplomados d ON o.diploma_id = d.id
                        WHERE ep.id = ? LIMIT 1";
            $stmt = $db->prepare($sqlInfo);
            $stmt->execute([$paymentId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Aprobamos el pago
            $result = $this->model->approvePayment($paymentId, $reference, $adminId);

            if ($result) {
                // 3. Enviamos el correo con todos los fierros
                if ($userData) {
                    $this->sendValidationEmail(
                        $userData['email'], 
                        $userData['full_name'], 
                        $userData['diploma_name'], 
                        $reference,
                        $userData['amount'],
                        $userData['currency'],
                        $userData['method'],
                        $userData['fecha_pago']
                    );
                }
                echo json_encode(['ok' => true, 'message' => "Pago validado y comprobante detallado enviado."]);
            } else {
                throw new Exception("Error al procesar la aprobación.");
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }



    /**
     * Rechazo individual de un Pago Móvil con limpieza total de inscripción.
     */
    public function rejectPayment(): void
    {
        $this->setJsonHeaders();

        try {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $adminId = (int)$_SESSION['user']['id'];

            if ($paymentId <= 0) throw new Exception("ID de pago inválido.");

            // 1. Obtener enrollment_id para la limpieza profunda
            $db = (new \App\Core\Database())->getConnection();
            $stmt = $db->prepare("SELECT enrollment_id FROM tbl_enrollments_payments WHERE id = ? LIMIT 1");
            $stmt->execute([$paymentId]);
            $enrollId = (int)$stmt->fetchColumn();

            if ($enrollId > 0) {
                // 2. Limpieza de Ledger, Cupo y estatus en tbl_enrollments
                $adminModel = new AdministrativeInscriptionsModel();
                // FIX APLICADO AQUÍ: Quitamos el $reason extra que rompe el strict_types
                $adminModel->rejectEnrollmentFull($enrollId);

                // 3. Forzar estatus a RECHAZADO para asegurar liberación
                $stmtStatus = $db->prepare("UPDATE tbl_enrollments SET status = 'RECHAZADO' WHERE id = ?");
                $stmtStatus->execute([$enrollId]);
            }

            // 4. Marcar el pago en la tabla de pagos como REJECTED
            $result = $this->model->rejectPayment($paymentId, $adminId);

            if ($result) {
                AuditService::log([
                    'module'      => 'FINANCIAL_PAGOMOVIL',
                    'action'      => 'REJECT_PAYMENT',
                    'description' => "Pago Móvil Rechazado. Enrollment ($enrollId) marcado como RECHAZADO.",
                    'event_type'  => 'WARNING'
                ]);
                echo json_encode(['ok' => true, 'message' => "El pago e inscripción han sido rechazados y el Ledger limpiado."]);
            } else {
                throw new Exception("Error al rechazar el pago en el modelo financiero.");
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Aprobación masiva.
     */
    public function approveMassivePayments(): void
    {
        $this->setJsonHeaders();

        try {
            $payments = $_POST['payments'] ?? [];
            if (empty($payments) || !is_array($payments)) {
                throw new Exception("No se recibieron pagos para procesar.");
            }

            $adminId = (int)$_SESSION['user']['id'];
            $count = $this->model->approveMassivePayments($payments, $adminId);

            if ($count > 0) {
                AuditService::log([
                    'module'      => 'FINANCIAL_PAGOMOVIL',
                    'action'      => 'APPROVE_MASSIVE',
                    'description' => "Aprobación MASIVA ejecutada. Total: $count pagos.",
                    'event_type'  => 'SUCCESS'
                ]);
            }

            echo json_encode([
                'ok' => true, 
                'message' => "Se procesaron exitosamente " . $count . " aprobaciones."
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function sendValidationEmail($email, $fullName, $diplomaName, $reference, $amount, $currency, $method, $date): void
    {
        try {
            $db = (new \App\Core\Database())->getConnection();
            $stmt = $db->prepare("SELECT * FROM tbl_email_settings WHERE tipo_correo = 'INSCRIPCION' LIMIT 1");
            $stmt->execute();
            $conf = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$conf) return;

            $nombreInstitucion = "Plataforma de Diplomados"; 
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
            $mail->Subject = "✅ Comprobante de Pago Validado - $reference";

            $mail->Body = "
            <div style='background-color: #f4f7f6; padding: 30px 10px; font-family: sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0;'>
                    <div style='background-color: $successColor; padding: 20px; text-align: center;'>
                        <h2 style='color: #ffffff; margin: 0; font-size: 20px;'>CONFIRMACIÓN DE PAGO</h2>
                    </div>

                    <div style='padding: 30px; color: #444444;'>
                        <p style='font-size: 16px;'>Hola <strong>$fullName</strong>,</p>
                        <p>Tu pago ha sido verificado satisfactoriamente. A continuación, los detalles de tu transacción:</p>
                        
                        <table style='width: 100%; border-collapse: collapse; margin: 20px 0; background-color: #fcfcfc;'>
                            <tr>
                                <td style='padding: 10px; border-bottom: 1px solid #eee; color: #666;'>Programa:</td>
                                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>$diplomaName</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px; border-bottom: 1px solid #eee; color: #666;'>Referencia:</td>
                                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; color: $successColor;'>#$reference</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px; border-bottom: 1px solid #eee; color: #666;'>Monto Validado:</td>
                                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>$montoFormateado</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px; border-bottom: 1px solid #eee; color: #666;'>Método de Pago:</td>
                                <td style='padding: 10px; border-bottom: 1px solid #eee;'>$method</td>
                            </tr>
                            <tr>
                                <td style='padding: 10px; border-bottom: 1px solid #eee; color: #666;'>Fecha de Registro:</td>
                                <td style='padding: 10px; border-bottom: 1px solid #eee;'>$date</td>
                            </tr>
                        </table>

                        <p style='font-size: 14px; background-color: #e9f7ef; padding: 15px; border-radius: 5px; color: #155724;'>
                            <strong>Estatus:</strong> Conciliado. Tu cupo está garantizado y tu estado de cuenta ha sido actualizado.
                        </p>
                    </div>

                    <div style='background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                        <p style='margin: 0;'>Atentamente,</p>
                        <p style='margin: 5px 0 0 0; font-weight: bold; color: $successColor; font-size: 14px;'>$nombreInstitucion</p>
                    </div>
                </div>
            </div>";

            $mail->send();
        } catch (\Throwable $e) {
            error_log("Error de correo: " . $e->getMessage());
        }
    }

}