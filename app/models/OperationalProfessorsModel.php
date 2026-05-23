<?php
/**
 * MÓDULO: GESTIÓN OPERATIVA / PROFESSORS
 * ARCHIVO: app/Models/OperationalProfessorsModel.php
 * PROPÓSITO: Gestión de persistencia para la extensión de datos web y sincronización.
 * VERSIÓN: 1.1.1 - Integración de método unsyncWpPost para desvinculación de perfiles.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class OperationalProfessorsModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene la lista de profesores con su estado de preparación web y sincronización.
     */
    public function getProfessorsForGrid(array $filters = []): array
    {
        $sql = "SELECT 
                    p.id, 
                    p.first_name, 
                    p.last_name, 
                    ps.specialty_name as specialty,
                    p.photo_path as admin_photo,
                    w.wp_post_id,
                    w.is_ready,
                    w.last_sync,
                    w.web_label,
                    w.web_bio,
                    w.web_photo_url,
                    (CASE WHEN w.web_photo_url IS NOT NULL AND w.web_photo_url != '' THEN 1 ELSE 0 END) as has_web_photo,
                    (CASE WHEN w.web_bio IS NOT NULL AND w.web_bio != '' THEN 1 ELSE 0 END) as has_web_bio
                FROM tbl_professors p
                LEFT JOIN tbl_professor_specialties ps ON p.id = ps.professor_id AND ps.is_main = 1
                LEFT JOIN tbl_operational_professors_web w ON p.id = w.professor_id
                WHERE p.is_active = 1";

        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.first_name LIKE :search OR p.last_name LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['specialty'])) {
            $sql .= " AND ps.specialty_name = :specialty";
            $params[':specialty'] = $filters['specialty'];
        }

        if (isset($filters['only_incomplete']) && $filters['only_incomplete'] === true) {
            $sql .= " AND (w.is_ready = 0 OR w.is_ready IS NULL)";
        }

        if (!empty($filters['sync_status'])) {
            if ($filters['sync_status'] === 'SYNC') {
                $sql .= " AND w.wp_post_id IS NOT NULL";
            } else if ($filters['sync_status'] === 'OFFLINE') {
                $sql .= " AND w.wp_post_id IS NULL";
            }
        }

        $sql .= " ORDER BY p.last_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los datos específicos de un profesor para enviar a WordPress.
     */
    public function getByProfessorId(int $professorId): ?array
    {
        $sql = "SELECT w.*, p.first_name, p.last_name 
                FROM tbl_operational_professors_web w
                JOIN tbl_professors p ON w.professor_id = p.id
                WHERE w.professor_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$professorId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Actualiza el ID de WordPress y marca la fecha de sincronización.
     */
    public function updateWpSync(int $professorId, int $wpPostId): bool
    {
        $sql = "UPDATE tbl_operational_professors_web 
                SET wp_post_id = ?, last_sync = NOW() 
                WHERE professor_id = ?";
        return $this->db->prepare($sql)->execute([$wpPostId, $professorId]);
    }

    /**
     * Elimina el ID de WordPress vinculado al profesor (Desvinculación).
     */
    public function unsyncWpPost(int $professorId): bool
    {
        $sql = "UPDATE tbl_operational_professors_web 
                SET wp_post_id = NULL, last_sync = NOW() 
                WHERE professor_id = ?";
        return $this->db->prepare($sql)->execute([$professorId]);
    }

    /**
     * Guarda o actualiza los textos web (Label y Bio).
     * También evalúa si el perfil está listo para ser publicado.
     */
    public function saveWebTexts(int $professorId, string $label, string $bio): bool
    {
        $stmt = $this->db->prepare("SELECT id, web_photo_url FROM tbl_operational_professors_web WHERE professor_id = ?");
        $stmt->execute([$professorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Lógica de "Listo": Debe tener Bio y tener una Foto ya guardada
        $isReady = (!empty($bio) && !empty($row['web_photo_url'] ?? '')) ? 1 : 0;

        if ($row) {
            $sql = "UPDATE tbl_operational_professors_web SET web_label = ?, web_bio = ?, is_ready = ? WHERE professor_id = ?";
            return $this->db->prepare($sql)->execute([$label, $bio, $isReady, $professorId]);
        } else {
            $sql = "INSERT INTO tbl_operational_professors_web (professor_id, web_label, web_bio, is_ready) VALUES (?, ?, ?, ?)";
            return $this->db->prepare($sql)->execute([$professorId, $label, $bio, $isReady]);
        }
    }

    /**
     * Guarda o actualiza la URL de la foto web.
     */
    public function saveWebPhoto(int $professorId, string $photoUrl): bool
    {
        $stmt = $this->db->prepare("SELECT id, web_bio FROM tbl_operational_professors_web WHERE professor_id = ?");
        $stmt->execute([$professorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Lógica de "Listo": Debe tener Foto y tener una Bio ya guardada
        $isReady = (!empty($photoUrl) && !empty($row['web_bio'] ?? '')) ? 1 : 0;

        if ($row) {
            $sql = "UPDATE tbl_operational_professors_web SET web_photo_url = ?, is_ready = ? WHERE professor_id = ?";
            return $this->db->prepare($sql)->execute([$photoUrl, $isReady, $professorId]);
        } else {
            $sql = "INSERT INTO tbl_operational_professors_web (professor_id, web_photo_url, is_ready) VALUES (?, ?, ?)";
            return $this->db->prepare($sql)->execute([$professorId, $photoUrl, $isReady]);
        }
    }

    /**
     * Obtiene las especialidades únicas para los filtros del grid.
     */
    public function getUniqueSpecialties(): array
    {
        $stmt = $this->db->query("
            SELECT DISTINCT specialty_name 
            FROM tbl_professor_specialties 
            WHERE specialty_name IS NOT NULL AND specialty_name != '' 
            ORDER BY specialty_name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}