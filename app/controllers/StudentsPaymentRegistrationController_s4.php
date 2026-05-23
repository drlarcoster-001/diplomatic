<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / REGISTRO DE PAGOS
 * ARCHIVO: app/controllers/StudentsPaymentRegistrationController_s4.php
 * PROPÓSITO: Gestión de estados de cuenta y persistencia de reportes (Versión Alumno).
 * VERSIÓN: 1.1.0 - FIX: Normalización de montos nativos (BS/USD) y blindaje de tasa de cambio.
 * REGLA DE EQUIPO: El campo 'amount' de la DB guarda el monto físico recibido (BS o USD).
 */

declare(strict_types=1);

namespace App\Controllers;

trait StudentsPaymentRegistrationController_s4
{
    /**
     * Endpoint AJAX: Carga el estado de cuenta detallado para el modal del alumno.
     */
    public function getAccountStatus(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $offeringId = (int)($_GET['offering_id'] ?? 0);
            
            if ($userId === 0 || $offeringId === 0) {
                throw new \Exception("Parámetros insuficientes para consultar el estado de cuenta.");
            }

            $data = $this->model->getAccountStatusDetails($userId, $offeringId);

            echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

 /**
     * Endpoint AJAX: Obtiene la tasa BCV aplicando la REGLA T-1.
     */

public function getLatestExchangeRate(): void
{
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    $date = $_GET['date'] ?? date('Y-m-d');

    try {
        $rateData = \App\Services\PaymentValidationService::obtenerTasaCorrecta($date);
        $result = $rateData;    

        if ($result && isset($result['dolar_bcv'])) {
            echo json_encode([
                'success' => true, 
                'tasa' => number_format((float)$result['dolar_bcv'], 2, '.', ''),
                'fecha_aplicada' => $result['rate_date']
            ]);
        } else {
            throw new \Exception("No hay tasa disponible para la fecha solicitada.");
        }
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/**
     * Endpoint POST: Procesa el reporte de pago con Blindaje T-1 y Truncamiento a Enteros.
     * VERSIÓN: 1.1.4 - FIX: Restauración estricta de tipos de datos y estructura JSON (Agente ID-Nombre).
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
        $userId     = (int)($_SESSION['user']['id'] ?? 0);
        $offeringId = (int)($_POST['offering_id'] ?? 0);
        $method     = strtoupper(trim((string)($_POST['payment_method_type'] ?? '')));
        
        // Decodificamos el JSON maestro enviado por el frontend
        $metadataJson = $_POST['payment_metadata'] ?? '{}';
        $rawMetadata = json_decode($metadataJson, true) ?: [];

        // 1. OBTENCIÓN DE LA FECHA REAL DEL PAGO (No la de hoy)
        // Buscamos primero en el JSON y luego como campo POST directo por si el JS no sincronizó el JSON.
        $fechaComprobante = $rawMetadata['detalles_transaccion']['fecha_comprobante'] ?? $_POST['pm_date'] ?? null;
        
        
        if (!$fechaComprobante || $fechaComprobante === "" || $fechaComprobante === "null") {
            throw new \Exception("La fecha en la que realizó el pago es obligatoria para validar el reporte.");
        }

// 2. MONTO NATIVO (lo que escribió el estudiante, sin tocar)
        $montoNativo = round((float)($rawMetadata['detalles_transaccion']['monto_nativo'] ?? 0.00), 2);

        // 3. LECTURA DE TASA DESDE LA BASE DE DATOS (nunca del frontend)
        if ($method === 'PAGOMOVIL') {
            

            // Determinamos si el pago es reciente (hoy o ayer) o antiguo (antiayer o antes)
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

        } else {
            // Zelle/Binance: monto directo en divisas, sin tasa
            $montoSistemaUsd = round($montoNativo, 2);
            $monedaFinal     = ($method === 'BINANCE') ? 'USDT' : 'USD';
            $tasaFinal       = 1.00;
        }

        // --- ARMADO DE IDENTIDAD ---
        $userName = $_SESSION['user']['name'] ?? $_SESSION['user']['display_name'] ?? 'ESTUDIANTE';
        $agenteString = $userId . ' - ' . strtoupper($userName);
        $identificadorEstudiante = $rawMetadata['detalles_origen']['identificador'] ?? $_SESSION['user']['document_id'] ?? 'N/A';

        // 4. RECONSTRUCCIÓN DEL MASTER JSON (Garantizando tipos de datos numéricos)
        $masterJson = [
            'metodo'               => $method,
            'monto_sistema_usd'    => (float)$montoSistemaUsd,
            'tasa_cambio'          => (float)$tasaFinal,
            'detalles_origen'      => [
                'identificador'      => $identificadorEstudiante,
                'cuenta_correo_telf' => $rawMetadata['detalles_origen']['cuenta_correo_telf'] ?? 'N/A',
                'nombre_titular'     => $rawMetadata['detalles_origen']['nombre_titular'] ?? 'NO_SUMINISTRADO',
                'banco_emisor'       => $rawMetadata['detalles_origen']['banco_emisor'] ?? 'N/A'
            ],
            'detalles_transaccion' => [
                'referencia'        => $rawMetadata['detalles_transaccion']['referencia'] ?? 'N/A',
                'fecha_comprobante' => $fechaComprobante, // FECHA REAL DEL PAGO (No del servidor)
                'monto_nativo'      => (float)$montoNativo,
                'moneda_nativa'     => $monedaFinal
            ],
            'auditoria' => [
                'fecha_registro' => date('Y-m-d H:i:s'), // TIMESTAMP DE AUDITORÍA DEL SERVIDOR
                'agente'         => $agenteString
            ]
        ];

        // 5. GESTIÓN DE ARCHIVO COMPROBANTE
        $screenshotPath = null;
        if ($method !== 'CASH') {
            if (!isset($_FILES['pay_screenshot']) || $_FILES['pay_screenshot']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception("El comprobante digital es obligatorio para este método de pago.");
            }

            $fileTmpPath = $_FILES['pay_screenshot']['tmp_name'];
            $fileExtension = strtolower(pathinfo($_FILES['pay_screenshot']['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
                throw new \Exception("Formato de imagen inválido. Use JPG o PNG.");
            }

            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/enrollments/' . $userId . '/payment/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true); 

            $newFileName = 'STU_' . $userId . '_REP_' . time() . '_' . bin2hex(random_bytes(2)) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                throw new \Exception("Error al guardar el comprobante en el servidor.");
            }
            $screenshotPath = 'uploads/enrollments/' . $userId . '/payment/' . $newFileName;
        }

        // 6. PERSISTENCIA EN BASE DE DATOS
        $payload = [
            'user_id'         => $userId,
            'offering_id'     => $offeringId,
            'amount'          => (float)$montoNativo, 
            'currency'        => $monedaFinal,
            'method'          => $method,
            'reference_id'    => $masterJson['detalles_transaccion']['referencia'],
            'metadata'        => $masterJson,
            'screenshot_path' => $screenshotPath,
            'collector_id'    => $userId
        ];
        
        $paymentId = $this->model->registerPayment($payload);
        
        if (!$paymentId) {
            if (isset($destPath) && file_exists($destPath)) @unlink($destPath);
            throw new \Exception("Error crítico al registrar el reporte en la base de datos.");
        }

        echo json_encode([
            'status' => 'success', 
            'message' => '¡Reporte enviado exitosamente!',
            'payment_id' => $paymentId
        ]);

    } catch (\Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

}