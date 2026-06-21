<?php
/**
 * MÓDULO: CORRESPONDENCIA / GENERADOR DE DOCUMENTOS
 * ARCHIVO: app/controllers/OperationalCorrespondenciaDocumentosController.php
 * PROPÓSITO: index() = historial de documentos generados. create() = paso 1
 *            (elegir plantilla). getRegistros()/getPlantillaInfo() = AJAX
 *            para el paso 2 (elegir registros + ver campos personalizados
 *            de la plantilla). generar() = produce el lote de PDFs.
 *            descargar() = sirve un PDF ya generado.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\OperationalCorrespondenciaDocumentosController;
 *   $router->get('/operational/correspondencia/documentos',              [...,'index']);
 *   $router->get('/operational/correspondencia/documentos/create',       [...,'create']);
 *   $router->get('/operational/correspondencia/documentos/getRegistros', [...,'getRegistros']);
 *   $router->get('/operational/correspondencia/documentos/getPlantillaInfo', [...,'getPlantillaInfo']);
 *   $router->post('/operational/correspondencia/documentos/generar',     [...,'generar']);
 *   $router->get('/operational/correspondencia/documentos/descargar',    [...,'descargar']);
 *   $router->get('/operational/correspondencia/documentos/lote',         [...,'lote']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OperationalCorrespondenciaDocumentosModel;
use App\Services\AuditService;
use Throwable;

class OperationalCorrespondenciaDocumentosController extends Controller
{
    private OperationalCorrespondenciaDocumentosModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new OperationalCorrespondenciaDocumentosModel();
    }

    public function index(): void
    {
        $search     = trim($_GET['search'] ?? '');
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $perPage    = 25;
        $total      = $this->model->countHistorial($search);
        $totalPages = (int) ceil($total / $perPage);
        $documentos = $this->model->getHistorial($search, $page, $perPage);

        $this->view('operational/correspondencia/documentos/index', [
            'documentos' => $documentos, 'search' => $search, 'page' => $page,
            'total' => $total, 'totalPages' => $totalPages,
        ]);
    }

    public function create(): void
    {
        $this->view('operational/correspondencia/documentos/create', [
            'plantillas' => $this->model->getPlantillas(),
        ]);
    }

    /** AJAX: lista de registros de la tabla objetivo de la plantilla, con búsqueda */
    public function getRegistros(): void
    {
        $tabla  = $_GET['tabla'] ?? '';
        $search = trim($_GET['search'] ?? '');
        $registros = $this->model->getRegistros($tabla, $search);
        $this->jsonFinal(['success' => true, 'registros' => $registros]);
    }

    /** AJAX: datos de la plantilla (tabla objetivo, campos personalizados) */
    public function getPlantillaInfo(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $plantilla = $id ? $this->model->getPlantillaById($id) : null;
        if (!$plantilla) {
            $this->jsonFinal(['success' => false]);
            return;
        }
        $this->jsonFinal([
            'success' => true,
            'tabla_objetivo' => $plantilla['tabla_objetivo'],
            'campos_personalizados' => $plantilla['campos_personalizados_arr'],
            'tipo_documento' => $plantilla['tipo_documento'],
        ]);
    }

    public function generar(): void
    {
        try {
            $plantillaId = (int) ($_POST['plantilla_id'] ?? 0);
            $registroIds = $_POST['registro_ids'] ?? [];
            $slugs       = $_POST['valor_slug'] ?? [];
            $valores     = $_POST['valor_valor'] ?? [];

            if (!$plantillaId || empty($registroIds)) {
                header('Location: /diplomatic/public/operational/correspondencia/documentos/create?error=incompleto');
                exit;
            }

            $valoresPersonalizados = [];
            foreach ($slugs as $i => $slug) {
                if ($slug === '') continue;
                $valoresPersonalizados[$slug] = $valores[$i] ?? '';
            }

            $userId = $_SESSION['user']['id'];
            $loteId = $this->model->generarLote($plantillaId, $registroIds, $valoresPersonalizados, $userId);

            AuditService::log($userId, 'Correspondencia', 'GENERAR_DOCUMENTOS', "Generó " . count($registroIds) . " documento(s), lote {$loteId}", $plantillaId);

            header("Location: /diplomatic/public/operational/correspondencia/documentos/lote?lote={$loteId}");
            exit;
        } catch (Throwable $e) {
            header('Location: /diplomatic/public/operational/correspondencia/documentos/create?error=db');
            exit;
        }
    }

    public function lote(): void
    {
        $loteId = $_GET['lote'] ?? '';
        $documentos = $loteId ? $this->model->getHistorial('', 1, 500) : [];
        $documentos = array_filter($documentos, fn($d) => $d['lote_id'] === $loteId);

        $this->view('operational/correspondencia/documentos/lote', [
            'documentos' => $documentos, 'loteId' => $loteId,
        ]);
    }

    public function descargar(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $doc = $id ? $this->model->getDocumentoById($id) : null;

        if (!$doc || !$doc['pdf_path']) {
            http_response_code(404);
            echo 'Documento no encontrado.';
            exit;
        }

        $fullPath = dirname(__DIR__, 2) . '/public/' . $doc['pdf_path'];
        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo 'Archivo no encontrado en disco.';
            exit;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
        readfile($fullPath);
        exit;
    }

    public function delete(): void
    {
        $id  = (int) ($_POST['id'] ?? 0);
        $doc = $id ? $this->model->getDocumentoById($id) : null;

        if (!$doc) {
            header('Location: /diplomatic/public/operational/correspondencia/documentos?error=notfound');
            exit;
        }

        if (!empty($doc['pdf_path'])) {
            $fullPath = dirname(__DIR__, 2) . '/public/' . $doc['pdf_path'];
            if (file_exists($fullPath)) unlink($fullPath);
        }

        $userId = $_SESSION['user']['id'];
        $this->model->eliminarDocumento($id);

        AuditService::log($userId, 'Correspondencia', 'ELIMINAR_DOCUMENTO', "Eliminó el documento \"{$doc['codigo']}\"", $id);

        header('Location: /diplomatic/public/operational/correspondencia/documentos?deleted=1');
        exit;
    }

    private function jsonFinal(array $payload, int $code = 200): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        try { echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); }
        catch (Throwable $e) { echo json_encode(['success' => false, 'message' => 'Error JSON.']); }
        exit;
    }
}