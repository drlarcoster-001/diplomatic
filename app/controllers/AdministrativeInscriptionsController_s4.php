<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/Controllers/AdministrativeInscriptionsController_s4.php
 * PROPÓSITO: Sanitización y blindaje financiero con trazabilidad de errores (Flags).
 * VERSIÓN: 2.1.4 - FIX: Escudo Backend Autónomo y Logs de Trazabilidad para depuración en vivo.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdministrativeInscriptionsModel;

final class AdministrativeInscriptionsController_s4 extends Controller
{
/**
     * Estructura los datos de pago y CONGELA la tasa de cambio en el JSON.
     * Blindaje total contra recalculos de tasas del día anterior/siguiente.
     */
    public function sanitizePaymentData(array $postData, string $payMethod): array
    {
        error_log("--- [DEBUG S4] INICIO DE SANITIZACIÓN ---");

        $rawAmount = '0.00';
        $payMethod = strtoupper(trim($payMethod));
        
        // 1. Selección de la fuente del monto según el canal
        switch ($payMethod) {
            case 'CASH':      $rawAmount = $postData['amount'] ?? '0.00'; break;
            case 'ZELLE':     $rawAmount = $postData['z_amount'] ?? '0.00'; break;
            case 'BINANCE':   $rawAmount = $postData['b_amount'] ?? '0.00'; break;
            case 'PAGOMOVIL': $rawAmount = $postData['pm_amount'] ?? '0.00'; break;
            default:          $rawAmount = $postData['amount'] ?? '0.00'; break;
        }

        // 2. Limpieza de formato (Fix venezolano: 1.234,56 -> 1234.56)
        $cleanString = str_replace('.', '', (string)$rawAmount); 
        $cleanString = str_replace(',', '.', $cleanString);      
        $cleanAmount = (float)$cleanString;

        // 3. Procesamiento del JSON (Metadata)
        $rawMetadata = $postData['payment_metadata'] ?? '{}';
        $metadata = json_decode($rawMetadata, true) ?: [];

        // Determinar moneda (Zelle y Binance son USD, el resto se asume BS para este sistema)
        $currency = (in_array($payMethod, ['ZELLE', 'BINANCE'])) ? 'USD' : 'BS';

        // ====================================================================
        // ESCUDO DE TASAS: CONGELACIÓN DE LA VERDAD HISTÓRICA
        // ====================================================================
if ($currency === 'BS') {
            $fechaComprobante = $metadata['detalles_transaccion']['fecha_comprobante'] ?? date('Y-m-d');

            // ANTI-DUPLICADOS
            $referencia = $metadata['detalles_transaccion']['referencia'] ?? '';
            $telefono   = $metadata['detalles_origen']['cuenta_correo_telf'] ?? '';
            $validacion = \App\Services\PaymentValidationService::verificarDuplicado(
                $referencia, $cleanAmount, $fechaComprobante, $telefono, $payMethod
            );
            if ($validacion['duplicado']) {
                throw new \Exception($validacion['mensaje']);
            }

            // TASA CORRECTA + REDONDEO EXACTO
            $calculo = \App\Services\PaymentValidationService::calcularMontoUsd($cleanAmount, $fechaComprobante);
            $metadata['monto_sistema_usd'] = $calculo['monto_usd'];
            $metadata['tasa_cambio']       = $calculo['tasa'];

        } else {
            $metadata['tasa_cambio']       = 1;
            $metadata['monto_sistema_usd'] = round($cleanAmount, 2);
        }

        // ====================================================================

        // Auditoría de registro
        if (!isset($metadata['auditoria'])) {
            $metadata['auditoria'] = [
                'fecha_registro' => date('Y-m-d H:i:s'),
                'agente' => $_SESSION['user']['id'] ?? 1
            ];
        }

        $finalMetadataJson = json_encode($metadata, JSON_UNESCAPED_UNICODE);

        $paymentData = [
            'method'     => $payMethod,
            'currency'   => $currency,
            'amount'     => $cleanAmount,
            'reference'  => 'PENDIENTE', 
            'metadata'   => $finalMetadataJson,
            'screenshot' => isset($postData['pay_screenshot']) ? str_replace(['public/', 'public\\'], ['', ''], (string)$postData['pay_screenshot']) : null
        ];

        // Mapeo de referencias
        if ($payMethod === 'CASH') {
            $paymentData['reference'] = 'EFECTIVO_TAQUILLA';
        } elseif ($payMethod === 'ZELLE') {
            $paymentData['reference'] = trim((string)($postData['z_ref'] ?? 'ZELLE_REF'));
        } elseif ($payMethod === 'BINANCE') {
            $paymentData['reference'] = trim((string)($postData['b_order'] ?? 'BINANCE_ORD'));
        } elseif ($payMethod === 'PAGOMOVIL') {
            $paymentData['reference'] = trim((string)($postData['pm_ref'] ?? 'PM_REF'));
        }

        error_log("[DEBUG S4] Tasa congelada en JSON: " . ($metadata['tasa_cambio'] ?? 'N/A'));
        error_log("[DEBUG S4] Monto USD congelado: " . ($metadata['monto_sistema_usd'] ?? 'N/A'));
        error_log("--- [DEBUG S4] FIN DE SANITIZACIÓN ---");

        return $paymentData;
    }


    /**
     * Endpoint para consultar la tasa según la fecha (Para el JS Front-end)
     */
    public function getExchangeRate()
    {
        header('Content-Type: application/json');
        
        $date = $_GET['date'] ?? date('Y-m-d');
        $tasa = 0.00;

        try {
            $modelAdmin = new \App\Models\AdministrativeInscriptionsModel();
            
            // Si tienes un método que busca por fecha úsalo aquí. 
            // Si no, usamos el que ya vi que tienes para traer la tasa activa:
            $tasa = (float) $modelAdmin->getActiveExchangeRate(); 
            
            if ($tasa > 0) {
                echo json_encode(['success' => true, 'tasa' => $tasa]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Tasa en cero']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error en servidor']);
        }
        exit;
    }
}