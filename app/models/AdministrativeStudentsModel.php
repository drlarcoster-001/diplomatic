<?php
/**
 * MÓDULO: ADMINISTRATIVO / ESTUDIANTES
 * ARCHIVO: app/models/AdministrativeStudentsModel.php
 * PROPÓSITO: Consultas para listar estudiantes con búsqueda global y filtro de estatus.
 * VERSIÓN: 1.1.4 - Fix HY093 (Parámetros únicos) y Búsqueda Global (incluye Diplomados).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class AdministrativeStudentsModel {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

/**
 * REPARACIÓN TOTAL: Obtiene todos los datos del estudiante, contacto y archivos.
 */
public function getAllStudents(array $filters = []): array {
    $params = [];
    $where = "WHERE 1=1";

    // Filtro de búsqueda global (Nombre, Cédula, Expediente, Correo)
    if (!empty($filters['search'])) {
        $searchTerm = "%{$filters['search']}%";
        $where .= " AND (s.first_name LIKE :s1 OR s.last_name LIKE :s2 OR s.document_id LIKE :s3 OR s.student_code LIKE :s4 OR s.email LIKE :s5)";
        $params[':s1'] = $searchTerm;
        $params[':s2'] = $searchTerm;
        $params[':s3'] = $searchTerm;
        $params[':s4'] = $searchTerm;
        $params[':s5'] = $searchTerm;
    }

    // Filtros específicos
    if (!empty($filters['diploma_id'])) { $where .= " AND d.id = :dip"; $params[':dip'] = $filters['diploma_id']; }
    if (!empty($filters['status'])) { $where .= " AND s.status = :st"; $params[':st'] = $filters['status']; }
    if (!empty($filters['docs'])) {
    if ($filters['docs'] === 'COMPLETE') {
        $where .= " AND e.document_status = 'COMPLETE'";
    } elseif ($filters['docs'] === 'INCOMPLETE') {
        $where .= " AND e.document_status != 'COMPLETE'";
    }
}
if (!empty($filters['verified'])) {
    if ($filters['verified'] === 'VERIFIED') {
        $where .= " AND dv.id_card_approved = 1 AND dv.degree_approved = 1";
    } elseif ($filters['verified'] === 'UNVERIFIED') {
        $where .= " AND (dv.id IS NULL OR dv.id_card_approved = 0 OR dv.degree_approved = 0)";
    }
}

    $sql = "SELECT 
                s.id, 
                s.student_code AS expediente, 
                s.document_id AS cedula,
                CONCAT(s.first_name, ' ', s.last_name) AS nombre_completo,
                s.email, 
                u.phone, 
                u.address AS direccion,
                s.undergraduate_degree AS titulo, 
                e.provenance AS procedencia,
                d.name AS diplomado_nombre, 
                s.status AS estatus_academico,
                e.document_status AS estatus_digital,
                e.doc_id_card, 
                e.doc_degree, 
                e.doc_cv,
                DATE_FORMAT(s.admission_date, '%d/%m/%Y') AS fecha_ingreso,
                e.id AS enrollment_id,
                COALESCE(dv.id_card_approved, 0) AS id_card_approved,
                COALESCE(dv.degree_approved,  0) AS degree_approved,
                COALESCE(dv.cv_approved,      0) AS cv_approved
            FROM tbl_students s
            INNER JOIN tbl_users u ON s.user_id = u.id
            LEFT JOIN tbl_enrollments e ON s.enrollment_id = e.id
            LEFT JOIN tbl_academic_offerings o ON e.offering_id = o.id
            LEFT JOIN tbl_diplomados d ON o.diploma_id = d.id
            LEFT JOIN tbl_document_verifications dv ON dv.enrollment_id = e.id
            $where 
            ORDER BY s.id DESC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Necesario para llenar el select del filtro en el Front
 */
public function getDiplomadosList(): array {
    $sql = "SELECT DISTINCT d.id, d.name 
            FROM tbl_diplomados d
            INNER JOIN tbl_academic_offerings o ON d.id = o.diploma_id
            INNER JOIN tbl_enrollments e ON o.id = e.offering_id
            INNER JOIN tbl_students s ON s.enrollment_id = e.id
            WHERE d.status = 'ACTIVO'
            ORDER BY d.name ASC";
    return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

   public function updateStudentStatus(int $studentId, string $status): bool {
    $sql = "UPDATE tbl_students SET status = :status WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
        ':status' => $status,
        ':id' => $studentId
    ]);
} 

public function getDocumentVerification(int $enrollmentId): ?array {
    $sql = "SELECT * FROM tbl_document_verifications WHERE enrollment_id = ? LIMIT 1";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$enrollmentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

public function saveDocumentVerification(int $enrollmentId, int $userId, int $studentId, string $field): bool {
    // Validar que el campo sea permitido
    $allowed = ['id_card_approved', 'degree_approved', 'cv_approved'];
    if (!in_array($field, $allowed)) return false;

    // Verificar que el documento existe en tbl_enrollments
    $docMap = [
        'id_card_approved' => 'doc_id_card',
        'degree_approved'  => 'doc_degree',
        'cv_approved'      => 'doc_cv',
    ];
    $docCol = $docMap[$field];
    $check = $this->db->prepare("SELECT $docCol FROM tbl_enrollments WHERE id = ? LIMIT 1");
    $check->execute([$enrollmentId]);
    $row = $check->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row[$docCol])) return false;

    // INSERT o UPDATE
    // INSERT o UPDATE — toggle
    $sql = "INSERT INTO tbl_document_verifications 
            (enrollment_id, user_id, student_id, $field, verified_by)
        VALUES 
            (:enroll, :uid, :sid, 1, :vby)
        ON DUPLICATE KEY UPDATE 
            $field = IF($field = 1, 0, 1),
            verified_by = :vby2,
            updated_at = NOW()";

    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
        ':enroll' => $enrollmentId,
        ':uid'    => $userId,
        ':sid'    => $studentId,
        ':vby'    => $userId,
        ':vby2'   => $userId,
    ]);
}


}