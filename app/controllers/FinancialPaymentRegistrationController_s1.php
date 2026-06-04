<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / REGISTRO DE PAGOS
 * ARCHIVO: app/controllers/FinancialPaymentRegistrationController_s1.php
 * PROPÓSITO: Trait especializado en la búsqueda reactiva de estudiantes (S1).
 * VERSIÓN: 2.1.0 - Validación final: Sincronización de comentarios de retorno, trim de seguridad y blindaje de búfer.
 */

declare(strict_types=1);

namespace App\Controllers;

trait FinancialPaymentRegistrationController_s1
{
    /**
     * Endpoint AJAX: Localiza estudiantes por coincidencia de nombre o documento.
     * Acceso: /financial/payment_registration/searchStudents?q=[query]
     */
    public function searchStudents(): void
    {
        // Blindaje de búfer: Limpieza profunda para asegurar que el JSON no contenga basura (BOM/Errors)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');

        try {
            // Captura de parámetros y sanitización básica
            $query = trim((string)($_GET['q'] ?? ''));
            
            // Validación de longitud mínima (Política de rendimiento de BD)
            if (mb_strlen($query) < 3) {
                echo json_encode([
                    'status' => 'success', 
                    'data' => [],
                    'message' => 'Query demasiado corto'
                ]);
                exit;
            }

            /** * Ejecución vía Modelo: El modelo retorna un array asociativo con:
             * id, first_name, last_name, cedula, avatar, student_code
             */
            $results = $this->model->searchStudents($query);

            echo json_encode([
                'status' => 'success', 
                'count'  => count($results),
                'data'   => $results
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            // Log de error interno (Sugerencia: Integrar con LoggerService)
            echo json_encode([
                'status' => 'error', 
                'message' => 'Fallo en motor de búsqueda: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}