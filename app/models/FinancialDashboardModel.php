<?php
/**
 * MÓDULO: FINANCIERO / DASHBOARD
 * ARCHIVO: app/models/FinancialDashboardModel.php
 * PROPÓSITO: Obtiene los indicadores clave del dashboard financiero:
 *            ingresos del mes, egresos del mes, saldo, órdenes pendientes,
 *            pagos pendientes en tesorería, tasa BCV y gráfica mensual.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class FinancialDashboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // INDICADORES PRINCIPALES
    // =========================================================================

    public function getIndicadores(): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                (SELECT IFNULL(SUM(amount_paid), 0)
                 FROM tbl_financial_student_ledger
                 WHERE MONTH(processed_at) = MONTH(NOW())
                   AND YEAR(processed_at)  = YEAR(NOW())) AS ingresos_mes,

                (SELECT IFNULL(SUM(ABS(monto_usd)), 0)
                 FROM tbl_libro_egresos
                 WHERE tipo_movimiento = 'PAGO'
                   AND MONTH(fecha) = MONTH(NOW())
                   AND YEAR(fecha)  = YEAR(NOW())) AS egresos_mes,

                (SELECT COUNT(*) FROM tbl_ordenes_pago
                 WHERE estado = 'PENDIENTE') AS ordenes_pendientes,

                (SELECT COUNT(*) FROM tbl_tesoreria_pagos
                 WHERE estado = 'PENDIENTE') AS tesoreria_pendientes,

                (SELECT dolar_bcv FROM tbl_financial_exchange_rates
                 WHERE status = 'ACTIVE'
                 ORDER BY rate_date DESC LIMIT 1) AS tasa_bcv"
        );
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // =========================================================================
    // GRÁFICA — INGRESOS VS EGRESOS (últimos 6 meses)
    // =========================================================================

    public function getGraficaMensual(): array
    {
        $meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts    = strtotime("-{$i} months");
            $mes   = (int) date('m', $ts);
            $anio  = (int) date('Y', $ts);

            $stmtI = $this->db->prepare(
                "SELECT IFNULL(SUM(amount_paid), 0)
                 FROM tbl_financial_student_ledger
                 WHERE MONTH(processed_at) = :m AND YEAR(processed_at) = :y"
            );
            $stmtI->execute([':m' => $mes, ':y' => $anio]);
            $ingreso = (float) $stmtI->fetchColumn();

            $stmtE = $this->db->prepare(
                "SELECT IFNULL(SUM(ABS(monto_usd)), 0)
                 FROM tbl_libro_egresos
                 WHERE tipo_movimiento = 'PAGO'
                   AND MONTH(fecha) = :m AND YEAR(fecha) = :y"
            );
            $stmtE->execute([':m' => $mes, ':y' => $anio]);
            $egreso = (float) $stmtE->fetchColumn();

            $meses[] = [
                'label'   => $this->getNombreMes($mes) . ' ' . $anio,
                'ingreso' => $ingreso,
                'egreso'  => $egreso,
                'saldo'   => $ingreso - $egreso,
            ];
        }
        return $meses;
    }

    // =========================================================================
    // ÚLTIMOS EGRESOS
    // =========================================================================

    public function getUltimosEgresos(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT fecha, numero_orden, concepto, destinatario, monto_usd, tipo_movimiento
             FROM tbl_libro_egresos
             ORDER BY id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function getNombreMes(int $m): string
    {
        return ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'][$m] ?? '';
    }
}