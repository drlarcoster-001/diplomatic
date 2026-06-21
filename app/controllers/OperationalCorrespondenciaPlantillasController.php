<?php
/**
 * MÓDULO: CORRESPONDENCIA / PLANTILLAS
 * ARCHIVO: app/controllers/OperationalCorrespondenciaPlantillasController.php
 * PROPÓSITO: index() lista plantillas. create()/save() crea una nueva.
 *            edit()/update() edita. getCamposSistema() AJAX para refrescar
 *            el sidebar de variables al cambiar la tabla objetivo, sin
 *            recargar la página. delete() elimina si no tiene documentos
 *            generados vinculados.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\OperationalCorrespondenciaPlantillasController;
 *   $router->get('/operational/correspondencia/plantillas',                 [OperationalCorrespondenciaPlantillasController::class, 'index']);
 *   $router->get('/operational/correspondencia/plantillas/create',           [OperationalCorrespondenciaPlantillasController::class, 'create']);
 *   $router->post('/operational/correspondencia/plantillas/save',            [OperationalCorrespondenciaPlantillasController::class, 'save']);
 *   $router->get('/operational/correspondencia/plantillas/edit',              [OperationalCorrespondenciaPlantillasController::class, 'edit']);
 *   $router->post('/operational/correspondencia/plantillas/update',           [OperationalCorrespondenciaPlantillasController::class, 'update']);
 *   $router->post('/operational/correspondencia/plantillas/delete',           [OperationalCorrespondenciaPlantillasController::class, 'delete']);
 *   $router->get('/operational/correspondencia/plantillas/getCamposSistema', [OperationalCorrespondenciaPlantillasController::class, 'getCamposSistema']);
 *   $router->get('/operational/correspondencia/plantillas/getDetails',        [OperationalCorrespondenciaPlantillasController::class, 'getDetails']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OperationalCorrespondenciaPlantillasModel;
use App\Services\AuditService;
use Throwable;

class OperationalCorrespondenciaPlantillasController extends Controller
{
    private OperationalCorrespondenciaPlantillasModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new OperationalCorrespondenciaPlantillasModel();
    }

    public function index(): void
    {
        $search     = trim($_GET['search'] ?? '');
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $perPage    = 25;
        $total      = $this->model->countPlantillas($search);
        $totalPages = (int) ceil($total / $perPage);
        $plantillas = $this->model->getPlantillas($search, $page, $perPage);

        // Conteo de documentos generados por plantilla (para bloquear el borrado en pantalla)
        foreach ($plantillas as &$p) {
            $p['documentos_count'] = $this->model->countDocumentosGenerados((int) $p['id']);
        }
        unset($p);

        $this->view('operational/correspondencia/plantillas/index', [
            'plantillas' => $plantillas, 'search' => $search, 'page' => $page,
            'total' => $total, 'totalPages' => $totalPages,
            'tiposDocumento' => $this->model->getTiposDocumento(),
        ]);
    }

    public function create(): void
    {
        $this->view('operational/correspondencia/plantillas/create', [
            'tipos'  => $this->model->getTiposDocumento(),
            'tablas' => $this->model->getTablasObjetivo(),
        ]);
    }

    public function save(): void
    {
        try {
            $nombre    = trim($_POST['nombre'] ?? '');
            $tipo      = $_POST['tipo_documento'] ?? '';
            $tabla     = $_POST['tabla_objetivo'] ?? '';
            $contenido = $_POST['contenido'] ?? '';
            $etiquetas = $_POST['campo_etiqueta'] ?? [];
            $tipos     = $_POST['campo_tipo'] ?? [];

            if ($nombre === '' || !array_key_exists($tipo, $this->model->getTiposDocumento())
                || !array_key_exists($tabla, $this->model->getTablasObjetivo())) {
                header('Location: /diplomatic/public/operational/correspondencia/plantillas/create?error=incompleto');
                exit;
            }

            $camposPersonalizados = $this->normalizarCamposPersonalizados($etiquetas, $tipos);

            $userId = $_SESSION['user']['id'];
            $id = $this->model->crearPlantilla($nombre, $tipo, $tabla, $contenido, $camposPersonalizados, $userId);

            AuditService::log($userId, 'Correspondencia', 'CREAR_PLANTILLA', "Creó la plantilla \"{$nombre}\"", $id);

            header("Location: /diplomatic/public/operational/correspondencia/plantillas/edit?id={$id}&created=1");
            exit;
        } catch (Throwable $e) {
            header('Location: /diplomatic/public/operational/correspondencia/plantillas/create?error=db');
            exit;
        }
    }

    public function edit(): void
    {
        $id        = (int) ($_GET['id'] ?? 0);
        $plantilla = $id ? $this->model->getPlantillaById($id) : null;

        if (!$plantilla) {
            header('Location: /diplomatic/public/operational/correspondencia/plantillas?error=notfound');
            exit;
        }

        $this->view('operational/correspondencia/plantillas/edit', [
            'plantilla' => $plantilla,
            'tipos'     => $this->model->getTiposDocumento(),
            'tablas'    => $this->model->getTablasObjetivo(),
        ]);
    }

    public function update(): void
    {
        try {
            $id        = (int) ($_POST['id'] ?? 0);
            $nombre    = trim($_POST['nombre'] ?? '');
            $tipo      = $_POST['tipo_documento'] ?? '';
            $tabla     = $_POST['tabla_objetivo'] ?? '';
            $contenido = $_POST['contenido'] ?? '';
            $etiquetas = $_POST['campo_etiqueta'] ?? [];
            $tipos     = $_POST['campo_tipo'] ?? [];

            if (!$id || $nombre === '' || !array_key_exists($tipo, $this->model->getTiposDocumento())
                || !array_key_exists($tabla, $this->model->getTablasObjetivo())) {
                header("Location: /diplomatic/public/operational/correspondencia/plantillas/edit?id={$id}&error=incompleto");
                exit;
            }

            $camposPersonalizados = $this->normalizarCamposPersonalizados($etiquetas, $tipos);

            $userId = $_SESSION['user']['id'];
            $this->model->actualizarPlantilla($id, $nombre, $tipo, $tabla, $contenido, $camposPersonalizados, $userId);

            AuditService::log($userId, 'Correspondencia', 'EDITAR_PLANTILLA', "Editó la plantilla \"{$nombre}\"", $id);

            header("Location: /diplomatic/public/operational/correspondencia/plantillas/edit?id={$id}&updated=1");
            exit;
        } catch (Throwable $e) {
            header("Location: /diplomatic/public/operational/correspondencia/plantillas/edit?id={$id}&error=db");
            exit;
        }
    }

    public function delete(): void
    {
        $id        = (int) ($_POST['id'] ?? 0);
        $plantilla = $id ? $this->model->getPlantillaById($id) : null;

        if (!$plantilla) {
            header('Location: /diplomatic/public/operational/correspondencia/plantillas?error=notfound');
            exit;
        }
        if ($this->model->countDocumentosGenerados($id) > 0) {
            header('Location: /diplomatic/public/operational/correspondencia/plantillas?error=in_use');
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $this->model->eliminarPlantilla($id);

        AuditService::log($userId, 'Correspondencia', 'ELIMINAR_PLANTILLA', "Eliminó la plantilla \"{$plantilla['nombre']}\"", $id);

        header('Location: /diplomatic/public/operational/correspondencia/plantillas?deleted=1');
        exit;
    }

    /**
     * AJAX: devuelve los campos del sistema disponibles para la tabla objetivo
     * elegida, sin recargar la página (para no perder lo escrito en el editor).
     */
    public function getCamposSistema(): void
    {
        $tabla  = $_GET['tabla'] ?? '';
        $campos = $this->model->getCamposSistema($tabla);

        $this->jsonFinal(['success' => true, 'campos' => $campos]);
    }

    public function getDetails(): void
    {
        $id        = (int) ($_GET['id'] ?? 0);
        $plantilla = $id ? $this->model->getPlantillaById($id) : null;

        if (!$plantilla) {
            $this->jsonFinal(['ok' => false]);
            return;
        }

        $this->jsonFinal(['ok' => true, 'plantilla' => $plantilla]);
    }

    private function normalizarCamposPersonalizados(array $etiquetas, array $tipos): array
    {
        $resultado = [];
        foreach ($etiquetas as $i => $etiqueta) {
            $etiqueta = trim((string) $etiqueta);
            if ($etiqueta === '') continue;
            $slug = preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '-'], '_', mb_strtolower($etiqueta)));
            $resultado[] = [
                'etiqueta' => $etiqueta,
                'slug'     => $slug,
                'tipo'     => $tipos[$i] ?? 'texto',
            ];
        }
        return $resultado;
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