<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * ARCHIVO: app/controllers/ResourcesTiposPersonalController.php
 * PROPÓSITO: Administración del catálogo de tipos de personal con sus siglas institucionales.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ResourcesTiposPersonalModel;
use App\Services\AuditService;

final class ResourcesTiposPersonalController extends Controller
{
    private ResourcesTiposPersonalModel $model;
    private int $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;

        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model  = new ResourcesTiposPersonalModel();
        $this->userId = (int)($_SESSION['user']['id'] ?? 0);
    }

    public function index(): void
    {
        AuditService::log([
            'module'      => 'TIPOS_PERSONAL',
            'action'      => 'ACCESS_INDEX',
            'description' => 'El usuario ingresó al catálogo de tipos de personal.'
        ]);

        $search = $_GET['search'] ?? '';
        $this->view('resources/tipos_personal/index', [
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
                'module'      => 'TIPOS_PERSONAL',
                'action'      => 'CREATE_SUCCESS',
                'description' => "Registró tipo de personal: {$_POST['nombre']} ({$_POST['siglas']})",
                'entity_id'   => $id
            ]);

            header('Location: /diplomatic/public/resources/tipos-personal?created=1');
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/resources/tipos-personal?error=duplicate');
        }
        exit();
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0 && $this->model->update($id, $_POST, $this->userId)) {
            AuditService::log([
                'module'      => 'TIPOS_PERSONAL',
                'action'      => 'UPDATE_SUCCESS',
                'description' => "Actualizó tipo de personal ID: $id",
                'entity_id'   => $id
            ]);
        }

        header('Location: /diplomatic/public/resources/tipos-personal?updated=1');
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
                'module'      => 'TIPOS_PERSONAL',
                'action'      => $res === 'deleted' ? 'DELETE_PHYSICAL' : 'INACTIVATE',
                'description' => "Procesó eliminación del tipo: {$tipo['nombre']}",
                'entity_id'   => $id,
                'event_type'  => 'WARNING'
            ]);

            if ($res === 'referenced') {
                header('Location: /diplomatic/public/resources/tipos-personal?error=in_use');
                exit();
            }
        }

        header('Location: /diplomatic/public/resources/tipos-personal?deleted=1');
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