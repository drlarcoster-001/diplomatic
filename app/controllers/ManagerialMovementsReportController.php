<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / REPORTE DE MOVIMIENTOS
 * ARCHIVO: app/controllers/ManagerialMovementsReportController.php
 * PROPÓSITO: Controlador maestro para la gestión de trazabilidad 360°.
 * Maneja la carga de vistas, filtros de búsqueda y procesamiento de datos dinámicos.
 * VERSIÓN: 3.5.0 - FIX: Sincronización con el Modelo 3.5.0 (Data Plana).
 * Soporta la nueva lógica de Abonos Parciales y Observaciones dinámicas.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ManagerialMovementsReportModel;
use App\Core\Controller;

class ManagerialMovementsReportController extends Controller 
{
    private $model;

    public function __construct() {
        // Inicializamos el modelo de trazabilidad dinámica en su versión más reciente.
        $this->model = new ManagerialMovementsReportModel();
    }

    /**
     * Carga la interfaz principal del reporte (Helium UI).
     * Inyecta la lista de diplomados únicos para el selector de filtros globales.
     */
    public function index() {
        $periodoId = (int) ($_GET['periodo_id'] ?? 0);
        $data = [
            'title'      => 'Reporte Maestro de Movimientos',
            'offerings'  => $periodoId
                ? $this->model->getOfferingsByPeriodo($periodoId)
                : $this->model->getOfferings(),
            'periodos'   => $this->model->getPeriodos(),
            'periodoId'  => $periodoId,
        ];
        
        // Renderizado de la vista principal del módulo
        $this->view('managerial/movements_report/index', $data);
    }

    /**
     * Endpoint AJAX: loadData
     * Procesa la matriz de movimientos financieros con filtros dinámicos.
     * Garantiza una salida JSON limpia para el consumo del JavaScript.
     */
    public function loadData() {
        // 1. LIMPIEZA DE BÚFER (CRÍTICO)
        // Evita que basura de PHP o espacios en blanco corrompan el parseo del JSON en el frontend.
        if (ob_get_level()) {
            ob_end_clean();
        }

        // 2. CAPTURA DE FILTROS DESDE LA PETICIÓN GET
        $filters = [
            'search'          => $_GET['search'] ?? '',
            'offering_id'     => $_GET['offering_id'] ?? 'ALL',
            'group_id'        => $_GET['group_id'] ?? 'ALL',
            'academic_status' => $_GET['academic_status'] ?? 'ALL'
        ];

        // 3. PARÁMETROS DE PAGINACIÓN
        $page   = (int)($_GET['page'] ?? 1);
        $limit  = (int)($_GET['limit'] ?? 25);
        $offset = ($page - 1) * $limit;

        try {
            // Consulta al modelo v3.5.0: Devuelve headers (Conceptos) y data (Data Plana con Observaciones).
            $report = $this->model->getReportData($filters, $limit, $offset);

            // 4. RESPUESTA ESTANDARIZADA
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'success',
                'headers' => $report['headers'],
                'data'    => $report['data'],
                'page'    => $page,
                'total'   => count($report['data']) // Informativo para la UI
            ]);
            exit; // Blindaje final del stream de salida.

        } catch (\Exception $e) {
            // En caso de error, devolvemos un 500 con el mensaje para facilitar el debug al equipo.
            header('Content-Type: application/json', true, 500);
            echo json_encode([
                'status'  => 'error',
                'message' => "Error en Controlador: " . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Endpoint AJAX: getGroupsByOffering
     * Carga reactiva de grupos basada en el diplomado seleccionado.
     */
    public function getGroupsByOffering() {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $offeringId = (int)($_GET['id'] ?? 0);
        
        try {
            $groups = $this->model->getGroupsByOffering($offeringId);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($groups);
            exit;
            
        } catch (\Exception $e) {
            header('Content-Type: application/json', true, 500);
            echo json_encode([
                'error' => "Error al obtener grupos: " . $e->getMessage()
            ]);
            exit;
        }
    }
}