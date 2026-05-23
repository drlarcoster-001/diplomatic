<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/models/ProfesoresModel.php
 * PROPÓSITO: CRUD maestro de profesores con limpieza automática de dependencias internas y bloqueo por oferta académica.
 * VERSIÓN: 1.4.0 - Rediseño: Borrado físico en cascada manual con Transacciones SQL.
 */

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class ProfesoresModel
{
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene todos los profesores activos.
     */
    public function getAll(string $search = ''): array {
        $sql = "SELECT p.* FROM tbl_professors p 
                WHERE p.is_active = 1 AND (p.full_name LIKE ? OR p.identification LIKE ?)
                ORDER BY p.full_name ASC";
        $stmt = $this->db->prepare($sql);
        $term = "%$search%";
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene data base de un profesor.
     */
    public function getById(int $id) {
        $stmt = $this->db->prepare("SELECT * FROM tbl_professors WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Carga el expediente completo para la vista de edición.
     */
    public function getDetails(int $id): ?array {
        $profesor = $this->getById($id);
        if (!$profesor) return null;

        $stmt = $this->db->prepare("SELECT * FROM tbl_professor_contacts WHERE professor_id = ?");
        $stmt->execute([$id]);
        $profesor['contact'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $stmt = $this->db->prepare("SELECT * FROM tbl_professor_specialties WHERE professor_id = ? ORDER BY is_main DESC");
        $stmt->execute([$id]);
        $profesor['specialties'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("SELECT * FROM tbl_professor_academic_formations WHERE professor_id = ? ORDER BY year_obtained DESC");
        $stmt->execute([$id]);
        $profesor['formations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("SELECT * FROM tbl_professor_work_experiences WHERE professor_id = ? ORDER BY is_current DESC, start_date DESC");
        $stmt->execute([$id]);
        $profesor['work_experiences'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("SELECT * FROM tbl_professor_documents WHERE professor_id = ? ORDER BY uploaded_at DESC");
        $stmt->execute([$id]);
        $profesor['documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $profesor;
    }

    /**
     * VERIFICACIÓN DE BLOQUEO (Dependencias Externas)
     * Solo bloquea si el profesor está asignado en la Oferta Académica.
     */
    public function hasDependencies(int $id): bool 
    {
        $sql = "SELECT COUNT(*) FROM tbl_academic_offering_professors WHERE professor_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * BORRADO FÍSICO INTELIGENTE (Cascada Manual)
     * Limpia todas las tablas de detalles antes de borrar al profesor.
     * Utiliza Transacciones para asegurar que no se borre nada si algo falla.
     */
    public function deletePhysical(int $id): bool 
    {
        try {
            $this->db->beginTransaction();

            // 1. Limpiar detalles internos (No bloqueantes)
            $tables = [
                'tbl_professor_contacts',
                'tbl_professor_academic_formations',
                'tbl_professor_work_experiences',
                'tbl_professor_specialties',
                'tbl_professor_documents'
            ];

            foreach ($tables as $table) {
                $stmt = $this->db->prepare("DELETE FROM $table WHERE professor_id = ?");
                $stmt->execute([$id]);
            }

            // 2. Borrar el registro maestro
            $stmt = $this->db->prepare("DELETE FROM tbl_professors WHERE id = ?");
            $result = $stmt->execute([$id]);

            $this->db->commit();
            return $result;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en deletePhysical Profesores: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactivación lógica.
     */
    public function setInactive(int $id, int $userId): bool {
        return $this->db->prepare("UPDATE tbl_professors SET is_active = 0, updated_by = ? WHERE id = ?")
                        ->execute([$userId, $id]);
    }

    // --- MÉTODOS DE ESCRITURA ---

    public function insertBasic(array $d, int $userId): int {
        $sql = "INSERT INTO tbl_professors (identification, first_name, last_name, full_name, professor_type, created_by) VALUES (?, ?, ?, ?, ?, ?)";
        $fn = trim($d['first_name'] . ' ' . $d['last_name']);
        $this->db->prepare($sql)->execute([$d['identification'], $d['first_name'], $d['last_name'], $fn, $d['professor_type'], $userId]);
        return (int)$this->db->lastInsertId();
    }

    public function updateBasicData(int $id, array $d, int $userId): bool {
        $fn = trim($d['first_name'] . ' ' . $d['last_name']);
        $this->db->prepare("UPDATE tbl_professors SET identification=?, first_name=?, last_name=?, full_name=?, professor_type=?, biography=?, updated_by=? WHERE id=?")
                 ->execute([$d['identification'], $d['first_name'], $d['last_name'], $fn, $d['professor_type'], $d['biography'] ?? null, $userId, $id]);

        $sqlC = "INSERT INTO tbl_professor_contacts (professor_id, email, phone, linkedin_url, other_contact) VALUES (?,?,?,?,?) 
                 ON DUPLICATE KEY UPDATE email=VALUES(email), phone=VALUES(phone), linkedin_url=VALUES(linkedin_url), other_contact=VALUES(other_contact)";
        $this->db->prepare($sqlC)->execute([$id, $d['contact_email'] ?? null, $d['contact_phone'] ?? null, $d['contact_linkedin'] ?? null, $d['other_contact'] ?? null]);
        return true;
    }

    public function updatePhoto($id, $p) { 
        return $this->db->prepare("UPDATE tbl_professors SET photo_path=? WHERE id=?")->execute([$p, $id]); 
    }

    // --- GESTIÓN DE DETALLES INDIVIDUALES ---

    public function insertFormation($d) { 
        return $this->db->prepare("INSERT INTO tbl_professor_academic_formations (professor_id, degree_title, academic_level, study_area, institution, year_obtained) VALUES (?,?,?,?,?,?)")
                    ->execute([$d['professor_id'], $d['degree_title'], $d['academic_level'] ?? 'Pregrado', $d['study_area'] ?? null, $d['institution'], !empty($d['year_obtained']) ? $d['year_obtained'] : null]); 
    }

    public function updateFormation($id, $d) { 
        return $this->db->prepare("UPDATE tbl_professor_academic_formations SET degree_title=?, academic_level=?, study_area=?, institution=?, year_obtained=? WHERE id=?")
                    ->execute([$d['degree_title'], $d['academic_level'] ?? 'Pregrado', $d['study_area'] ?? null, $d['institution'], !empty($d['year_obtained']) ? $d['year_obtained'] : null, $id]); 
    }

    public function deleteFormation($id) { return $this->db->prepare("DELETE FROM tbl_professor_academic_formations WHERE id=?")->execute([$id]); }

    public function insertWork($d) { 
        return $this->db->prepare("INSERT INTO tbl_professor_work_experiences (professor_id, job_title, institution, description, start_date, end_date, is_current) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$d['professor_id'], $d['job_title'], $d['institution'], $d['description'] ?? null, $d['start_date'], $d['end_date'], $d['is_current']]); 
    }

    public function updateWork($id, $d) { 
        return $this->db->prepare("UPDATE tbl_professor_work_experiences SET job_title=?, institution=?, description=?, start_date=?, end_date=?, is_current=? WHERE id=?")
                    ->execute([$d['job_title'], $d['institution'], $d['description'] ?? null, $d['start_date'], $d['end_date'], $d['is_current'], $id]); 
    }

    public function deleteWork($id) { return $this->db->prepare("DELETE FROM tbl_professor_work_experiences WHERE id=?")->execute([$id]); }

    public function insertSpecialty($d) { return $this->db->prepare("INSERT INTO tbl_professor_specialties (professor_id, specialty_name, is_main) VALUES (?,?,?)")->execute([$d['professor_id'], $d['specialty_name'], $d['is_main']]); }
    public function updateSpecialty($id, $d) { return $this->db->prepare("UPDATE tbl_professor_specialties SET specialty_name=?, is_main=? WHERE id=?")->execute([$d['specialty_name'], $d['is_main'], $id]); }
    public function deleteSpecialty($id) { return $this->db->prepare("DELETE FROM tbl_professor_specialties WHERE id=?")->execute([$id]); }

    public function insertDocument($d) { return $this->db->prepare("INSERT INTO tbl_professor_documents (professor_id, document_type, document_name, file_path) VALUES (?,?,?,?)")->execute([$d['professor_id'], $d['document_type'], $d['document_name'], $d['file_path']]); }
    public function deleteDocument($id) { return $this->db->prepare("DELETE FROM tbl_professor_documents WHERE id=?")->execute([$id]); }
}