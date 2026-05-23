<?php
/**
 * MÓDULO: PANEL GERENCIAL / REPORTE DE PAGOS
 * ARCHIVO: app/models/ManagerialPaymentsReportModel.php
 * PROPÓSITO: Modelo de consolidación financiera multipágina con desglose de conceptos.
 * VERSIÓN: 9.5.8 - FIX: Conversión segura a USD protegiendo la extracción JSON para evitar Error 400.
 * LOGIC: Agrupación maestra (d.id), Total Proyectado y observaciones detalladas (Inscrip/Cuota).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ManagerialPaymentsReportModel 
{
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene la lista de ofertas académicas abiertas para los filtros.
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
     * Obtiene la lista de grupos vinculados a una oferta académica específica.
     */
    public function getGroupsByOfferingId(int $offeringId): array {
     $sql = "SELECT g.id, CONCAT(g.name, ' - ', COALESCE(g.description, '')) as name 
                FROM tbl_grupos g
                INNER JOIN tbl_academic_offering_groups og ON g.id = og.group_id
                WHERE og.offering_id = ?
                ORDER BY g.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * HOJA 1: Genera el resumen detallado por diplomado.
     * Incluye sumatorias por concepto, compromisos (convertidos a USD), y total proyectado.
     */
    public function getSummaryByDiploma(array $f): array {
        $params = [];
        $where = " WHERE 1=1 ";
        if ($f['offering_id'] !== 'ALL') {
            $where .= " AND o.id = ? ";
            $params[] = (int)$f['offering_id'];
        }

        $sql = "SELECT 
                    resumen.*,
                    (resumen.total_validado + resumen.total_compromiso) AS total_proyectado,
                    IF(resumen.total_compromiso > 0, 'Hay personas en este diplomado con pagos pendientes por validar', '-') AS observacion_resumen
                FROM (
                    SELECT 
                        d.name AS diplomado,
                        COUNT(DISTINCT e.id) AS total_estudiantes,
                        
                        -- Desglose de recaudación REAL (Ledger siempre está en USD)
                        COALESCE(SUM(CASE WHEN l.concept LIKE '%INSCRIP%' THEN l.amount_paid ELSE 0 END), 0) AS sum_inscrip,
                        COALESCE(SUM(CASE WHEN l.concept LIKE '%CUOTA 1%' THEN l.amount_paid ELSE 0 END), 0) AS sum_c1,
                        COALESCE(SUM(CASE WHEN l.concept LIKE '%CUOTA 2%' THEN l.amount_paid ELSE 0 END), 0) AS sum_c2,
                        COALESCE(SUM(CASE WHEN l.concept LIKE '%CUOTA 3%' THEN l.amount_paid ELSE 0 END), 0) AS sum_c3,
                        COALESCE(SUM(CASE WHEN l.concept LIKE '%CUOTA 4%' THEN l.amount_paid ELSE 0 END), 0) AS sum_c4,
                        COALESCE(SUM(CASE WHEN l.concept LIKE '%CUOTA 5%' THEN l.amount_paid ELSE 0 END), 0) AS sum_c5,
                        COALESCE(SUM(l.amount_paid), 0) AS total_validado,
                        
                        -- Dinero en Compromiso (PENDING) convertido a USD de forma segura
                        (
                            (SELECT COALESCE(SUM(
                                CASE 
                                    WHEN ep.currency = 'USD' THEN ep.amount
                                    WHEN JSON_VALID(ep.payment_metadata) = 1 THEN ep.amount / NULLIF(CAST(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.tasa_cambio')) AS DECIMAL(15,4)), 0)
                                    ELSE ep.amount
                                END
                             ), 0) 
                             FROM tbl_enrollments_payments ep 
                             INNER JOIN tbl_enrollments e2 ON ep.enrollment_id = e2.id 
                             INNER JOIN tbl_academic_offerings o2 ON e2.offering_id = o2.id
                             WHERE o2.diploma_id = d.id AND ep.status = 'PENDING') 
                            +
                            (SELECT COALESCE(SUM(
                                CASE 
                                    WHEN fp.currency = 'USD' THEN fp.amount
                                    WHEN JSON_VALID(fp.payment_metadata) = 1 THEN fp.amount / NULLIF(CAST(JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.tasa_cambio')) AS DECIMAL(15,4)), 0)
                                    ELSE fp.amount
                                END
                             ), 0) 
                             FROM tbl_financial_payments fp 
                             INNER JOIN tbl_students s ON fp.student_id = s.id
                             INNER JOIN tbl_enrollments e3 ON s.user_id = e3.user_id
                             INNER JOIN tbl_academic_offerings o3 ON e3.offering_id = o3.id
                             WHERE o3.diploma_id = d.id AND fp.status = 'PENDING')
                        ) AS total_compromiso

                    FROM tbl_diplomados d
                    INNER JOIN tbl_academic_offerings o ON d.id = o.diploma_id
                    LEFT JOIN tbl_enrollments e ON o.id = e.offering_id
                    LEFT JOIN tbl_financial_student_ledger l ON e.id = l.enrollment_id
                    $where
                    GROUP BY d.id
                ) AS resumen
                ORDER BY resumen.diplomado ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }



/**
     * HOJA 2+: Matriz detallada de estudiantes con estatus dinámicos y conversiones.
     */
    public function getMatrixData(array $f, int $limit = 25, int $offset = 0): array {
        $sql = "SELECT t.*, 
                TRIM(CONCAT(
                    IF(t.m_p_i > 0, 'Inscripción sin validar. ', ''),
                    IF(t.m_p_c > 0, 'Cuota sin validar.', '')
                )) AS observacion
                FROM (
                    SELECT 
                        u.id AS user_id, 
                        CONCAT(u.first_name, ' ', u.last_name) AS participante, 
                        u.document_id AS cedula,
                        d.name AS diplomado,
                        o.id AS offering_id,
                        
                        -- CIRUGÍA: Obtenemos los grupos asociados a la oferta
                        (SELECT GROUP_CONCAT(g.name SEPARATOR ', ') 
                         FROM tbl_academic_offering_groups og 
                         INNER JOIN tbl_grupos g ON og.group_id = g.id 
                         WHERE og.offering_id = o.id) AS grupos_nombres,
                        
                        -- Conversión segura a USD para inscripciones PENDING
                        (SELECT COALESCE(SUM(
                            CASE 
                                WHEN ep.currency = 'USD' THEN ep.amount
                                WHEN JSON_VALID(ep.payment_metadata) = 1 THEN ep.amount / NULLIF(CAST(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.tasa_cambio')) AS DECIMAL(15,4)), 0)
                                ELSE ep.amount
                            END
                         ), 0) FROM tbl_enrollments_payments ep WHERE ep.enrollment_id = e.id AND ep.status = 'PENDING') AS m_p_i,
                        
                        -- Conversión segura a USD para cuotas PENDING
                        (SELECT COALESCE(SUM(
                            CASE 
                                WHEN fp.currency = 'USD' THEN fp.amount
                                WHEN JSON_VALID(fp.payment_metadata) = 1 THEN fp.amount / NULLIF(CAST(JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.tasa_cambio')) AS DECIMAL(15,4)), 0)
                                ELSE fp.amount
                            END
                         ), 0) FROM tbl_financial_payments fp INNER JOIN tbl_students s2 ON fp.student_id = s2.id WHERE s2.user_id = u.id AND fp.status = 'PENDING') AS m_p_c,
                        
                        (SELECT amount FROM tbl_academic_offering_payment_plans WHERE offering_id = o.id AND name LIKE '%INSCRIP%' LIMIT 1) AS monto_plan_i,
                        COALESCE(SUM(CASE WHEN l.concept LIKE '%INSCRIP%' THEN l.amount_paid ELSE 0 END), 0) AS pago_inscripcion,
                        
                        (SELECT amount FROM tbl_academic_offering_payment_plans WHERE offering_id = o.id AND name LIKE '%CUOTA 1%' LIMIT 1) AS m_p_1,
                        COALESCE(SUM(CASE WHEN l.concept LIKE '%CUOTA 1%' THEN l.amount_paid ELSE 0 END), 0) AS pago_cuota_1,
                        
                        (SELECT amount FROM tbl_academic_offering_payment_plans WHERE offering_id = o.id AND name LIKE '%CUOTA 2%' LIMIT 1) AS m_p_2,
                        COALESCE(SUM(CASE WHEN l.concept LIKE '%CUOTA 2%' THEN l.amount_paid ELSE 0 END), 0) AS pago_cuota_2,
                        
                        (SELECT amount FROM tbl_academic_offering_payment_plans WHERE offering_id = o.id AND name LIKE '%CUOTA 3%' LIMIT 1) AS m_p_3,
                        COALESCE(SUM(CASE WHEN l.concept LIKE '%CUOTA 3%' THEN l.amount_paid ELSE 0 END), 0) AS pago_cuota_3,
                        
                        (SELECT amount FROM tbl_academic_offering_payment_plans WHERE offering_id = o.id AND name LIKE '%CUOTA 4%' LIMIT 1) AS m_p_4,
                        COALESCE(SUM(CASE WHEN l.concept LIKE '%CUOTA 4%' THEN l.amount_paid ELSE 0 END), 0) AS pago_cuota_4,
                        
                        (SELECT amount FROM tbl_academic_offering_payment_plans WHERE offering_id = o.id AND name LIKE '%CUOTA 5%' LIMIT 1) AS m_p_5,
                        COALESCE(SUM(CASE WHEN l.concept LIKE '%CUOTA 5%' THEN l.amount_paid ELSE 0 END), 0) AS pago_cuota_5,
                        
                        COALESCE(SUM(l.amount_paid), 0) AS total_abonado
                    FROM tbl_users u
                    INNER JOIN tbl_enrollments e ON u.id = e.user_id
                    INNER JOIN tbl_academic_offerings o ON e.offering_id = o.id
                    INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                    LEFT JOIN tbl_financial_student_ledger l ON e.id = l.enrollment_id
                    WHERE u.user_type = 'PARTICIPANT'
                    GROUP BY u.id, e.id, o.id, d.id
                ) AS t 
                WHERE 1=1";

        $params = [];
        if (!empty($f['student'])) {
            $sql .= " AND (t.participante LIKE ? OR t.cedula LIKE ?)";
            $b = "%{$f['student']}%"; $params[] = $b; $params[] = $b;
        }
        
        if ($f['offering_id'] !== 'ALL') {
            $sql .= " AND t.offering_id = ?";
            $params[] = (int)$f['offering_id'];
        }
        
        // CIRUGÍA LÁSER SIN TOCAR TABLAS: Filtro por grupo
        if (isset($f['group_id']) && $f['group_id'] !== 'ALL') {
            $sql .= " AND EXISTS (SELECT 1 FROM tbl_academic_offering_groups og WHERE og.offering_id = t.offering_id AND og.group_id = ?)";
            $params[] = (int)$f['group_id'];
        }

        $sql .= " ORDER BY t.diplomado ASC, t.participante ASC";
        if ($limit > 0) $sql .= " LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($results as &$r) {
            $r['estatus_i'] = $this->calculateStatus((float)($r['monto_plan_i'] ?? 0), (float)$r['pago_inscripcion']);
            $r['estatus_1'] = $this->calculateStatus((float)($r['m_p_1'] ?? 0), (float)$r['pago_cuota_1']);
            $r['estatus_2'] = $this->calculateStatus((float)($r['m_p_2'] ?? 0), (float)$r['pago_cuota_2']);
            $r['estatus_3'] = $this->calculateStatus((float)($r['m_p_3'] ?? 0), (float)$r['pago_cuota_3']);
            $r['estatus_4'] = $this->calculateStatus((float)($r['m_p_4'] ?? 0), (float)$r['pago_cuota_4']);
            $r['estatus_5'] = $this->calculateStatus((float)($r['m_p_5'] ?? 0), (float)$r['pago_cuota_5']);
        }
        return $results;
    }


    public function countMatrixTotal(array $f): int {
        $sql = "SELECT COUNT(*) FROM tbl_enrollments e INNER JOIN tbl_users u ON e.user_id = u.id WHERE u.user_type = 'PARTICIPANT'";
        return (int)$this->db->query($sql)->fetchColumn();
    }

    /**
     * Calcula los totales generales de la parte inferior de la vista con conversión a USD
     */
    public function getGlobalTotals(array $f): array {
        $params = []; $where = " WHERE 1=1 ";
        if ($f['offering_id'] !== 'ALL') { $where .= " AND e.offering_id = ? "; $params[] = (int)$f['offering_id']; }

        $sqlA = "SELECT COALESCE(SUM(l.amount_paid), 0) FROM tbl_financial_student_ledger l INNER JOIN tbl_enrollments e ON l.enrollment_id = e.id $where";
        $stmtA = $this->db->prepare($sqlA); $stmtA->execute($params);
        $aprobado = (float)$stmtA->fetchColumn();

        $sqlB = "SELECT COALESCE(SUM(
                    CASE 
                        WHEN ep.currency = 'USD' THEN ep.amount
                        WHEN JSON_VALID(ep.payment_metadata) = 1 THEN ep.amount / NULLIF(CAST(JSON_UNQUOTE(JSON_EXTRACT(ep.payment_metadata, '$.tasa_cambio')) AS DECIMAL(15,4)), 0)
                        ELSE ep.amount
                    END
                 ), 0) 
                 FROM tbl_enrollments_payments ep 
                 INNER JOIN tbl_enrollments e ON ep.enrollment_id = e.id $where AND ep.status = 'PENDING'";
        $stmtB = $this->db->prepare($sqlB); $stmtB->execute($params);
        $pInsc = (float)$stmtB->fetchColumn();

        $sqlC = "SELECT COALESCE(SUM(
                    CASE 
                        WHEN fp.currency = 'USD' THEN fp.amount
                        WHEN JSON_VALID(fp.payment_metadata) = 1 THEN fp.amount / NULLIF(CAST(JSON_UNQUOTE(JSON_EXTRACT(fp.payment_metadata, '$.tasa_cambio')) AS DECIMAL(15,4)), 0)
                        ELSE fp.amount
                    END
                 ), 0) 
                 FROM tbl_financial_payments fp 
                 INNER JOIN tbl_students s ON fp.student_id = s.id 
                 INNER JOIN tbl_enrollments e ON s.user_id = e.user_id $where AND fp.status = 'PENDING'";
        $stmtC = $this->db->prepare($sqlC); $stmtC->execute($params);
        $pCuotas = (float)$stmtC->fetchColumn();

        return [
            'total_aprobado' => $aprobado,
            'total_compromiso' => $pInsc + $pCuotas,
            'total_general' => $aprobado + $pInsc + $pCuotas
        ];
    }

    private function calculateStatus(float $plan, float $paid): string {
        if ($plan <= 0 && $paid <= 0) return 'N/A';
        if ($plan > 0 && $paid == 0) return 'SIN MOVIMIENTO';
        return ($paid >= $plan) ? 'PAGADO' : 'ABONADO';
    }
}