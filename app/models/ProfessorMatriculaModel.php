<?php
/**
 * MÓDULO: PORTAL DOCENTE / MATRÍCULA
 * ARCHIVO: app/models/ProfessorMatriculaModel.php
 * PROPÓSITO: Lista plana de las clases del profesor (para elegir cuál
 *            consultar) + el roster completo de estudiantes de una oferta
 *            específica, combinando tbl_students (datos académicos) con
 *            tbl_users (datos personales: teléfono, dirección, avatar).
 * VERSIÓN: 1.1.0 - Agrega filtros por período y cohorte.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ProfessorMatriculaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Lista plana de TODAS las clases del profesor (las 3 modalidades
     * juntas), para mostrarlas como selector.
     */
    public function getMisOfertas(int $professorId, ?int $periodoId = null, ?int $cohorteId = null): array
    {
        $filtroPeriodo  = $periodoId  ? "AND c.periodo_id = :periodo_id"  : "";
        $filtroCohorte  = $cohorteId  ? "AND c.id = :cohorte_id"          : "";
        $filtroPeriodo2 = $periodoId  ? "AND c.periodo_id = :periodo_id2" : "";
        $filtroCohorte2 = $cohorteId  ? "AND c.id = :cohorte_id2"         : "";
        $filtroPeriodo3 = $periodoId  ? "AND c.periodo_id = :periodo_id3" : "";
        $filtroCohorte3 = $cohorteId  ? "AND c.id = :cohorte_id3"         : "";

        $sql = "SELECT DISTINCT subq.offering_id, subq.diplomado_nombre, subq.cohorte_nombre,
                       subq.start_date, subq.periodo_nombre,
                       (SELECT GROUP_CONCAT(g.name SEPARATOR ', ')
                        FROM tbl_academic_offering_groups og
                        INNER JOIN tbl_grupos g ON g.id = og.group_id
                        WHERE og.offering_id = subq.offering_id AND og.is_enabled = 1) AS grupos_nombre
                FROM (
                    SELECT o.id AS offering_id, d.name AS diplomado_nombre, c.name AS cohorte_nombre,
                           c.start_date, p.nombre AS periodo_nombre
                    FROM tbl_academic_offerings o
                    INNER JOIN tbl_diplomados d ON d.id = o.diploma_id
                    INNER JOIN tbl_cohortes c ON c.id = o.cohort_id
                    INNER JOIN tbl_periodos_cohorte p ON p.id = c.periodo_id
                    WHERE o.id IN (
                        SELECT offering_id FROM tbl_academic_offering_professors WHERE professor_id = :pid1
                        UNION
                        SELECT offering_id FROM tbl_profesor_modalidad WHERE professor_id = :pid2
                    )
                    {$filtroPeriodo} {$filtroCohorte}

                    UNION

                    SELECT o.id AS offering_id, d.name AS diplomado_nombre, c.name AS cohorte_nombre,
                           c.start_date, p.nombre AS periodo_nombre
                    FROM tbl_profesor_modalidad pm
                    INNER JOIN tbl_academic_offerings o ON o.id = pm.offering_id
                    INNER JOIN tbl_diplomados d ON d.id = o.diploma_id
                    INNER JOIN tbl_cohortes c ON c.id = o.cohort_id
                    INNER JOIN tbl_periodos_cohorte p ON p.id = c.periodo_id
                    WHERE pm.professor_id = :pid3
                    {$filtroPeriodo2} {$filtroCohorte2}
                ) subq
                ORDER BY subq.start_date DESC";

        $params = [
            ':pid1' => $professorId,
            ':pid2' => $professorId,
            ':pid3' => $professorId,
        ];
        if ($periodoId) {
            $params[':periodo_id']  = $periodoId;
            $params[':periodo_id2'] = $periodoId;
        }
        if ($cohorteId) {
            $params[':cohorte_id']  = $cohorteId;
            $params[':cohorte_id2'] = $cohorteId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTodosEstudiantes(int $professorId, ?int $periodoId = null, ?int $cohorteId = null): array
    {
        $filtroPeriodo = $periodoId ? "AND c.periodo_id = :periodo_id" : "";
        $filtroCohorte = $cohorteId ? "AND c.id = :cohorte_id" : "";

        $stmt = $this->db->prepare(
            "SELECT s.id, s.student_code, s.first_name, s.last_name, s.document_id, s.email,
                    s.undergraduate_degree, s.admission_date, s.status,
                    u.phone, u.address, u.avatar,
                    d.name AS diplomado_nombre, c.name AS cohorte_nombre
             FROM tbl_students s
             INNER JOIN tbl_enrollments en ON en.id = s.enrollment_id
             INNER JOIN tbl_users u ON u.id = s.user_id
             INNER JOIN tbl_academic_offerings ao ON ao.id = en.offering_id
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes c ON c.id = ao.cohort_id
             WHERE en.offering_id IN (
                 SELECT offering_id FROM tbl_academic_offering_professors WHERE professor_id = :pid
                 UNION
                 SELECT offering_id FROM tbl_profesor_modalidad WHERE professor_id = :pid2
             )
             {$filtroPeriodo} {$filtroCohorte}
             ORDER BY d.name ASC, s.last_name ASC, s.first_name ASC"
        );
        $params = [':pid' => $professorId, ':pid2' => $professorId];
        if ($periodoId) $params[':periodo_id'] = $periodoId;
        if ($cohorteId) $params[':cohorte_id'] = $cohorteId;
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function profesorTieneAccesoOferta(int $professorId, int $offeringId): bool
    {
        $ofertas = $this->getMisOfertas($professorId);
        foreach ($ofertas as $o) {
            if ((int) $o['offering_id'] === $offeringId) return true;
        }
        return false;
    }

    public function getEstudiantesPorOferta(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id, s.student_code, s.first_name, s.last_name, s.document_id, s.email,
                    s.undergraduate_degree, s.admission_date, s.status,
                    u.phone, u.address, u.avatar
             FROM tbl_students s
             INNER JOIN tbl_enrollments en ON en.id = s.enrollment_id
             INNER JOIN tbl_users u ON u.id = s.user_id
             WHERE en.offering_id = :oid
             ORDER BY s.last_name, s.first_name"
        );
        $stmt->execute([':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPeriodosProfesor(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT p.id, p.nombre, p.estado
             FROM tbl_periodos_cohorte p
             INNER JOIN tbl_cohortes c ON c.periodo_id = p.id
             INNER JOIN tbl_academic_offerings o ON o.cohort_id = c.id
             WHERE o.id IN (
                 SELECT offering_id FROM tbl_academic_offering_professors WHERE professor_id = :pid
                 UNION
                 SELECT offering_id FROM tbl_profesor_modalidad WHERE professor_id = :pid2
             )
             AND p.is_active = 1
             ORDER BY p.id DESC"
        );
        $stmt->execute([':pid' => $professorId, ':pid2' => $professorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCohortesProfesor(int $professorId, int $periodoId = 0): array
    {
        $filtroPeriodo = $periodoId ? "AND c.periodo_id = :periodo_id" : "";
        $stmt = $this->db->prepare(
            "SELECT DISTINCT c.id, c.name, c.cohort_code
             FROM tbl_cohortes c
             INNER JOIN tbl_academic_offerings o ON o.cohort_id = c.id
             WHERE o.id IN (
                 SELECT offering_id FROM tbl_academic_offering_professors WHERE professor_id = :pid
                 UNION
                 SELECT offering_id FROM tbl_profesor_modalidad WHERE professor_id = :pid2
             )
             AND c.is_active = 1
             {$filtroPeriodo}
             ORDER BY c.name ASC"
        );
        $params = [':pid' => $professorId, ':pid2' => $professorId];
        if ($periodoId) $params[':periodo_id'] = $periodoId;
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}