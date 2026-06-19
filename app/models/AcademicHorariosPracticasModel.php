<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/models/AcademicHorariosPracticasModel.php
 * PROPÓSITO: Gestión de horarios de práctica con grupos, estudiantes, centros médicos
 *            y fechas específicas por asignación grupo→centro (tbl_horario_practica_fechas).
 * VERSIÓN: 2.0.0 - Agrega manejo de fechas específicas por horario de práctica.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class AcademicHorariosPracticasModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // OFERTAS (INDEX)
    // =========================================================================

    public function getOfertasConHorarios(string $search = '', int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE ao.is_active = 1 AND ao.status IN ('ABIERTA', 'EN CURSO')";
        $params = [];

        if ($search !== '') {
            $where .= " AND (d.name LIKE :search OR c.name LIKE :search OR g.name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql = "SELECT ao.id AS offering_id,
                       d.name AS diplomado_nombre,
                       c.name AS cohorte_nombre,
                       ao.general_modality,
                       ao.status,
                       GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') AS grupos,
                       COUNT(DISTINCT gp.id) AS total_grupos,
                       COUNT(DISTINCT hp.id) AS total_horarios,
                       COUNT(DISTINCT e.id)  AS total_matriculados
                FROM tbl_academic_offerings ao
                INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                INNER JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
                LEFT JOIN tbl_academic_offering_groups aog ON aog.offering_id = ao.id
                LEFT JOIN tbl_grupos g    ON g.id = aog.group_id
                LEFT JOIN tbl_grupos_practica gp ON gp.offering_id = ao.id AND gp.is_active = 1
                LEFT JOIN tbl_horarios_practicas hp ON hp.offering_id = ao.id AND hp.is_active = 1
                LEFT JOIN tbl_enrollments e ON e.offering_id = ao.id AND e.status = 'APROBADO'
                {$where}
                GROUP BY ao.id, d.name, c.name, ao.general_modality, ao.status
                ORDER BY d.name ASC, c.name ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countOfertas(string $search = ''): int
    {
        $where  = "WHERE ao.is_active = 1 AND ao.status IN ('ABIERTA', 'EN CURSO')";
        $params = [];
        if ($search !== '') {
            $where .= " AND (d.name LIKE :search OR c.name LIKE :search OR g.name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT ao.id)
             FROM tbl_academic_offerings ao
             INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
             INNER JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
             LEFT JOIN tbl_academic_offering_groups aog ON aog.offering_id = ao.id
             LEFT JOIN tbl_grupos g ON g.id = aog.group_id
             {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getOferta(int $offeringId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ao.id, d.name AS diplomado_nombre, c.name AS cohorte_nombre,
                    ao.general_modality, ao.status,
                    GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') AS grupos
             FROM tbl_academic_offerings ao
             INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
             INNER JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
             LEFT JOIN tbl_academic_offering_groups aog ON aog.offering_id = ao.id
             LEFT JOIN tbl_grupos g ON g.id = aog.group_id
             WHERE ao.id = :id
             GROUP BY ao.id, d.name, c.name, ao.general_modality, ao.status"
        );
        $stmt->execute([':id' => $offeringId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // GRUPOS DE PRÁCTICA
    // =========================================================================

    public function getGruposByOffering(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT gp.id, gp.nombre,
                    COUNT(DISTINCT ge.id) AS total_estudiantes
            FROM tbl_grupos_practica gp
            LEFT JOIN tbl_grupo_estudiantes ge ON ge.grupo_id = gp.id AND ge.is_active = 1
            WHERE gp.offering_id = :oid AND gp.is_active = 1 AND gp.nombre IS NOT NULL
            GROUP BY gp.id, gp.nombre
            ORDER BY gp.nombre ASC"
        );
        $stmt->execute([':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }

    public function getGrupoById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_grupos_practica WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function grupoNombreExists(int $offeringId, string $nombre, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM tbl_grupos_practica WHERE offering_id=:oid AND nombre=:n AND is_active=1";
        $params = [':oid' => $offeringId, ':n' => $nombre];
        if ($excludeId) { $sql .= " AND id != :id"; $params[':id'] = $excludeId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function createGrupo(int $offeringId, string $nombre, int $userId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_grupos_practica (offering_id, nombre, is_active, created_by)
             VALUES (:oid, :nombre, 1, :uid)"
        );
        $stmt->execute([':oid' => $offeringId, ':nombre' => $nombre, ':uid' => $userId]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteGrupo(int $id, int $userId): string
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_grupo_estudiantes WHERE grupo_id=:id AND is_active=1"
        );
        $stmt->execute([':id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->db->prepare(
                "UPDATE tbl_grupos_practica SET is_active=0, updated_by=:uid WHERE id=:id"
            )->execute([':uid' => $userId, ':id' => $id]);
            return 'inactivated';
        }
        $this->db->prepare("DELETE FROM tbl_grupos_practica WHERE id=:id")->execute([':id' => $id]);
        return 'deleted';
    }

    // =========================================================================
    // ESTUDIANTES POR GRUPO
    // =========================================================================

    public function getEstudiantesDelGrupo(int $grupoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ge.id AS asignacion_id, ge.enrollment_id,
                    u.first_name, u.last_name, u.document_id
             FROM tbl_grupo_estudiantes ge
             INNER JOIN tbl_enrollments e ON ge.enrollment_id = e.id
             INNER JOIN tbl_users u ON e.user_id = u.id
             WHERE ge.grupo_id = :gid AND ge.is_active = 1
             ORDER BY u.last_name ASC, u.first_name ASC"
        );
        $stmt->execute([':gid' => $grupoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEstudiantesSinGrupo(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.id AS enrollment_id, u.first_name, u.last_name, u.document_id
             FROM tbl_enrollments e
             INNER JOIN tbl_users u ON e.user_id = u.id
             WHERE e.offering_id = :oid AND e.status = 'APROBADO'
               AND e.id NOT IN (
                   SELECT ge.enrollment_id
                   FROM tbl_grupo_estudiantes ge
                   INNER JOIN tbl_grupos_practica gp ON ge.grupo_id = gp.id
                   WHERE gp.offering_id = :oid2 AND ge.is_active = 1 AND gp.is_active = 1
               )
             ORDER BY u.last_name ASC, u.first_name ASC"
        );
        $stmt->execute([':oid' => $offeringId, ':oid2' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveEstudiante(int $grupoId, int $enrollmentId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_grupo_estudiantes ge
             INNER JOIN tbl_grupos_practica gp ON ge.grupo_id = gp.id
             WHERE ge.enrollment_id = :eid
               AND gp.offering_id = (SELECT offering_id FROM tbl_grupos_practica WHERE id = :gid)
               AND ge.is_active = 1"
        );
        $stmt->execute([':eid' => $enrollmentId, ':gid' => $grupoId]);
        if ((int) $stmt->fetchColumn() > 0) return false;

        $this->db->prepare(
            "INSERT INTO tbl_grupo_estudiantes (grupo_id, enrollment_id, is_active, created_by)
             VALUES (:gid, :eid, 1, :uid)"
        )->execute([':gid' => $grupoId, ':eid' => $enrollmentId, ':uid' => $userId]);
        return true;
    }

    public function deleteEstudiante(int $asignacionId): void
    {
        $this->db->prepare("DELETE FROM tbl_grupo_estudiantes WHERE id=:id")
                 ->execute([':id' => $asignacionId]);
    }

    // =========================================================================
    // HORARIOS DE PRÁCTICA (GRUPO → CENTRO MÉDICO)
    // =========================================================================

    public function getHorariosByOffering(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT hp.id, hp.offering_id, hp.centro_medico_id, hp.grupo_id,
                    cm.nombre AS centro_nombre,
                    gp.nombre AS grupo_nombre,
                    COUNT(hpf.id) AS total_fechas
             FROM tbl_horarios_practicas hp
             INNER JOIN tbl_centros_medicos cm ON hp.centro_medico_id = cm.id
             INNER JOIN tbl_grupos_practica gp ON hp.grupo_id = gp.id
             LEFT JOIN tbl_horario_practica_fechas hpf ON hpf.horario_practica_id = hp.id AND hpf.is_active = 1
             WHERE hp.offering_id = :oid AND hp.is_active = 1
             GROUP BY hp.id, hp.offering_id, hp.centro_medico_id, hp.grupo_id,
                      cm.nombre, gp.nombre
             ORDER BY gp.nombre ASC, cm.nombre ASC"
        );
        $stmt->execute([':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHorarioById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT hp.*, cm.nombre AS centro_nombre, gp.nombre AS grupo_nombre
             FROM tbl_horarios_practicas hp
             INNER JOIN tbl_centros_medicos cm ON hp.centro_medico_id = cm.id
             INNER JOIN tbl_grupos_practica gp ON hp.grupo_id = gp.id
             WHERE hp.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getCentrosMedicos(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre FROM tbl_centros_medicos WHERE is_active=1 ORDER BY nombre ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function horarioExists(int $offeringId, int $grupoId, int $centroId, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM tbl_horarios_practicas
                   WHERE offering_id=:oid AND grupo_id=:gid AND centro_medico_id=:cid AND is_active=1";
        $params = [':oid' => $offeringId, ':gid' => $grupoId, ':cid' => $centroId];
        if ($excludeId) { $sql .= " AND id != :id"; $params[':id'] = $excludeId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function saveHorario(array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_horarios_practicas
                (offering_id, grupo_id, centro_medico_id, is_active, created_by)
             VALUES (:oid, :gid, :cid, 1, :uid)"
        );
        $stmt->execute([
            ':oid' => $data['offering_id'],
            ':gid' => $data['grupo_id'],
            ':cid' => $data['centro_medico_id'],
            ':uid' => $userId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteHorario(int $id, int $userId): string
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_sesiones WHERE tipo_horario='PRACTICA' AND horario_id=:id"
        );
        $stmt->execute([':id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->db->prepare(
                "UPDATE tbl_horarios_practicas SET is_active=0, updated_by=:uid WHERE id=:id"
            )->execute([':uid' => $userId, ':id' => $id]);
            return 'inactivated';
        }
        // Borrar fechas asociadas primero
        $this->db->prepare("DELETE FROM tbl_horario_practica_fechas WHERE horario_practica_id=:id")
                 ->execute([':id' => $id]);
        $this->db->prepare("DELETE FROM tbl_horarios_practicas WHERE id=:id")->execute([':id' => $id]);
        return 'deleted';
    }

    // =========================================================================
    // FECHAS POR HORARIO DE PRÁCTICA
    // =========================================================================

    public function getFechasByHorario(int $horarioPracticaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, fecha FROM tbl_horario_practica_fechas
             WHERE horario_practica_id=:id AND is_active=1
             ORDER BY fecha ASC"
        );
        $stmt->execute([':id' => $horarioPracticaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna TODAS las fechas de todos los horarios de una oferta,
     * con info de grupo y centro para pintar el calendario.
     */
    public function getFechasByOffering(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT hpf.id, hpf.fecha, hpf.horario_practica_id,
                    gp.id AS grupo_id, gp.nombre AS grupo_nombre,
                    cm.nombre AS centro_nombre
             FROM tbl_horario_practica_fechas hpf
             INNER JOIN tbl_horarios_practicas hp ON hpf.horario_practica_id = hp.id
             INNER JOIN tbl_grupos_practica gp ON hp.grupo_id = gp.id
             INNER JOIN tbl_centros_medicos cm ON hp.centro_medico_id = cm.id
             WHERE hp.offering_id = :oid AND hp.is_active = 1 AND hpf.is_active = 1
             ORDER BY hpf.fecha ASC, gp.nombre ASC"
        );
        $stmt->execute([':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda un lote de fechas para un horario de práctica.
     * Elimina las anteriores y reemplaza con las nuevas.
     */
    public function saveFechas(int $horarioPracticaId, array $fechas, int $userId): void
    {
        // Borrar fechas anteriores de este horario
        $this->db->prepare(
            "DELETE FROM tbl_horario_practica_fechas WHERE horario_practica_id=:id"
        )->execute([':id' => $horarioPracticaId]);

        if (empty($fechas)) return;

        $stmt = $this->db->prepare(
            "INSERT INTO tbl_horario_practica_fechas (horario_practica_id, fecha, is_active, created_by)
             VALUES (:hid, :fecha, 1, :uid)"
        );
        foreach ($fechas as $fecha) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                $stmt->execute([':hid' => $horarioPracticaId, ':fecha' => $fecha, ':uid' => $userId]);
            }
        }
    }

    public function deleteFecha(int $id): void
    {
        $this->db->prepare("DELETE FROM tbl_horario_practica_fechas WHERE id=:id")->execute([':id' => $id]);
    }
}