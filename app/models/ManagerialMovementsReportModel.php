<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / REPORTE DE MOVIMIENTOS
 * ARCHIVO: app/models/ManagerialMovementsReportModel.php
 * PROPÓSITO: Modelo maestro para la consolidación de trazabilidad horizontal dinámica.
 * VERSIÓN: 4.0.0 - FIX: Número de Recibo igual al número de Referencia.
 * Extracción profunda de JSON para garantizar datos de Pago Móvil.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class ManagerialMovementsReportModel 
{
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function getOfferings(): array {
        $sql = "SELECT DISTINCT d.id, d.name as diploma_name 
                FROM tbl_diplomados d
                INNER JOIN tbl_academic_offerings o ON d.id = o.diploma_id
                ORDER BY d.name ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getGroupsByOffering(int $diplomaId): array {
        $sql = "SELECT DISTINCT g.id, CONCAT(g.name, ' - ', COALESCE(LEFT(g.description, 40), '')) as name 
                FROM tbl_grupos g
                INNER JOIN tbl_academic_offering_groups og ON g.id = og.group_id
                INNER JOIN tbl_academic_offerings o ON og.offering_id = o.id
                WHERE o.diploma_id = ? 
                ORDER BY g.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$diplomaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getReportData(array $f, int $limit = 25, int $offset = 0): array {
        try {
            // 1. Detectar conceptos dinámicos
            $conceptsSql = "SELECT DISTINCT concept FROM tbl_financial_student_ledger 
                            ORDER BY 
                            (CASE WHEN concept LIKE '%INSCRIP%' THEN 0 WHEN concept = 'CUOTA 1' THEN 1 ELSE 2 END), 
                            CAST(SUBSTRING_INDEX(concept, ' ', -1) AS UNSIGNED) ASC, concept ASC";
            $concepts = $this->db->query($conceptsSql)->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $dynamicColumns = "";
            foreach ($concepts as $concept) {
                $safeConcept = str_replace("'", "''", $concept);
                $alias = str_replace(' ', '_', mb_strtoupper($safeConcept, 'UTF-8')); 
                
                $isEnrollment = (stripos($concept, 'INSCRIP') !== false || $concept === 'CUOTA 1');
                $pPrefix = $isEnrollment ? 'ep' : 'fp';

                $dynamicColumns .= "
                    -- MONTO
                    MAX(CASE WHEN l.concept = '$safeConcept' THEN l.amount_paid END) AS `MONTO_$alias`,
                    
                    -- FORMA PAGO
                    MAX(CASE WHEN l.concept = '$safeConcept' THEN 
                        IF(l.status = 'ABONADO', 'ABONO PARCIAL',
                            COALESCE(
                                JSON_UNQUOTE(JSON_EXTRACT($pPrefix.payment_metadata, '$.metodo')),
                                $pPrefix.method,
                                'N/A'
                            )
                        )
                    END) AS `FORMA_$alias`,
                    
                    -- REFERENCIA
                    MAX(CASE WHEN l.concept = '$safeConcept' THEN 
                        COALESCE(
                            JSON_UNQUOTE(JSON_EXTRACT($pPrefix.payment_metadata, '$.detalles_transaccion.referencia')),
                            $pPrefix.reference_id,
                            '-'
                        )
                    END) AS `REF_$alias`,
                    
                    -- BANCO
                    MAX(CASE WHEN l.concept = '$safeConcept' THEN 
                        COALESCE(JSON_UNQUOTE(JSON_EXTRACT($pPrefix.payment_metadata, '$.detalles_origen.banco_emisor')), 'N/A')
                    END) AS `BANCO_$alias`,
                    
                    -- RECIBO (AHORA ES IGUAL A LA REFERENCIA SEGÚN SOLICITUD)
                    MAX(CASE WHEN l.concept = '$safeConcept' THEN 
                        COALESCE(
                            JSON_UNQUOTE(JSON_EXTRACT($pPrefix.payment_metadata, '$.detalles_transaccion.referencia')),
                            $pPrefix.reference_id,
                            '-'
                        )
                    END) AS `RECIBO_$alias`,
                    
                    -- FECHA
                    MAX(CASE WHEN l.concept = '$safeConcept' THEN 
                        COALESCE(
                            DATE_FORMAT(JSON_UNQUOTE(JSON_EXTRACT($pPrefix.payment_metadata, '$.detalles_transaccion.fecha_comprobante')), '%d/%m/%Y'),
                            DATE_FORMAT($pPrefix.created_at, '%d/%m/%Y'),
                            '-'
                        )
                    END) AS `FECHA_$alias`,
                ";
            }

            $queryBuilder = $this->buildFilteredQuery($f);
            
            $sql = "SELECT 
                        u.id as user_id,
                        CONCAT(u.last_name, ' ', u.first_name) as participante,
                        u.document_id as cedula,
                        u.email,
                        d.name as diplomado,
                        $dynamicColumns
                        COALESCE(SUM(l.amount_paid), 0) AS total_abonado,
                        GROUP_CONCAT(DISTINCT 
                            CASE WHEN l.status = 'ABONADO' 
                            THEN CONCAT('ABONO PARCIAL del pago anterior (Aplica en ', l.concept, ')') 
                            END SEPARATOR ' | '
                        ) AS observaciones
                    FROM tbl_users u
                    INNER JOIN tbl_students s ON u.id = s.user_id
                    INNER JOIN tbl_enrollments e ON s.enrollment_id = e.id
                    INNER JOIN tbl_academic_offerings o ON e.offering_id = o.id
                    INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                    LEFT JOIN tbl_financial_student_ledger l ON l.enrollment_id = e.id
                    LEFT JOIN tbl_enrollments_payments ep ON ep.id = COALESCE(l.payment_id, (SELECT MAX(id) FROM tbl_enrollments_payments WHERE enrollment_id = e.id))
                    LEFT JOIN tbl_financial_payments fp ON fp.id = COALESCE(l.payment_id, (SELECT MAX(id) FROM tbl_financial_payments WHERE student_id = s.id))
                    " . $queryBuilder['where'] . "
                    GROUP BY u.id, e.id, d.id, o.id
                    ORDER BY d.name ASC, u.last_name ASC";

            if ($limit !== -1) { $sql .= " LIMIT $limit OFFSET $offset"; }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($queryBuilder['params']);
            
            return [
                'headers' => $concepts,
                'data'    => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
            ];

        } catch (\PDOException $e) {
            throw new Exception("Error en Modelo: " . $e->getMessage());
        }
    }

    private function buildFilteredQuery(array $f): array {
        $params = [];
        $where = " WHERE u.user_type = 'PARTICIPANT' AND u.status = 'ACTIVE' ";
        if (!empty($f['search'])) {
            $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.document_id LIKE ?) ";
            $t = "%" . $f['search'] . "%"; 
            $params[] = $t; $params[] = $t; $params[] = $t;
        }
        if (isset($f['offering_id']) && $f['offering_id'] !== 'ALL') { $where .= " AND d.id = ? "; $params[] = (int)$f['offering_id']; }
        if (isset($f['group_id']) && $f['group_id'] !== 'ALL' && !empty($f['group_id'])) {
            $where .= " AND EXISTS (SELECT 1 FROM tbl_academic_offering_groups aog 
                                   INNER JOIN tbl_academic_offerings o2 ON aog.offering_id = o2.id
                                   WHERE o2.diploma_id = d.id AND aog.group_id = ?) ";
            $params[] = (int)$f['group_id'];
        }
        if (isset($f['academic_status']) && $f['academic_status'] !== 'ALL') { $where .= " AND e.status = ? "; $params[] = $f['academic_status']; }
        return ['where' => $where, 'params' => $params];
    }
}