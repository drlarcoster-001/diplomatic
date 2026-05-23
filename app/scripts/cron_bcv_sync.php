<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / AUTOMATIZACIÓN
 * ARCHIVO: app/scripts/cron_bcv_sync.php
 * PROPÓSITO: Sincronización horaria con el portal del BCV, validación de deltas y persistencia en tbl_financial_exchange_rates.
 * VERSIÓN: 1.3.2 - Fix de rutas para soporte /diplomatic/ y eliminación definitiva de dependencia de vendor.
 */

declare(strict_types=1);

// 1. CONFIGURACIÓN DE ENTORNO
// Sincronizamos la zona horaria para que los registros en tbl_financial_exchange_rates sean coherentes
date_default_timezone_set('America/Caracas');

// Blindaje de errores para el cron_log.txt en cPanel
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "[".date('Y-m-d H:i:s')."] Iniciando validación horaria del sistema...\n";

// 2. CARGA DE NÚCLEO (Sin Autoload de Vendor)
// Las rutas se basan en la estructura: app/scripts/ -> app/core/ | app/models/ | app/services/
require_once __DIR__ . '/../core/Database.php'; 
require_once __DIR__ . '/../models/FinancialExchangeRatesModel.php';
require_once __DIR__ . '/../services/AuditService.php';

use App\Models\FinancialExchangeRatesModel;
use App\Services\AuditService;

/**
 * Método de extracción idéntico al del FinancialExchangeRatesController.
 * Garantiza que el comportamiento del Cron sea paritario al del botón manual.
 */
function extraer($html, $id) {
    preg_match('/<div[^>]+id="' . $id . '"[^>]*>.*?<strong[^>]*>\s*([\d.,]+)\s*<\/strong>/s', $html, $matches);
    if (isset($matches[1])) {
        $val = str_replace('.', '', $matches[1]);
        return (float)str_replace(',', '.', $val);
    }
    return 0;
}

// --- PROCESO DE SINCRONIZACIÓN ---
try {
    // 3. CONSULTA AL PORTAL BCV
    $ch = curl_init("https://www.bcv.org.ve/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0');

    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) {
        throw new Exception("Error de red: No se pudo obtener respuesta del portal del BCV.");
    }

    $dolar = extraer($html, 'dolar');
    $euro  = extraer($html, 'euro');

    if ($dolar <= 0) {
        throw new Exception("Error de parseo: La tasa del dólar no pudo ser extraída del HTML.");
    }

    $model = new FinancialExchangeRatesModel();
    $lastRate = $model->getLastRate();

    // 4. VALIDACIÓN DE DELTA (Comparación a 4 decimales)
    // Se valida contra la última tasa en BD para evitar saturar tbl_financial_exchange_rates con duplicados
    if ($lastRate && 
        round((float)$lastRate['dolar_bcv'], 4) === round($dolar, 4) && 
        round((float)$lastRate['euro_bcv'], 4) === round($euro, 4)) {
        echo "Aviso: La tasa capturada es idéntica a la última registrada. Omitiendo guardado.\n";
        exit;
    }

    // 5. REGISTRO DE NUEVA TASA
    $data = [
        'dolar'   => $dolar,
        'euro'    => $euro,
        'user_id' => 1 // ID del sistema para procesos automáticos
    ];

    if ($model->save($data)) {
        // Registro en auditoría para control de cambios gerencial
        AuditService::log([
            'module'      => 'FINANCIAL_EXCHANGE_RATES',
            'action'      => 'CRON_AUTO_UPDATE',
            'description' => "Cambio de tasa detectado por Cron: USD {$data['dolar']} / EUR {$data['euro']}",
            'event_type'  => 'NORMAL'
        ]);
        echo "¡Sincronización exitosa! Nueva tasa registrada: USD " . $data['dolar'] . "\n";
    }

} catch (Exception $e) {
    error_log("CRON ERROR: " . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
}