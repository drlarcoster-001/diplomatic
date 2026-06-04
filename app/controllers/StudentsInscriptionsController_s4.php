<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: app/Controllers/StudentsInscriptionsController_s4.php
 * PROPÓSITO: Sanitización de datos financieros y empaquetado de metadatos de pago.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

final class StudentsInscriptionsController_s4 
{
    public function sanitizeFinancialData(array $data, string $method): array 
    {
        $metadata = ['canal' => $method];
        
        // Limpiamos montos (Eliminar puntos de miles y cambiar coma por punto)
        $cleanAmount = function($val) {
            return (float) str_replace(['.', ','], ['', '.'], $val);
        };

        if ($method === 'ZELLE') {
            $metadata['monto'] = $cleanAmount($data['z_amount'] ?? '0');
            $metadata['referencia'] = $data['z_ref'] ?? '';
        } elseif ($method === 'PAGOMOVIL') {
            $metadata['monto'] = $cleanAmount($data['pm_amount'] ?? '0');
            $metadata['referencia'] = $data['pm_ref'] ?? '';
        }

        return $metadata;
    }
}