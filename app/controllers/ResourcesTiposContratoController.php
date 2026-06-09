<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * ARCHIVO: app/controllers/ResourcesTiposContratoController.php
 * PROPÓSITO: Administración del catálogo de tipos de contrato con sus siglas institucionales.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ResourcesTiposContratoModel;
use App\Services\AuditService;

final class ResourcesTiposContratoController extends Controller
{
    private ResourcesTiposContratoModel $model;
    private int $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;

        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model  = new ResourcesTiposContratoModel();
        $this->userId = (int)($_SESSION['user']['id'] ?? 0);
    }

    public function index(): void
    {
        AuditService::log([
            'module'      => 'TIPOS_CONTRATO',
            'action'      => 'ACCESS_INDEX',
            'description' => 'El usuario ingresó al catálogo de tipos de contrato.'
        ]);

        $search = $_GET['search'] ?? '';
        $this->view('resources/tipos_contrato/index', [
            'tipos'  => $this->model->getAll($search),
            'search' => $search
        ]);
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        try {
            $id = $this->model->insert($_POST, $this->userId);

            AuditService::log([
                'module'      => 'TIPOS_CONTRATO',
                'action'      => 'CREATE_SUCCESS',
                'description' => "Registró tipo de contrato: {$_POST['nombre']} ({$_POST['siglas']})",
                'entity_id'   => $id
            ]);

            header('Location: /diplomatic/public/resources/tipos-contrato?created=1');
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/resources/tipos-contrato?error=duplicate');
        }
        exit();
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0 && $this->model->update($id, $_POST)) {
            AuditService::log([
                'module'      => 'TIPOS_CONTRATO',
                'action'      => 'UPDATE_SUCCESS',
                'description' => "Actualizó tipo de contrato ID: $id",
                'entity_id'   => $id
            ]);
        }

        header('Location: /diplomatic/public/resources/tipos-contrato?updated=1');
        exit();
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id   = (int)($_POST['id'] ?? 0);
        $tipo = $this->model->getById($id);

        if ($tipo) {
            $res = $this->model->smartDelete($id);

            AuditService::log([
                'module'      => 'TIPOS_CONTRATO',
                'action'      => $res === 'deleted' ? 'DELETE_PHYSICAL' : 'DELETE_BLOCKED',
                'description' => "Procesó eliminación del tipo de contrato: {$tipo['nombre']}",
                'entity_id'   => $id,
                'event_type'  => 'WARNING'
            ]);

            if ($res === 'referenced') {
                header('Location: /diplomatic/public/resources/tipos-contrato?error=in_use');
                exit();
            }
        }

        header('Location: /diplomatic/public/resources/tipos-contrato?deleted=1');
        exit();
    }

    public function getDetails(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id   = (int)($_GET['id'] ?? 0);
        $tipo = $this->model->getById($id);
        echo json_encode(['ok' => (bool)$tipo, 'tipo' => $tipo]);
        exit();
    }
}