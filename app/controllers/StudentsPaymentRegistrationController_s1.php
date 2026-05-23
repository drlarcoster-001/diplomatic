<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / REGISTRO DE PAGOS
 * ARCHIVO: app/controllers/StudentsPaymentRegistrationController_s1.php
 * PROPÓSITO: Trait para la recuperación automática de la identidad del estudiante (S1).
 * VERSIÓN: 1.0.1 - FIX: Refuerzo de captura de sesión para evitar "Error de Identidad".
 */

declare(strict_types=1);

namespace App\Controllers;

trait StudentsPaymentRegistrationController_s1
{
    /**
     * Endpoint AJAX: Recupera los datos del estudiante logueado para el Paso 1.
     * Acceso: /students/payment_registration/getStudentData
     */
    public function getStudentData(): void
    {
        // Blindaje de búfer: Limpieza absoluta para evitar que basura en el output rompa el JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');

        try {
            // Aseguramos que la sesión esté iniciada (Blindaje preventivo)
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            /** * CAPTURA DE IDENTIDAD: 
             * Se verifica la existencia del ID en la sesión del usuario.
             */
            $userId = (int)($_SESSION['user']['id'] ?? 0);

            if ($userId === 0) {
                throw new \Exception("Su sesión ha expirado o es inválida. Por favor, vuelva a ingresar al sistema.");
            }

            /** * Ejecución vía Modelo: 
             * Se buscan los datos del estudiante (cedula, code, nombre) vinculados al ID de usuario.
             */
            $data = $this->model->getStudentDataById($userId);

            if (!$data) {
                throw new \Exception("No se encontró un perfil de estudiante asociado a su cuenta de usuario.");
            }

            // Retorno exitoso
            echo json_encode([
                'status' => 'success', 
                'data'   => $data
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            // Este mensaje es el que captura el Swal.fire de "Error de Identidad"
            echo json_encode([
                'status' => 'error', 
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}