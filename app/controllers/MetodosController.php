<?php
/**
 * MÓDULO: CONFIGURACIÓN / MÉTODOS DE PAGO
 * ARCHIVO: app/controllers/MetodosController.php
 * PROPÓSITO: Controlador administrativo para tbl_settings_payment_methods.
 * VERSIÓN: 1.7.0 - Limpieza de métodos y estandarización de respuestas JSON.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MetodosModel;
use App\Services\AuditService;

class MetodosController extends Controller {
    private $metodosModel;

    public function __construct() {
        // Si no es ADMIN, mandamos al dashboard
        if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
            header('Location: /diplomatic/public/dashboard');
            exit;
        }
        $this->metodosModel = new MetodosModel();
    }

    public function index(): void {
        $metodos = $this->metodosModel->getAll();
        $this->view('settings/metodos', ['metodos' => $metodos]);
    }

    public function save(): void {
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = (int)($_POST['id'] ?? 0);
            $data = [
                'method_name'    => $_POST['method_name'] ?? '',
                'titular'        => $_POST['titular'] ?? null,
                'identifier'     => $_POST['identifier'] ?? null,
                'identification' => $_POST['identification'] ?? null,
                'extra_info'     => $_POST['extra_info'] ?? null,
                'qr_path'        => $_POST['qr_path'] ?? null,
                'description'    => $_POST['description'] ?? null,
                'status'         => isset($_POST['status']) ? 1 : 0
            ];

            $res = $this->metodosModel->update($id, $data);
            
            if ($res['status'] === 'success') {
                AuditService::log([
                    'module' => 'CONFIG', 'action' => 'UPDATE_PAYMENT',
                    'description' => "Cambio en canal ID: $id",
                    'entity' => 'tbl_settings_payment_methods', 'entity_id' => $id, 'db_action' => 'UPDATE'
                ]);
            }
            echo json_encode($res);
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}