<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / EGRESOS
 * ARCHIVO: app/controllers/FinancialGastoConceptosController.php
 * PROPÓSITO: Administración del catálogo de conceptos de gasto institucional.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\FinancialGastoConceptosModel;
use App\Services\AuditService;

final class FinancialGastoConceptosController extends Controller
{
    private FinancialGastoConceptosModel $model;
    private int $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;

        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model  = new FinancialGastoConceptosModel();
        $this->userId = (int)($_SESSION['user']['id'] ?? 0);
    }

    public function index(): void
    {
        AuditService::log([
            'module'      => 'GASTO_CONCEPTOS',
            'action'      => 'ACCESS_INDEX',
            'description' => 'El usuario ingresó al catálogo de conceptos de gasto.'
        ]);

        $search = $_GET['search'] ?? '';
        $this->view('financial/gasto_conceptos/index', [
            'conceptos'  => $this->model->getAll($search),
            'categorias' => $this->model->getCategorias(),
            'search'     => $search
        ]);
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        try {
            $id = $this->model->insert($_POST, $this->userId);

            AuditService::log([
                'module'      => 'GASTO_CONCEPTOS',
                'action'      => 'CREATE_SUCCESS',
                'description' => "Registró concepto de gasto: {$_POST['codigo']} - {$_POST['nombre']}",
                'entity_id'   => $id
            ]);

            header('Location: /diplomatic/public/financial/gasto-conceptos?created=1');
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/financial/gasto-conceptos?error=duplicate');
        }
        exit();
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0 && $this->model->update($id, $_POST)) {
            AuditService::log([
                'module'      => 'GASTO_CONCEPTOS',
                'action'      => 'UPDATE_SUCCESS',
                'description' => "Actualizó concepto de gasto ID: $id",
                'entity_id'   => $id
            ]);
        }

        header('Location: /diplomatic/public/financial/gasto-conceptos?updated=1');
        exit();
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id      = (int)($_POST['id'] ?? 0);
        $concepto = $this->model->getById($id);

        if ($concepto) {
            $res = $this->model->smartDelete($id);

            AuditService::log([
                'module'      => 'GASTO_CONCEPTOS',
                'action'      => 'DELETE_PHYSICAL',
                'description' => "Eliminó concepto de gasto: {$concepto['nombre']}",
                'entity_id'   => $id,
                'event_type'  => 'WARNING'
            ]);
        }

        header('Location: /diplomatic/public/financial/gasto-conceptos?deleted=1');
        exit();
    }

    public function getDetails(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id      = (int)($_GET['id'] ?? 0);
        $concepto = $this->model->getById($id);
        echo json_encode(['ok' => (bool)$concepto, 'concepto' => $concepto]);
        exit();
    }
}