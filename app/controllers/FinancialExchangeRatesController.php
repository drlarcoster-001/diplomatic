<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / TASAS DE CAMBIO
 * ARCHIVO: app/controllers/FinancialExchangeRatesController.php
 * PROPÓSITO: Controlador especializado para el manejo de scraping BCV, gestión de tasas y auditoría de eventos.
 * VERSIÓN: 1.1.0 - Integración de método delete con borrado físico y log de auditoría detallado.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialExchangeRatesModel;
use App\Services\AuditService;

final class FinancialExchangeRatesController extends Controller
{
    private $model;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;
        
        // Verificación de seguridad y roles
        if (!$user || !in_array($user['role'], ['FINANZAS', 'ADMIN'])) {
            $this->redirect('/dashboard');
        }
        $this->model = new FinancialExchangeRatesModel();
    }

    /**
     * Vista principal: Carga cards y grid desde el modelo.
     */
    public function index(): void {
        if (ob_get_level() > 0) ob_clean();

        $desde = $_GET['desde'] ?? null;
        $hasta = $_GET['hasta'] ?? null;
        $limit = 25; 
        $page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        // Buscamos la tasa más actual basada en FECHA
        $lastRateRow = $this->model->getLastRate();
        
        // Obtenemos conteo y datos usando la nueva función de paginación del modelo
        $totalRecords = $this->model->countAll($desde, $hasta);
        $history      = $this->model->getHistoryPaginated($limit, $offset, $desde, $hasta);

        $this->view('financial/exchange_rates/index', [
            'last_usd'   => (float)($lastRateRow['dolar_bcv'] ?? 0),
            'last_eur'   => (float)($lastRateRow['euro_bcv'] ?? 0),
            'history'    => $history,
            'pagination' => [
                'current_page' => $page,
                'total_pages'  => (int)ceil($totalRecords / $limit),
                'start_index'  => $offset + 1,
                'desde'        => $desde,
                'hasta'        => $hasta
            ]
        ]);
    }

    /**
     * AJAX: Scraper directo desde el portal del BCV.
     */
    public function fetchBCV(): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        $ch = curl_init("https://www.bcv.org.ve/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0.0.0');

        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) {
            echo json_encode(["status" => "error", "message" => "Portal BCV no disponible"]);
            exit;
        }


       // 👇 AGREGA ESTO TEMPORALMENTE para ver qué está trayendo
    file_put_contents(__DIR__ . '/bcv_debug.html', $html);


        $dolar = $this->extraer($html, 'dolar');
        $euro  = $this->extraer($html, 'euro');

        echo json_encode([
            "status" => "success",
            "dolar"  => $dolar,
            "euro"   => $euro,
            "fecha"  => date('d/m/Y')
        ]);
        exit;
    }

 private function extraer($html, $id) {
    // Busca el div con el id, luego cualquier <strong> con o sin atributos
    preg_match('/<div[^>]+id="' . $id . '"[^>]*>.*?<strong[^>]*>\s*([\d.,]+)\s*<\/strong>/s', $html, $matches);
    if (isset($matches[1])) {
        $val = str_replace('.', '', $matches[1]);
        return (float)str_replace(',', '.', $val);
    }
    return 0;
}

    /**
     * AJAX: Persistencia de la tasa consultada.
     */
    public function store(): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');

        $data = [
            'dolar'   => (float)$_POST['dolar_bcv'],
            'euro'    => (float)$_POST['euro_bcv'],
            'user_id' => $_SESSION['user']['id']
        ];

        if ($this->model->save($data)) {
            AuditService::log([
                'module'      => 'FINANCIAL_EXCHANGE_RATES',
                'action'      => 'CREATE_RECORD',
                'description' => "Nueva Tasa Registrada: USD {$data['dolar']} / EUR {$data['euro']}",
                'event_type'  => 'NORMAL'
            ]);
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al guardar en la base de datos"]);
        }
        exit;
    }

    /**
     * AJAX: Borrado físico de registro con auditoría de evento.
     */
    public function delete(): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');

        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new \Exception("ID de registro no válido.");

            // 1. Obtenemos los datos antes de borrar para el log de auditoría
            $oldData = $this->model->getById($id);
            if (!$oldData) throw new \Exception("El registro que intenta eliminar no existe.");

            // 2. Ejecutamos el borrado físico
            if ($this->model->delete($id)) {
                
                // 3. Registramos en el módulo de eventos
                AuditService::log([
                    'module'      => 'FINANCIAL_EXCHANGE_RATES',
                    'action'      => 'DELETE_RECORD',
                    'description' => "ELIMINACIÓN FÍSICA - Registro ID: $id | Fecha de la Tasa: {$oldData['rate_date']} | USD: {$oldData['dolar_bcv']} | EUR: {$oldData['euro_bcv']}",
                    'event_type'  => 'DANGER'
                ]);

                echo json_encode(["status" => "success", "message" => "Registro eliminado correctamente."]);
            } else {
                throw new \Exception("No se pudo completar la eliminación en la base de datos.");
            }

        } catch (\Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Devuelve la tasa de cambio para una fecha específica (Formato JSON)
     */
    /**
     * Endpoint para consultar tasa por fecha desde el Front-end.
     * Utiliza el modelo para la lógica de base de datos (MVC).
     */
    public function getRateByDate()
    {
        header('Content-Type: application/json');
        
        $date = $_GET['date'] ?? date('Y-m-d');
        $tasa = 0.00;

        try {
            // Instanciamos el modelo donde tienes tu función
            // (Ajusta el namespace si tu modelo se llama distinto)
            $result = $this->model->getRateByDate($date);

            // Como devuelve un array (FETCH_ASSOC), extraemos el dólar
            if ($result && isset($result['dolar_bcv'])) {
                $tasa = (float) $result['dolar_bcv'];
            }

            if ($tasa > 0) {
                echo json_encode(['success' => true, 'tasa' => $tasa]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'No hay tasa asignada para esta fecha'
                ]);
            }
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
        }
        exit;
    }


}