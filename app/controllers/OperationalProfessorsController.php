<?php
/**
 * MÓDULO: GESTIÓN OPERATIVA / PROFESSORS
 * ARCHIVO: app/Controllers/OperationalProfessorsController.php
 * PROPÓSITO: Controlador maestro para la gestión de datos estéticos y sincronización hacia WordPress.
 * VERSIÓN: 1.0.3 - Integración de endpoints para guardado de textos web (saveTexts) y procesamiento de imágenes en Base64 (saveImage).
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OperationalProfessorsModel;

final class OperationalProfessorsController extends Controller
{
    private OperationalProfessorsModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Verificación de sesión
        if (!isset($_SESSION['user'])) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'message' => 'Sesión expirada']);
                exit;
            }
            header("Location: /diplomatic/public/login");
            exit;
        }

        $this->model = new OperationalProfessorsModel();
    }

    /**
     * Vista principal de la Grid
     */
    public function index(): void
    {
        $this->view('operational/professors/index', [
            'title' => 'Gestión de Staff Web',
            'specialties' => $this->model->getUniqueSpecialties()
        ]);
    }

    /**
     * Endpoint AJAX para listar profesores en la Grid
     */
    public function list(): void
    {
        // --- BLINDAJE CRÍTICO ---
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json; charset=utf-8');

        try {
            $filters = [
                'search' => $_GET['search'] ?? '',
                'specialty' => $_GET['specialty'] ?? '',
                'sync_status' => $_GET['sync_status'] ?? '',
                'only_incomplete' => isset($_GET['incomplete']) && $_GET['incomplete'] === 'true'
            ];

            $data = $this->model->getProfessorsForGrid($filters);
            
            echo json_encode([
                'ok' => true, 
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false, 
                'message' => $e->getMessage()
            ]);
        }
        exit; // Evita que el motor de rutas intente renderizar algo más
    }

    /**
     * Endpoint: Guarda o actualiza la Etiqueta (Cargo) y la Biografía
     */
    public function saveTexts(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = (int)($_POST['id'] ?? 0);
            $label = trim($_POST['label'] ?? '');
            $bio = trim($_POST['bio'] ?? '');

            if ($id === 0 || empty($label) || empty($bio)) {
                throw new \Exception("Faltan datos obligatorios para guardar los textos.");
            }

            $this->model->saveWebTexts($id, $label, $bio);
            
            echo json_encode(['ok' => true, 'message' => 'Textos guardados correctamente.']);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Endpoint: Procesa la imagen Base64 de Cropper y guarda el archivo físico
     */
    public function saveImage(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = (int)($_POST['id'] ?? 0);
            $base64Image = $_POST['image'] ?? '';

            if ($id === 0 || empty($base64Image)) {
                throw new \Exception("Faltan datos de la imagen o del profesor.");
            }

            // 1. Decodificar la imagen Base64
            // El formato que llega es: data:image/png;base64,iVBORw0KGgo...
            $image_parts = explode(";base64,", $base64Image);
            if (count($image_parts) !== 2) {
                throw new \Exception("El formato de la imagen procesada es inválido.");
            }
            
            $image_base64 = base64_decode($image_parts[1]);
            if ($image_base64 === false) {
                throw new \Exception("Error al decodificar la imagen Base64.");
            }
            
            // 2. Definir rutas físicas y públicas
            // Usamos __DIR__ para subir niveles: app/Controllers -> app -> raiz -> public
            $uploadDir = __DIR__ . '/../../public/assets/uploads/profesores_web/';
            
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new \Exception("No se pudo crear el directorio de subida.");
                }
            }

            $fileName = 'prof_web_' . $id . '_' . time() . '.png';
            $filePath = $uploadDir . $fileName;
            $publicUrl = '/diplomatic/public/assets/uploads/profesores_web/' . $fileName;

            // 3. Guardar el archivo físico en el servidor
            if (!file_put_contents($filePath, $image_base64)) {
                throw new \Exception("Error de permisos al escribir el archivo de imagen en el servidor.");
            }

            // 4. Guardar la URL pública en la Base de Datos
            $this->model->saveWebPhoto($id, $publicUrl);

            echo json_encode(['ok' => true, 'message' => 'Imagen procesada y guardada correctamente.']);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Helper: Verifica si la solicitud es AJAX
     */
    private function isAjax(): bool 
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}