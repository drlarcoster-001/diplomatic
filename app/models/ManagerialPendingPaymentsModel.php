<?php
/**
 * MÓDULO: PANEL GERENCIAL / PAGOS PENDIENTES
 * ARCHIVO: app/models/ManagerialPendingPaymentsModel.php
 * PROPÓSITO: Modelo para la auditoría y unificación de pagos en tránsito (Inscripciones y Cuotas).
 * VERSIÓN: 1.1.1 - Sincronización exacta con BD: method, reference_id, created_at y extracción de JSON para tasa_cambio.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ManagerialPendingPaymentsModel 
{
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene la lista de ofertas académicas abiertas para el select de filtros.
     */
    public function getOfferingsList(): array {
        $sql = "SELECT o.id, d.name as diploma_name 
                FROM tbl_academic_offerings o
                INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                WHERE o.status = 'ABIERTA' 
                ORDER BY d.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Construye la consulta base con UNION.
     * Extrae dinámicamente la tasa de cambio desde el campo JSON payment_metadata.
     */
    private function buildBaseQuery(): string {
        return "SELECT 
                    ep.id AS payment_id,
                    'INSCRIPCION' AS origin,
                    u.id AS user_id,
                    u.document_id AS cedula,
                    CONCAT(u.first_name, ' ', u.last_name) AS estudiante,
                    d.name AS diplomado,
                    o.id AS offering_id,
                    COALESCE(ep.method, 'NO ESPECIFICADO') AS tipo_pago,
                    COALESCE(ep.currency, 'USD') AS moneda,
                    COALESCE(ep.amount, 0) AS monto,
                    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.tasa_cambio')), 1) AS tasa,
                    COALESCE(ep.reference_id, 'S/R') AS referencia,
                    COALESCE(ep.created_at, NOW()) AS fecha_pago
                FROM tbl_enrollments_payments ep
                INNER JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                INNER JOIN tbl_users u ON e.user_id = u.id
                INNER JOIN tbl_academic_offerings o ON e.offering_id = o.id
                INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                WHERE ep.status = 'PENDING'

                UNION ALL

                SELECT 
                    fp.id AS payment_id,
                    'PAGO REGULAR' AS origin,
                    u.id AS user_id,
                    u.document_id AS cedula,
                    CONCAT(u.first_name, ' ', u.last_name) AS estudiante,
                    d.name AS diplomado,
                    o.id AS offering_id,
                    COALESCE(fp.method, 'NO ESPECIFICADO') AS tipo_pago,
                    COALESCE(fp.currency, 'USD') AS moneda,
                    COALESCE(fp.amount, 0) AS monto,
                    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.tasa_cambio')), 1) AS tasa,
                    COALESCE(fp.reference_id, 'S/R') AS referencia,
                    COALESCE(fp.created_at, NOW()) AS fecha_pago
                FROM tbl_financial_payments fp
                INNER JOIN tbl_students s ON fp.student_id = s.id
                INNER JOIN tbl_users u ON s.user_id = u.id
                INNER JOIN tbl_enrollments e ON u.id = e.user_id
                INNER JOIN tbl_academic_offerings o ON e.offering_id = o.id
                INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                WHERE fp.status = 'PENDING'";
    }

    /**
     * Retorna el listado paginado de pagos pendientes aplicando todos los filtros.
     */
    public function getPendingPayments(array $f, int $limit = 25, int $offset = 0): array {
        $baseQuery = $this->buildBaseQuery();
        
        // Envolvemos el UNION en una tabla derivada (t) para aplicar los filtros fácilmente
        $sql = "SELECT t.*, 
                       CASE 
                           WHEN t.moneda = 'USD' THEN t.monto 
                           ELSE (t.monto / NULLIF(t.tasa, 0)) 
                       END AS monto_usd,
                       IF(t.tipo_pago = 'CASH', 'Cobranza: Efectivo no entregado', 'Por verificar en banco') AS observacion
                FROM ($baseQuery) AS t 
                WHERE 1=1";

        $params = [];

        // Filtro 1: Buscador Inteligente
        if (!empty($f['student'])) {
            $sql .= " AND (t.estudiante LIKE ? OR t.cedula LIKE ? OR t.referencia LIKE ?)";
            $b = "%{$f['student']}%"; 
            array_push($params, $b, $b, $b);
        }

        // Filtro 2: Diplomado / Oferta
        if ($f['offering_id'] !== 'ALL') {
            $sql .= " AND t.offering_id = ?";
            $params[] = (int)$f['offering_id'];
        }

        // Filtro 3: Estatus / Origen (Inscripción vs Pago Regular)
        if ($f['origin'] !== 'ALL') {
            if ($f['origin'] === 'INSCRIPTION') {
                $sql .= " AND t.origin = 'INSCRIPCION'";
            } elseif ($f['origin'] === 'INSTALLMENT') {
                $sql .= " AND t.origin = 'PAGO REGULAR'";
            }
        }

        $sql .= " ORDER BY t.fecha_pago ASC"; // Los más antiguos de primero (FIFO)
        
        if ($limit > 0) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Cuenta el total de registros para la paginación.
     */
    public function countPendingPayments(array $f): int {
        $baseQuery = $this->buildBaseQuery();
        
        $sql = "SELECT COUNT(*) FROM ($baseQuery) AS t WHERE 1=1";
        $params = [];

        if (!empty($f['student'])) {
            $sql .= " AND (t.estudiante LIKE ? OR t.cedula LIKE ? OR t.referencia LIKE ?)";
            $b = "%{$f['student']}%"; 
            array_push($params, $b, $b, $b);
        }

        if ($f['offering_id'] !== 'ALL') {
            $sql .= " AND t.offering_id = ?";
            $params[] = (int)$f['offering_id'];
        }

        if ($f['origin'] !== 'ALL') {
            if ($f['origin'] === 'INSCRIPTION') {
                $sql .= " AND t.origin = 'INSCRIPCION'";
            } elseif ($f['origin'] === 'INSTALLMENT') {
                $sql .= " AND t.origin = 'PAGO REGULAR'";
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}