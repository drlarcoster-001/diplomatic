<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/DiplomadosController.php
 * Propósito: Administración del catálogo con auditoría integral.
 * Nota: Corregido registro de eliminación para mostrar Código y Nombre.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\DiplomadosModel;
use App\Core\Database;
use App\Services\AuditService;

class DiplomadosController extends Controller
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $user = $_SESSION['user'] ?? null;
        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR', 'ACADEMIC'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model = new DiplomadosModel();
    }

    public function index(): void
    {
        AuditService::log([
            'module' => 'ACADEMIC_DIPLOMADOS',
            'action' => 'ACCESS',
            'description' => 'Ingreso al listado de diplomados.'
        ]);

        $search = $_GET['search'] ?? '';
        $diplomados = $this->model->getAll($search);
        
        $this->view('academic/diplomados/index', [
            'diplomados' => $diplomados,
            'search' => $search
        ]);
    }

    public function create(): void
    {
        AuditService::log([
            'module' => 'ACADEMIC_DIPLOMADOS',
            'action' => 'CREATE_ACCESS',
            'description' => 'Entró a crear un nuevo diplomado.'
        ]);
        $this->view('academic/diplomados/create');
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $diplomado = $this->model->getById($id);

        if (!$diplomado) {
            header('Location: /diplomatic/public/academic/diplomados');
            exit();
        }

        AuditService::log([
            'module' => 'ACADEMIC_DIPLOMADOS',
            'action' => 'EDIT_ACCESS',
            'description' => 'Entró a editar el diplomado: ' . $diplomado['name'],
            'entity_id' => $id
        ]);

        $this->view('academic/diplomados/edit', [
            'd' => $diplomado,
            'diplomado' => $diplomado,
            'requirements' => $this->model->getRequirements($id),
            'conditions' => $this->model->getConditions($id)
        ]);
    }

    public function getDetails(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $diplomado = $this->model->getById($id);
        
        if ($diplomado) {
            AuditService::log([
                'module' => 'ACADEMIC_DIPLOMADOS',
                'action' => 'PREVIEW',
                'description' => 'Vio la vista previa de: ' . $diplomado['name']
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'ok' => true,
                'diplomado' => $diplomado,
                'requirements' => $this->model->getRequirements($id),
                'conditions' => $this->model->getConditions($id)
            ]);
            exit;
        }
        echo json_encode(['ok' => false]);
        exit;
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $dataBefore = $this->model->getById($id);
        
        $db = (new Database())->getConnection();
        try {
            $db->beginTransaction();
            $sql = "UPDATE tbl_diplomados SET code = :code, name = :name, description = :description, 
                    directed_to = :directed_to, total_hours = :total_hours WHERE id = :id";
            $db->prepare($sql)->execute([
                ':code' => $_POST['code'],
                ':name' => $_POST['name'],
                ':description' => $_POST['description'],
                ':directed_to' => $_POST['directed_to'],
                ':total_hours' => $_POST['total_hours'],
                ':id' => $id
            ]);

            AuditService::log([
                'module' => 'ACADEMIC_DIPLOMADOS',
                'action' => 'UPDATE',
                'description' => 'Actualizó el diplomado: ' . $_POST['name'],
                'data_before' => $dataBefore,
                'data_after' => $_POST
            ]);

            $db->commit();
            header('Location: /diplomatic/public/academic/diplomados?updated=1');
        } catch (\Exception $e) {
            $db->rollBack();
            header('Location: /diplomatic/public/academic/diplomados?error=1');
        }
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $db = (new Database())->getConnection();
        try {
            $db->beginTransaction();
            $sql = "INSERT INTO tbl_diplomados (code, name, description, directed_to, total_hours) 
                    VALUES (:code, :name, :description, :directed_to, :total_hours)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':code' => $_POST['code'],
                ':name' => $_POST['name'],
                ':description' => $_POST['description'],
                ':directed_to' => $_POST['directed_to'],
                ':total_hours' => $_POST['total_hours']
            ]);
            $id = $db->lastInsertId();

            AuditService::log([
                'module' => 'ACADEMIC_DIPLOMADOS',
                'action' => 'CREATE',
                'description' => 'Creó el diplomado: ' . $_POST['name'],
                'data_after' => $_POST
            ]);

            $db->commit();
            header('Location: /diplomatic/public/academic/diplomados?success=1');
        } catch (\Exception $e) {
            if(isset($db)) $db->rollBack();
            header('Location: /diplomatic/public/academic/diplomados?error=1');
        }
    }

    /**
     * CORRECCIÓN: Captura Código y Nombre antes de eliminar.
     */
    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Buscamos los datos para que el log sea informativo
            $info = $this->model->getById($id);
            $identificador = $info ? "[{$info['code']}] - {$info['name']}" : "ID: $id";

            $this->model->updateStatus($id, 'INACTIVE');

            AuditService::log([
                'module' => 'ACADEMIC_DIPLOMADOS',
                'action' => 'DELETE',
                'description' => 'Eliminó el diplomado: ' . $identificador,
                'event_type' => 'WARNING'
            ]);
        }
        header('Location: /diplomatic/public/academic/diplomados?deleted=1');
    }
}