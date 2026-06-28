<?php
/**
 * MÓDULO: PORTAL DOCENTE / CONTROL DE ASISTENCIA
 * ARCHIVO: app/models/ProfessorControlAsistenciaModel.php
 * PROPÓSITO: Obtiene las sesiones del profesor autenticado a través de la
 *            cadena tbl_professors → tbl_personal → tbl_sesiones.
 *            También entrega la lista de estudiantes matriculados para el PDF.
 * VERSIÓN: 1.1.0 - Agrega filtros por período y grupo en oferta.
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
    // PERÍODOS DEL PROFESOR
    // =========================================================================

    public function getPeriodos(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT p.id, p.nombre, p.estado
             FROM tbl_periodos_cohorte p
             INNER JOIN tbl_cohortes c ON c.periodo_id = p.id
             INNER JOIN tbl_academic_offerings ao ON ao.cohort_id = c.id
             INNER JOIN tbl_horarios_teoricos ht ON ht.offering_id = ao.id
             INNER JOIN tbl_sesiones s ON s.horario_id = ht.id AND s.tipo_horario = 'TEORICO'
             INNER JOIN tbl_personal per ON per.id = s.personal_id
             WHERE per.profesor_id = :pid AND p.is_active = 1
             UNION
             SELECT DISTINCT p.id, p.nombre, p.estado
             FROM tbl_periodos_cohorte p
             INNER JOIN tbl_cohortes c ON c.periodo_id = p.id
             INNER JOIN tbl_academic_offerings ao ON ao.cohort_id = c.id
             INNER JOIN tbl_horarios_practicas hp ON hp.offering_id = ao.id
             INNER JOIN tbl_sesiones s ON s.horario_id = hp.id AND s.tipo_horario = 'PRACTICA'
             INNER JOIN tbl_personal per ON per.id = s.personal_id
             WHERE per.profesor_id = :pid2 AND p.is_active = 1
             ORDER BY id DESC"
        );
        $stmt->execute([':pid' => $professorId, ':pid2' => $professorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // OFERTAS DEL PROFESOR (para el selector)
    // =========================================================================

    public function getMisOfertas(int $professorId, int $periodoId = 0): array
    {
        $filtroPeriodo = $periodoId ? "AND c.periodo_id = :periodo_id" : "";
        $stmt = $this->db->prepare(
            "SELECT ao.id AS offering_id,
                    d.name  AS diplomado_nombre,
                    c.name  AS cohorte_nombre,
                    ao.general_modality,
                    (SELECT GROUP_CONCAT(g.name SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og
                     INNER JOIN tbl_grupos g ON g.id = og.group_id
                     WHERE og.offering_id = ao.id AND og.is_enabled = 1) AS grupos_nombre,
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
               {$filtroPeriodo}
             GROUP BY ao.id, d.name, c.name, ao.general_modality
             ORDER BY d.name ASC, c.name ASC"
        );
        $params = [':pid' => $professorId];
        if ($periodoId) $params[':periodo_id'] = $periodoId;
        $stmt->execute($params);
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
    // DATOS DE SESIÓN PARA PDF
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