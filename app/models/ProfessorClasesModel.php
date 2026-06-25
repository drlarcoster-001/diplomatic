<?php
/**
 * MÓDULO: PORTAL DOCENTE / MIS CLASES
 * ARCHIVO: app/models/ProfessorClasesModel.php
 * PROPÓSITO: Trae las clases asignadas a un profesor, separadas en 3
 *            modalidades. Teórica combina las DOS fuentes existentes
 *            (tbl_academic_offering_professors, la asignación de siempre
 *            hecha desde Ofertas Académicas, y tbl_profesor_modalidad, la
 *            nueva) para no perder profesores asignados por la vía vieja.
 *            Virtual sale solo de tbl_profesor_modalidad (es la única
 *            fuente posible). Práctica sale de tbl_grupos_practica, sin
 *            cambios respecto a lo que ya existía.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ProfessorClasesModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getClasesTeoricas(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT o.id AS offering_id, d.name AS diplomado_nombre, c.start_date,
                    o.status, o.enrolled_count,
                    (SELECT GROUP_CONCAT(CONCAT(dia_semana, ' ', TIME_FORMAT(hora_inicio,'%H:%i'), '-', TIME_FORMAT(hora_fin,'%H:%i')) SEPARATOR ', ')
                     FROM tbl_horarios_teoricos WHERE offering_id = o.id AND is_active = 1) AS horario
             FROM tbl_academic_offerings o
             INNER JOIN tbl_diplomados d ON d.id = o.diploma_id
             INNER JOIN tbl_cohortes c ON c.id = o.cohort_id
             WHERE o.id IN (
                 SELECT offering_id FROM tbl_academic_offering_professors WHERE professor_id = :pid1
                 UNION
                 SELECT offering_id FROM tbl_profesor_modalidad WHERE professor_id = :pid2 AND modalidad = 'TEORICA'
             )
             ORDER BY c.start_date DESC"
        );
        $stmt->execute([':pid1' => $professorId, ':pid2' => $professorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClasesVirtuales(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT o.id AS offering_id, d.name AS diplomado_nombre, c.start_date, o.status, o.enrolled_count
             FROM tbl_profesor_modalidad pm
             INNER JOIN tbl_academic_offerings o ON o.id = pm.offering_id
             INNER JOIN tbl_diplomados d ON d.id = o.diploma_id
             INNER JOIN tbl_cohortes c ON c.id = o.cohort_id
             WHERE pm.professor_id = :pid AND pm.modalidad = 'VIRTUAL'
             ORDER BY c.start_date DESC"
        );
        $stmt->execute([':pid' => $professorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * CORRECCIÓN: tbl_grupos_practica NO tiene columna professor_id (nunca
     * la tuvo). La asignación de Práctica funciona igual que Virtual: sale
     * completa de tbl_profesor_modalidad (modalidad='PRACTICA'), a nivel
     * de OFERTA — el profesor de práctica de una oferta cubre todos los
     * grupos de esa oferta, no se asigna grupo por grupo.
     */
    public function getClasesPracticas(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT o.id AS offering_id, d.name AS diplomado_nombre, c.start_date, o.status, o.enrolled_count,
                    (SELECT GROUP_CONCAT(g.nombre ORDER BY g.nombre SEPARATOR ', ')
                     FROM tbl_grupos_practica g WHERE g.offering_id = o.id AND g.is_active = 1) AS grupos,
                    (SELECT COUNT(*) FROM tbl_grupo_estudiantes ge
                     INNER JOIN tbl_grupos_practica g2 ON g2.id = ge.grupo_id
                     WHERE g2.offering_id = o.id AND ge.is_active = 1) AS total_estudiantes,
                    (SELECT GROUP_CONCAT(DISTINCT cm.nombre SEPARATOR ', ')
                     FROM tbl_horarios_practicas hp
                     INNER JOIN tbl_centros_medicos cm ON cm.id = hp.centro_medico_id
                     WHERE hp.offering_id = o.id AND hp.is_active = 1) AS centros
             FROM tbl_profesor_modalidad pm
             INNER JOIN tbl_academic_offerings o ON o.id = pm.offering_id
             INNER JOIN tbl_diplomados d ON d.id = o.diploma_id
             INNER JOIN tbl_cohortes c ON c.id = o.cohort_id
             WHERE pm.professor_id = :pid AND pm.modalidad = 'PRACTICA'
             ORDER BY c.start_date DESC"
        );
        $stmt->execute([':pid' => $professorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}