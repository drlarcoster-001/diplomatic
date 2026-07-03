<?php
/**
 * MÓDULO: PORTAL DOCENTE / MI HORARIO
 * ARCHIVO: app/models/ProfessorHorarioModel.php
 * PROPÓSITO: Obtiene ofertas del profesor desde tbl_profesor_modalidad con
 *            grupos identificables. Trae horarios teóricos y prácticos
 *            asignados al profesor en la oferta seleccionada.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ProfessorHorarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // OFERTAS DEL PROFESOR CON GRUPOS
    // =========================================================================

    public function getMisOfertas(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT ao.id AS offering_id,
                    d.name AS diplomado_nombre,
                    c.name AS cohorte_nombre,
                    (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og2
                     INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                     WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre
             FROM tbl_academic_offerings ao
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes   c ON c.id = ao.cohort_id
             WHERE ao.id IN (
                 SELECT offering_id FROM tbl_academic_offering_professors WHERE professor_id = :pid
                 UNION
                 SELECT offering_id FROM tbl_profesor_modalidad WHERE professor_id = :pid2
             )
             AND ao.is_active = 1
             ORDER BY d.name ASC, c.name ASC"
        );
        $stmt->execute([':pid' => $professorId, ':pid2' => $professorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // DATOS DE LA OFERTA
    // =========================================================================

    public function getPeriodos(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT p.id, p.nombre, p.estado
             FROM tbl_periodos_cohorte p
             INNER JOIN tbl_cohortes c ON c.periodo_id = p.id
             INNER JOIN tbl_academic_offerings ao ON ao.cohort_id = c.id
             WHERE ao.id IN (
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

    public function getDiplomadosPorPeriodo(int $professorId, int $periodoId = 0): array
    {
        $filtroPeriodo = $periodoId ? "AND c.periodo_id = :periodo_id" : "";
        $stmt = $this->db->prepare(
            "SELECT DISTINCT d.id, d.name
             FROM tbl_diplomados d
             INNER JOIN tbl_academic_offerings ao ON ao.diploma_id = d.id
             INNER JOIN tbl_cohortes c ON c.id = ao.cohort_id
             WHERE ao.id IN (
                 SELECT offering_id FROM tbl_academic_offering_professors WHERE professor_id = :pid
                 UNION
                 SELECT offering_id FROM tbl_profesor_modalidad WHERE professor_id = :pid2
             )
             AND ao.is_active = 1
             {$filtroPeriodo}
             ORDER BY d.name ASC"
        );
        $params = [':pid' => $professorId, ':pid2' => $professorId];
        if ($periodoId) $params[':periodo_id'] = $periodoId;
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOferta(int $offeringId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ao.id AS offering_id,
                    d.name AS diplomado_nombre,
                    c.name AS cohorte_nombre,
                    c.start_date, c.end_date,
                    (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og2
                     INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                     WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre
             FROM tbl_academic_offerings ao
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes   c ON c.id = ao.cohort_id
             WHERE ao.id = :id"
        );
        $stmt->execute([':id' => $offeringId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // HORARIOS TEÓRICOS
    // =========================================================================

    public function getHorariosTeoricos(int $professorId, int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT ht.id, ht.dia_semana,
                    TIME_FORMAT(ht.hora_inicio, '%H:%i') AS hora_inicio,
                    TIME_FORMAT(ht.hora_fin,   '%H:%i') AS hora_fin,
                    g.name AS grupo_nombre
             FROM tbl_horarios_teoricos ht
             INNER JOIN tbl_sesiones s ON s.horario_id = ht.id AND s.tipo_horario = 'TEORICO' AND s.is_active = 1
             INNER JOIN tbl_personal per ON per.id = s.personal_id
             LEFT JOIN tbl_academic_offering_groups og ON og.offering_id = ht.offering_id AND og.is_enabled = 1
             LEFT JOIN tbl_grupos g ON g.id = og.group_id
             WHERE ht.offering_id = :oid
               AND ht.is_active = 1
               AND per.profesor_id = :pid
             ORDER BY FIELD(ht.dia_semana,
                'Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'),
                ht.hora_inicio ASC"
        );
        $stmt->execute([':pid' => $professorId, ':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // HORARIOS PRÁCTICOS
    // =========================================================================

    public function getHorariosPracticos(int $professorId, int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT hp.id, gp.nombre AS grupo_practica,
                    cm.nombre AS centro_medico, cm.direccion,
                    hpf.fecha
             FROM tbl_horarios_practicas hp
             INNER JOIN tbl_grupos_practica gp ON gp.id = hp.grupo_id
             INNER JOIN tbl_centros_medicos cm ON cm.id = hp.centro_medico_id
             INNER JOIN tbl_horario_practica_fechas hpf ON hpf.horario_practica_id = hp.id
                AND hpf.is_active = 1
             INNER JOIN tbl_sesiones s ON s.horario_id = hp.id AND s.tipo_horario = 'PRACTICA' AND s.is_active = 1
             INNER JOIN tbl_personal per ON per.id = s.personal_id
             WHERE hp.offering_id = :oid
               AND hp.is_active = 1
               AND per.profesor_id = :pid
             ORDER BY hpf.fecha ASC"
        );
        $stmt->execute([':pid' => $professorId, ':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}