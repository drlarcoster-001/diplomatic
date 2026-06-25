<?php
/**
 * MÓDULO: PORTAL DOCENTE / CONTROL DE ASISTENCIA
 * ARCHIVO: app/models/ProfessorControlAsistenciaModel.php
 * PROPÓSITO: Obtiene las sesiones del profesor autenticado a través de la
 *            cadena tbl_professors → tbl_personal → tbl_sesiones.
 *            También entrega la lista de estudiantes matriculados para el PDF.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ProfessorControlAsistenciaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // OFERTAS DEL PROFESOR (para el selector)
    // =========================================================================

    public function getMisOfertas(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ao.id AS offering_id,
                    d.name  AS diplomado_nombre,
                    c.name  AS cohorte_nombre,
                    ao.general_modality,
                    COUNT(DISTINCT s.id) AS total_sesiones,
                    SUM(CASE WHEN s.estado = 'PROGRAMADA' THEN 1 ELSE 0 END) AS programadas,
                    SUM(CASE WHEN s.estado = 'DICTADA'    THEN 1 ELSE 0 END) AS dictadas
             FROM tbl_sesiones s
             INNER JOIN tbl_personal p ON s.personal_id = p.id
             LEFT JOIN tbl_horarios_teoricos ht
                    ON s.tipo_horario = 'TEORICO'  AND s.horario_id = ht.id
             LEFT JOIN tbl_horarios_practicas hp
                    ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id
             LEFT JOIN tbl_academic_offerings ao
                    ON COALESCE(ht.offering_id, hp.offering_id) = ao.id
             LEFT JOIN tbl_diplomados d ON ao.diploma_id = d.id
             LEFT JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
             WHERE p.profesor_id = :pid
               AND s.is_active   = 1
               AND ao.id IS NOT NULL
             GROUP BY ao.id, d.name, c.name, ao.general_modality
             ORDER BY d.name ASC, c.name ASC"
        );
        $stmt->execute([':pid' => $professorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // SESIONES DEL PROFESOR PARA UNA OFERTA
    // =========================================================================

    public function getMisSesiones(int $professorId, int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id, s.tipo_horario, s.fecha, s.estado,
                    CASE
                        WHEN s.tipo_horario = 'TEORICO' THEN
                            CONCAT(ht.dia_semana, ' ',
                                   TIME_FORMAT(ht.hora_inicio, '%H:%i'), '–',
                                   TIME_FORMAT(ht.hora_fin,   '%H:%i'))
                        WHEN s.tipo_horario = 'PRACTICA' THEN
                            CONCAT(gp.nombre, ' / ', cm.nombre)
                    END AS horario_desc
             FROM tbl_sesiones s
             INNER JOIN tbl_personal p ON s.personal_id = p.id
             LEFT JOIN tbl_horarios_teoricos ht
                    ON s.tipo_horario = 'TEORICO'  AND s.horario_id = ht.id
             LEFT JOIN tbl_horarios_practicas hp
                    ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id
             LEFT JOIN tbl_grupos_practica  gp ON hp.grupo_id        = gp.id
             LEFT JOIN tbl_centros_medicos  cm ON hp.centro_medico_id = cm.id
             LEFT JOIN tbl_academic_offerings ao
                    ON COALESCE(ht.offering_id, hp.offering_id) = ao.id
             WHERE p.profesor_id = :pid
               AND ao.id         = :oid
               AND s.is_active   = 1
             ORDER BY s.fecha ASC"
        );
        $stmt->execute([':pid' => $professorId, ':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // DATOS DE SESIÓN PARA PDF (con validación de pertenencia al profesor)
    // =========================================================================

    public function getSesionParaPdf(int $sesionId, int $professorId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id, s.tipo_horario, s.fecha, s.estado,
                    p.first_name, p.last_name,
                    CASE
                        WHEN s.tipo_horario = 'TEORICO' THEN
                            CONCAT(ht.dia_semana, ' ',
                                   TIME_FORMAT(ht.hora_inicio, '%H:%i'), '–',
                                   TIME_FORMAT(ht.hora_fin,   '%H:%i'))
                        WHEN s.tipo_horario = 'PRACTICA' THEN
                            CONCAT(gp.nombre, ' / ', cm.nombre)
                    END AS horario_desc,
                    d.name AS diplomado_nombre,
                    c.name AS cohorte_nombre,
                    ao.id  AS offering_id
             FROM tbl_sesiones s
             INNER JOIN tbl_personal p ON s.personal_id = p.id
             LEFT JOIN tbl_horarios_teoricos ht
                    ON s.tipo_horario = 'TEORICO'  AND s.horario_id = ht.id
             LEFT JOIN tbl_horarios_practicas hp
                    ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id
             LEFT JOIN tbl_grupos_practica  gp ON hp.grupo_id        = gp.id
             LEFT JOIN tbl_centros_medicos  cm ON hp.centro_medico_id = cm.id
             LEFT JOIN tbl_academic_offerings ao
                    ON COALESCE(ht.offering_id, hp.offering_id) = ao.id
             LEFT JOIN tbl_diplomados d ON ao.diploma_id = d.id
             LEFT JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
             WHERE s.id         = :sid
               AND p.profesor_id = :pid
               AND s.is_active   = 1"
        );
        $stmt->execute([':sid' => $sesionId, ':pid' => $professorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // ESTUDIANTES MATRICULADOS EN LA OFERTA
    // =========================================================================

    public function getEstudiantesPorOferta(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.first_name, u.last_name, u.document_id
             FROM tbl_enrollments e
             INNER JOIN tbl_users u ON e.user_id = u.id
             WHERE e.offering_id = :oid
               AND e.status = 'APROBADO'
             ORDER BY u.last_name ASC, u.first_name ASC"
        );
        $stmt->execute([':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}