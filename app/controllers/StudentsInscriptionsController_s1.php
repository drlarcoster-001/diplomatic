<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: app/Controllers/StudentsInscriptionsController_s1.php
 * PROPÓSITO: API interna para validación de pre-requisitos e identidad del estudiante (Paso 1).
 * VERSIÓN: 1.0.1 - Implementación self-service sin buscador. Sincronización de tipos INT UNSIGNED.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\StudentsInscriptionsModel_s1;

final class StudentsInscriptionsController_s1 extends Controller
{
    private StudentsInscriptionsModel_s1 $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Verificación de seguridad: Solo usuarios autenticados
        if (empty($_SESSION['user']['id'])) {
            $this->sendJson(['ok' => false, 'msg' => 'Sesión no válida o expirada.'], 401);
        }
        
        $this->model = new StudentsInscriptionsModel_s1();
    }

    /**
     * Verifica de forma atómica si el estudiante ya está inscrito en la oferta.
     * Invocado vía AJAX al cargar el Paso 1 o intentar avanzar al Paso 2.
     */
    public function checkExisting(): void
    {
        try {
            // El ID del estudiante se toma de la sesión, no del cliente (Seguridad)
            $userId = (int)$_SESSION['user']['id'];
            $offeringId = (int)($_GET['offering_id'] ?? 0);

            if ($offeringId <= 0) {
                $this->sendJson(['ok' => false, 'msg' => 'ID de oferta académica no válido.'], 400);
            }

            $exists = $this->model->checkExistingEnrollment($userId, $offeringId);

            $this->sendJson([
                'ok' => true,
                'exists' => $exists,
                'msg' => $exists ? 'Ya posees una inscripción activa para este programa.' : 'Validación exitosa.'
            ]);

        } catch (\Throwable $e) {
            $this->sendJson([
                'ok' => false, 
                'msg' => 'Error interno de validación.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper para envío de respuestas JSON con blindaje de búfer.
     */
    private function sendJson(array $data, int $code = 200): void
    {
        // Limpiamos cualquier salida previa (espacios, errores de otros archivos)
        while (ob_get_level()) ob_end_clean(); 
        
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}