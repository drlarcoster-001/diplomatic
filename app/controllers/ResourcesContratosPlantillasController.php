<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * ARCHIVO: app/controllers/ResourcesContratosPlantillasController.php
 * PROPÓSITO: Administración de plantillas de contratos institucionales con campos personalizados y editor de contenido.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ResourcesContratosPlantillasModel;
use App\Services\AuditService;

final class ResourcesContratosPlantillasController extends Controller
{
    private ResourcesContratosPlantillasModel $model;
    private int $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;

        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model  = new ResourcesContratosPlantillasModel();
        $this->userId = (int)($_SESSION['user']['id'] ?? 0);
    }

    /**
     * Grid principal de plantillas.
     */
    public function index(): void
    {
        AuditService::log([
            'module'      => 'CONTRATOS_PLANTILLAS',
            'action'      => 'ACCESS_INDEX',
            'description' => 'El usuario ingresó al módulo de plantillas de contratos.'
        ]);

        $search = $_GET['search'] ?? '';
        $this->view('resources/contratos_plantillas/index', [
            'plantillas' => $this->model->getAll($search),
            'search'     => $search
        ]);
    }

    /**
     * Formulario de creación de plantilla.
     */
    public function create(): void
    {
        AuditService::log([
            'module'      => 'CONTRATOS_PLANTILLAS',
            'action'      => 'CREATE_FORM',
            'description' => 'El usuario abrió el formulario de nueva plantilla.'
        ]);

        $this->view('resources/contratos_plantillas/create', [
            'tipos'          => $this->model->getTiposContrato(),
            'campos_sistema' => $this->model->getCamposSistema()
        ]);
    }

    /**
     * Procesa el guardado de una nueva plantilla con sus campos personalizados.
     */
public function save(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    try {
        $id = $this->model->insert($_POST, $this->userId);

        $campos = $this->parseCampos($_POST);
        if (!empty($campos)) {
            $this->model->syncCampos($id, $campos);
        }

        AuditService::log([
            'module'      => 'CONTRATOS_PLANTILLAS',
            'action'      => 'CREATE_SUCCESS',
            'description' => "Creó plantilla de contrato: {$_POST['nombre']}",
            'entity_id'   => $id
        ]);

        header("Location: /diplomatic/public/resources/contratos/plantillas?created=1");
        exit();
    } catch (\Exception $e) {
        file_put_contents(__DIR__ . '/debug_error.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
        header('Location: /diplomatic/public/resources/contratos/plantillas/create?error=db');
    }
    exit();
}

    /**
     * Formulario de edición de plantilla.
     */
    public function edit(): void
    {
        $id       = (int)($_GET['id'] ?? 0);
        $plantilla = $this->model->getById($id);

        if (!$plantilla) {
            header('Location: /diplomatic/public/resources/contratos/plantillas');
            exit();
        }

        AuditService::log([
            'module'      => 'CONTRATOS_PLANTILLAS',
            'action'      => 'EDIT_FORM',
            'description' => "Abrió edición de plantilla: {$plantilla['nombre']}",
            'entity_id'   => $id
        ]);

        $this->view('resources/contratos_plantillas/edit', [
            'plantilla'      => $plantilla,
            'tipos'          => $this->model->getTiposContrato(),
            'campos_sistema' => $this->model->getCamposSistema()
        ]);
    }

    /**
     * Procesa la actualización de una plantilla existente.
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = (int)$_POST['id'];

        $this->model->update($id, $_POST, $this->userId);

        // Sincronizar campos personalizados
        $campos = $this->parseCampos($_POST);
        $this->model->syncCampos($id, $campos);

        AuditService::log([
            'module'      => 'CONTRATOS_PLANTILLAS',
            'action'      => 'UPDATE_SUCCESS',
            'description' => "Actualizó plantilla de contrato ID: #$id",
            'entity_id'   => $id
        ]);

        header("Location: /diplomatic/public/resources/contratos/plantillas?updated=1");
        exit();
        exit();
    }

    /**
     * Inactivación de plantilla.
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id       = (int)$_POST['id'];
        $plantilla = $this->model->getById($id);

        if ($plantilla) {
            $res = $this->model->smartDelete($id, $this->userId);

            AuditService::log([
                'module'      => 'CONTRATOS_PLANTILLAS',
                'action'      => $res === 'referenced' ? 'DELETE_BLOCKED' : 'DELETE_SUCCESS',
                'description' => "Procesó eliminación de plantilla: {$plantilla['nombre']}",
                'entity_id'   => $id,
                'event_type'  => 'WARNING'
            ]);

            if ($res === 'referenced') {
                header('Location: /diplomatic/public/resources/contratos/plantillas?error=in_use');
                exit();
            }
        }

        header('Location: /diplomatic/public/resources/contratos/plantillas?deleted=1');
        exit();
    }

    /**
     * Retorna detalles en JSON para previsualización.
     */
    public function getDetails(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id       = (int)($_GET['id'] ?? 0);
        $plantilla = $this->model->getById($id);
        echo json_encode(['ok' => (bool)$plantilla, 'plantilla' => $plantilla]);
        exit();
    }

    /**
     * Retorna los campos de sistema en JSON para el editor.
     */
    public function getCamposSistema(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'campos' => $this->model->getCamposSistema()]);
        exit();
    }

    /**
     * Extrae y estructura los campos personalizados del POST.
     */
    private function parseCampos(array $post): array
    {
        $campos = [];
        $etiquetas = $post['campo_etiqueta'] ?? [];
        $tipos     = $post['campo_tipo']     ?? [];

        foreach ($etiquetas as $i => $etiqueta) {
            if (empty(trim($etiqueta))) continue;
            $campos[] = [
                'etiqueta' => trim($etiqueta),
                'tipo'     => $tipos[$i] ?? 'texto'
            ];
        }

        return $campos;
    }
}