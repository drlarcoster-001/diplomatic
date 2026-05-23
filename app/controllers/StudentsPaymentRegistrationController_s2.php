<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / REGISTRO DE PAGOS
 * ARCHIVO: app/controllers/StudentsPaymentRegistrationController_s2.php
 * PROPÓSITO: Trait especializado en la carga de diplomados vinculados al estudiante logueado (S2).
 * VERSIÓN: 1.0.0 - FEATURE: Extracción segura de ID desde sesión para evitar inyección de parámetros.
 */

declare(strict_types=1);

namespace App\Controllers;

trait StudentsPaymentRegistrationController_s2
{
    /**
     * Endpoint AJAX: Retorna los diplomados asociados al estudiante en sesión.
     * Invocado por: students_payment_registration_s2.js
     * Ruta: /students/payment_registration/getOfferingsByUser
     */
    public function getOfferingsByUser(): void
    {
        // Blindaje de búfer: Eliminación de cualquier salida previa para garantizar JSON puro
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');

        try {
            /** * BLINDAJE DE IDENTIDAD: 
             * En autogestión, el ID no viene por GET, se toma de la sesión activa.
             */
            $userId = (int)($_SESSION['user']['id'] ?? 0);

            if ($userId <= 0) {
                throw new \Exception("Sesión inválida o expirada. Por favor, inicie sesión nuevamente.");
            }

            /** * Ejecución vía Modelo:
             * Se consultan las matrículas activas del usuario logueado.
             * Retorna: offering_id, diploma_name, cohort_name, total_pending.
             */
            $offerings = $this->model->getStudentEnrollments($userId);

            echo json_encode([
                'status' => 'success',
                'count'  => count($offerings),
                'data'   => $offerings
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            // Respuesta de error controlada para la interfaz del alumno
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al cargar sus programas: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}