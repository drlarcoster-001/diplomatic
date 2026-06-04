<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / MODELOS
 * ARCHIVO: app/models/StudentsPaymentHistoryModel.php
 * PROPÓSITO: Consulta del historial de pagos del estudiante en sesión (inscripciones y cuotas).
 * VERSIÓN: 1.0.0 - Creación inicial del módulo de historial de pagos estudiantil.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class StudentsPaymentHistoryModel
{
    protected PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function getPaymentHistory(int $userId): array
    {
        // Pagos de inscripción
        $sql1 = "SELECT 
                    ep.id,
                    'INSCRIPCION' as tipo,
                    d.name as diplomado_name,
                    ep.method,
                    ep.amount,
                    ep.currency,
                    ep.reference_id,
                    ep.status,
                    ep.screenshot_path,
                    ep.created_at,
                    ep.observation
                FROM tbl_enrollments_payments ep
                JOIN tbl_enrollments e ON ep.enrollment_id = e.id
                JOIN tbl_academic_offerings o ON e.offering_id = o.id
                JOIN tbl_diplomados d ON o.diploma_id = d.id
                WHERE e.user_id = ?
                ORDER BY ep.created_at DESC";

        // Pagos de cuotas
        $sql2 = "SELECT 
                    fp.id,
                    'CUOTA' as tipo,
                    d.name as diplomado_name,
                    fp.method,
                    fp.amount,
                    fp.currency,
                    fp.reference_id,
                    fp.status,
                    fp.screenshot_path,
                    fp.created_at,
                    fp.observation
                FROM tbl_financial_payments fp
                JOIN tbl_student_matriculations sm ON fp.matriculation_id = sm.id
                JOIN tbl_students s ON sm.student_id = s.id
                JOIN tbl_academic_offerings o ON sm.offering_id = o.id
                JOIN tbl_diplomados d ON o.diploma_id = d.id
                WHERE s.user_id = ?
                ORDER BY fp.created_at DESC";

        $stmt1 = $this->db->prepare($sql1);
        $stmt1->execute([$userId]);
        $inscripciones = $stmt1->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmt2 = $this->db->prepare($sql2);
        $stmt2->execute([$userId]);
        $cuotas = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $todos = array_merge($inscripciones, $cuotas);
        usort($todos, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return $todos;
    }
}