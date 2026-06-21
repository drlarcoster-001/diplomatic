<?php
/**
 * MÓDULO: FINANCIERO / APROBAR PAGOS A PROVEEDORES
 * ARCHIVO: app/models/FinancialAprobarPagosModel.php
 * PROPÓSITO: Lista pagos PROCESADA pendientes de aprobación y pagos APROBADA
 *            ya aprobados. Aprobar genera una orden de pago en tbl_ordenes_pago
 *            (tipo PROVEEDOR). Reversar elimina esa orden (si no fue pagada).
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class FinancialAprobarPagosModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // PENDIENTES DE APROBAR (PROCESADA)
    // =========================================================================

    public function getPagosProcesados(string $search = '', int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE pp.estado = 'PROCESADA'";
        $params = [];
        if ($search !== '') {
            $where .= " AND (pp.numero_pago LIKE :search OR pr.nombre LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        $sql = "SELECT pp.id, pp.numero_pago, pp.fecha_pago, pp.total_usd, pp.created_at,
                       pr.nombre AS proveedor_nombre
                FROM tbl_pagos_proveedores pp
                INNER JOIN tbl_proveedores pr ON pp.proveedor_id = pr.id
                {$where}
                ORDER BY pp.created_at ASC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPagosProcesados(string $search = ''): int
    {
        $where  = "WHERE pp.estado = 'PROCESADA'";
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
    // APROBADOS (APROBADA)
    // =========================================================================

    public function getPagosAprobados(string $search = '', int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE pp.estado = 'APROBADA'";
        $params = [];
        if ($search !== '') {
            $where .= " AND (pp.numero_pago LIKE :search OR pr.nombre LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        $sql = "SELECT pp.id, pp.numero_pago, pp.fecha_pago, pp.total_usd, pp.created_at,
                       pr.nombre AS proveedor_nombre,
                       (SELECT COUNT(*) FROM tbl_ordenes_pago op WHERE op.pago_proveedor_id = pp.id AND op.estado = 'PAGADA') AS ordenes_pagadas
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

    public function countPagosAprobados(string $search = ''): int
    {
        $where  = "WHERE pp.estado = 'APROBADA'";
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
    // DETALLE (SOLO LECTURA)
    // =========================================================================

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

    public function getItems(int $pagoId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_pagos_proveedores_items WHERE pago_id = :id ORDER BY id ASC");
        $stmt->execute([':id' => $pagoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAjustes(int $pagoId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_pagos_proveedores_ajustes WHERE pago_id = :id ORDER BY id ASC");
        $stmt->execute([':id' => $pagoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // APROBAR / REVERSAR
    // =========================================================================

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
        $pago  = $this->getPagoById($pagoId);
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

    public function countOrdenesPagadas(int $pagoId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_ordenes_pago WHERE pago_proveedor_id = :id AND estado = 'PAGADA'"
        );
        $stmt->execute([':id' => $pagoId]);
        return (int) $stmt->fetchColumn();
    }

    public function reversarAprobacion(int $pagoId, int $userId): void
    {
        $this->db->prepare("DELETE FROM tbl_ordenes_pago WHERE pago_proveedor_id = :id")
                 ->execute([':id' => $pagoId]);

        $this->db->prepare("UPDATE tbl_pagos_proveedores SET estado = 'PROCESADA', updated_by = :uid WHERE id = :id")
                 ->execute([':uid' => $userId, ':id' => $pagoId]);
    }
}