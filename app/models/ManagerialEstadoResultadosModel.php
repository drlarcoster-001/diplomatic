<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / ESTADO DE RESULTADOS
 * ARCHIVO: app/models/ManagerialEstadoResultadosModel.php
 * PROPÓSITO: Obtiene ingresos desde tbl_financial_student_ledger y
 *            egresos desde tbl_libro_egresos filtrados por fecha.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ManagerialEstadoResultadosModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getIngresos(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT IFNULL(SUM(amount_paid), 0) AS total,
                    COUNT(*) AS cantidad
             FROM tbl_financial_student_ledger
             WHERE status != 'ANULADO'
               AND DATE(processed_at) BETWEEN :desde AND :hasta"
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'cantidad' => 0];
    }

    public function getEgresos(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                IFNULL(SUM(CASE WHEN tipo = 'NOMINA'    AND tipo_movimiento = 'PAGO' THEN ABS(monto_usd) ELSE 0 END), 0) AS nomina,
                IFNULL(SUM(CASE WHEN tipo = 'PROVEEDOR' AND tipo_movimiento = 'PAGO' THEN ABS(monto_usd) ELSE 0 END), 0) AS proveedor,
                IFNULL(SUM(CASE WHEN tipo = 'DIRECTA'   AND tipo_movimiento = 'PAGO' THEN ABS(monto_usd) ELSE 0 END), 0) AS directa,
                IFNULL(SUM(CASE WHEN tipo_movimiento = 'REVERSA' THEN monto_usd ELSE 0 END), 0) AS reversas
             FROM tbl_libro_egresos
             WHERE fecha BETWEEN :desde AND :hasta"
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['nomina' => 0, 'proveedor' => 0, 'directa' => 0, 'reversas' => 0];
    }

    public function getPeriodos(): array
        {
            $stmt = $this->db->query(
                "SELECT id, nombre, fecha_inicio, fecha_fin, estado
                FROM tbl_periodos_cohorte
                WHERE is_active = 1 ORDER BY id DESC"
            );
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
}