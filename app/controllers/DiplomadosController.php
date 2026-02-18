<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/DiplomadosController.php
 * Propósito: Controlador para la administración del catálogo de diplomados.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\DiplomadosModel;
use App\Core\Database;

class DiplomadosController extends Controller
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $role = $_SESSION['user']['role'] ?? '';
        if (!in_array($role, ['ADMIN', 'OPERATOR', 'ACADEMIC'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model = new DiplomadosModel();
    }

    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $diplomados = $this->model->getAll($search);
        
        $this->view('academic/diplomados/index', [
            'diplomados' => $diplomados,
            'search' => $search
        ]);
    }

    public function create(): void
    {
        $this->view('academic/diplomados/create');
    }

    /**
     * Carga el formulario de edición capturando el ID desde $_GET
     * Corregido: Ya no recibe argumentos para evitar ArgumentCountError
     */
    public function edit(): void
    {
        // Leemos el ID que viene en la URL (?id=X)
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            header('Location: /diplomatic/public/academic/diplomados');
            exit();
        }

        $diplomado = $this->model->getById($id);
        
        if (!$diplomado) {
            header('Location: /diplomatic/public/academic/diplomados?error=not_found');
            exit();
        }

        $this->view('academic/diplomados/edit', [
            'diplomado'    => $diplomado,
            'requirements' => $this->model->getRequirements($id),
            'conditions'   => $this->model->getConditions($id)
        ]);
    }

    /**
     * Obtiene los detalles completos para el Popup de Vista Previa (AJAX)
     */
    public function getDetails(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $diplomado = $this->model->getById($id);
        
        if ($diplomado) {
            $data = [
                'ok'           => true,
                'diplomado'    => $diplomado,
                'requirements' => $this->model->getRequirements($id),
                'conditions'   => $this->model->getConditions($id)
            ];
            header('Content-Type: application/json');
            echo json_encode($data);
        } else {
            echo json_encode(['ok' => false]);
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
                ':code'         => $_POST['code'],
                ':name'         => $_POST['name'],
                ':description'  => $_POST['description'] ?? null,
                ':directed_to'  => $_POST['directed_to'] ?? null,
                ':total_hours'  => $_POST['total_hours'] ?? 200
            ]);
            $id = $db->lastInsertId();
            if (!empty($_POST['requirements'])) {
                $stmtR = $db->prepare("INSERT INTO tbl_diplomados_requirements (diplomado_id, requirement_text) VALUES (?, ?)");
                foreach ($_POST['requirements'] as $r) { if (!empty(trim($r))) $stmtR->execute([$id, trim($r)]); }
            }
            if (!empty($_POST['conditions'])) {
                $stmtC = $db->prepare("INSERT INTO tbl_diplomados_conditions (diplomado_id, condition_text) VALUES (?, ?)");
                foreach ($_POST['conditions'] as $c) { if (!empty(trim($c))) $stmtC->execute([$id, trim($c)]); }
            }
            $db->commit();
            header('Location: /diplomatic/public/academic/diplomados?success=1');
        } catch (\Exception $e) {
            $db->rollBack();
            header('Location: /diplomatic/public/academic/diplomados?error=1');
        }
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        $db = (new Database())->getConnection();
        try {
            $db->beginTransaction();
            $sql = "UPDATE tbl_diplomados SET code = :code, name = :name, description = :description, 
                    directed_to = :directed_to, total_hours = :total_hours WHERE id = :id";
            $db->prepare($sql)->execute([
                ':code'        => $_POST['code'],
                ':name'        => $_POST['name'],
                ':description' => $_POST['description'],
                ':directed_to' => $_POST['directed_to'],
                ':total_hours' => $_POST['total_hours'],
                ':id'          => $id
            ]);
            $db->prepare("DELETE FROM tbl_diplomados_requirements WHERE diplomado_id = ?")->execute([$id]);
            if (!empty($_POST['requirements'])) {
                $stmtR = $db->prepare("INSERT INTO tbl_diplomados_requirements (diplomado_id, requirement_text) VALUES (?, ?)");
                foreach ($_POST['requirements'] as $r) { if (!empty(trim($r))) $stmtR->execute([$id, trim($r)]); }
            }
            $db->prepare("DELETE FROM tbl_diplomados_conditions WHERE diplomado_id = ?")->execute([$id]);
            if (!empty($_POST['conditions'])) {
                $stmtC = $db->prepare("INSERT INTO tbl_diplomados_conditions (diplomado_id, condition_text) VALUES (?, ?)");
                foreach ($_POST['conditions'] as $c) { if (!empty(trim($c))) $stmtC->execute([$id, trim($c)]); }
            }
            $db->commit();
            header('Location: /diplomatic/public/academic/diplomados?updated=1');
        } catch (\Exception $e) {
            $db->rollBack();
            header('Location: /diplomatic/public/academic/diplomados?error=1');
        }
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) $this->model->updateStatus($id, 'INACTIVE');
        header('Location: /diplomatic/public/academic/diplomados?deleted=1');
    }
}