<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / REGISTRO DE PAGOS
 * ARCHIVO: app/controllers/FinancialPaymentRegistrationController_s2.php
 * PROPÓSITO: Trait especializado en la carga de diplomados vinculados al estudiante (S2).
 * VERSIÓN: 2.1.0 - Validación final: Sincronización perfecta con el Modelo y el JS Frontend.
 */

declare(strict_types=1);

namespace App\Controllers;

trait FinancialPaymentRegistrationController_s2
{
    /**
     * Endpoint AJAX: Retorna los diplomados asociados al estudiante seleccionado.
     * Invocado por: financial_payment_registration_s2.js
     * Ruta: /financial/payment_registration/getOfferingsByUser?user_id=[ID]
     */
    public function getOfferingsByUser(): void
    {
        // Blindaje de búfer: Eliminación de cualquier salida previa para garantizar JSON puro
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');

        try {
            // Captura y tipado del parámetro de entrada
            $userId = (int)($_GET['user_id'] ?? 0);

            if ($userId <= 0) {
                throw new \Exception("Identificador de estudiante inválido.");
            }

            /** * Ejecución vía Modelo:
             * Se consultan las matrículas activas del usuario en tbl_student_matriculations.
             * Conecta con la función del modelo que trae offering_id y total_pending.
             */
            $offerings = $this->model->getStudentEnrollments($userId);

            echo json_encode([
                'status' => 'success',
                'count'  => count($offerings),
                'data'   => $offerings
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            // Respuesta de error controlada para la API fetch
            echo json_encode([
                'status'  => 'error',
                'message' => 'Fallo al recuperar programas: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}