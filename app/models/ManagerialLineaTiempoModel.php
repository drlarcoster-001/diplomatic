<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / LÍNEA DE TIEMPO DEL ESTUDIANTE
 * ARCHIVO: app/models/ManagerialLineaTiempoModel.php
 * PROPÓSITO: Obtiene todos los eventos del ciclo de vida de un estudiante
 *            dentro de un diplomado: registro, inscripción, documentos,
 *            pagos, validaciones, matrícula, notas y cierre de acta.
 *            Cascada de filtros: Usuario (búsqueda global) → Períodos donde
 *            ese usuario tiene inscripciones → Diplomados de ese usuario en
 *            ese período (soporta usuarios con múltiples inscripciones).
 * VERSIÓN: 2.0.1 - Cascada invertida (Usuario → Período → Diplomado) para
 *          soportar usuarios con varios diplomados inscritos. Fix: getActa
 *          ahora recibe el offering_id real (antes caía en enrollment_id
 *          por error cuando offering_id llegaba en 0). Fix crítico:
 *          buscarUsuarios() reutilizaba el mismo parámetro con nombre :s
 *          cuatro veces, lo cual PDO con prepares nativos rechaza
 *          (SQLSTATE[HY093] Invalid parameter number) — cada LIKE ahora
 *          usa su propio nombre de parámetro (:s1..:s4).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ManagerialLineaTiempoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // =========================================================================
    // PASO 1: BÚSQUEDA GLOBAL DE USUARIO (por nombre, cédula o correo)
    // Solo devuelve usuarios que tengan al menos una inscripción, ya que la
    // línea de tiempo necesita un enrollment para construirse.
    // =========================================================================

    public function buscarUsuarios(string $search): array
    {
        if ($search === '') return [];

        // PDO con prepares nativos (MySQL) NO permite reutilizar un mismo
        // parámetro con nombre varias veces en la misma consulta — provoca
        // "SQLSTATE[HY093]: Invalid parameter number". Cada aparición debe
        // tener su propio nombre, aunque el valor sea idéntico.
        $term = "%{$search}%";
        $stmt = $this->db->prepare(
            "SELECT DISTINCT u.id,
                    CONCAT(u.last_name, ', ', u.first_name) AS nombre,
                    u.document_id, u.email
             FROM tbl_users u
             INNER JOIN tbl_enrollments e ON e.user_id = u.id
             WHERE u.first_name LIKE :s1 OR u.last_name LIKE :s2
                OR u.document_id LIKE :s3 OR u.email LIKE :s4
             ORDER BY u.last_name ASC, u.first_name ASC
             LIMIT 30"
        );
        $stmt->execute([':s1' => $term, ':s2' => $term, ':s3' => $term, ':s4' => $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // PASO 2: PERÍODOS donde ESE usuario tiene al menos una inscripción
    // =========================================================================

    public function getPeriodosByUsuario(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT p.id, p.periodo_code, p.nombre, p.estado
             FROM tbl_periodos_cohorte p
             INNER JOIN tbl_cohortes c ON c.periodo_id = p.id
             INNER JOIN tbl_academic_offerings ao ON ao.cohort_id = c.id
             INNER JOIN tbl_enrollments e ON e.offering_id = ao.id
             WHERE e.user_id = :uid
             ORDER BY p.id DESC"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // PASO 3: DIPLOMADOS de ESE usuario dentro de ESE período
    // Devuelve el enrollment_id directamente (una fila = una inscripción)
    // =========================================================================

    public function getOfertasByUsuarioPeriodo(int $userId, int $periodoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.id AS enrollment_id, ao.id AS offering_id,
                    CONCAT(d.name, COALESCE(
                        (SELECT CONCAT(' — ', GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ', '))
                         FROM tbl_academic_offering_groups og
                         INNER JOIN tbl_grupos g ON g.id = og.group_id
                         WHERE og.offering_id = ao.id AND og.is_enabled = 1), ''
                    )) AS name,
                    e.status AS expediente_status
             FROM tbl_enrollments e
             INNER JOIN tbl_academic_offerings ao ON ao.id = e.offering_id
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes c ON c.id = ao.cohort_id
             WHERE e.user_id = :uid AND c.periodo_id = :pid
             ORDER BY d.name ASC"
        );
        $stmt->execute([':uid' => $userId, ':pid' => $periodoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // DATOS DEL ESTUDIANTE (incluye offering_id real, usado luego para getActa)
    // =========================================================================

    public function getDatosEstudiante(int $enrollmentId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id AS user_id, u.first_name, u.last_name, u.document_id,
                    u.email, u.phone, u.created_at AS fecha_registro,
                    e.id AS enrollment_id, e.created_at AS fecha_inscripcion,
                    e.status AS expediente_status, e.updated_at AS fecha_expediente,
                    e.doc_id_card, e.doc_degree, e.doc_cv,
                    e.document_status,
                    ao.id AS offering_id,
                    d.name AS diplomado_nombre,
                    c.name AS cohorte_nombre,
                    COALESCE(
                        (SELECT GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ', ')
                         FROM tbl_academic_offering_groups og
                         INNER JOIN tbl_grupos g ON g.id = og.group_id
                         WHERE og.offering_id = ao.id AND og.is_enabled = 1), ''
                    ) AS grupos_nombre,
                    s.id AS student_id, s.student_code,
                    s.created_at AS fecha_creacion_estudiante,
                    sm.id AS matriculation_id,
                    sm.registered_at AS fecha_matricula,
                    sm.academic_status, sm.final_grade
             FROM tbl_enrollments e
             INNER JOIN tbl_users u ON u.id = e.user_id
             INNER JOIN tbl_academic_offerings ao ON ao.id = e.offering_id
             INNER JOIN tbl_diplomados d ON d.id = ao.diploma_id
             INNER JOIN tbl_cohortes c ON c.id = ao.cohort_id
             LEFT JOIN tbl_students s ON s.enrollment_id = e.id
             LEFT JOIN tbl_student_matriculations sm ON sm.enrollment_id = e.id
             WHERE e.id = :eid
             LIMIT 1"
        );
        $stmt->execute([':eid' => $enrollmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // =========================================================================
    // PAGOS DE INSCRIPCIÓN
    // =========================================================================

    public function getPagosInscripcion(int $enrollmentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ep.id, ep.method, ep.amount, ep.currency,
                    ep.reference_id, ep.status, ep.created_at,
                    ep.validation_date, ep.payment_metadata,
                    ep.observation,
                    u.first_name AS validado_first, u.last_name AS validado_last
             FROM tbl_enrollments_payments ep
             LEFT JOIN tbl_users u ON u.id = ep.validated_by
             WHERE ep.enrollment_id = :eid
             ORDER BY ep.created_at ASC"
        );
        $stmt->execute([':eid' => $enrollmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // LEDGER — A QUÉ CORRESPONDIÓ CADA PAGO
    // =========================================================================

    public function getLedger(int $enrollmentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT fl.concept, fl.amount_due, fl.amount_paid,
                    fl.status, fl.processed_at, fl.payment_id
             FROM tbl_financial_student_ledger fl
             WHERE fl.enrollment_id = :eid
             ORDER BY fl.due_date ASC"
        );
        $stmt->execute([':eid' => $enrollmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // PAGOS DE CUOTAS
    // =========================================================================

    public function getPagosCuotas(int $enrollmentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT fp.id, fp.method, fp.amount, fp.currency,
                    fp.reference_id, fp.status, fp.created_at,
                    fp.payment_metadata, fp.observation,
                    u.first_name AS cobrador_first, u.last_name AS cobrador_last
             FROM tbl_financial_payments fp
             INNER JOIN tbl_students s ON s.id = fp.student_id
             LEFT JOIN tbl_users u ON u.id = fp.collector_id
             WHERE s.enrollment_id = :eid
             ORDER BY fp.created_at ASC"
        );
        $stmt->execute([':eid' => $enrollmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // NOTAS
    // =========================================================================

    public function getNotas(int $enrollmentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ne.modalidad, ne.nota, ne.created_at,
                    p.full_name AS profesor_nombre
             FROM tbl_notas_estudiantes ne
             LEFT JOIN tbl_professors p ON p.id = ne.professor_id
             WHERE ne.enrollment_id = :eid
             ORDER BY FIELD(ne.modalidad, 'TEORICA', 'PRACTICA', 'VIRTUAL')"
        );
        $stmt->execute([':eid' => $enrollmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // ACTA (recibe el offering_id real del enrollment, no un valor de fallback)
    // =========================================================================

    public function getActa(int $offeringId, int $enrollmentId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.estado, a.updated_at AS fecha_acta,
                    u.first_name AS aprobado_first, u.last_name AS aprobado_last
             FROM tbl_actas a
             LEFT JOIN tbl_users u ON u.id = a.aprobada_por
             WHERE a.offering_id = :oid AND a.estado = 'APROBADA'
             ORDER BY a.updated_at DESC
             LIMIT 1"
        );
        $stmt->execute([':oid' => $offeringId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}