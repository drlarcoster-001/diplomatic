<?php
/**
 * MÓDULO: PORTAL DOCENTE / MATRÍCULA
 * ARCHIVO: app/models/ProfessorMatriculaModel.php
 * PROPÓSITO: Lista plana de las clases del profesor (para elegir cuál
 *            consultar) + el roster completo de estudiantes de una oferta
 *            específica, combinando tbl_students (datos académicos) con
 *            tbl_users (datos personales: teléfono, dirección, avatar).
 * VERSIÓN: 1.0.0 - Creación inicial.
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
     * juntas), para mostrarlas como selector. Reusa la misma lógica de
     * fuentes que "Mis Clases". Incluye nombre de cohorte y grupos
     * habilitados de la oferta, para diferenciar ofertas con el mismo
     * diplomado y fecha.
     */
    public function getMisOfertas(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT subq.offering_id, subq.diplomado_nombre, subq.cohorte_nombre, subq.start_date, subq.modalidad,
                    (SELECT GROUP_CONCAT(g.name SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og
                     INNER JOIN tbl_grupos g ON g.id = og.group_id
                     WHERE og.offering_id = subq.offering_id AND og.is_enabled = 1) AS grupos_nombre
             FROM (
                 SELECT o.id AS offering_id, d.name AS diplomado_nombre, c.name AS cohorte_nombre, c.start_date, 'TEORICA' AS modalidad
                 FROM tbl_academic_offerings o
                 INNER JOIN tbl_diplomados d ON d.id = o.diploma_id
                 INNER JOIN tbl_cohortes c ON c.id = o.cohort_id
                 WHERE o.id IN (
                     SELECT offering_id FROM tbl_academic_offering_professors WHERE professor_id = :pid1
                     UNION
                     SELECT offering_id FROM tbl_profesor_modalidad WHERE professor_id = :pid2 AND modalidad = 'TEORICA'
                 )
                 UNION
                 SELECT o.id, d.name, c.name, c.start_date, 'PRACTICA'
                 FROM tbl_profesor_modalidad pm
                 INNER JOIN tbl_academic_offerings o ON o.id = pm.offering_id
                 INNER JOIN tbl_diplomados d ON d.id = o.diploma_id
                 INNER JOIN tbl_cohortes c ON c.id = o.cohort_id
                 WHERE pm.professor_id = :pid3 AND pm.modalidad = 'PRACTICA'
                 UNION
                 SELECT o.id, d.name, c.name, c.start_date, 'VIRTUAL'
                 FROM tbl_profesor_modalidad pm
                 INNER JOIN tbl_academic_offerings o ON o.id = pm.offering_id
                 INNER JOIN tbl_diplomados d ON d.id = o.diploma_id
                 INNER JOIN tbl_cohortes c ON c.id = o.cohort_id
                 WHERE pm.professor_id = :pid4 AND pm.modalidad = 'VIRTUAL'
             ) subq
             ORDER BY subq.start_date DESC"
        );
        $stmt->execute([':pid1' => $professorId, ':pid2' => $professorId, ':pid3' => $professorId, ':pid4' => $professorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica que el profesor SÍ esté vinculado a esa oferta (por
     * cualquiera de las 3 modalidades), antes de mostrarle el roster.
     * Evita que alguien manipule la URL para ver estudiantes de una
     * oferta ajena.
     */
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
}