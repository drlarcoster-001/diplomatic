<?php
/**
 * MÓDULO: RECURSOS HUMANOS / SESIONES
 * ARCHIVO: app/models/ResourcesSesionesModel.php
 * PROPÓSITO: Gestión de sesiones programadas. Obtiene ofertas activas con
 *            sus grupos habilitados, personal docente, horarios teóricos y
 *            prácticos por oferta, sesiones ya programadas por personal,
 *            y crea/elimina sesiones en tbl_sesiones.
 * VERSIÓN: 1.1.0 - Agrega grupos a getOfertasActivas y filtro por grupo en buscador.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ResourcesSesionesModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // OFERTAS (INDEX)
    // =========================================================================

    public function getOfertasActivas(string $search = '', int $page = 1, int $perPage = 25): array
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
                       (SELECT GROUP_CONCAT(g2.name ORDER BY g2.name SEPARATOR ', ')
                        FROM tbl_academic_offering_groups og2
                        INNER JOIN tbl_grupos g2 ON g2.id = og2.group_id
                        WHERE og2.offering_id = ao.id AND og2.is_enabled = 1) AS grupos_nombre,
                       COUNT(DISTINCT s.id) AS total_sesiones,
                       SUM(CASE WHEN s.estado = 'PROGRAMADA' THEN 1 ELSE 0 END) AS sesiones_programadas,
                       SUM(CASE WHEN s.estado = 'DICTADA' THEN 1 ELSE 0 END) AS sesiones_dictadas
                FROM tbl_academic_offerings ao
                INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                INNER JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
                LEFT JOIN tbl_academic_offering_groups og ON og.offering_id = ao.id AND og.is_enabled = 1
                LEFT JOIN tbl_grupos g ON g.id = og.group_id
                LEFT JOIN tbl_horarios_teoricos ht ON ht.offering_id = ao.id AND ht.is_active = 1
                LEFT JOIN tbl_sesiones s ON s.tipo_horario = 'TEORICO'
                    AND s.horario_id = ht.id AND s.is_active = 1
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
             LEFT JOIN tbl_academic_offering_groups og ON og.offering_id = ao.id AND og.is_enabled = 1
             LEFT JOIN tbl_grupos g ON g.id = og.group_id
             {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getOferta(int $offeringId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ao.id, d.name AS diplomado_nombre, c.name AS cohorte_nombre,
                    ao.general_modality, ao.status
             FROM tbl_academic_offerings ao
             INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
             INNER JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
             WHERE ao.id = :id"
        );
        $stmt->execute([':id' => $offeringId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // PERSONAL DOCENTE
    // =========================================================================

    public function getPersonalDocente(): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.first_name, p.last_name, p.document_id,
                    tp.nombre AS tipo_nombre
             FROM tbl_personal p
             INNER JOIN tbl_personal_tipos tp ON p.tipo_personal_id = tp.id
             WHERE p.is_active = 1 AND tp.nombre = 'Profesor'
             ORDER BY p.last_name ASC, p.first_name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // SESIONES DEL PERSONAL EN ESTA OFERTA
    // =========================================================================

    public function getSesionesByPersonal(int $personalId, int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id, s.tipo_horario, s.horario_id, s.fecha, s.estado,
                    CASE
                        WHEN s.tipo_horario = 'TEORICO' THEN
                            CONCAT(d.name, ' — ', ht.dia_semana, ' ', ht.hora_inicio, '-', ht.hora_fin)
                        WHEN s.tipo_horario = 'PRACTICA' THEN
                            CONCAT(d.name, ' — ', gp.nombre, ' / ', cm.nombre)
                    END AS descripcion
             FROM tbl_sesiones s
             LEFT JOIN tbl_horarios_teoricos ht ON s.tipo_horario = 'TEORICO' AND s.horario_id = ht.id
             LEFT JOIN tbl_horarios_practicas hp ON s.tipo_horario = 'PRACTICA' AND s.horario_id = hp.id
             LEFT JOIN tbl_grupos_practica gp ON hp.grupo_id = gp.id
             LEFT JOIN tbl_centros_medicos cm ON hp.centro_medico_id = cm.id
             LEFT JOIN tbl_academic_offerings ao ON COALESCE(ht.offering_id, hp.offering_id) = ao.id
             LEFT JOIN tbl_diplomados d ON ao.diploma_id = d.id
             WHERE s.personal_id = :pid
               AND COALESCE(ht.offering_id, hp.offering_id) = :oid
               AND s.is_active = 1
             ORDER BY s.fecha ASC"
        );
        $stmt->execute([':pid' => $personalId, ':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // HORARIOS TEÓRICOS DE LA OFERTA
    // =========================================================================

    public function getHorariosTeoricos(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ht.id, ht.dia_semana, ht.hora_inicio, ht.hora_fin,
                    COUNT(DISTINCT s.id) AS total_sesiones
             FROM tbl_horarios_teoricos ht
             LEFT JOIN tbl_sesiones s ON s.tipo_horario = 'TEORICO'
                AND s.horario_id = ht.id AND s.is_active = 1
             WHERE ht.offering_id = :oid AND ht.is_active = 1
             GROUP BY ht.id, ht.dia_semana, ht.hora_inicio, ht.hora_fin
             ORDER BY FIELD(ht.dia_semana,
                'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'),
                ht.hora_inicio ASC"
        );
        $stmt->execute([':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // HORARIOS PRÁCTICOS DE LA OFERTA
    // =========================================================================

    public function getHorariosPracticos(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT hp.id, gp.nombre AS grupo_nombre, cm.nombre AS centro_nombre,
                    hpf.id AS fecha_id, hpf.fecha,
                    COUNT(DISTINCT s.id) AS tiene_sesion
             FROM tbl_horarios_practicas hp
             INNER JOIN tbl_grupos_practica gp ON hp.grupo_id = gp.id
             INNER JOIN tbl_centros_medicos cm ON hp.centro_medico_id = cm.id
             INNER JOIN tbl_horario_practica_fechas hpf ON hpf.horario_practica_id = hp.id
                AND hpf.is_active = 1
             LEFT JOIN tbl_sesiones s ON s.tipo_horario = 'PRACTICA'
                AND s.horario_id = hp.id AND s.fecha = hpf.fecha AND s.is_active = 1
             WHERE hp.offering_id = :oid AND hp.is_active = 1
             GROUP BY hp.id, gp.nombre, cm.nombre, hpf.id, hpf.fecha
             ORDER BY gp.nombre ASC, hpf.fecha ASC"
        );
        $stmt->execute([':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // CREAR / ELIMINAR SESIONES
    // =========================================================================

    public function sesionExists(string $tipo, int $horarioId, string $fecha, int $personalId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_sesiones
             WHERE tipo_horario = :tipo AND horario_id = :hid
               AND fecha = :fecha AND personal_id = :pid AND is_active = 1"
        );
        $stmt->execute([':tipo' => $tipo, ':hid' => $horarioId, ':fecha' => $fecha, ':pid' => $personalId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function createSesion(array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_sesiones
                (tipo_horario, horario_id, personal_id, fecha, estado, is_active, created_by)
             VALUES (:tipo, :hid, :pid, :fecha, 'PROGRAMADA', 1, :uid)"
        );
        $stmt->execute([
            ':tipo'  => $data['tipo_horario'],
            ':hid'   => $data['horario_id'],
            ':pid'   => $data['personal_id'],
            ':fecha' => $data['fecha'],
            ':uid'   => $userId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteSesion(int $id, int $userId): void
    {
        $this->db->prepare(
            "UPDATE tbl_sesiones SET is_active = 0, updated_by = :uid WHERE id = :id"
        )->execute([':uid' => $userId, ':id' => $id]);
    }

    public function getSesionById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_sesiones WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}