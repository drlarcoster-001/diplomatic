<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA
 * Archivo: app/controllers/FinancialController.php
 * Propósito: Controlador maestro para el Panel Financiero y Despachador de Operaciones de Caja.
 * VERSIÓN: 2.7.0
 * CAMBIOS:
 * 1. Unificación de rutas: 'physical' ahora redirige automáticamente a 'efectivo'.
 * 2. Limpieza de métodos obsoletos: Las vistas ahora son gestionadas por controladores especializados.
 * 3. Compatibilidad total con Bootstrap v1.9.0.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuditService;
use App\Core\Database;
use PDO;
use Exception;

final class FinancialController extends Controller
{
    /**
     * Constructor: Perímetro de seguridad.
     * Solo permite acceso a usuarios INTERNAL con rol FINANZAS, ADMIN o SUPERADMIN.
     */
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $user = $_SESSION['user'] ?? null;

        if (!$user || empty($user['id'])) {
            $this->redirect('/login');
            exit;
        }

        // Normalización para evitar fallos por mayúsculas/minúsculas en la BD
        $userType = strtoupper((string)($user['user_type'] ?? ''));
        $userRole = strtoupper((string)($user['role'] ?? ''));
        $allowedRoles = ['FINANZAS', 'ADMIN', 'SUPERADMIN'];

        if ($userType !== 'INTERNAL' || !in_array($userRole, $allowedRoles)) {
            if (ob_get_level() > 0) ob_end_clean();
            $this->redirect('/dashboard');
            exit;
        }
    }

    /**
     * Dashboard principal del Panel Financiero.
     */
    public function index(): void {
        if (ob_get_level() > 0) ob_clean();

        AuditService::log([
            'module' => 'FINANCIAL', 
            'action' => 'ACCESS',
            'description' => "Ingreso autorizado al Panel Financiero.", 
            'event_type' => 'INFO'
        ]);

        $this->view('financial/index');
    }

    /**
     * SUBMÓDULO: Bandeja de Validación (Dispatcher)
     * Maneja la visualización de las 4 tarjetas principales.
     * Si recibe un $type, redirige a la ruta oficial del Bootstrap para que el
     * controlador especializado tome el control.
     */
    public function cashOperations(?string $type = null): void {
        if (ob_get_level() > 0) ob_clean();

        // --- LÓGICA DE REDIRECCIÓN (Evita el desvío al dashboard) ---
        if ($type !== null) {
            $route = strtolower($type);
            
            // Mapeo de nombres viejos a rutas nuevas del Bootstrap
            $map = [
                'physical'  => 'efectivo',
                'cash'      => 'efectivo',
                'pagomovil' => 'pagomovil',
                'zelle'     => 'zelle',
                'binance'   => 'binance'
            ];

            $target = $map[$route] ?? $route;
            
            // Redirigimos a la ruta oficial definida en Bootstrap.php
            // Esto permite que el Router llame al controlador especializado (ej: FinancialCashEfectivoController)
            $this->redirect("/financial/cash-operations/{$target}");
            return;
        }

        // --- LÓGICA DE LA BANDEJA (Si $type es null) ---
        $db = Database::getConnection();
        
        // Contamos los pagos pendientes por método para mostrar en las tarjetas
        $sql = "SELECT method, COUNT(*) as total 
                FROM tbl_enrollments_payments 
                WHERE status = 'PENDING' 
                GROUP BY method";
        
        $stmt = $db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $counts = ['PAGOMOVIL' => 0, 'ZELLE' => 0, 'BINANCE' => 0, 'EFECTIVO' => 0];

        foreach ($results as $row) {
            $m = strtoupper((string)$row['method']);
            // Unificamos terminología de base de datos
            if ($m === 'CASH' || $m === 'PHYSICAL') $m = 'EFECTIVO';
            if (isset($counts[$m])) $counts[$m] = (int)$row['total'];
        }

        $this->view('financial/cash_operations/index', [
            'title'  => 'Operaciones de Caja',
            'counts' => $counts
        ]);
    }

    /**
     * SUBMÓDULO: Tasa de Cambio BCV
     * Nota: Si el equipo decide usar FinancialExchangeRatesController, este método puede ser removido.
     */
    public function exchange_rates(): void {
        if (ob_get_level() > 0) ob_clean();

        $db = Database::getConnection();

        // Obtener la última tasa
        $sqlLast = "SELECT dolar_bcv, euro_bcv FROM tbl_financial_exchange_rates 
                    WHERE status = 'ACTIVE' 
                    ORDER BY id DESC LIMIT 1";
        $stmtLast = $db->query($sqlLast);
        $lastRate = $stmtLast->fetch(PDO::FETCH_ASSOC);

        // Obtener historial
        $desde = $_GET['desde'] ?? null;
        $hasta = $_GET['hasta'] ?? null;

        $sqlGrid = "SELECT rate_date, dolar_bcv, euro_bcv, created_at 
                    FROM tbl_financial_exchange_rates 
                    WHERE status = 'ACTIVE'";
        
        if (!empty($desde) && !empty($hasta)) {
            $sqlGrid .= " AND rate_date BETWEEN :desde AND :hasta";
        }

        $sqlGrid .= " ORDER BY id DESC LIMIT 100";
        $stmtGrid = $db->prepare($sqlGrid);
        if (!empty($desde) && !empty($hasta)) {
            $stmtGrid->execute(['desde' => $desde, 'hasta' => $hasta]);
        } else {
            $stmtGrid->execute();
        }
        $history = $stmtGrid->fetchAll(PDO::FETCH_ASSOC);

        $this->view('financial/exchange_rates/index', [
            'title'    => 'Gestión de Tasa de Cambio',
            'last_usd' => $lastRate['dolar_bcv'] ?? 0,
            'last_eur' => $lastRate['euro_bcv'] ?? 0,
            'history'  => $history
        ]);
    }

    /**
     * Acción: Guardar nueva tasa.
     */
    public function store_rate(): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $dolar = (float)($_POST['dolar_bcv'] ?? 0);
            $euro  = (float)($_POST['euro_bcv'] ?? 0);
            $fecha = date('Y-m-d');

            if ($dolar <= 0) throw new Exception("La tasa del dólar debe ser mayor a cero.");

            $db = Database::getConnection();
            $sql = "INSERT INTO tbl_financial_exchange_rates (rate_date, dolar_bcv, euro_bcv, user_id) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([$fecha, $dolar, $euro, $userId]);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Tasa de cambio actualizada correctamente.']);
            }

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function getLogosBase64(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        $base = dirname(__DIR__, 2);
        $logoUcla     = $base . '/public/assets/uploads/logos/logo-ucla.png';
        $logoMedicina = $base . '/public/assets/uploads/logos/logo-medicina.jpg';

        echo json_encode([
            'ucla'     => file_exists($logoUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($logoUcla))     : null,
            'medicina' => file_exists($logoMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoMedicina)) : null,
        ]);
        exit;
    }
}