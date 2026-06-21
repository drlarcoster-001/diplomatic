<?php
/**
 * MÓDULO: FINANCIERO / TESORERÍA
 * ARCHIVO: app/models/FinancialTesoreriaModel.php
 * PROPÓSITO: Ejecución real de pagos sobre órdenes de pago APROBADAS. Marca
 *            como PAGADO con los datos del medio de pago (efectivo con
 *            arqueo, transferencia o pago móvil con comprobante), y
 *            sincroniza el estado con tbl_ordenes_pago. También permite
 *            reversar de vuelta a Órdenes de Pago si algo está mal antes
 *            de pagar.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class FinancialTesoreriaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // LISTADO (INDEX)
    // =========================================================================

    private function buildWhere(string $search, string $estado, array &$params): string
    {
        $where = "WHERE 1=1";
        if ($estado !== '') {
            $where .= " AND tp.estado = :estado";
            $params[':estado'] = $estado;
        }
        if ($search !== '') {
            $where .= " AND (op.numero_orden LIKE :search
                          OR p.first_name LIKE :search OR p.last_name LIKE :search
                          OR pr.nombre LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        return $where;
    }

    public function getPagos(string $search, string $estado, int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where  = $this->buildWhere($search, $estado, $params);

        $sql = "SELECT tp.id, tp.estado, tp.medio_pago, tp.paid_at, tp.created_at,
                       op.id AS orden_id, op.numero_orden, op.tipo, op.monto_usd, op.monto_bs, op.fecha_pago,
                       CASE WHEN op.tipo = 'NOMINA' THEN CONCAT(p.last_name, ', ', p.first_name)
                            ELSE pr.nombre END AS destinatario
                FROM tbl_tesoreria_pagos tp
                INNER JOIN tbl_ordenes_pago op ON tp.orden_pago_id = op.id
                LEFT JOIN tbl_personal p   ON op.personal_id = p.id
                LEFT JOIN tbl_proveedores pr ON op.proveedor_id = pr.id
                {$where}
                ORDER BY tp.created_at ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPagos(string $search, string $estado): int
    {
        $params = [];
        $where  = $this->buildWhere($search, $estado, $params);
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_tesoreria_pagos tp
             INNER JOIN tbl_ordenes_pago op ON tp.orden_pago_id = op.id
             LEFT JOIN tbl_personal p   ON op.personal_id = p.id
             LEFT JOIN tbl_proveedores pr ON op.proveedor_id = pr.id
             {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // =========================================================================
    // DETALLE
    // =========================================================================

    public function getPagoById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT tp.*,
                    op.id AS orden_id, op.numero_orden, op.tipo, op.concepto, op.fecha_pago,
                    op.monto_usd, op.monto_bs, op.tasa_bcv,
                    CASE WHEN op.tipo = 'NOMINA' THEN CONCAT(p.last_name, ', ', p.first_name)
                         ELSE pr.nombre END AS destinatario,
                    COALESCE(p.banco, pr.banco) AS banco_origen,
                    COALESCE(p.numero_cuenta, pr.numero_cuenta) AS cuenta_origen,
                    COALESCE(p.titular_cuenta, pr.titular_cuenta) AS titular_origen,
                    COALESCE(p.telefono_pago_movil, pr.telefono_pago_movil) AS telefono_origen,
                    COALESCE(p.banco_pago_movil, pr.banco_pago_movil) AS banco_movil_origen
             FROM tbl_tesoreria_pagos tp
             INNER JOIN tbl_ordenes_pago op ON tp.orden_pago_id = op.id
             LEFT JOIN tbl_personal p   ON op.personal_id = p.id
             LEFT JOIN tbl_proveedores pr ON op.proveedor_id = pr.id
             WHERE tp.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // ACCIONES
    // =========================================================================

    public function marcarComoPagado(int $id, int $ordenPagoId, array $datos, int $userId): void
    {
        $this->db->prepare(
            "UPDATE tbl_tesoreria_pagos
             SET estado = 'PAGADO', medio_pago = :medio_pago, moneda_efectivo = :moneda_efectivo,
                 arqueo_detalle = :arqueo_detalle, banco = :banco, cuenta = :cuenta,
                 telefono = :telefono, nombre_destinatario = :nombre_destinatario,
                 referencia = :referencia, comprobante_path = :comprobante_path,
                 paid_at = NOW(), paid_by = :uid
             WHERE id = :id"
        )->execute([
            ':medio_pago' => $datos['medio_pago'], ':moneda_efectivo' => $datos['moneda_efectivo'],
            ':arqueo_detalle' => $datos['arqueo_detalle'], ':banco' => $datos['banco'],
            ':cuenta' => $datos['cuenta'], ':telefono' => $datos['telefono'],
            ':nombre_destinatario' => $datos['nombre_destinatario'], ':referencia' => $datos['referencia'],
            ':comprobante_path' => $datos['comprobante_path'], ':uid' => $userId, ':id' => $id,
        ]);

        $this->db->prepare("UPDATE tbl_ordenes_pago SET estado = 'PAGADA' WHERE id = :id")
                 ->execute([':id' => $ordenPagoId]);
    }

    public function reversarAOrdenPago(int $id, int $ordenPagoId): void
    {
        $this->db->prepare("DELETE FROM tbl_tesoreria_pagos WHERE id = :id")->execute([':id' => $id]);

        $this->db->prepare(
            "UPDATE tbl_ordenes_pago
             SET estado = 'PENDIENTE', aprobado_by = NULL, aprobado_at = NULL
             WHERE id = :id"
        )->execute([':id' => $ordenPagoId]);
    }
}