<?php
/**
 * MÓDULO: FINANCIERO / ÓRDENES DE PAGO
 * ARCHIVO: app/models/FinancialOrdenesPagoModel.php
 * PROPÓSITO: Módulo integrador de solo revisión/aprobación de órdenes de pago
 *            generadas por Nómina y Pagos a Proveedores. Aprobar crea el
 *            registro asociado en tbl_tesoreria_pagos. Rechazar requiere nota
 *            y es recuperable. Anular requiere contraseña y es definitivo.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class FinancialOrdenesPagoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // LISTADO (INDEX)
    // =========================================================================

    private function buildWhere(string $search, string $tipo, string $estado, array &$params): string
    {
        $where = "WHERE 1=1";

        if ($tipo !== '') {
            $where .= " AND op.tipo = :tipo";
            $params[':tipo'] = $tipo;
        }
        if ($estado !== '') {
            $where .= " AND op.estado = :estado";
            $params[':estado'] = $estado;
        }
        if ($search !== '') {
            $where .= " AND (op.numero_orden LIKE :search
                          OR p.first_name LIKE :search OR p.last_name LIKE :search
                          OR pr.nombre LIKE :search OR op.concepto LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        return $where;
    }

    public function getOrdenes(string $search, string $tipo, string $estado, int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where  = $this->buildWhere($search, $tipo, $estado, $params);

        $sql = "SELECT op.id, op.numero_orden, op.tipo, op.estado, op.monto_usd, op.monto_bs,
                       op.tasa_bcv, op.fecha_pago, op.concepto, op.created_at,
                       CASE WHEN op.tipo = 'NOMINA' THEN CONCAT(p.last_name, ', ', p.first_name)
                            ELSE pr.nombre END AS destinatario,
                       CASE WHEN op.tipo = 'NOMINA' THEN n.nombre
                            WHEN op.tipo = 'PROVEEDOR' THEN pp.numero_pago
                            ELSE 'Orden directa' END AS documento_origen
                FROM tbl_ordenes_pago op
                LEFT JOIN tbl_personal p   ON op.personal_id = p.id
                LEFT JOIN tbl_proveedores pr ON op.proveedor_id = pr.id
                LEFT JOIN tbl_nominas n    ON op.nomina_id = n.id
                LEFT JOIN tbl_pagos_proveedores pp ON op.pago_proveedor_id = pp.id
                {$where}
                ORDER BY op.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countOrdenes(string $search, string $tipo, string $estado): int
    {
        $params = [];
        $where  = $this->buildWhere($search, $tipo, $estado, $params);

        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM tbl_ordenes_pago op
             LEFT JOIN tbl_personal p   ON op.tipo = 'NOMINA'    AND op.personal_id = p.id
             LEFT JOIN tbl_proveedores pr ON op.tipo = 'PROVEEDOR' AND op.proveedor_id = pr.id
             {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // =========================================================================
    // DETALLE
    // =========================================================================

    public function getOrdenById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT op.*,
                    CASE WHEN op.tipo = 'NOMINA' THEN CONCAT(p.last_name, ', ', p.first_name)
                         ELSE pr.nombre END AS destinatario,
                    CASE WHEN op.tipo = 'NOMINA' THEN p.document_id
                         ELSE pr.rif_cedula END AS destinatario_doc,
                    CASE WHEN op.tipo = 'NOMINA' THEN n.nombre
                         WHEN op.tipo = 'PROVEEDOR' THEN pp.numero_pago
                         ELSE 'Orden directa' END AS documento_origen,
                    pr.banco, pr.numero_cuenta, pr.titular_cuenta, pr.tipo_cuenta,
                    pr.telefono_pago_movil, pr.banco_pago_movil, pr.cedula_pago_movil
             FROM tbl_ordenes_pago op
             LEFT JOIN tbl_personal p   ON op.personal_id = p.id
             LEFT JOIN tbl_proveedores pr ON op.proveedor_id = pr.id
             LEFT JOIN tbl_nominas n    ON op.nomina_id = n.id
             LEFT JOIN tbl_pagos_proveedores pp ON op.pago_proveedor_id = pp.id
             WHERE op.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // ÓRDENES DE PAGO DIRECTAS
    // =========================================================================

    public function getProveedoresActivos(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, rif_cedula FROM tbl_proveedores WHERE is_active = 1 ORDER BY nombre ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTasaBcvActual(): float
    {
        $stmt = $this->db->prepare(
            "SELECT dolar_bcv FROM tbl_financial_exchange_rates
             WHERE status = 'ACTIVE' ORDER BY rate_date DESC LIMIT 1"
        );
        $stmt->execute();
        $val = $stmt->fetchColumn();
        return $val !== false ? (float) $val : 0;
    }

    public function generarNumeroOrden(): string
    {
        $anio = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_ordenes_pago WHERE numero_orden LIKE :p");
        $stmt->execute([':p' => "OP-{$anio}-%"]);
        $siguiente = (int) $stmt->fetchColumn() + 1;
        return sprintf('OP-%s-%04d', $anio, $siguiente);
    }

    public function crearOrdenDirecta(int $proveedorId, string $concepto, float $montoUsd, string $fechaPago, int $userId): string
    {
        $numero  = $this->generarNumeroOrden();
        $tasaBcv = $this->getTasaBcvActual();
        $montoBs = $montoUsd * $tasaBcv;

        $this->db->prepare(
            "INSERT INTO tbl_ordenes_pago
                (numero_orden, tipo, proveedor_id, concepto, monto_usd, tasa_bcv, monto_bs, fecha_pago, estado, created_by)
             VALUES (:num, 'DIRECTA', :prov, :concepto, :usd, :tasa, :bs, :fecha, 'PENDIENTE', :uid)"
        )->execute([
            ':num' => $numero, ':prov' => $proveedorId, ':concepto' => $concepto,
            ':usd' => $montoUsd, ':tasa' => $tasaBcv, ':bs' => $montoBs,
            ':fecha' => $fechaPago, ':uid' => $userId,
        ]);

        return $numero;
    }

    // =========================================================================
    // ACCIONES
    // =========================================================================

    public function aprobarOrden(int $id, int $userId): void
    {
        $this->db->prepare(
            "UPDATE tbl_ordenes_pago SET estado = 'APROBADA', aprobado_by = :uid, aprobado_at = NOW() WHERE id = :id"
        )->execute([':uid' => $userId, ':id' => $id]);

        $this->db->prepare(
            "INSERT INTO tbl_tesoreria_pagos (orden_pago_id, created_by) VALUES (:opid, :uid)"
        )->execute([':opid' => $id, ':uid' => $userId]);
    }

    public function countOrdenesHermanasAvanzadas(int $nominaId, int $excludeId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_ordenes_pago
             WHERE nomina_id = :nomina_id AND id != :exclude_id AND estado IN ('APROBADA', 'PAGADA')"
        );
        $stmt->execute([':nomina_id' => $nominaId, ':exclude_id' => $excludeId]);
        return (int) $stmt->fetchColumn();
    }

    public function rechazarOrden(int $id, int $userId, string $motivo, string $tipo, ?int $nominaId, ?int $pagoProveedorId): void
    {
        $this->db->prepare(
            "UPDATE tbl_ordenes_pago SET estado = 'RECHAZADA', motivo_rechazo = :motivo WHERE id = :id"
        )->execute([':motivo' => $motivo, ':id' => $id]);

        if ($tipo === 'NOMINA' && $nominaId) {
            $this->db->prepare("UPDATE tbl_nominas SET estado = 'PROCESADA' WHERE id = :id")
                     ->execute([':id' => $nominaId]);
        } elseif ($tipo === 'PROVEEDOR' && $pagoProveedorId) {
            $this->db->prepare("UPDATE tbl_pagos_proveedores SET estado = 'PROCESADA' WHERE id = :id")
                     ->execute([':id' => $pagoProveedorId]);
        }
        // DIRECTA: no hay origen al cual regresar, la orden queda RECHAZADA y se puede
        // reversar dentro del propio módulo de Órdenes de Pago.
    }

    public function anularOrden(int $id, int $userId): void
    {
        $this->db->prepare("DELETE FROM tbl_tesoreria_pagos WHERE orden_pago_id = :id")
                 ->execute([':id' => $id]);

        $this->db->prepare(
            "UPDATE tbl_ordenes_pago SET estado = 'ANULADA', anulado_by = :uid, anulado_at = NOW() WHERE id = :id"
        )->execute([':uid' => $userId, ':id' => $id]);
    }

    public function reversarOrden(int $id): void
    {
        $this->db->prepare("DELETE FROM tbl_tesoreria_pagos WHERE orden_pago_id = :id")
                 ->execute([':id' => $id]);

        $this->db->prepare(
            "UPDATE tbl_ordenes_pago
             SET estado = 'PENDIENTE', motivo_rechazo = NULL, aprobado_by = NULL, aprobado_at = NULL
             WHERE id = :id"
        )->execute([':id' => $id]);
    }

    // =========================================================================
    // VALIDACIÓN DE CONTRASEÑA (para Anular)
    // =========================================================================

    public function verificarPassword(int $userId, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT password_hash FROM tbl_users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $hash = $stmt->fetchColumn();
        return $hash && password_verify($password, $hash);
    }
}