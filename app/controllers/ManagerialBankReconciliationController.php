<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / AUDITORÍA BANCARIA
 * ARCHIVO: app/controllers/ManagerialBankReconciliationController.php
 * PROPÓSITO: Controlador maestro para auditar movimientos bancarios (CSV) vs Sistema (Inscripciones y Cuotas).
 * VERSIÓN: 1.5.1 - Fix KPIs: Sincronización de conteo de registros conciliados para tablero gerencial.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ManagerialBankReconciliationModel;
use Throwable;
use Exception;

final class ManagerialBankReconciliationController extends Controller
{
    private ManagerialBankReconciliationModel $reconciliationModel;

    /**
     * Constructor: Inicializa el modelo y asegura la sesión administrativa.
     */
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Verificación de rango y tipo de usuario para acceso administrativo
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['user_type'] !== 'INTERNAL') {
            $this->redirect('/login');
            exit;
        }

        $this->reconciliationModel = new ManagerialBankReconciliationModel();
    }

    /**
     * 1. CARGA LA INTERFAZ PRINCIPAL
     * Proporciona las fechas por defecto del mes actual para la carga inicial.
     */
    public function index(): void {
        // Blindaje de búfer para evitar el error "Unexpected token <" al renderizar la vista
        if (ob_get_level() > 0) ob_end_clean(); 
        
        $this->view('managerial/bank_reconciliation/index', [
            'default_date_from' => date('Y-m-01'),
            'default_date_to'   => date('Y-m-t')
        ]);
    }

    /**
     * 2. MOTOR DE DATOS (JSON)
     * Procesa los filtros de Etapa Financiera, Estatus de Sistema y Conciliación.
     */
    public function getReconciliationData(): void {
        // Limpieza absoluta de búfer para asegurar un JSON puro
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // Sincronización de filtros con los inputs de la Vista
            $filters = [
                'date_from'           => !empty($_GET['date_from']) ? trim($_GET['date_from']) : null,
                'date_to'             => !empty($_GET['date_to']) ? trim($_GET['date_to']) : null,
                'search'              => !empty($_GET['search']) ? trim($_GET['search']) : '', 
                'etapa_financiera'    => !empty($_GET['etapa_financiera']) ? trim($_GET['etapa_financiera']) : 'ALL',
                'status_sistema'      => !empty($_GET['status_sistema']) ? trim($_GET['status_sistema']) : 'ALL',
                'status_conciliacion' => !empty($_GET['status_conciliacion']) ? trim($_GET['status_conciliacion']) : 'ALL'
            ];

            // Llamada al modelo con los parámetros de la Matriz Maestra
            $data = $this->reconciliationModel->getReconciliationMatrix($filters);

            /**
             * CÁLCULO DE KPIs DE AUDITORÍA:
             * total_banco: Monto real cargado desde el TPago (fuente de verdad).
             * total_conciliado: Montos que cruzaron exitosamente con el sistema.
             * total_huerfano: Montos en banco que no tienen registro en el sistema.
             * conteo_huerfanos: Cantidad de transacciones sin identificar.
             * conteo_conciliados: Cantidad de transacciones que sí hicieron match.
             */
            $kpis = [
                'total_banco'        => 0,
                'total_conciliado'   => 0,
                'total_huerfano'     => 0,
                'conteo_huerfanos'   => 0,
                'conteo_conciliados' => 0 
            ];

            if (!empty($data)) {
                foreach ($data as $row) {
                    $monto = (float)($row['monto_banco'] ?? 0);
                    
                    // Solo sumamos al total lo que efectivamente existe en el banco
                    $kpis['total_banco'] += $monto;

                    // IMPORTANTE: La validación del string debe coincidir con el CASE del Modelo
                    if ($row['estatus_conciliacion'] === '✅ CONCILIADO') {
                        $kpis['total_conciliado'] += $monto;
                        $kpis['conteo_conciliados']++; // Incremento de registros conciliados exitosos
                    } else {
                        // Es un pago huérfano (No encontrado en sistema)
                        $kpis['total_huerfano'] += $monto;
                        $kpis['conteo_huerfanos']++;
                    }
                }
            }

            echo json_encode([
                'ok'   => true, 
                'data' => $data, 
                'kpis' => $kpis
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            // Manejo de errores con código 400 para facilitar depuración en JS
            http_response_code(400);
            echo json_encode([
                'ok'      => false, 
                'message' => 'Error de Auditoría: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Redirección segura con soporte para la subcarpeta /diplomatic/public/
     */
    protected function redirect(string $path): void {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $baseDir = str_replace('/index.php', '', $scriptName);
        $urlBase = (strpos($baseDir, 'public') === false) ? $baseDir . '/public' : $baseDir;
        header("Location: " . $urlBase . $path);
        exit;
    }
}