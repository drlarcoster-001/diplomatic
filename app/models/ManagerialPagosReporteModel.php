<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / REPORTE DE PAGOS
 * ARCHIVO: app/models/ManagerialPagosReporteModel.php
 * PROPÓSITO: Obtiene pagos validados por período, oferta y usuario.
 *            Cascada: Período → Oferta(Diplomado+Grupo) → Usuario.
 * VERSIÓN: 1.2.0 - Cascada simplificada. Filtro por ao.id. Sin límite en usuarios.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ManagerialPagosReporteModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // CASCADA: PERÍODOS
    // =========================================================================

    public function getPeriodos(): array
    {
        $stmt = $this->db->query(
            "SELECT id, periodo_code, nombre FROM tbl_periodos_cohorte
             WHERE is_active = 1 ORDER BY id DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // CASCADA: OFERTAS (Diplomado + Grupo) POR PERÍODO
    // =========================================================================

    public function getOfertasByPeriodo(int $periodoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ao.id,
                    CONCAT(d.name,
                        COALESCE((
                            SELECT CONCAT(' — ', GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ', '))
                            FROM tbl_academic_offering_groups og
                            INNER JOIN tbl_grupos g ON g.id = og.group_id
                            WHERE og.offering_id = ao.id AND og.is_enabled = 1
                        ), '')
                    ) AS name
             FROM tbl_academic_offerings ao
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes c ON c.id = ao.cohort_id
             WHERE c.periodo_id = :pid AND c.is_active = 1 AND ao.is_active = 1
             ORDER BY d.name ASC"
        );
        $stmt->execute([':pid' => $periodoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // CASCADA: USUARIOS POR OFERTA
    // =========================================================================

    public function getUsuariosByOferta(int $offeringId, string $search = ''): array
    {
        $where  = "WHERE e.offering_id = :oid AND e.status = 'APROBADO'";
        $params = [':oid' => $offeringId];

        if ($search !== '') {
            $where .= " AND (u.first_name LIKE :s OR u.last_name LIKE :s OR u.document_id LIKE :s OR u.email LIKE :s)";
            $params[':s'] = "%{$search}%";
        }

        $stmt = $this->db->prepare(
            "SELECT DISTINCT u.id, u.document_id,
                    CONCAT(u.last_name, ', ', u.first_name) AS nombre
             FROM tbl_users u
             INNER JOIN tbl_enrollments e ON e.user_id = u.id
             {$where}
             ORDER BY u.last_name ASC, u.first_name ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // REPORTE DE PAGOS (UNION de ambas tablas)
    // =========================================================================

    public function getPagos(int $periodoId, int $offeringId = 0, int $userId = 0): array
    {
        $whereEnrollments = "WHERE ep.status = 'APPROVED' AND c.periodo_id = :pid1";
        $whereFinancial   = "WHERE fp.status = 'APPROVED' AND c2.periodo_id = :pid2";
        $params           = [':pid1' => $periodoId, ':pid2' => $periodoId];

        if ($offeringId) {
            $whereEnrollments .= " AND ao.id = :oid1";
            $whereFinancial   .= " AND ao2.id = :oid2";
            $params[':oid1']   = $offeringId;
            $params[':oid2']   = $offeringId;
        }
        if ($userId) {
            $whereEnrollments .= " AND u.id = :uid1";
            $whereFinancial   .= " AND u2.id = :uid2";
            $params[':uid1']   = $userId;
            $params[':uid2']   = $userId;
        }

        $stmt = $this->db->prepare(
            "SELECT u.last_name, u.first_name, u.document_id,
                    d.name AS diplomado, c.name AS cohorte,
                    ep.method, ep.reference_id, ep.amount, ep.currency,
                    JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.tasa_bcv')) AS tasa_bcv,
                    JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.monto_sistema_usd')) AS monto_usd,
                    ep.created_at AS fecha_pago
             FROM tbl_enrollments_payments ep
             INNER JOIN tbl_enrollments e ON e.id = ep.enrollment_id
             INNER JOIN tbl_users u ON u.id = e.user_id
             INNER JOIN tbl_academic_offerings ao ON ao.id = e.offering_id
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes c ON c.id = ao.cohort_id
             {$whereEnrollments}

             UNION ALL

             SELECT u2.last_name, u2.first_name, u2.document_id,
                    d2.name AS diplomado, c2.name AS cohorte,
                    fp.method, fp.reference_id, fp.amount, fp.currency,
                    JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.tasa_bcv')) AS tasa_bcv,
                    JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.monto_sistema_usd')) AS monto_usd,
                    fp.created_at AS fecha_pago
             FROM tbl_financial_payments fp
             INNER JOIN tbl_students s ON s.id = fp.student_id
             INNER JOIN tbl_users u2 ON u2.id = s.user_id
             INNER JOIN tbl_enrollments e2 ON e2.user_id = u2.id
             INNER JOIN tbl_academic_offerings ao2 ON ao2.id = e2.offering_id
             INNER JOIN tbl_diplomados d2 ON d2.id = ao2.diploma_id
             INNER JOIN tbl_cohortes c2 ON c2.id = ao2.cohort_id
             {$whereFinancial}

             ORDER BY last_name ASC, first_name ASC, fecha_pago ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // TOTALES
    // =========================================================================

    public function getTotales(array $pagos): array
    {
        $totalBs  = 0;
        $totalUsd = 0;
        foreach ($pagos as $p) {
            $totalBs += (float) $p['amount'];
            if (!empty($p['monto_usd'])) {
                $totalUsd += (float) $p['monto_usd'];
            }
        }
        return [
            'total_bs'  => $totalBs,
            'total_usd' => $totalUsd,
            'cantidad'  => count($pagos),
        ];
    }
}