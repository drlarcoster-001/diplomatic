<?php
/**
 * MÓDULO: GESTIÓN GENERAL / GERENCIAL - PAGOS
 * ARCHIVO: app/models/ManagerialPaymentsModel.php
 * PROPÓSITO: Consolidación de ingresos estratégicos mediante la integración de las tablas de inscripciones y pagos regulares.
 * VERSIÓN: 1.0.2 - Sincronización con tbl_financial_payments y tbl_enrollments_payments. Unificación de flujo de caja.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ManagerialPaymentsModel
{
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene los KPIs globales sumando ambas tablas de pagos.
     * Asume que ambas tablas manejan el campo 'monto' (o 'amount') y 'estatus' (o 'status').
     */
    public function getGlobalKPIs(): array {
        // Consultamos la sumatoria de ambas tablas (Solo lo validado)
        $sql = "SELECT 
                (
                    SELECT SUM(amount) FROM tbl_enrollments_payments WHERE status = 'VALIDATED'
                ) as recaudado_inscripciones,
                (
                    SELECT SUM(amount) FROM tbl_financial_payments WHERE status = 'VALIDATED'
                ) as recaudado_cuotas,
                (
                    SELECT COUNT(*) FROM tbl_students
                ) as total_estudiantes,
                (
                    SELECT COUNT(*) FROM tbl_students WHERE scholarship_status = 1
                ) as total_becados";
        
        $stmt = $this->db->query($sql);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalRecaudado = (float)($res['recaudado_inscripciones'] ?? 0) + (float)($res['recaudado_cuotas'] ?? 0);

        return [
            'recaudado'  => $totalRecaudado,
            'becados'    => (int)($res['total_becados'] ?? 0),
            'activos'    => (int)($res['total_estudiantes'] ?? 0)
        ];
    }

    /**
     * Genera la tabla consolidada por Diplomado unificando ambas fuentes de ingresos.
     */
    public function getConsolidatedByDiplomado(): array {
        $sql = "SELECT 
                    d.name as diplomado,
                    c.name as cohorte,
                    COUNT(DISTINCT s.id) as inscritos,
                    (
                        SELECT SUM(amount) 
                        FROM tbl_enrollments_payments ep 
                        WHERE ep.student_id IN (SELECT id FROM tbl_students WHERE offering_id = o.id)
                        AND ep.status = 'VALIDATED'
                    ) +
                    (
                        SELECT SUM(amount) 
                        FROM tbl_financial_payments fp 
                        WHERE fp.student_id IN (SELECT id FROM tbl_students WHERE offering_id = o.id)
                        AND fp.status = 'VALIDATED'
                    ) as recaudado
                FROM tbl_diplomados d
                INNER JOIN tbl_academic_offerings o ON d.id = o.diploma_id
                INNER JOIN tbl_cohortes c ON o.cohort_id = c.id
                LEFT JOIN tbl_students s ON o.id = s.offering_id
                GROUP BY d.id, o.id
                ORDER BY d.name ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}