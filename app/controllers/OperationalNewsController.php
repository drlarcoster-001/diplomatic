<?php
/**
 * MÓDULO: GESTIÓN OPERATIVA / NEWS (CARTELERA)
 * ARCHIVO: app/Controllers/OperationalNewsController.php
 * PROPÓSITO: Controlador maestro con bloqueo de edición y sincronización con "Cartelera".
 * VERSIÓN: 1.4.0 - Fix de categoría para diseño Elementor.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OperationalNewsModel;
use App\Models\WordpressConfigModel;
use App\Services\WordpressService;
use Exception;

final class OperationalNewsController extends Controller
{
    private OperationalNewsModel $model;
    private WordpressConfigModel $configModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user'])) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'message' => 'Sesión expirada']);
                exit;
            }
            header("Location: /diplomatic/public/login");
            exit;
        }

        $this->model = new OperationalNewsModel();
        $this->configModel = new WordpressConfigModel();
    }

    public function index(): void
    {
        $this->view('operational/news/index', [
            'title' => 'Cartelera de Noticias Web'
        ]);
    }

    /**
     * Lista las noticias para la grid principal.
     */
    public function list(): void
    {
        while (ob_get_level() > 0) ob_end_clean(); 
        header('Content-Type: application/json; charset=utf-8');

        try {
            $filters = [
                'search' => $_GET['search'] ?? '',
                'only_incomplete' => isset($_GET['incomplete']) && $_GET['incomplete'] === 'true'
            ];

            $data = $this->model->getNewsForGrid($filters);
            echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Guarda o actualiza los textos de la noticia.
     * BLOQUEO: Si wp_post_id > 0, impide la edición.
     */
    public function saveTexts(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = (int)($_POST['id'] ?? 0);
            
            // Verificación de integridad: No editar si está publicada
            if ($id > 0) {
                $news = $this->model->getById($id);
                if ($news && (int)($news['wp_post_id'] ?? 0) > 0) {
                    throw new Exception("La noticia está en línea. Bájela de la web para poder editar.");
                }
            }

            $title = trim($_POST['title'] ?? '');
            $excerpt = trim($_POST['excerpt'] ?? '');
            $content = trim($_POST['content'] ?? '');

            if (empty($title) || empty($content)) {
                throw new Exception("El título y el cuerpo de la noticia son obligatorios.");
            }

            $newId = $this->model->saveTexts($id, $title, $excerpt, $content);
            echo json_encode(['ok' => true, 'message' => 'Textos guardados correctamente.', 'id' => $newId]);

        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Procesa y guarda la imagen (recortada en JS).
     * BLOQUEO: Si wp_post_id > 0, impide el cambio de imagen.
     */
    public function saveImage(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = (int)($_POST['id'] ?? 0);
            
            $news = $this->model->getById($id);
            if ($news && (int)($news['wp_post_id'] ?? 0) > 0) {
                throw new Exception("No puede cambiar la portada de una noticia publicada.");
            }

            $base64Image = $_POST['image'] ?? '';
            if ($id === 0 || empty($base64Image)) throw new Exception("ID de noticia o imagen faltante.");

            $image_parts = explode(";base64,", $base64Image);
            if (count($image_parts) !== 2) throw new Exception("Formato de imagen inválido.");
            
            $image_base64 = base64_decode($image_parts[1]);
            $uploadDir = __DIR__ . '/../../public/assets/uploads/news_web/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName = 'news_' . $id . '_' . time() . '.png';
            $filePath = $uploadDir . $fileName;
            $publicUrl = '/diplomatic/public/assets/uploads/news_web/' . $fileName;

            if (!file_put_contents($filePath, $image_base64)) throw new Exception("Error de escritura en disco.");

            $this->model->savePhoto($id, $publicUrl);
            echo json_encode(['ok' => true, 'message' => 'Portada actualizada.']);

        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * PUBLICA EN WORDPRESS: Envía a la categoría "Cartelera".
     */
    public function publish(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = (int)($_POST['id'] ?? 0);
            $news = $this->model->getById($id);
            if (!$news) throw new Exception("Noticia no encontrada.");

            $config = $this->configModel->getConfig();
            if (empty($config['wp_url'])) throw new Exception("WordPress no está configurado.");

            // Preparar Imagen FÍSICA para enviar en Base64
            $photoBase64 = '';
            $photoName   = '';
            if (!empty($news['image_url'])) {
                $rutaFisica = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/' . ltrim($news['image_url'], '/\\');
                if (file_exists($rutaFisica)) {
                    $photoBase64 = base64_encode(file_get_contents($rutaFisica));
                    $photoName   = basename($rutaFisica);
                }
            }

            $wpService = new WordpressService($config['wp_url'], $config['wp_pass']);
            $wpPostId = (int)($news['wp_post_id'] ?? 0);
            
            $params = [
                'meta' => ['excerpt' => $news['excerpt'] ?? ''],
                'image_data' => ['base64' => $photoBase64, 'name' => $photoName]
            ];

            // CORRECCIÓN: Usamos "Cartelera" para activar el diseño del Theme Builder
            $result = $wpService->createPost(
                $news['title'], 
                $news['content'], 
                "Cartelera", 
                "", 
                $wpPostId, 
                $params
            );

            if ($result['ok'] && isset($result['post_id'])) {
                $this->model->updateWpSync($id, (int)$result['post_id']);
            }

            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * UNPUBLISH: Elimina de WordPress y limpia el wp_post_id local.
     */
    public function unpublish(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = (int)($_POST['id'] ?? 0);
            $news = $this->model->getById($id);
            if (!$news || empty($news['wp_post_id'])) throw new Exception("Esta noticia no está en la web.");

            $config = $this->configModel->getConfig();
            $wpService = new WordpressService($config['wp_url'], $config['wp_pass']);
            
            // Llamamos a la API para borrar en WP
            $result = $wpService->deletePost((int)$news['wp_post_id']);

            if ($result['ok']) {
                // Seteamos a 0 para habilitar de nuevo los botones en la grid
                $this->model->updateWpSync($id, 0);
                echo json_encode(['ok' => true, 'message' => 'Noticia retirada. Edición habilitada.']);
            } else {
                throw new Exception("WP no pudo eliminar el post.");
            }
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * ELIMINAR: Borra la noticia localmente si no está publicada.
     */
    public function delete(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = (int)($_POST['id'] ?? 0);
            $news = $this->model->getById($id);
            
            if ($news && (int)($news['wp_post_id'] ?? 0) > 0) {
                throw new Exception("No puede eliminar una noticia publicada. Retírela de la web primero.");
            }

            $this->model->deleteNews($id);
            echo json_encode(['ok' => true, 'message' => 'Noticia eliminada del sistema.']);

        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}