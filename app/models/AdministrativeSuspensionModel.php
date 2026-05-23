<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / SUSPENSIONES
 * ARCHIVO: app/models/AdministrativeSuspensionModel.php
 * PROPÓSITO: Motor financiero con actualización de estatus sincronizada (Transacción).
 * VERSIÓN: 1.5.0 - Fix: Integridad dual entre tbl_users y tbl_students.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class AdministrativeSuspensionModel {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Dashboard Principal: Conteo de alumnos e insolventes por oferta.
     * Corregido para evitar duplicados en el conteo por oferta.
     */
    public function getOfferingsDashboard(): array {
        $sql = "SELECT 
                    o.id AS offering_id,
                    d.name AS diplomado_name,
                    c.name AS cohorte_name,
                    (SELECT COUNT(e_total.id) FROM tbl_enrollments e_total WHERE e_total.offering_id = o.id) AS total_alumnos,
                    (SELECT COUNT(DISTINCT e_ins.id)
                     FROM tbl_enrollments e_ins
                     INNER JOIN tbl_financial_student_ledger l ON e_ins.id = l.enrollment_id
                     WHERE e_ins.offering_id = o.id 
                     AND l.due_date < CURDATE() 
                     AND l.status NOT IN ('PAGADO', 'A FAVOR')
                    ) AS total_insolventes
                FROM tbl_academic_offerings o
                INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                INNER JOIN tbl_cohortes c ON o.cohort_id = c.id
                WHERE o.is_active = 1
                GROUP BY o.id
                HAVING total_alumnos > 0
                ORDER BY total_insolventes DESC, d.name ASC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el listado de estudiantes por oferta académica para la Grid y el Popup.
     * Versión 2.0 - Incluye montos numéricos para desglose de deuda en modal.
     */
    public function getStudentsByOffering(int $offeringId): array {
        $sql = "SELECT 
                    u.id AS user_id,
                    u.document_id AS cedula,
                    CONCAT(u.last_name, ' ', u.first_name) AS participante,
                    u.phone,
                    u.email,
                    u.status AS user_status,
                    s.student_code AS expediente,
                    d.name AS diplomado_nombre_real,
                    c.name AS cohorte_nombre_real,
                    e.id AS enrollment_id,
                    (SELECT 
                        CASE 
                            WHEN EXISTS (SELECT 1 FROM tbl_financial_student_ledger l2 WHERE l2.enrollment_id = e.id AND l2.due_date < CURDATE() AND l2.status NOT IN ('PAGADO', 'A FAVOR')) THEN 'INSOLVENTE'
                            WHEN EXISTS (SELECT 1 FROM tbl_financial_student_ledger l3 WHERE l3.enrollment_id = e.id AND l3.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY) AND l3.status NOT IN ('PAGADO', 'A FAVOR')) THEN 'POR_VENCER'
                            ELSE 'SOLVENTE'
                        END
                    ) AS estatus_financiero,
                    (SELECT CONCAT(concept, ' (Vence: ', DATE_FORMAT(due_date, '%d/%m/%Y'), ')')
                     FROM tbl_financial_student_ledger l4 
                     WHERE l4.enrollment_id = e.id AND l4.status NOT IN ('PAGADO', 'A FAVOR')
                     ORDER BY l4.due_date ASC LIMIT 1) AS detalle_deuda,
                    /* NUEVOS CAMPOS NUMÉRICOS */
                    (SELECT amount_due FROM tbl_financial_student_ledger l5 
                     WHERE l5.enrollment_id = e.id AND l5.status NOT IN ('PAGADO', 'A FAVOR') 
                     ORDER BY l5.due_date ASC LIMIT 1) AS monto_total_cuota,
                    (SELECT amount_paid FROM tbl_financial_student_ledger l6 
                     WHERE l6.enrollment_id = e.id AND l6.status NOT IN ('PAGADO', 'A FAVOR') 
                     ORDER BY l6.due_date ASC LIMIT 1) AS monto_ya_pagado
                FROM tbl_users u
                INNER JOIN tbl_students s ON u.id = s.user_id
                INNER JOIN tbl_enrollments e ON s.enrollment_id = e.id
                INNER JOIN tbl_academic_offerings o ON e.offering_id = o.id
                INNER JOIN tbl_diplomados d ON o.diploma_id = d.id
                INNER JOIN tbl_cohortes c ON o.cohort_id = c.id
                WHERE e.offering_id = :offering_id
                ORDER BY u.last_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':offering_id' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza el estatus del estudiante en ambas tablas críticas.
     * Utiliza transacciones para asegurar la consistencia total.
     */
    public function updateStudentStatus(int $userId, string $newStatus): bool {
        try {
            // Iniciamos transacción para asegurar que ambos cambios ocurran o ninguno
            $this->db->beginTransaction();

            // 1. Actualizamos tbl_users (Afecta el login del estudiante)
            $stmtUser = $this->db->prepare("UPDATE tbl_users SET status = ? WHERE id = ?");
            $stmtUser->execute([$newStatus, $userId]);

            // 2. Actualizamos tbl_students (Afecta el estatus académico)
            $stmtStudent = $this->db->prepare("UPDATE tbl_students SET status = ? WHERE user_id = ?");
            $stmtStudent->execute([$newStatus, $userId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // Si algo falla, revertimos todos los cambios
            $this->db->rollBack();
            error_log("Error en updateStudentStatus: " . $e->getMessage());
            return false;
        }
    }
}