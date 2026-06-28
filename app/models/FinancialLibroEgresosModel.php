<?php
/**
 * MÓDULO: FINANCIERO / LIBRO DE EGRESOS
 * ARCHIVO: app/models/FinancialLibroEgresosModel.php
 * PROPÓSITO: Obtiene registros de tbl_libro_egresos con filtros por fecha,
 *            tipo y tipo_movimiento. Calcula totales de pagos y reversas.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class FinancialLibroEgresosModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // BUILD WHERE
    // =========================================================================

    private function buildWhere(array $filtros, array &$params): string
    {
        $where = "WHERE 1=1";

        if (!empty($filtros['desde'])) {
            $where .= " AND le.fecha >= :desde";
            $params[':desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where .= " AND le.fecha <= :hasta";
            $params[':hasta'] = $filtros['hasta'];
        }
        if (!empty($filtros['tipo'])) {
            $where .= " AND le.tipo = :tipo";
            $params[':tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['movimiento'])) {
            $where .= " AND le.tipo_movimiento = :movimiento";
            $params[':movimiento'] = $filtros['movimiento'];
        }
        if (!empty($filtros['search'])) {
            $where .= " AND (le.concepto LIKE :search OR le.destinatario LIKE :search OR le.numero_orden LIKE :search)";
            $params[':search'] = "%" . $filtros['search'] . "%";
        }

        return $where;
    }

    // =========================================================================
    // REGISTROS PAGINADOS
    // =========================================================================

    public function getEgresos(array $filtros, int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where  = $this->buildWhere($filtros, $params);

        $stmt = $this->db->prepare(
            "SELECT le.id, le.fecha, le.fecha_tasa, le.tipo, le.numero_orden,
                    le.concepto, le.destinatario, le.monto_usd, le.tasa_bcv,
                    le.monto_bs, le.tipo_movimiento, le.referencia_reversa_id,
                    le.created_at,
                    u.first_name AS creado_first, u.last_name AS creado_last
             FROM tbl_libro_egresos le
             LEFT JOIN tbl_users u ON u.id = le.created_by
             {$where}
             ORDER BY le.fecha DESC, le.id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countEgresos(array $filtros): int
    {
        $params = [];
        $where  = $this->buildWhere($filtros, $params);
        $stmt   = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_libro_egresos le {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // =========================================================================
    // TOTALES
    // =========================================================================

    public function getTotales(array $filtros): array
    {
        $params = [];
        $where  = $this->buildWhere($filtros, $params);

        $stmt = $this->db->prepare(
            "SELECT
                IFNULL(SUM(CASE WHEN le.tipo_movimiento = 'PAGO'   THEN ABS(le.monto_usd) ELSE 0 END), 0) AS total_pagos_usd,
                IFNULL(SUM(CASE WHEN le.tipo_movimiento = 'REVERSA' THEN le.monto_usd ELSE 0 END), 0)     AS total_reversas_usd,
                IFNULL(SUM(CASE WHEN le.tipo_movimiento = 'PAGO'   THEN ABS(le.monto_bs) ELSE 0 END), 0)  AS total_pagos_bs,
                IFNULL(SUM(CASE WHEN le.tipo_movimiento = 'REVERSA' THEN le.monto_bs ELSE 0 END), 0)       AS total_reversas_bs,
                COUNT(CASE WHEN le.tipo_movimiento = 'PAGO'   THEN 1 END) AS cant_pagos,
                COUNT(CASE WHEN le.tipo_movimiento = 'REVERSA' THEN 1 END) AS cant_reversas
             FROM tbl_libro_egresos le {$where}"
        );
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // =========================================================================
    // TODOS (para PDF sin paginación)
    // =========================================================================

    public function getAllEgresos(array $filtros): array
    {
        $params = [];
        $where  = $this->buildWhere($filtros, $params);

        $stmt = $this->db->prepare(
            "SELECT le.fecha, le.numero_orden, le.tipo, le.concepto, le.destinatario,
                    le.monto_usd, le.tasa_bcv, le.monto_bs, le.tipo_movimiento
             FROM tbl_libro_egresos le
             {$where}
             ORDER BY le.fecha DESC, le.id DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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