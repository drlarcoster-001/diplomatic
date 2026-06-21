<?php
/**
 * MÓDULO: FINANCIERO / PAGOS A PROVEEDORES
 * ARCHIVO: app/models/FinancialPagosProveedoresModel.php
 * PROPÓSITO: Gestión de pagos a proveedores tipo factura: BORRADOR (ítems +
 *            ajustes editables) → PROCESADA (congelada) → APROBADA (genera
 *            una orden de pago en tbl_ordenes_pago, tipo PROVEEDOR).
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class FinancialPagosProveedoresModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // PROVEEDORES (para el selector)
    // =========================================================================

    public function getProveedoresActivos(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, rif_cedula, banco, numero_cuenta, titular_cuenta,
                    telefono_pago_movil, banco_pago_movil, cedula_pago_movil
             FROM tbl_proveedores WHERE is_active = 1 ORDER BY nombre ASC"
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

    // =========================================================================
    // LISTADO (INDEX)
    // =========================================================================

    public function getPagos(string $search = '', int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $where .= " AND (pp.numero_pago LIKE :search OR pr.nombre LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql = "SELECT pp.id, pp.numero_pago, pp.fecha_pago, pp.estado, pp.total_usd, pp.created_at,
                       pr.nombre AS proveedor_nombre
                FROM tbl_pagos_proveedores pp
                INNER JOIN tbl_proveedores pr ON pp.proveedor_id = pr.id
                {$where}
                ORDER BY pp.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPagos(string $search = ''): int
    {
        $where  = "WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $where .= " AND (pp.numero_pago LIKE :search OR pr.nombre LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_pagos_proveedores pp
             INNER JOIN tbl_proveedores pr ON pp.proveedor_id = pr.id {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // =========================================================================
    // CREAR PAGO
    // =========================================================================

    public function generarNumeroPago(): string
    {
        $anio = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_pagos_proveedores WHERE numero_pago LIKE :p");
        $stmt->execute([':p' => "PAG-{$anio}-%"]);
        $siguiente = (int) $stmt->fetchColumn() + 1;
        return sprintf('PAG-%s-%04d', $anio, $siguiente);
    }

    public function crearPago(int $proveedorId, string $fechaPago, int $userId): int
    {
        $numero = $this->generarNumeroPago();
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_pagos_proveedores (numero_pago, proveedor_id, fecha_pago, estado, created_by)
             VALUES (:num, :pid, :fecha, 'BORRADOR', :uid)"
        );
        $stmt->execute([':num' => $numero, ':pid' => $proveedorId, ':fecha' => $fechaPago, ':uid' => $userId]);
        return (int) $this->db->lastInsertId();
    }

    public function cambiarProveedor(int $pagoId, int $nuevoProveedorId): void
    {
        $this->db->prepare("UPDATE tbl_pagos_proveedores SET proveedor_id = :pid WHERE id = :id")
                 ->execute([':pid' => $nuevoProveedorId, ':id' => $pagoId]);
    }

    public function getPagoById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT pp.*, pr.nombre AS proveedor_nombre, pr.rif_cedula, pr.banco, pr.numero_cuenta,
                    pr.titular_cuenta, pr.tipo_cuenta, pr.telefono_pago_movil, pr.banco_pago_movil,
                    pr.cedula_pago_movil
             FROM tbl_pagos_proveedores pp
             INNER JOIN tbl_proveedores pr ON pp.proveedor_id = pr.id
             WHERE pp.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // ÍTEMS
    // =========================================================================

    public function getItems(int $pagoId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_pagos_proveedores_items WHERE pago_id = :id ORDER BY id ASC");
        $stmt->execute([':id' => $pagoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addItem(int $pagoId, string $descripcion, float $cantidad, float $precioUnitario): void
    {
        $subtotal = $cantidad * $precioUnitario;
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_pagos_proveedores_items (pago_id, descripcion, cantidad, precio_unitario, subtotal)
             VALUES (:pid, :desc, :cant, :precio, :sub)"
        );
        $stmt->execute([
            ':pid' => $pagoId, ':desc' => $descripcion, ':cant' => $cantidad,
            ':precio' => $precioUnitario, ':sub' => $subtotal,
        ]);
        $this->recalcularTotales($pagoId);
    }

    public function removeItem(int $itemId, int $pagoId): void
    {
        $this->db->prepare("DELETE FROM tbl_pagos_proveedores_items WHERE id = :id")->execute([':id' => $itemId]);
        $this->recalcularTotales($pagoId);
    }

    // =========================================================================
    // AJUSTES (impuestos / deducciones)
    // =========================================================================

    public function getAjustes(int $pagoId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_pagos_proveedores_ajustes WHERE pago_id = :id ORDER BY id ASC");
        $stmt->execute([':id' => $pagoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addAjuste(int $pagoId, string $nombre, string $tipo, string $direccion, float $valor): void
    {
        $pago = $this->getPagoById($pagoId);
        $subtotal = (float) ($pago['subtotal'] ?? 0);
        $montoCalculado = $tipo === 'PORCENTAJE' ? round($subtotal * ($valor / 100), 2) : $valor;

        $stmt = $this->db->prepare(
            "INSERT INTO tbl_pagos_proveedores_ajustes (pago_id, nombre, tipo, direccion, valor, monto_calculado)
             VALUES (:pid, :nombre, :tipo, :dir, :valor, :monto)"
        );
        $stmt->execute([
            ':pid' => $pagoId, ':nombre' => $nombre, ':tipo' => $tipo,
            ':dir' => $direccion, ':valor' => $valor, ':monto' => $montoCalculado,
        ]);
        $this->recalcularTotales($pagoId);
    }

    public function removeAjuste(int $ajusteId, int $pagoId): void
    {
        $this->db->prepare("DELETE FROM tbl_pagos_proveedores_ajustes WHERE id = :id")->execute([':id' => $ajusteId]);
        $this->recalcularTotales($pagoId);
    }

    // =========================================================================
    // RECALCULAR TOTALES
    // =========================================================================

    public function recalcularTotales(int $pagoId): void
    {
        $stmtSub = $this->db->prepare("SELECT COALESCE(SUM(subtotal),0) FROM tbl_pagos_proveedores_items WHERE pago_id = :id");
        $stmtSub->execute([':id' => $pagoId]);
        $subtotal = (float) $stmtSub->fetchColumn();

        // Recalcular el monto de cada ajuste PORCENTAJE contra el subtotal actual
        $stmtAjustes = $this->db->prepare("SELECT id, tipo, valor FROM tbl_pagos_proveedores_ajustes WHERE pago_id = :id");
        $stmtAjustes->execute([':id' => $pagoId]);
        $ajustes = $stmtAjustes->fetchAll(PDO::FETCH_ASSOC);

        $stmtUpdateAjuste = $this->db->prepare(
            "UPDATE tbl_pagos_proveedores_ajustes SET monto_calculado = :monto WHERE id = :id"
        );

        $totalAjustado = 0;
        foreach ($ajustes as $a) {
            if ($a['tipo'] === 'PORCENTAJE') {
                $monto = round($subtotal * ((float) $a['valor'] / 100), 2);
                $stmtUpdateAjuste->execute([':monto' => $monto, ':id' => $a['id']]);
            }
        }

        // Releer los ajustes ya actualizados para sumar el total final
        $stmtAjustes2 = $this->db->prepare("SELECT direccion, monto_calculado FROM tbl_pagos_proveedores_ajustes WHERE pago_id = :id");
        $stmtAjustes2->execute([':id' => $pagoId]);
        $ajustesFinal = $stmtAjustes2->fetchAll(PDO::FETCH_ASSOC);

        $totalUsd = $subtotal;
        foreach ($ajustesFinal as $a) {
            $totalUsd += $a['direccion'] === 'SUMA' ? (float) $a['monto_calculado'] : -((float) $a['monto_calculado']);
        }
        $totalUsd = max(0, $totalUsd);

        $tasaBcv = $this->getTasaBcvActual();
        $totalBs = $totalUsd * $tasaBcv;

        $this->db->prepare(
            "UPDATE tbl_pagos_proveedores
             SET subtotal = :sub, total_usd = :usd, tasa_bcv = :tasa, total_bs = :bs
             WHERE id = :id"
        )->execute([':sub' => $subtotal, ':usd' => $totalUsd, ':tasa' => $tasaBcv, ':bs' => $totalBs, ':id' => $pagoId]);
    }

    // =========================================================================
    // PROCESAR / DESCARTAR / REVERSAR / APROBAR
    // =========================================================================

    public function procesarPago(int $pagoId, int $userId): void
    {
        $this->db->prepare("UPDATE tbl_pagos_proveedores SET estado = 'PROCESADA', updated_by = :uid WHERE id = :id")
                 ->execute([':uid' => $userId, ':id' => $pagoId]);
    }

    public function descartarPago(int $pagoId): void
    {
        $this->db->prepare("DELETE FROM tbl_pagos_proveedores WHERE id = :id")->execute([':id' => $pagoId]);
    }

    public function countOrdenesPagadas(int $pagoId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_ordenes_pago WHERE pago_proveedor_id = :id AND estado = 'PAGADA'"
        );
        $stmt->execute([':id' => $pagoId]);
        return (int) $stmt->fetchColumn();
    }

    public function reversarPago(int $pagoId, int $userId): void
    {
        $pago = $this->getPagoById($pagoId);

        if ($pago && $pago['estado'] === 'APROBADA') {
            $this->db->prepare("DELETE FROM tbl_ordenes_pago WHERE pago_proveedor_id = :id")
                     ->execute([':id' => $pagoId]);
            $this->db->prepare("UPDATE tbl_pagos_proveedores SET estado = 'PROCESADA', updated_by = :uid WHERE id = :id")
                     ->execute([':uid' => $userId, ':id' => $pagoId]);
            return;
        }

        $this->db->prepare("UPDATE tbl_pagos_proveedores SET estado = 'BORRADOR', updated_by = :uid WHERE id = :id")
                 ->execute([':uid' => $userId, ':id' => $pagoId]);
    }

    public function generarNumeroOrden(): string
    {
        $anio = date('Y');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_ordenes_pago WHERE numero_orden LIKE :p");
        $stmt->execute([':p' => "OP-{$anio}-%"]);
        $siguiente = (int) $stmt->fetchColumn() + 1;
        return sprintf('OP-%s-%04d', $anio, $siguiente);
    }

    public function aprobarPago(int $pagoId, int $userId): string
    {
        $pago = $this->getPagoById($pagoId);
        $items = $this->getItems($pagoId);
        $concepto = implode(', ', array_column($items, 'descripcion'));
        if (mb_strlen($concepto) > 250) $concepto = mb_substr($concepto, 0, 247) . '...';

        $numero = $this->generarNumeroOrden();

        $this->db->prepare(
            "INSERT INTO tbl_ordenes_pago
                (numero_orden, tipo, proveedor_id, pago_proveedor_id, concepto,
                 monto_usd, tasa_bcv, monto_bs, fecha_pago, estado, created_by)
             VALUES (:num, 'PROVEEDOR', :prov, :pago, :concepto, :usd, :tasa, :bs, :fecha, 'PENDIENTE', :uid)"
        )->execute([
            ':num' => $numero, ':prov' => $pago['proveedor_id'], ':pago' => $pagoId,
            ':concepto' => $concepto, ':usd' => $pago['total_usd'], ':tasa' => $pago['tasa_bcv'],
            ':bs' => $pago['total_bs'], ':fecha' => $pago['fecha_pago'], ':uid' => $userId,
        ]);

        $this->db->prepare("UPDATE tbl_pagos_proveedores SET estado = 'APROBADA', updated_by = :uid WHERE id = :id")
                 ->execute([':uid' => $userId, ':id' => $pagoId]);

        return $numero;
    }
}