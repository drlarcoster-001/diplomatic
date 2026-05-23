<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / EXCEPCIONES
 * ARCHIVO: app/controllers/AdministrativeAnnulmentsController.php
 * PROPÓSITO: Gestión de reversión operativa de inscripciones, eliminación de registros de matrícula y visor de detalles para confirmación.
 * VERSIÓN: 1.2.0 - Implementación de soporte para modal de detalles, fix de filtros inteligentes y blindaje de búfer estricto.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AdministrativeAnnulmentsModel;
use Throwable;

final class AdministrativeAnnulmentsController extends Controller
{
    private AdministrativeAnnulmentsModel $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Blindaje de seguridad: Solo ADMIN tiene acceso a procesos de reversión
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit();
        }

        $this->model = new AdministrativeAnnulmentsModel();
    }

    /**
     * Carga la interfaz principal del módulo de cancelaciones.
     */
    public function index(): void {
        $this->view('administrative/annulments/index', [
            'title' => 'Cancelar Inscripciones'
        ]);
    }

    /**
     * Endpoint para la Grid dinámica. 
     * Responde a la búsqueda inteligente por cédula, nombre o diplomado.
     */
    public function list(): void {
        $term = $_GET['term'] ?? '';
        try {
            $data = $this->model->getApprovedEnrollments($term);
            $this->jsonFinal($data);
        } catch (Throwable $e) {
            $this->jsonFinal(['error' => 'Error al listar inscripciones: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint para el Popup (Modal). 
     * Extrae la ficha completa del usuario antes de la anulación.
     */
    public function getDetails(): void {
        $id = (int)($_GET['id'] ?? 0);
        
        try {
            $data = $this->model->getEnrollmentDetail($id);
            
            if (!$data) {
                $this->jsonFinal(['error' => 'No se encontraron datos para la inscripción solicitada.'], 404);
            }
            
            $this->jsonFinal($data);
        } catch (Throwable $e) {
            $this->jsonFinal(['error' => 'Fallo en la consulta de detalles.'], 500);
        }
    }

    /**
     * Ejecuta el proceso transaccional de anulación.
     * Recibe JSON vía Body (Fetch API).
     */
    public function process(): void {
        // Captura de salida para evitar interferencias en el JSON
        while (ob_get_level() > 0) ob_end_clean();

        $jsonInput = file_get_contents('php://input');
        $postData = json_decode($jsonInput, true);
        
        $enrollmentId = (int)($postData['enrollment_id'] ?? 0);

        if ($enrollmentId <= 0) {
            $this->jsonFinal(['success' => false, 'message' => 'Identificador de inscripción inválido.'], 400);
        }

        try {
            // El modelo valida pagos y ejecuta la transacción ACID (Delete + Update)
            $result = $this->model->cancelIncription($enrollmentId);
            $this->jsonFinal($result);
        } catch (Throwable $e) {
            $this->jsonFinal([
                'success' => false, 
                'message' => 'Error crítico en el servidor al procesar la cancelación.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Garantiza una salida JSON pura, limpiando cualquier residuo del búfer.
     * Previene el error de parseo en el frontend "Unexpected token <".
     */
    private function jsonFinal(array $payload, int $code = 200): void {
        while (ob_get_level() > 0) ob_end_clean();
        
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            echo json_encode(['error' => 'Error de codificación interna']);
        }
        exit;
    }
}