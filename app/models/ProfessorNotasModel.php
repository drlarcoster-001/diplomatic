<?php
/**
 * MÓDULO: PORTAL DOCENTE / CARGAR NOTAS
 * ARCHIVO: app/models/ProfessorNotasModel.php
 * PROPÓSITO: Obtiene ofertas y modalidades del profesor desde tbl_profesor_modalidad.
 *            Gestiona notas en tbl_notas_estudiantes y actas en tbl_actas.
 *            Cadena: tbl_professors → tbl_profesor_modalidad → tbl_academic_offerings.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ProfessorNotasModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // OFERTAS DEL PROFESOR (desde tbl_profesor_modalidad)
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

    public function getMisOfertas(int $professorId, int $periodoId = 0): array
    {
        $filtroPeriodo = $periodoId ? "AND c.periodo_id = :periodo_id" : "";
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
                 SELECT offering_id FROM tbl_academic_offering_professors WHERE professor_id = :pid2
                 UNION
                 SELECT offering_id FROM tbl_profesor_modalidad WHERE professor_id = :pid3
             )
               AND ao.is_active = 1
               AND ao.status = 'ABIERTA'
               AND c.cohort_status IN ('En curso', 'Reabierta')
               {$filtroPeriodo}
             GROUP BY ao.id, d.name, c.name
             ORDER BY d.name ASC, c.name ASC"
        );
        $params = [':pid2' => $professorId, ':pid3' => $professorId];
        if ($periodoId) $params[':periodo_id'] = $periodoId;
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // MODALIDADES DEL PROFESOR EN UNA OFERTA
    // =========================================================================

    public function getMisModalidades(int $professorId, int $offeringId): array
    {
        $modalidades = [];

        // VIRTUAL → tbl_profesor_modalidad
        $stmt = $this->db->prepare(
            "SELECT modalidad FROM tbl_profesor_modalidad
             WHERE professor_id = :pid AND offering_id = :oid AND modalidad = 'VIRTUAL'"
        );
        $stmt->execute([':pid' => $professorId, ':oid' => $offeringId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $modalidades = array_merge($modalidades, $rows);

        // TEORICA → tbl_sesiones
        $stmt2 = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_sesiones s
             INNER JOIN tbl_personal per ON per.id = s.personal_id
             INNER JOIN tbl_horarios_teoricos ht ON ht.id = s.horario_id AND s.tipo_horario = 'TEORICO'
             WHERE per.profesor_id = :pid AND ht.offering_id = :oid AND s.is_active = 1"
        );
        $stmt2->execute([':pid' => $professorId, ':oid' => $offeringId]);
        if ((int) $stmt2->fetchColumn() > 0) $modalidades[] = 'TEORICA';

        // PRACTICA → tbl_sesiones
        $stmt3 = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_sesiones s
             INNER JOIN tbl_personal per ON per.id = s.personal_id
             INNER JOIN tbl_horarios_practicas hp ON hp.id = s.horario_id AND s.tipo_horario = 'PRACTICA'
             WHERE per.profesor_id = :pid AND hp.offering_id = :oid AND s.is_active = 1"
        );
        $stmt3->execute([':pid' => $professorId, ':oid' => $offeringId]);
        if ((int) $stmt3->fetchColumn() > 0) $modalidades[] = 'PRACTICA';

        // Si está en tbl_academic_offering_professors y no tiene ninguna modalidad, asumir TEORICA
        if (empty($modalidades)) {
            $stmt4 = $this->db->prepare(
                "SELECT COUNT(*) FROM tbl_academic_offering_professors
                 WHERE professor_id = :pid AND offering_id = :oid"
            );
            $stmt4->execute([':pid' => $professorId, ':oid' => $offeringId]);
            if ((int) $stmt4->fetchColumn() > 0) $modalidades[] = 'TEORICA';
        }

        // Ordenar
        $orden = ['TEORICA' => 1, 'PRACTICA' => 2, 'VIRTUAL' => 3];
        usort($modalidades, fn($a, $b) => ($orden[$a] ?? 9) - ($orden[$b] ?? 9));

        return array_values(array_unique($modalidades));
    }

    // =========================================================================
    // VALIDAR QUE EL PROFESOR TIENE ESA MODALIDAD EN ESA OFERTA
    // =========================================================================

    public function profesorTieneModalidad(int $professorId, int $offeringId, string $modalidad): bool
    {
        $modalidades = $this->getMisModalidades($professorId, $offeringId);
        return in_array($modalidad, $modalidades, true);
    }

    public function profesorTieneModalidadLegacy(int $professorId, int $offeringId, string $modalidad): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_profesor_modalidad
             WHERE professor_id = :pid AND offering_id = :oid AND modalidad = :m"
        );
        $stmt->execute([':pid' => $professorId, ':oid' => $offeringId, ':m' => $modalidad]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // =========================================================================
    // DATOS DE LA OFERTA
    // =========================================================================

    public function getOferta(int $offeringId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ao.id AS offering_id, d.id AS diplomado_id,
                    d.name AS diplomado_nombre, c.name AS cohorte_nombre,
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
    // NOTA MÍNIMA DEL DIPLOMADO
    // =========================================================================

    public function getNotaMinima(int $offeringId): float
    {
        $stmt = $this->db->prepare(
            "SELECT dc.nota_minima
             FROM tbl_diplomados_config dc
             INNER JOIN tbl_academic_offerings ao ON ao.diploma_id = dc.diplomado_id
             WHERE ao.id = :oid
             LIMIT 1"
        );
        $stmt->execute([':oid' => $offeringId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (float) $val : 15.00;
    }

    // =========================================================================
    // ESTUDIANTES CON SUS NOTAS DE LA MODALIDAD
    // =========================================================================

    public function getEstudiantesConNotas(int $offeringId, string $modalidad): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.id AS enrollment_id,
                    u.first_name, u.last_name, u.document_id,
                    ne.nota, ne.id AS nota_id
             FROM tbl_enrollments e
             INNER JOIN tbl_users u ON e.user_id = u.id
             LEFT JOIN tbl_notas_estudiantes ne
                    ON ne.enrollment_id = e.id
                    AND ne.offering_id  = :oid
                    AND ne.modalidad    = :modalidad
             WHERE e.offering_id = :oid2
               AND e.status = 'APROBADO'
             ORDER BY u.last_name ASC, u.first_name ASC"
        );
        $stmt->execute([':oid' => $offeringId, ':modalidad' => $modalidad, ':oid2' => $offeringId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // GUARDAR NOTAS
    // =========================================================================

    public function guardarNotas(int $offeringId, int $professorId, string $modalidad, array $notas, int $userId): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_notas_estudiantes
             (offering_id, enrollment_id, professor_id, modalidad, nota, created_by)
             VALUES (:oid, :eid, :pid, :modalidad, :nota, :uid)
             ON DUPLICATE KEY UPDATE nota = VALUES(nota), updated_at = NOW()"
        );
        foreach ($notas as $enrollmentId => $nota) {
            $stmt->execute([
                ':oid'       => $offeringId,
                ':eid'       => (int) $enrollmentId,
                ':pid'       => $professorId,
                ':modalidad' => $modalidad,
                ':nota'      => min(20, max(0, (float) $nota)),
                ':uid'       => $userId,
            ]);
        }
    }

    // =========================================================================
    // ACTA
    // =========================================================================

    public function getActa(int $offeringId, string $modalidad): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tbl_actas WHERE offering_id = :oid AND modalidad = :m"
        );
        $stmt->execute([':oid' => $offeringId, ':m' => $modalidad]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function generarActa(int $offeringId, string $modalidad, int $professorId, int $userId): int
    {
        $existing = $this->getActa($offeringId, $modalidad);

        if ($existing) {
            $this->db->prepare(
                "UPDATE tbl_actas
                 SET estado = 'ENVIADA', generada_por = :uid, updated_at = NOW()
                 WHERE id = :id"
            )->execute([':uid' => $userId, ':id' => $existing['id']]);
            return (int) $existing['id'];
        }

        $this->db->prepare(
            "INSERT INTO tbl_actas (offering_id, modalidad, estado, generada_por)
             VALUES (:oid, :m, 'ENVIADA', :uid)"
        )->execute([':oid' => $offeringId, ':m' => $modalidad, ':uid' => $userId]);
        return (int) $this->db->lastInsertId();
    }

    public function todasNotasCargadas(int $offeringId, string $modalidad): bool
    {
        // Verifica que todos los estudiantes APROBADOS tienen nota en esta modalidad
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_enrollments e
             LEFT JOIN tbl_notas_estudiantes ne
                    ON ne.enrollment_id = e.id
                    AND ne.offering_id  = :oid
                    AND ne.modalidad    = :m
             WHERE e.offering_id = :oid2
               AND e.status = 'APROBADO'
               AND ne.id IS NULL"
        );
        $stmt->execute([':oid' => $offeringId, ':m' => $modalidad, ':oid2' => $offeringId]);
        return (int) $stmt->fetchColumn() === 0;
    }
}