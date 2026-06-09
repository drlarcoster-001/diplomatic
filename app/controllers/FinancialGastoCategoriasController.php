<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / EGRESOS
 * ARCHIVO: app/controllers/FinancialGastoCategoriasController.php
 * PROPÓSITO: Administración del catálogo de categorías de gasto institucional.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialGastoCategoriasModel;
use App\Services\AuditService;

final class FinancialGastoCategoriasController extends Controller
{
    private FinancialGastoCategoriasModel $model;
    private int $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;

        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model  = new FinancialGastoCategoriasModel();
        $this->userId = (int)($_SESSION['user']['id'] ?? 0);
    }

    public function index(): void
    {
        AuditService::log([
            'module'      => 'GASTO_CATEGORIAS',
            'action'      => 'ACCESS_INDEX',
            'description' => 'El usuario ingresó al catálogo de categorías de gasto.'
        ]);

        $search = $_GET['search'] ?? '';
        $this->view('financial/gasto_categorias/index', [
            'categorias' => $this->model->getAll($search),
            'search'     => $search
        ]);
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        try {
            $id = $this->model->insert($_POST, $this->userId);

            AuditService::log([
                'module'      => 'GASTO_CATEGORIAS',
                'action'      => 'CREATE_SUCCESS',
                'description' => "Registró categoría de gasto: {$_POST['codigo']} - {$_POST['nombre']}",
                'entity_id'   => $id
            ]);

            header('Location: /diplomatic/public/financial/gasto-categorias?created=1');
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/financial/gasto-categorias?error=duplicate');
        }
        exit();
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0 && $this->model->update($id, $_POST)) {
            AuditService::log([
                'module'      => 'GASTO_CATEGORIAS',
                'action'      => 'UPDATE_SUCCESS',
                'description' => "Actualizó categoría de gasto ID: $id",
                'entity_id'   => $id
            ]);
        }

        header('Location: /diplomatic/public/financial/gasto-categorias?updated=1');
        exit();
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id  = (int)($_POST['id'] ?? 0);
        $cat = $this->model->getById($id);

        if ($cat) {
            $res = $this->model->smartDelete($id);

            AuditService::log([
                'module'      => 'GASTO_CATEGORIAS',
                'action'      => $res === 'deleted' ? 'DELETE_PHYSICAL' : 'DELETE_BLOCKED',
                'description' => "Procesó eliminación de categoría: {$cat['nombre']}",
                'entity_id'   => $id,
                'event_type'  => 'WARNING'
            ]);

            if ($res === 'referenced') {
                header('Location: /diplomatic/public/financial/gasto-categorias?error=in_use');
                exit();
            }
        }

        header('Location: /diplomatic/public/financial/gasto-categorias?deleted=1');
        exit();
    }

    public function getDetails(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id  = (int)($_GET['id'] ?? 0);
        $cat = $this->model->getById($id);
        echo json_encode(['ok' => (bool)$cat, 'categoria' => $cat]);
        exit();
    }
}