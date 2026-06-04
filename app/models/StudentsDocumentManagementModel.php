<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / MODELOS
 * ARCHIVO: app/models/StudentsDocumentManagementModel.php
 * PROPÓSITO: Persistencia de recaudos. Lógica: Cédula y Título obligatorios, CV opcional.
 * VERSIÓN: 1.1.2 - REGLA: Estatus 'COMPLETE' no depende del Currículum.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class StudentsDocumentManagementModel 
{
    private PDO $db;

    public function __construct() 
    {
        $this->db = (new Database())->getConnection();
    }

    public function getStudentIdByUserId(int $userId): ?int 
    {
        $stmt = $this->db->prepare("SELECT id FROM tbl_students WHERE user_id = ? AND status = 'ACTIVO' LIMIT 1");
        $stmt->execute([$userId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? (int)$res['id'] : null;
    }

    public function getStudentEnrollments(int $userId): array 
    {
        $sql = "SELECT e.id AS enrollment_id, d.name AS diploma_name, c.cohort_code AS cohort_name,
                       e.status AS enrollment_status, e.document_status
                FROM tbl_enrollments e
                INNER JOIN tbl_academic_offerings o ON e.offering_id = o.id
                LEFT JOIN tbl_diplomados d ON o.diploma_id = d.id
                LEFT JOIN tbl_cohortes c ON o.cohort_id = c.id
                WHERE e.user_id = :uid
                ORDER BY e.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEnrollmentDocs(int $enrollmentId, int $userId): ?array 
    {
        $sql = "SELECT id, doc_id_card, doc_degree, doc_cv, document_status 
                FROM tbl_enrollments 
                WHERE id = :eid AND user_id = :uid LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $enrollmentId, ':uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateDocumentField(int $enrollmentId, int $userId, string $column, ?string $path): bool 
    {
        try {
            $this->db->beginTransaction();
            $sql = "UPDATE tbl_enrollments SET $column = :path WHERE id = :eid AND user_id = :uid";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':path' => $path, ':eid' => $enrollmentId, ':uid' => $userId]);

            $this->refreshDocumentStatus($enrollmentId);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }

    private function refreshDocumentStatus(int $enrollmentId): void 
    {
        // Solo verificamos los dos obligatorios
        $stmt = $this->db->prepare("SELECT doc_id_card, doc_degree FROM tbl_enrollments WHERE id = ?");
        $stmt->execute([$enrollmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $isComplete = (!empty($row['doc_id_card']) && !empty($row['doc_degree']));
        $status = $isComplete ? 'COMPLETE' : 'INCOMPLETE';

        $update = $this->db->prepare("UPDATE tbl_enrollments SET document_status = ? WHERE id = ?");
        $update->execute([$status, $enrollmentId]);
    }
}