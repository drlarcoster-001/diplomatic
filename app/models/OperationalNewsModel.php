<?php
/**
 * MÓDULO: GESTIÓN OPERATIVA / NEWS (CARTELERA)
 * ARCHIVO: app/Models/OperationalNewsModel.php
 * PROPÓSITO: Persistencia de datos para las noticias del blog (Cartelera).
 * VERSIÓN: 1.1.0 - Soporte para sincronización con WordPress.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class OperationalNewsModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Obtiene la lista de noticias para la Grid.
     */
    public function getNewsForGrid(array $filters = []): array
    {
        $sql = "SELECT 
                    id, 
                    title, 
                    excerpt, 
                    content, 
                    image_url, 
                    wp_post_id, 
                    is_ready, 
                    last_sync,
                    created_at,
                    (CASE WHEN image_url IS NOT NULL AND image_url != '' THEN 1 ELSE 0 END) as has_image,
                    (CASE WHEN content IS NOT NULL AND content != '' THEN 1 ELSE 0 END) as has_content
                FROM tbl_operational_news_web
                WHERE 1=1";

        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND title LIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['only_incomplete']) && $filters['only_incomplete'] === true) {
            $sql .= " AND is_ready = 0";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * MÉTODO NUEVO: Obtiene una noticia específica por ID.
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM tbl_operational_news_web WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Guarda (crea) o actualiza los textos de una noticia.
     */
    public function saveTexts(int $id, string $title, string $excerpt, string $content): int
    {
        if ($id > 0) {
            $sql = "UPDATE tbl_operational_news_web SET title = ?, excerpt = ?, content = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$title, $excerpt, $content, $id]);
            return $id;
        } else {
            $sql = "INSERT INTO tbl_operational_news_web (title, excerpt, content) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$title, $excerpt, $content]);
            return (int) $this->db->lastInsertId();
        }
    }

    /**
     * Actualiza la URL de la imagen principal.
     */
    public function savePhoto(int $id, string $photoUrl): bool
    {
        $sql = "UPDATE tbl_operational_news_web SET image_url = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$photoUrl, $id]);
    }

    /**
     * MÉTODO NUEVO: Actualiza el ID de WordPress y marca como lista/sincronizada.
     */
    public function updateWpSync(int $id, int $wpPostId): bool
    {
        $sql = "UPDATE tbl_operational_news_web 
                SET wp_post_id = ?, 
                    is_ready = 1, 
                    last_sync = NOW() 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$wpPostId, $id]);
    }

    /**
     * Elimina una noticia físicamente.
     */
    public function deleteNews(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM tbl_operational_news_web WHERE id = ?");
        return $stmt->execute([$id]);
    }
}