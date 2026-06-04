<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/Controllers/AdministrativeInscriptionsController_s1.php
 * PROPÓSITO: API interna para búsqueda de participantes y validación de duplicados (Paso 1).
 * VERSIÓN: 2.2.0 - FIX: Soporte para re-inscripción tras rechazo administrativo.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdministrativeInscriptionsModel;

final class AdministrativeInscriptionsController_s1 extends Controller
{
    private AdministrativeInscriptionsModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Verificación de seguridad básica
        if (empty($_SESSION['user']['id'])) {
            $this->sendJson(['error' => 'Sesión no válida o expirada'], 401);
        }
        
        $this->model = new AdministrativeInscriptionsModel();
    }

    /**
     * Búsqueda dinámica de participantes para el Paso 1
     */
    public function search(): void
    {
        try {
            $term = trim((string)($_GET['q'] ?? ''));
            if (strlen($term) < 1) {
                $this->sendJson([]);
            }
            
            $results = $this->model->searchParticipants($term);
            $this->sendJson($results);

        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'msg' => 'Error en búsqueda: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Validación de duplicidad (S1)
     * IMPORTANTE: El modelo debe ignorar registros con estatus 'RECHAZADO'
     * para permitir que el usuario vuelva a intentar inscribirse.
     */
    public function checkExisting(): void
    {
        try {
            $userId = (int)($_GET['user_id'] ?? 0);
            $offeringId = (int)($_GET['offering_id'] ?? 0);

            if ($userId <= 0 || $offeringId <= 0) {
                throw new \Exception("Parámetros de validación insuficientes (ID Usuario/Oferta).");
            }

            // El modelo filtrará por estatus activos (APROBADO, REVISION, COMPROMISO)
            $exists = $this->model->checkExistingEnrollment($userId, $offeringId);
            
            $this->sendJson([
                'success' => true,
                'exists'  => $exists,
                'message' => $exists ? 'Inscripción activa o pendiente detectada.' : 'Usuario libre para inscripción.'
            ]);

        } catch (\Throwable $e) {
            $this->sendJson(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    /**
     * Limpiador de búfer y salida JSON estricta para evitar errores de parseo (SyntaxError en JS)
     */
    private function sendJson(array $data, int $code = 200): void
    {
        // Limpiamos cualquier salida previa (notices, warnings) para que el JSON sea puro
        while (ob_get_level()) ob_end_clean(); 
        
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}