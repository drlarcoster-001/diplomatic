<?php
/**
 * MÓDULO: PORTAL DOCENTE / REGISTRAR ASISTENCIA
 * ARCHIVO: app/models/ProfessorRegistrarAsistenciaModel.php
 * PROPÓSITO: Obtiene las sesiones PROGRAMADAS del profesor para que pueda
 *            cargar la asistencia. Guarda en tbl_sesion_asistencia.
 *            Cadena: tbl_professors → tbl_personal → tbl_sesiones.
 * VERSIÓN: 1.1.0 - getMisOfertas agrega grupos_nombre para mostrar en selector.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ProfessorRegistrarAsistenciaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // OFERTAS DEL PROFESOR CON SESIONES PROGRAMADAS
    // =========================================================================

    public function getMisOfertas(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ao.id AS offering_id,
                    d.name AS diplomado_nombre,
                    c.name AS cohorte_nombre,
                    (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og2
                     INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                     WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre,
                    COUNT(DISTINCT s.id) AS total_sesiones,
                    SUM(CASE WHEN sa.id IS NOT NULL THEN 1 ELSE 0 END) AS con_asistencia
             FROM tbl_sesiones s
             INNER JOIN tbl_personal p ON s.personal_id = p.id
             LEFT JOIN tbl_horarios_teoricos ht ON s.tipo_horario = 'TEORICO' AND s.horario_id = ht.id
             LEFT JOIN tbl_horarios_practicas hp ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id
             LEFT JOIN tbl_academic_offerings ao ON COALESCE(ht.offering_id, hp.offering_id) = ao.id
             LEFT JOIN tbl_diplomados d ON ao.diploma_id = d.id
             LEFT JOIN tbl_cohortes c ON ao.cohort_id = c.id
             LEFT JOIN tbl_sesion_asistencia sa ON sa.sesion_id = s.id
             WHERE p.profesor_id = :pid
               AND s.estado = 'PROGRAMADA'
               AND s.is_active = 1
               AND ao.id IS NOT NULL
             GROUP BY ao.id, d.name, c.name
             ORDER BY d.name ASC, c.name ASC"
        );
        $stmt->execute([':pid' => $professorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // SESIONES PROGRAMADAS DEL PROFESOR EN UNA OFERTA
    // =========================================================================

    public function getMisSesiones(int $professorId, int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id, s.tipo_horario, s.fecha, s.estado,
                    CASE
                        WHEN s.tipo_horario = 'TEORICO' THEN
                            CONCAT(ht.dia_semana, ' ',
                                   TIME_FORMAT(ht.hora_inicio, '%H:%i'), '–',
                                   TIME_FORMAT(ht.hora_fin, '%H:%i'))
                        WHEN s.tipo_horario = 'PRACTICA' THEN
                            CONCAT(gp.nombre, ' / ', cm.nombre)
                    END AS horario_desc,
                    (SELECT COUNT(*) FROM tbl_sesion_asistencia WHERE sesion_id = s.id) AS tiene_asistencia
             FROM tbl_sesiones s
             INNER JOIN tbl_personal p ON s.personal_id = p.id
             LEFT JOIN tbl_horarios_teoricos ht ON s.tipo_horario = 'TEORICO' AND s.horario_id = ht.id
             LEFT JOIN tbl_horarios_practicas hp ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id
             LEFT JOIN tbl_grupos_practica gp ON hp.grupo_id = gp.id
             LEFT JOIN tbl_centros_medicos cm ON hp.centro_medico_id = cm.id
             LEFT JOIN tbl_academic_offerings ao ON COALESCE(ht.offering_id, hp.offering_id) = ao.id
             WHERE p.profesor_id = :pid
               AND ao.id = :oid
               AND s.estado = 'PROGRAMADA'
               AND s.is_active = 1
             ORDER BY s.fecha ASC"
        );
        $stmt->execute([':pid' => $professorId, ':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // SESIÓN CON VALIDACIÓN DE PERTENENCIA AL PROFESOR
    // =========================================================================

    public function getSesion(int $sesionId, int $professorId): ?array
{
    $stmt = $this->db->prepare(
        "SELECT s.id, s.tipo_horario, s.fecha, s.estado,
                CASE
                    WHEN s.tipo_horario = 'TEORICO' THEN
                        CONCAT(ht.dia_semana, ' ',
                               TIME_FORMAT(ht.hora_inicio, '%H:%i'), '–',
                               TIME_FORMAT(ht.hora_fin, '%H:%i'))
                    WHEN s.tipo_horario = 'PRACTICA' THEN
                        CONCAT(gp.nombre, ' / ', cm.nombre)
                END AS horario_desc,
                d.name AS diplomado_nombre,
                c.name AS cohorte_nombre,
                ao.id  AS offering_id,
                prof.full_name AS profesor_nombre,
                (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                 FROM tbl_academic_offering_groups og2
                 INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                 WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre
         FROM tbl_sesiones s
         INNER JOIN tbl_personal p ON s.personal_id = p.id
         INNER JOIN tbl_professors prof ON prof.id = p.profesor_id
         LEFT JOIN tbl_horarios_teoricos ht ON s.tipo_horario = 'TEORICO' AND s.horario_id = ht.id
         LEFT JOIN tbl_horarios_practicas hp ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id
         LEFT JOIN tbl_grupos_practica gp ON hp.grupo_id = gp.id
         LEFT JOIN tbl_centros_medicos cm ON hp.centro_medico_id = cm.id
         LEFT JOIN tbl_academic_offerings ao ON COALESCE(ht.offering_id, hp.offering_id) = ao.id
         LEFT JOIN tbl_diplomados d ON ao.diploma_id = d.id
         LEFT JOIN tbl_cohortes c ON ao.cohort_id = c.id
         WHERE s.id = :sid
           AND p.profesor_id = :pid
           AND s.is_active = 1"
    );
    $stmt->execute([':sid' => $sesionId, ':pid' => $professorId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

    // =========================================================================
    // ESTUDIANTES CON ASISTENCIA YA CARGADA (si existe)
    // =========================================================================

    public function getEstudiantes(int $sesionId, int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.id AS enrollment_id,
                    u.first_name, u.last_name, u.document_id,
                    COALESCE(sa.asistio, 1) AS asistio,
                    sa.id AS asistencia_id
             FROM tbl_enrollments e
             INNER JOIN tbl_users u ON e.user_id = u.id
             LEFT JOIN tbl_sesion_asistencia sa
                    ON sa.sesion_id = :sid AND sa.enrollment_id = e.id
             WHERE e.offering_id = :oid
               AND e.status = 'APROBADO'
             ORDER BY u.last_name ASC, u.first_name ASC"
        );
        $stmt->execute([':sid' => $sesionId, ':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // GUARDAR ASISTENCIA
    // =========================================================================

    public function guardarAsistencia(int $sesionId, array $asistencia, int $userId): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_sesion_asistencia (sesion_id, enrollment_id, asistio, created_by)
             VALUES (:sid, :eid, :asistio, :uid)
             ON DUPLICATE KEY UPDATE asistio = VALUES(asistio)"
        );
        foreach ($asistencia as $enrollmentId => $asistio) {
            $stmt->execute([
                ':sid'     => $sesionId,
                ':eid'     => (int) $enrollmentId,
                ':asistio' => $asistio ? 1 : 0,
                ':uid'     => $userId,
            ]);
        }
    }

    public function tieneAsistencia(int $sesionId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_sesion_asistencia WHERE sesion_id = :sid"
        );
        $stmt->execute([':sid' => $sesionId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}