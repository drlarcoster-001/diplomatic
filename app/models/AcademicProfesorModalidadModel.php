<?php
/**
 * MÓDULO: ACADÉMICO / ASIGNACIÓN DE MODALIDAD A PROFESORES
 * ARCHIVO: app/models/AcademicProfesorModalidadModel.php
 * PROPÓSITO: Tabla nueva e independiente (tbl_profesor_modalidad) que
 *            registra qué profesor dicta cuál modalidad (Teórica/Práctica/
 *            Virtual) en cada oferta académica. Es la fuente de verdad
 *            exclusiva del Portal Docente — no reemplaza ni depende de
 *            tbl_academic_offering_professors ni tbl_grupos_practica, que
 *            siguen funcionando igual para lo que ya hacían.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class AcademicProfesorModalidadModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function getAsignaciones(string $search = ''): array
    {
        $where  = "WHERE pm.modalidad = 'VIRTUAL'";
        $params = [];
        if ($search !== '') {
            $where .= " AND (p.full_name LIKE :s OR d.name LIKE :s)";
            $params[':s'] = "%{$search}%";
        }
        $stmt = $this->db->prepare(
            "SELECT pm.id, pm.modalidad, pm.created_at, pm.offering_id, pm.professor_id, pm.offering_group_id,
                    p.full_name AS profesor_nombre,
                    d.name AS diplomado_nombre, c.name AS cohorte_nombre, c.start_date,
                    g.name AS grupo_nombre
             FROM tbl_profesor_modalidad pm
             INNER JOIN tbl_professors p ON p.id = pm.professor_id
             INNER JOIN tbl_academic_offerings o ON o.id = pm.offering_id
             INNER JOIN tbl_diplomados d ON d.id = o.diploma_id
             INNER JOIN tbl_cohortes c ON c.id = o.cohort_id
             LEFT JOIN tbl_academic_offering_groups og ON og.id = pm.offering_group_id
             LEFT JOIN tbl_grupos g ON g.id = og.group_id
             {$where}
             ORDER BY pm.created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProfesores(): array
    {
        $stmt = $this->db->query("SELECT id, full_name FROM tbl_professors WHERE is_active = 1 ORDER BY full_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOfertas(): array
    {
        $stmt = $this->db->query(
            "SELECT o.id, d.name AS diplomado_nombre, c.name AS cohorte_nombre, c.start_date,
                    (SELECT GROUP_CONCAT(g.name SEPARATOR ', ')
                     FROM tbl_academic_offering_groups og
                     INNER JOIN tbl_grupos g ON g.id = og.group_id
                     WHERE og.offering_id = o.id AND og.is_enabled = 1) AS grupos_nombre
             FROM tbl_academic_offerings o
             INNER JOIN tbl_diplomados d ON d.id = o.diploma_id
             INNER JOIN tbl_cohortes c ON c.id = o.cohort_id
             ORDER BY c.start_date DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mapa profesor_id => [offering_id, offering_id, ...] basado en la
     * asignación YA EXISTENTE en tbl_academic_offering_professors (la que
     * se hace desde Ofertas Académicas). Se usa para que, al elegir un
     * profesor en el formulario de Modalidad, el buscador de Oferta solo
     * muestre las ofertas donde ese profesor ya está vinculado — no se
     * puede asignar modalidad a una oferta donde el profesor no aparece.
     */
    public function getMapaProfesorOfertas(): array
    {
        $stmt = $this->db->query("SELECT professor_id, offering_id FROM tbl_academic_offering_professors");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mapa = [];
        foreach ($rows as $r) {
            $pid = (string) $r['professor_id'];
            if (!isset($mapa[$pid])) $mapa[$pid] = [];
            if (!in_array((int) $r['offering_id'], $mapa[$pid], true)) {
                $mapa[$pid][] = (int) $r['offering_id'];
            }
        }
        return $mapa;
    }

    /**
     * Grupos habilitados para una oferta específica (vía
     * tbl_academic_offering_groups + tbl_grupos). Solo se usa cuando la
     * modalidad es TEORICA, ya que Práctica y Virtual no se subdividen
     * por grupo — son un solo profesor para toda la oferta.
     */
    public function getGruposPorOferta(int $offeringId): array
    {
        $stmt = $this->db->prepare(
            "SELECT og.id AS offering_group_id, g.name AS grupo_nombre
             FROM tbl_academic_offering_groups og
             INNER JOIN tbl_grupos g ON g.id = og.group_id
             WHERE og.offering_id = :oid AND og.is_enabled = 1
             ORDER BY g.name"
        );
        $stmt->execute([':oid' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mapa offering_id => [{offering_group_id, grupo_nombre}, ...] para
     * TODAS las ofertas, usado por el buscador inteligente de Grupo
     * (se filtra en el navegador según la oferta elegida, sin AJAX).
     */
    public function getMapaOfertaGrupos(): array
    {
        $stmt = $this->db->query(
            "SELECT og.offering_id, og.id AS offering_group_id, g.name AS grupo_nombre
             FROM tbl_academic_offering_groups og
             INNER JOIN tbl_grupos g ON g.id = og.group_id
             WHERE og.is_enabled = 1"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mapa = [];
        foreach ($rows as $r) {
            $oid = (string) $r['offering_id'];
            if (!isset($mapa[$oid])) $mapa[$oid] = [];
            $mapa[$oid][] = ['id' => (int) $r['offering_group_id'], 'label' => $r['grupo_nombre']];
        }
        return $mapa;
    }

    /**
     * CORRECCIÓN: para TEORICA, el duplicado se valida por
     * (offering_id, modalidad, offering_group_id) — distintos grupos de
     * la misma oferta SÍ pueden tener profesores distintos. Para
     * PRACTICA/VIRTUAL, sigue siendo por (offering_id, modalidad) ya que
     * no se subdividen por grupo.
     */
    public function asignacionExists(int $offeringId, string $modalidad, ?int $groupId = null): bool
    {
        if ($modalidad === 'TEORICA') {
            $stmt = $this->db->prepare(
                "SELECT id FROM tbl_profesor_modalidad
                 WHERE offering_id = :oid AND modalidad = :m
                   AND offering_group_id " . ($groupId ? "= :gid" : "IS NULL")
            );
            $params = [':oid' => $offeringId, ':m' => $modalidad];
            if ($groupId) $params[':gid'] = $groupId;
            $stmt->execute($params);
            return (bool) $stmt->fetch();
        }
        $stmt = $this->db->prepare("SELECT id FROM tbl_profesor_modalidad WHERE offering_id = :oid AND modalidad = :m");
        $stmt->execute([':oid' => $offeringId, ':m' => $modalidad]);
        return (bool) $stmt->fetch();
    }

    public function crearAsignacion(int $offeringId, int $professorId, string $modalidad, int $userId, ?int $groupId = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_profesor_modalidad (offering_id, offering_group_id, professor_id, modalidad, created_by)
             VALUES (:oid, :gid, :pid, :m, :uid)"
        );
        $stmt->execute([':oid' => $offeringId, ':gid' => $groupId, ':pid' => $professorId, ':m' => $modalidad, ':uid' => $userId]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Crea varias asignaciones de una vez. groupId solo se aplica a la
     * fila de TEORICA (si está entre las modalidades marcadas); Práctica
     * y Virtual siempre se crean con offering_group_id NULL.
     */
    public function crearAsignacionesMultiples(int $offeringId, int $professorId, array $modalidades, int $userId, ?int $groupId = null): array
    {
        $creadas  = [];
        $omitidas = [];
        foreach ($modalidades as $modalidad) {
            $gidParaEsta = ($modalidad === 'TEORICA') ? $groupId : null;
            if ($this->asignacionExists($offeringId, $modalidad, $gidParaEsta)) {
                $omitidas[] = $modalidad;
                continue;
            }
            $this->crearAsignacion($offeringId, $professorId, $modalidad, $userId, $gidParaEsta);
            $creadas[] = $modalidad;
        }
        return ['creadas' => $creadas, 'omitidas' => $omitidas];
    }

    public function eliminarAsignacion(int $id): void
    {
        $this->db->prepare("DELETE FROM tbl_profesor_modalidad WHERE id = :id")->execute([':id' => $id]);
    }

    public function getAsignacionById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_profesor_modalidad WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function actualizarAsignacion(int $id, int $offeringId, int $professorId, string $modalidad, ?int $groupId = null): void
    {
        $gid = ($modalidad === 'TEORICA') ? $groupId : null;
        $stmt = $this->db->prepare(
            "UPDATE tbl_profesor_modalidad SET offering_id = :oid, offering_group_id = :gid, professor_id = :pid, modalidad = :m WHERE id = :id"
        );
        $stmt->execute([':oid' => $offeringId, ':gid' => $gid, ':pid' => $professorId, ':m' => $modalidad, ':id' => $id]);
    }

    public function asignacionExistsExcluding(int $offeringId, string $modalidad, int $excludeId, ?int $groupId = null): bool
    {
        if ($modalidad === 'TEORICA') {
            $stmt = $this->db->prepare(
                "SELECT id FROM tbl_profesor_modalidad
                 WHERE offering_id = :oid AND modalidad = :m AND id != :ex
                   AND offering_group_id " . ($groupId ? "= :gid" : "IS NULL")
            );
            $params = [':oid' => $offeringId, ':m' => $modalidad, ':ex' => $excludeId];
            if ($groupId) $params[':gid'] = $groupId;
            $stmt->execute($params);
            return (bool) $stmt->fetch();
        }
        $stmt = $this->db->prepare(
            "SELECT id FROM tbl_profesor_modalidad WHERE offering_id = :oid AND modalidad = :m AND id != :ex"
        );
        $stmt->execute([':oid' => $offeringId, ':m' => $modalidad, ':ex' => $excludeId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Si la oferta tiene como único grupo habilitado "ONLINE", solo debe
     * permitirse la modalidad VIRTUAL (no tiene sentido Teórica/Práctica
     * presencial en una oferta 100% en línea).
     */
    public function esOfertaOnline(int $offeringId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT g.name FROM tbl_academic_offering_groups og
             INNER JOIN tbl_grupos g ON g.id = og.group_id
             WHERE og.offering_id = :oid AND og.is_enabled = 1"
        );
        $stmt->execute([':oid' => $offeringId]);
        $grupos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($grupos as $g) {
            if (stripos($g, 'ONLINE') !== false) return true;
        }
        return false;
    }
    /**
     * Validación de respaldo (servidor): confirma que el profesor SÍ está
     * vinculado a esa oferta en tbl_academic_offering_professors, antes de
     * permitir crear/editar una modalidad. El filtrado en el formulario ya
     * hace esto visualmente, pero esto evita que alguien fuerce un POST
     * directo con una combinación inválida.
     */
    public function profesorTieneOferta(int $professorId, int $offeringId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM tbl_academic_offering_professors WHERE professor_id = :pid AND offering_id = :oid"
        );
        $stmt->execute([':pid' => $professorId, ':oid' => $offeringId]);
        return (bool) $stmt->fetch();
    }
}