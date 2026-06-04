<?php
/**
 * MÓDULO: CONFIGURACIÓN / PÁGINA WEB
 * ARCHIVO: app/Controllers/SettingsWordpressController.php
 * PROPÓSITO: Controlador maestro para la sincronización de Staff con la Página Web.
 * VERSIÓN: 2.7.0 - FIX: Procesador de párrafos HTML para conservar saltos de línea en biografía.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\WordpressConfigModel;
use App\Models\OperationalProfessorsModel; 
use App\Services\AuditService;
use App\Services\WordpressService;
use Exception;

final class SettingsWordpressController extends Controller
{
    private WordpressConfigModel $configModel;
    private OperationalProfessorsModel $opProfModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user'])) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'message' => 'Sesión expirada.']);
                exit;
            }
            header("Location: /diplomatic/public/login");
            exit;
        }

        $this->configModel = new WordpressConfigModel();
        $this->opProfModel = new OperationalProfessorsModel();
    }

    public function index(): void
    {
        $config = $this->configModel->getConfig();
        $this->view('settings/wordpress', [
            'title'      => 'Configuración de Página Web',
            'config'     => $config,
            'profesores' => $this->opProfModel->getProfessorsForGrid()
        ]);
    }

    /**
     * MÉTODO: Probar Conexión
     */
    public function test(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $wpUrl  = $_POST['wp_url'] ?? '';
            $wpPass = $_POST['wp_pass'] ?? '';

            if (empty($wpUrl) || empty($wpPass)) {
                $config = $this->configModel->getConfig();
                $wpUrl  = $config['wp_url'] ?? '';
                $wpPass = $config['wp_pass'] ?? '';
            }

            if (empty($wpUrl) || empty($wpPass)) {
                throw new Exception("La URL y el Token de acceso son obligatorios.");
            }

            $wpService = new WordpressService($wpUrl, $wpPass);
            $isConnected = $wpService->authenticate();

            if ($isConnected) {
                echo json_encode(['ok' => true, 'message' => '¡Conexión exitosa con la página web!']);
            } else {
                echo json_encode(['ok' => false, 'message' => 'No se pudo autenticar. Verifique el token.']);
            }

        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * MÉTODO: Guardar Configuración
     */
    public function save(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $url  = trim($_POST['wp_url'] ?? '');
            $user = trim($_POST['wp_user'] ?? '');
            $pass = trim($_POST['wp_pass'] ?? '');

            if (empty($url) || empty($pass)) {
                throw new Exception("Faltan datos de configuración requeridos.");
            }

            $this->configModel->saveConfig($url, $user, $pass);
            
            AuditService::log([
                'module' => 'SETTINGS_WEB',
                'action' => 'UPDATE_CONFIG',
                'description' => 'Configuración de enlace web actualizada.'
            ]);

            echo json_encode(['ok' => true, 'message' => 'Ajustes guardados correctamente.']);

        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * MÉTODO PRINCIPAL: Sincronización de Staff
     */
    public function syncProfessor(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception("ID de docente no válido.");

            $profWeb = $this->opProfModel->getByProfessorId($id);
            if (!$profWeb) throw new Exception("No se encontraron los datos para subir.");

            $config = $this->configModel->getConfig();
            $wpService = new WordpressService($config['wp_url'], $config['wp_pass']);

            // 1. Normalización de Nombre
            $firstNameRaw = explode(' ', trim($profWeb['first_name'] ?? ''))[0];
            $lastNameRaw  = explode(' ', trim($profWeb['last_name'] ?? ''))[0];
            $fullName = ucwords(strtolower($firstNameRaw . ' ' . $lastNameRaw));
            
            $cargo = ucwords(strtolower(trim($profWeb['web_label'] ?? 'Docente Académico')));
            
            /**
             * 🛠️ CORRECCIÓN DE PÁRRAFOS (BIO)
             * El texto plano usa \n que la web ignora. Convertimos a HTML estructural.
             */
            $rawBio = trim($profWeb['web_bio'] ?? '');
            // Convertimos dobles saltos de línea en párrafos
            $formattedBio = '<p>' . str_replace(["\r\n\r\n", "\n\n"], '</p><p>', $rawBio) . '</p>';
            // Convertimos saltos simples en <br>
            $formattedBio = str_replace(["\r\n", "\n"], '<br>', $formattedBio);
            // Limpieza de párrafos vacíos accidentales
            $formattedBio = str_replace('<p></p>', '', $formattedBio);
            
            // 2. Procesamiento de Imagen
            $rutaFotoBD = $profWeb['web_photo_url'] ?? '';
            $photoBase64 = '';
            $photoName   = '';

            if (!empty($rutaFotoBD)) {
                $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'); 
                $pathBD  = ltrim($rutaFotoBD, '/\\'); 
                $rutaFisica = $docRoot . '/' . $pathBD;

                if (file_exists($rutaFisica)) {
                    $photoBase64 = base64_encode(file_get_contents($rutaFisica));
                    $photoName   = basename($rutaFisica);
                } else {
                    throw new Exception("Error: Archivo de imagen no encontrado físicamente.");
                }
            }

            // 3. Preparación de Parámetros
            $params = [
                'meta' => [
                    'tipo' => $cargo, 
                    'bio'  => $formattedBio // Enviamos la bio con etiquetas HTML
                ],
                'image_data' => [
                    'base64' => $photoBase64,
                    'name'   => $photoName
                ]
            ];

            $wpPostId = (int)($profWeb['wp_post_id'] ?? 0);
            
            // Sincronizamos (Create/Update)
            $result = $wpService->createPost($fullName, $formattedBio, "docente", "", $wpPostId, $params);

            if ($result['ok']) {
                $this->opProfModel->updateWpSync($id, (int)$result['post_id']);
                AuditService::log([
                    'module' => 'SETTINGS_WEB',
                    'action' => 'SYNC_PROF_SUCCESS',
                    'description' => "Docente {$fullName} montado en la web con formato de párrafos."
                ]);
            }

            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * MÉTODO: Eliminar de la página web
     */
    public function deleteProfessorPost(): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = (int)($_POST['id'] ?? 0);
            $profWeb = $this->opProfModel->getByProfessorId($id);
            if (!$profWeb || empty($profWeb['wp_post_id'])) throw new Exception("La publicación no existe en la web.");

            $wpService = new WordpressService($this->configModel->getConfig()['wp_url'], $this->configModel->getConfig()['wp_pass']);
            $result = $wpService->deletePost((int)$profWeb['wp_post_id']);

            if ($result['ok']) {
                $this->opProfModel->unsyncWpPost($id);
            }
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}