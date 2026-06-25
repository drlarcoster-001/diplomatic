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
                     WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre,
                    GROUP_CONCAT(DISTINCT pm.modalidad ORDER BY pm.modalidad SEPARATOR ', ') AS modalidades
             FROM tbl_profesor_modalidad pm
             INNER JOIN tbl_academic_offerings ao ON ao.id = pm.offering_id
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes   c ON c.id = ao.cohort_id
             WHERE pm.professor_id = :pid
               AND ao.is_active = 1
             GROUP BY ao.id, d.name, c.name
             ORDER BY d.name ASC, c.name ASC"
        );
        $stmt->execute([':pid' => $professorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // DATOS DE LA OFERTA
    // =========================================================================

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
            "SELECT ht.id, ht.dia_semana,
                    TIME_FORMAT(ht.hora_inicio, '%H:%i') AS hora_inicio,
                    TIME_FORMAT(ht.hora_fin,   '%H:%i') AS hora_fin,
                    g.name AS grupo_nombre
             FROM tbl_horarios_teoricos ht
             INNER JOIN tbl_profesor_modalidad pm ON pm.offering_id = ht.offering_id
                AND pm.professor_id = :pid AND pm.modalidad = 'TEORICA'
             LEFT JOIN tbl_academic_offering_groups og ON og.id = pm.offering_group_id
             LEFT JOIN tbl_grupos g ON g.id = og.group_id
             WHERE ht.offering_id = :oid
               AND ht.is_active = 1
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
            "SELECT hp.id, gp.nombre AS grupo_practica,
                    cm.nombre AS centro_medico, cm.direccion,
                    hpf.fecha
             FROM tbl_horarios_practicas hp
             INNER JOIN tbl_grupos_practica gp ON gp.id = hp.grupo_id
             INNER JOIN tbl_centros_medicos cm ON cm.id = hp.centro_medico_id
             INNER JOIN tbl_horario_practica_fechas hpf ON hpf.horario_practica_id = hp.id
                AND hpf.is_active = 1
             INNER JOIN tbl_profesor_modalidad pm ON pm.offering_id = hp.offering_id
                AND pm.professor_id = :pid AND pm.modalidad = 'PRACTICA'
             WHERE hp.offering_id = :oid
               AND hp.is_active = 1
             ORDER BY hpf.fecha ASC"
        );
        $stmt->execute([':pid' => $professorId, ':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}