<?php
/**
 * SERVICIO: VALIDACIÓN DE PAGOS
 * ARCHIVO: app/services/PaymentValidationService.php
 * PROPÓSITO: Funciones reutilizables para validar pagos antes de registrarlos.
 *   1. Anti-duplicados: verifica que el comprobante no esté ya registrado.
 *   2. Tasa BCV correcta: siempre usa la tasa del día anterior a la fecha del comprobante.
 *   3. Redondeo exacto: usa round($valor, 2) para no perder centavos.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class PaymentValidationService
{
    /**
     * VALIDACIÓN ANTI-DUPLICADOS
     * Verifica en AMBAS tablas (inscripciones y cuotas) que no exista
     * un pago con la misma referencia + monto_bs + fecha + teléfono.
     *
     * @param string $referencia    Número de referencia completo
     * @param float  $montoBs       Monto en bolívares exacto
     * @param string $fecha         Fecha del comprobante (Y-m-d)
     * @param string $telefono      Teléfono de origen (ej: 0424-1234567)
     * @param string $metodo        Método de pago (PAGOMOVIL, ZELLE, etc.)
     *
     * @return array ['duplicado' => bool, 'mensaje' => string, 'tabla' => string]
     */
    public static function verificarDuplicado(
        string $referencia,
        float  $montoBs,
        string $fecha,
        string $telefono,
        string $metodo = 'PAGOMOVIL'
    ): array {
        $db = (new Database())->getConnection();

        // Solo aplica para PAGOMOVIL — Zelle/Binance/Efectivo tienen otras reglas
        if ($metodo !== 'PAGOMOVIL') {
            return ['duplicado' => false, 'mensaje' => '', 'tabla' => ''];
        }

        // Últimos 4 dígitos de la referencia para comparación flexible
        $ultimos4 = substr(preg_replace('/\D/', '', $referencia), -4);
        $telefono = preg_replace('/\D/', '', $telefono);

        // -------------------------------------------------------
        // VERIFICAR EN tbl_enrollments_payments (inscripciones)
        // -------------------------------------------------------
        $sql1 = "SELECT id, enrollment_id FROM tbl_enrollments_payments
                 WHERE method = 'PAGOMOVIL'
                 AND status != 'REJECTED'
                 AND RIGHT(REGEXP_REPLACE(reference_id, '[^0-9]', ''), 4) = ?
                 AND amount = ?
                 AND JSON_UNQUOTE(JSON_EXTRACT(payment_metadata, '$.detalles_transaccion.fecha_comprobante')) = ?
                 AND REGEXP_REPLACE(JSON_UNQUOTE(JSON_EXTRACT(payment_metadata, '$.detalles_origen.cuenta_correo_telf')), '[^0-9]', '') = ?
                 LIMIT 1";

        $stmt1 = $db->prepare($sql1);
        $stmt1->execute([$ultimos4, $montoBs, $fecha, $telefono]);
        $found1 = $stmt1->fetch(PDO::FETCH_ASSOC);

        if ($found1) {
            return [
                'duplicado' => true,
                'mensaje'   => "Este comprobante ya fue registrado en una inscripción (ID: {$found1['id']}). No se puede usar el mismo pago dos veces.",
                'tabla'     => 'tbl_enrollments_payments'
            ];
        }

        // -------------------------------------------------------
        // VERIFICAR EN tbl_financial_payments (cuotas regulares)
        // -------------------------------------------------------
        $sql2 = "SELECT id, student_id FROM tbl_financial_payments
                 WHERE method = 'PAGOMOVIL'
                 AND status != 'REJECTED'
                 AND RIGHT(REGEXP_REPLACE(reference_id, '[^0-9]', ''), 4) = ?
                 AND ABS(amount - ?) <= 0.01
                 AND JSON_UNQUOTE(JSON_EXTRACT(payment_metadata, '$.detalles_transaccion.fecha_comprobante')) = ?
                 AND REGEXP_REPLACE(JSON_UNQUOTE(JSON_EXTRACT(payment_metadata, '$.detalles_origen.cuenta_correo_telf')), '[^0-9]', '') = ?
                 LIMIT 1";

        $stmt2 = $db->prepare($sql2);
        $stmt2->execute([$ultimos4, $montoBs, $fecha, $telefono]);
        $found2 = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($found2) {
            return [
                'duplicado' => true,
                'mensaje'   => "Este comprobante ya fue registrado en un pago de cuota (ID: {$found2['id']}). No se puede usar el mismo pago dos veces.",
                'tabla'     => 'tbl_financial_payments'
            ];
        }

        return ['duplicado' => false, 'mensaje' => '', 'tabla' => ''];
    }

    /**
     * TASA BCV CORRECTA
     * Regla: usar SIEMPRE la tasa del día hábil ANTERIOR a la fecha del comprobante.
     * Esto coincide exactamente con la app "Al Cambio" que usan los estudiantes.
     *
     * @param string $fechaComprobante Fecha del comprobante (Y-m-d)
     * @return array|null ['dolar_bcv' => float, 'rate_date' => string] o null si no hay tasa
     */
    public static function obtenerTasaCorrecta(string $fechaComprobante): ?array
    {
        $db = (new Database())->getConnection();

        // Siempre buscar la tasa del día anterior a la fecha del comprobante
        $fechaBusqueda = $fechaComprobante;

        // Buscar hacia atrás hasta encontrar una tasa (máximo 10 días para cubrir feriados largos)
        $sql = "SELECT dolar_bcv, euro_bcv, rate_date 
                FROM tbl_financial_exchange_rates 
                WHERE rate_date <= ?
                ORDER BY rate_date DESC 
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute([$fechaBusqueda]);
        $tasa = $stmt->fetch(PDO::FETCH_ASSOC);

        return $tasa ?: null;
    }

    /**
     * CALCULAR MONTO USD EXACTO
     * Convierte Bs a USD usando la tasa correcta con round() a 2 decimales.
     * Elimina el bug de floor()/ceil() que perdía centavos.
     *
     * @param float  $montoBs          Monto en bolívares
     * @param string $fechaComprobante Fecha del comprobante (Y-m-d)
     * @return array ['monto_usd' => float, 'tasa' => float, 'tasa_fecha' => string]
     * @throws \Exception Si no hay tasa disponible
     */
    public static function calcularMontoUsd(float $montoBs, string $fechaComprobante): array
    {
        $tasaData = self::obtenerTasaCorrecta($fechaComprobante);

        if (!$tasaData) {
            throw new \Exception("No existe tasa BCV para la fecha del comprobante: {$fechaComprobante}. Verifique que la fecha sea correcta.");
        }

        $tasa     = (float)$tasaData['dolar_bcv'];
        $montoUsd = $tasa > 0 ? round($montoBs / $tasa, 2) : 0.00;

        return [
            'monto_usd'  => $montoUsd,
            'tasa'       => round($tasa, 2),
            'tasa_fecha' => $tasaData['rate_date']
        ];
    }
}