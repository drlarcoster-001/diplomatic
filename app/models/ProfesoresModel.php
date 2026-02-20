<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/models/ProfesoresModel.php
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class ProfesoresModel
{
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function getAll(string $search = ''): array {
        $sql = "SELECT p.* FROM tbl_professors p 
                WHERE p.is_active = 1 AND (p.full_name LIKE ? OR p.identification LIKE ?)
                ORDER BY p.full_name ASC";
        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id) {
        $stmt = $this->db->prepare("SELECT * FROM tbl_professors WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDetails(int $id): ?array {
        $profesor = $this->getById($id);
        if (!$profesor) return null;

        // Contactos: email, phone, linkedin_url, other_contact
        $stmt = $this->db->prepare("SELECT * FROM tbl_professor_contacts WHERE professor_id = ?");
        $stmt->execute([$id]);
        $profesor['contact'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // Formación: degree_title, academic_level, study_area, institution, year_obtained
        $stmt = $this->db->prepare("SELECT * FROM tbl_professor_academic_formations WHERE professor_id = ? ORDER BY year_obtained DESC");
        $stmt->execute([$id]);
        $profesor['formations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Experiencia: job_title, institution, description, start_date, end_date, is_current
        $stmt = $this->db->prepare("SELECT * FROM tbl_professor_work_experiences WHERE professor_id = ? ORDER BY is_current DESC, start_date DESC");
        $stmt->execute([$id]);
        $profesor['work_experiences'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("SELECT * FROM tbl_professor_specialties WHERE professor_id = ? ORDER BY is_main DESC");
        $stmt->execute([$id]);
        $profesor['specialties'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("SELECT * FROM tbl_professor_documents WHERE professor_id = ? ORDER BY uploaded_at DESC");
        $stmt->execute([$id]);
        $profesor['documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $profesor;
    }

    public function setInactive(int $id, int $userId): bool {
        return $this->db->prepare("UPDATE tbl_professors SET is_active = 0, updated_by = ? WHERE id = ?")
                        ->execute([$userId, $id]);
    }

    public function insertBasic(array $d, int $userId): int {
        $sql = "INSERT INTO tbl_professors (identification, first_name, last_name, full_name, professor_type, created_by) VALUES (?, ?, ?, ?, ?, ?)";
        $fn = trim($d['first_name'] . ' ' . $d['last_name']);
        $this->db->prepare($sql)->execute([$d['identification'], $d['first_name'], $d['last_name'], $fn, $d['professor_type'], $userId]);
        return (int)$this->db->lastInsertId();
    }

    public function updateBasicData(int $id, array $d, int $userId): bool {
        $fn = trim($d['first_name'] . ' ' . $d['last_name']);
        $this->db->prepare("UPDATE tbl_professors SET identification=?, first_name=?, last_name=?, full_name=?, professor_type=?, biography=?, updated_by=? WHERE id=?")
                 ->execute([$d['identification'], $d['first_name'], $d['last_name'], $fn, $d['professor_type'], $d['biography'], $userId, $id]);

        $sqlC = "INSERT INTO tbl_professor_contacts (professor_id, email, phone, linkedin_url, other_contact) VALUES (?,?,?,?,?) 
                 ON DUPLICATE KEY UPDATE email=VALUES(email), phone=VALUES(phone), linkedin_url=VALUES(linkedin_url), other_contact=VALUES(other_contact)";
        $this->db->prepare($sqlC)->execute([$id, $d['contact_email'], $d['contact_phone'], $d['contact_linkedin'], $d['other_contact']]);
        return true;
    }

    public function updatePhoto($id, $p) { return $this->db->prepare("UPDATE tbl_professors SET photo_path=? WHERE id=?")->execute([$p, $id]); }
    public function insertFormation($d) { return $this->db->prepare("INSERT INTO tbl_professor_academic_formations (professor_id, degree_title, academic_level, study_area, institution, year_obtained) VALUES (?,?,?,?,?,?)")->execute([$d['professor_id'], $d['degree_title'], $d['academic_level'], $d['study_area'], $d['institution'], $d['year_obtained']]); }
    public function insertWork($d) { return $this->db->prepare("INSERT INTO tbl_professor_work_experiences (professor_id, job_title, institution, description, start_date, end_date, is_current) VALUES (?,?,?,?,?,?,?)")->execute([$d['professor_id'], $d['job_title'], $d['institution'], $d['description'], $d['start_date'], isset($d['is_current']) ? null : $d['end_date'], isset($d['is_current']) ? 1 : 0]); }
    public function insertSpecialty($d) { return $this->db->prepare("INSERT INTO tbl_professor_specialties (professor_id, specialty_name, is_main) VALUES (?,?,?)")->execute([$d['professor_id'], $d['specialty_name'], isset($d['is_main']) ? 1 : 0]); }
    public function insertDocument($d) { return $this->db->prepare("INSERT INTO tbl_professor_documents (professor_id, document_type, document_name, file_path) VALUES (?,?,?,?)")->execute([$d['professor_id'], $d['document_type'], $d['document_name'], $d['file_path']]); }
    public function deleteFormation($id) { return $this->db->prepare("DELETE FROM tbl_professor_academic_formations WHERE id=?")->execute([$id]); }
    public function deleteWork($id) { return $this->db->prepare("DELETE FROM tbl_professor_work_experiences WHERE id=?")->execute([$id]); }
    public function deleteSpecialty($id) { return $this->db->prepare("DELETE FROM tbl_professor_specialties WHERE id=?")->execute([$id]); }
    public function deleteDocument($id) { return $this->db->prepare("DELETE FROM tbl_professor_documents WHERE id=?")->execute([$id]); }
}