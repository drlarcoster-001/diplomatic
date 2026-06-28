<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/models/AcademicHorariosTeoricosModel.php
 * PROPÓSITO: Acceso a datos de tbl_horarios_teoricos. Index muestra solo ofertas
 *            ABIERTA o EN CURSO, con grupos colapsados via GROUP_CONCAT para evitar
 *            duplicados por el JOIN con tbl_academic_offering_groups.
 * VERSIÓN: 3.2.0 - Fix de duplicados: GROUP_CONCAT de grupos. Filtro por status activo.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class AcademicHorariosTeoricosModel
{
    private PDO $db;
    protected string $table = 'tbl_horarios_teoricos';

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Lista ofertas ABIERTA/EN CURSO con grupos colapsados y conteo de horarios.
     * GROUP_CONCAT evita duplicados cuando una oferta tiene múltiples grupos.
     */
    public function getOfertasConHorarios(string $search = '', int $page = 1, int $perPage = 25, int $periodoId = 0): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = "WHERE ao.is_active = 1 AND ao.status IN ('ABIERTA', 'EN CURSO', 'BORRADOR')";
        $params = [];
        if ($search !== '') {
            $where .= " AND (d.name LIKE :search OR c.name LIKE :search OR g.name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        if ($periodoId) {
            $where .= " AND c.periodo_id = :periodo_id";
            $params[':periodo_id'] = $periodoId;
        }

        $sql = "SELECT ao.id AS offering_id,
                       d.name  AS diplomado_nombre,
                       c.name  AS cohorte_nombre,
                       ao.general_modality,
                       ao.status,
                       ao.class_start,
                       ao.class_end,
                       GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') AS grupos,
                       COUNT(DISTINCT ht.id) AS total_horarios
                FROM tbl_academic_offerings ao
                INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
                INNER JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
                LEFT JOIN tbl_academic_offering_groups aog ON aog.offering_id = ao.id
                LEFT JOIN tbl_grupos g ON g.id = aog.group_id
                LEFT JOIN {$this->table} ht ON ht.offering_id = ao.id AND ht.is_active = 1
                {$where}
                GROUP BY ao.id, d.name, c.name, ao.general_modality, ao.status,
                         ao.class_start, ao.class_end
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

    public function getByOffering(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ht.id, ht.offering_id, ht.dia_semana, ht.hora_inicio, ht.hora_fin,
                    d.name AS diplomado_nombre, c.name AS cohorte_nombre
             FROM {$this->table} ht
             INNER JOIN tbl_academic_offerings ao ON ht.offering_id = ao.id
             INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
             INNER JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
             WHERE ht.offering_id = :oid AND ht.is_active = 1
             ORDER BY FIELD(ht.dia_semana,
                'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'),
                ht.hora_inicio ASC"
        );
        $stmt->execute([':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ht.*, d.name AS diplomado_nombre, c.name AS cohorte_nombre
             FROM {$this->table} ht
             INNER JOIN tbl_academic_offerings ao ON ht.offering_id = ao.id
             INNER JOIN tbl_diplomados d ON ao.diploma_id = d.id
             INNER JOIN tbl_cohortes   c ON ao.cohort_id  = c.id
             WHERE ht.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function exists(int $offeringId, string $diaSemana, string $horaInicio, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM {$this->table}
                   WHERE offering_id = :oid AND dia_semana = :dia
                     AND hora_inicio = :ini AND is_active = 1";
        $params = [':oid' => $offeringId, ':dia' => $diaSemana, ':ini' => $horaInicio];
        if ($excludeId) { $sql .= " AND id != :id"; $params[':id'] = $excludeId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data, int $userId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
                (offering_id, dia_semana, hora_inicio, hora_fin, is_active, created_by)
             VALUES (:oid, :dia, :ini, :fin, 1, :uid)"
        );
        $stmt->execute([
            ':oid' => $data['offering_id'],
            ':dia' => $data['dia_semana'],
            ':ini' => $data['hora_inicio'],
            ':fin' => $data['hora_fin'],
            ':uid' => $userId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET dia_semana = :dia, hora_inicio = :ini, hora_fin = :fin, updated_by = :uid
             WHERE id = :id"
        );
        return $stmt->execute([
            ':dia' => $data['dia_semana'],
            ':ini' => $data['hora_inicio'],
            ':fin' => $data['hora_fin'],
            ':uid' => $userId,
            ':id'  => $id,
        ]);
    }

    public function hasReferences(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_sesiones
             WHERE tipo_horario = 'TEORICO' AND horario_id = :id"
        );
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function smartDelete(int $id, int $userId): string
    {
        if ($this->hasReferences($id)) {
            $this->db->prepare(
                "UPDATE {$this->table} SET is_active = 0, updated_by = :uid WHERE id = :id"
            )->execute([':uid' => $userId, ':id' => $id]);
            return 'inactivated';
        }
        $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id")
                 ->execute([':id' => $id]);
        return 'deleted';
    }

    public function getPeriodos(): array
{
    $stmt = $this->db->query(
        "SELECT id, nombre, estado FROM tbl_periodos_cohorte
         WHERE is_active = 1 ORDER BY id DESC"
    );
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}
}