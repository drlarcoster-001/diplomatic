<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / EXCEPCIONES
 * ARCHIVO: app/controllers/AdministrativeReactivationsController.php
 * PROPÓSITO: Orquestación de la reactivación masiva de cohortes (Apertura de matrícula).
 * VERSIÓN: 2.1.3 - Fix: Manejo estricto de parámetros GET en manage() y blindaje de búfer para evitar redirecciones al dashboard.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdministrativeReactivationsModel;
use Throwable;

final class AdministrativeReactivationsController extends Controller
{
    private AdministrativeReactivationsModel $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Blindaje de seguridad: Solo administradores autorizados
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit();
        }

        $this->model = new AdministrativeReactivationsModel();
    }

    /**
     * Renderiza el panel principal con las tarjetas de diplomados/cohortes.
     * Ruta: /administrative/reactivations
     */
    public function index(): void {
        try {
            $cohorts = $this->model->getCohortsForReactivation();
            $this->view('administrative/reactivations/index', [
                'title'   => 'Reactivación Académica',
                'cohorts' => $cohorts
            ]);
        } catch (Throwable $e) {
            error_log("Error en ReactivationsController@index: " . $e->getMessage());
            $this->view('errors/500');
        }
    }

    /**
     * Renderiza el listado de estudiantes dentro de un diplomado específico.
     * Ruta: /administrative/reactivations/manage?id=X
     */
    public function manage(): void {
        // Capturamos el ID de la oferta académica
        $id = (int)($_GET['id'] ?? 0);

        // Si no hay ID válido, volvemos a la lista de diplomados en lugar de ir al dashboard
        if ($id <= 0) {
            header('Location: /diplomatic/public/administrative/reactivations');
            exit();
        }

        try {
            $students = $this->model->getStudentsByOffering($id);
            
            $this->view('administrative/reactivations/manage', [
                'title'       => 'Gestión de Cohorte',
                'students'    => $students,
                'offering_id' => $id
            ]);
        } catch (Throwable $e) {
            error_log("Error en ReactivationsController@manage: " . $e->getMessage());
            header('Location: /diplomatic/public/administrative/reactivations');
            exit();
        }
    }

    /**
     * API: Procesa la reactivación masiva de toda la cohorte.
     * Recibe JSON vía Body.
     */
    public function processMassive(): void {
        try {
            $jsonInput = file_get_contents('php://input');
            $postData = json_decode($jsonInput, true);
            $id = (int)($postData['offering_id'] ?? 0);

            if ($id <= 0) {
                $this->jsonFinal(['success' => false, 'message' => 'ID de oferta no válido.'], 400);
            }

            $result = $this->model->reactivateFullCohort($id);
            $this->jsonFinal($result);

        } catch (Throwable $e) {
            error_log("Fallo crítico en processMassive: " . $e->getMessage());
            $this->jsonFinal([
                'success' => false, 
                'message' => 'Fallo crítico en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cierre de salida: Limpia el búfer y garantiza JSON puro.
     * Previene el error "Unexpected token <" al eliminar cualquier eco o advertencia previa.
     */
    private function jsonFinal(array $payload, int $code = 200): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error de codificación JSON interna.']);
        }
        exit;
    }
}