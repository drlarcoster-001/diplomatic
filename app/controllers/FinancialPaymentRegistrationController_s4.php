<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / REGISTRO DE PAGOS
 * ARCHIVO: app/controllers/FinancialPaymentRegistrationController_s4.php
 * PROPÓSITO: Trait para gestión de estados de cuenta, consulta de tasa BCV y persistencia de pagos.
 * VERSIÓN: 2.5.0 - FIX: Mapeo de monto físico (BS/USD) a la columna 'amount' para coherencia contable.
 * REGLA DE EQUIPO: El campo 'amount' de la DB guarda lo recibido físicamente (BS o USD).
 */

declare(strict_types=1);

namespace App\Controllers;

trait FinancialPaymentRegistrationController_s4
{
    /**
     * Endpoint AJAX: Carga el estado de cuenta detallado para el modal.
     * Ruta: /financial/payment_registration/getAccountStatus
     */
    public function getAccountStatus(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = (int)($_GET['user_id'] ?? 0);
            $offeringId = (int)($_GET['offering_id'] ?? 0);
            
            if ($userId === 0 || $offeringId === 0) {
                throw new \Exception("Parámetros de identificación insuficientes.");
            }

            $data = $this->model->getAccountStatusDetails($userId, $offeringId);

            echo json_encode([
                'status' => 'success', 
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Endpoint AJAX: Obtiene la tasa BCV más actual.
     * Ruta: /financial/payment_registration/getLatestExchangeRate
     */
    public function getLatestExchangeRate(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $rate = $this->model->getLatestExchangeRate();
            echo json_encode(['status' => 'success', 'rate' => $rate]);
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Endpoint POST: Procesa el registro físico del pago.
     * Maneja subida de archivos y normalización de montos sistema vs montos nativos.
     */
    public function store(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            exit;
        }

        try {
            $userId     = (int)($_POST['user_id'] ?? 0);
            $offeringId = (int)($_POST['offering_id'] ?? 0);
            $method     = strtoupper(trim((string)($_POST['payment_method_type'] ?? 'CASH')));
            $adminId    = (int)($_SESSION['user']['id'] ?? 0); 
            
            // 1. RECEPCIÓN DE METADATA DESDE EL FRONTEND
            $metadataJson = $_POST['payment_metadata'] ?? '{}';
            $rawMetadata = json_decode($metadataJson, true);
            if (!is_array($rawMetadata)) {
                $rawMetadata = [];
            }


// 2. MONTO NATIVO (lo que escribió el administrador, sin tocar)
            $montoNativo = round((float)($rawMetadata['detalles_transaccion']['monto_nativo'] ?? 0.00), 2);

            // 3. LECTURA DE TASA DESDE LA BASE DE DATOS (nunca del frontend)
if ($method === 'PAGOMOVIL') {

                $fechaComprobante = $rawMetadata['detalles_transaccion']['fecha_comprobante'] ?? date('Y-m-d');

                // ANTI-DUPLICADOS
                $referencia = $rawMetadata['detalles_transaccion']['referencia'] ?? '';
                $telefono   = $rawMetadata['detalles_origen']['cuenta_correo_telf'] ?? '';
                $validacion = \App\Services\PaymentValidationService::verificarDuplicado(
                    $referencia, $montoNativo, $fechaComprobante, $telefono, $method
                );
                if ($validacion['duplicado']) {
                    throw new \Exception($validacion['mensaje']);
                }

                // TASA CORRECTA + REDONDEO EXACTO
                $calculo         = \App\Services\PaymentValidationService::calcularMontoUsd($montoNativo, $fechaComprobante);
                $montoSistemaUsd = $calculo['monto_usd'];
                $tasaFinal       = $calculo['tasa'];
                $monedaFinal     = 'BS';
                $montoParaDB     = $montoNativo;

            } else {
                $montoSistemaUsd = round($montoNativo, 2);
                $monedaFinal     = ($method === 'BINANCE') ? 'USDT' : 'USD';
                $tasaFinal       = 1.00;
                $montoParaDB     = $montoNativo;
            }
            

            // 3. CONSTRUCCIÓN DEL MASTER JSON NORMALIZADO (Para auditoría y reportes)
            $masterJson = [
                'metodo'            => $method,
                'monto_sistema_usd' => $montoSistemaUsd, // Valor referencial para contabilidad en USD
                'tasa_cambio'       => $tasaFinal,
                'detalles_origen'   => [
                    'identificador'      => $rawMetadata['detalles_origen']['identificador'] ?? 'N/A',
                    'cuenta_correo_telf' => $rawMetadata['detalles_origen']['cuenta_correo_telf'] ?? 'N/A',
                    'nombre_titular'     => $rawMetadata['detalles_origen']['nombre_titular'] ?? 'N/A',
                    'banco_emisor'       => $rawMetadata['detalles_origen']['banco_emisor'] ?? 'N/A'
                ],
                'detalles_transaccion' => [
                    'referencia'        => $rawMetadata['detalles_transaccion']['referencia'] ?? 'N/A',
                    'fecha_comprobante' => $rawMetadata['detalles_transaccion']['fecha_comprobante'] ?? date('Y-m-d'),
                    'monto_nativo'      => round($montoNativo, 2),
                    'moneda_nativa'     => $monedaFinal
                ],
                'auditoria' => [
                    'fecha_registro' => date('Y-m-d H:i:s'),
                    'agente'         => $adminId
                ]
            ];

            // --- VALIDACIONES DE INTEGRIDAD ---
            if ($userId === 0 || $offeringId === 0 || $montoNativo <= 0) {
                throw new \Exception("Datos de transacción inválidos. El monto debe ser mayor a cero.");
            }

            // --- GESTIÓN DE COMPROBANTE ---
            $screenshotPath = null;
            $destPath = null;
            
            if ($method !== 'CASH') {
                if (!isset($_FILES['pay_screenshot']) || $_FILES['pay_screenshot']['error'] !== UPLOAD_ERR_OK) {
                    throw new \Exception("El comprobante digital es obligatorio para pagos por " . $method);
                }

                $fileTmpPath = $_FILES['pay_screenshot']['tmp_name'];
                $fileExtension = strtolower(pathinfo($_FILES['pay_screenshot']['name'], PATHINFO_EXTENSION));

                if (!in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
                    throw new \Exception("Formato de imagen inválido. Solo JPG o PNG.");
                }

                $uploadDir = dirname(__DIR__, 2) . '/public/uploads/enrollments/' . $userId . '/payment/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true); 

                $newFileName = 'DOC_' . $userId . '_pay_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $screenshotPath = 'uploads/enrollments/' . $userId . '/payment/' . $newFileName;
                } else {
                    throw new \Exception("Error al resguardar el comprobante en el servidor.");
                }
            }

            // --- PERSISTENCIA ATÓMICA ---
            // IMPORTANTE: Aquí enviamos el monto nativo real y su moneda para la tabla
            $payload = [
                'user_id'         => $userId,
                'offering_id'     => $offeringId,
                'amount'          => $montoParaDB, // Guarda Bolívares si es Pago Móvil
                'currency'        => $monedaFinal, // Guarda 'BS' o 'USD' según corresponda
                'method'          => $method,
                'reference_id'    => $masterJson['detalles_transaccion']['referencia'],
                'metadata'        => $masterJson,
                'screenshot_path' => $screenshotPath,
                'collector_id'    => $adminId
            ];
            
            // El modelo FinancialPaymentRegistrationModel procesa este array
            $paymentId = $this->model->registerPayment($payload);
            
            if (!$paymentId) {
                if ($screenshotPath && file_exists($destPath)) @unlink($destPath);
                throw new \Exception("La base de datos rechazó el asiento contable.");
            }

            echo json_encode([
                'status' => 'success', 
                'message' => '¡Pago registrado y validado exitosamente!',
                'payment_id' => $paymentId
            ]);

        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

   /**
     * Endpoint AJAX: Obtiene la cédula del estudiante consultando al modelo.
     */
    public function getStudentIdentity(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = (int)($_GET['user_id'] ?? 0);
            
            if ($userId === 0) {
                echo json_encode(['status' => 'error', 'cedula' => 'N/A']);
                exit;
            }

            // LLAMAMOS AL MODELO
            $cedula = $this->model->getStudentIdCard($userId);

            echo json_encode([
                'status' => 'success', 
                'cedula' => $cedula ?: 'N/A'
            ]);

        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'cedula' => 'N/A']);
        }
        exit;
    }
}