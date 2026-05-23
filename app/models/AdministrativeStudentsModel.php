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
    if (!empty($filters['docs'])) { $where .= " AND e.document_status = :doc"; $params[':doc'] = $filters['docs']; }

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
                DATE_FORMAT(s.admission_date, '%d/%m/%Y') AS fecha_ingreso
            FROM tbl_students s
            INNER JOIN tbl_users u ON s.user_id = u.id
            LEFT JOIN tbl_enrollments e ON s.enrollment_id = e.id
            LEFT JOIN tbl_academic_offerings o ON e.offering_id = o.id
            LEFT JOIN tbl_diplomados d ON o.diploma_id = d.id
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
    $sql = "SELECT id, name FROM tbl_diplomados WHERE status = 'ACTIVO' ORDER BY name ASC";
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


}