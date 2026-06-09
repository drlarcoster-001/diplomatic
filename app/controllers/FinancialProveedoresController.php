<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / PROVEEDORES
 * ARCHIVO: app/controllers/FinancialProveedoresController.php
 * PROPÓSITO: Administración del catálogo de proveedores externos del programa.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialProveedoresModel;
use App\Services\AuditService;

final class FinancialProveedoresController extends Controller
{
    private FinancialProveedoresModel $model;
    private int $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;

        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model  = new FinancialProveedoresModel();
        $this->userId = (int)($_SESSION['user']['id'] ?? 0);
    }

    public function index(): void
    {
        AuditService::log([
            'module'      => 'PROVEEDORES',
            'action'      => 'ACCESS_INDEX',
            'description' => 'El usuario ingresó al catálogo de proveedores.'
        ]);

        $search = $_GET['search'] ?? '';
        $this->view('financial/proveedores/index', [
            'proveedores' => $this->model->getAll($search),
            'search'      => $search
        ]);
    }

    public function create(): void
    {
        $this->view('financial/proveedores/create');
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        try {
            $id = $this->model->insert($_POST, $this->userId);

            AuditService::log([
                'module'      => 'PROVEEDORES',
                'action'      => 'CREATE_SUCCESS',
                'description' => "Registró proveedor: {$_POST['nombre']}",
                'entity_id'   => $id
            ]);

            header("Location: /diplomatic/public/financial/proveedores/edit?id={$id}&created=1");
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/financial/proveedores/create?error=duplicate');
        }
        exit();
    }

    public function edit(): void
    {
        $id   = (int)($_GET['id'] ?? 0);
        $prov = $this->model->getById($id);

        if (!$prov) {
            header('Location: /diplomatic/public/financial/proveedores');
            exit();
        }

        $this->view('financial/proveedores/edit', ['proveedor' => $prov]);
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = (int)$_POST['id'];
        $this->model->update($id, $_POST, $this->userId);

        AuditService::log([
            'module'      => 'PROVEEDORES',
            'action'      => 'UPDATE_SUCCESS',
            'description' => "Actualizó proveedor ID: #$id",
            'entity_id'   => $id
        ]);

        if (($_POST['redirect'] ?? '') === 'index') {
            header('Location: /diplomatic/public/financial/proveedores?updated=1');
        } else {
            header("Location: /diplomatic/public/financial/proveedores/edit?id={$id}&updated=1");
        }
        exit();
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id   = (int)$_POST['id'];
        $prov = $this->model->getById($id);

        if ($prov) {
            $this->model->delete($id);

            AuditService::log([
                'module'      => 'PROVEEDORES',
                'action'      => 'DELETE_PHYSICAL',
                'description' => "Eliminó proveedor: {$prov['nombre']}",
                'entity_id'   => $id,
                'event_type'  => 'WARNING'
            ]);
        }

        header('Location: /diplomatic/public/financial/proveedores?deleted=1');
        exit();
    }

    public function getDetails(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id   = (int)($_GET['id'] ?? 0);
        $prov = $this->model->getById($id);
        echo json_encode(['ok' => (bool)$prov, 'proveedor' => $prov]);
        exit();
    }
}