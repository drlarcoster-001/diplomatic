<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS
 * ARCHIVO: app/models/FinancialPaymentValidationsModel.php
 * PROPÓSITO: Consultas para el panel de validación y conteo de pagos pendientes.
 * VERSIÓN: 1.5.0 - FIX: Sincronización de datos (mapeo del método 'CASH' de la BD a la clave 'EFECTIVO' de la UI) y restauración del filtro PENDIENTE.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class FinancialPaymentValidationsModel {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

 public function getPendingCounts(): array {
    $counts = [
        'PAGOMOVIL' => 0,
        'ZELLE'     => 0,
        'BINANCE'   => 0,
        'EFECTIVO'  => 0
    ];

    try {
        // Buscamos solo en la tabla de pagos de estudiantes
        $sql = "SELECT method, COUNT(*) as total 
                FROM tbl_financial_payments 
                WHERE status = 'PENDING' OR status = 'PENDIENTE'
                GROUP BY method";
        
        $stmt = $this->db->query($sql);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $methodRaw = strtoupper(trim($row['method']));
            
            // Mapeo directo
            if ($methodRaw === 'CASH' || $methodRaw === 'EFECTIVO') {
                $counts['EFECTIVO'] = (int)$row['total'];
            } elseif (isset($counts[$methodRaw])) {
                $counts[$methodRaw] = (int)$row['total'];
            }
        }
    } catch (\Exception $e) {
        error_log("Error en getPendingCounts: " . $e->getMessage());
    }

    return $counts;
}
}