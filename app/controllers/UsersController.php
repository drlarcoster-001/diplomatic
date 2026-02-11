<?php
/**
 * MÓDULO: USUARIOS
 * Archivo: app/controllers/UsersController.php
 * Propósito: Controlador central para la gestión de usuarios, perfiles y roles.
 * Integra la lógica de tipos de usuario y estados de cuenta.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Core\Database;

final class UsersController extends Controller
{
    public function index(): void
    {
        if (empty($_SESSION['user']['id'])) $this->redirect('/');

        $userModel = new UserModel();
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->query("SELECT role_key, name FROM tbl_roles WHERE status = 'ACTIVE' ORDER BY level DESC");
            $roles = $stmt->fetchAll();
        } catch (\Exception $e) {
            $roles = [];
        }

        $this->view('users/index', [
            'users' => $userModel->getAll(),
            'roles' => $roles
        ]);
    }

    public function save(): void
    {
        header('Content-Type: application/json');
        if (strtoupper($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
            echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'user_type'            => $_POST['user_type'] ?? 'INTERNAL',
            'status'               => $_POST['status'] ?? 'ACTIVE',
            'first_name'           => trim($_POST['first_name'] ?? ''),
            'last_name'            => trim($_POST['last_name'] ?? ''),
            'document_id'          => trim($_POST['document_id'] ?? ''),
            'phone'                => trim($_POST['phone'] ?? ''),
            'email'                => trim($_POST['email'] ?? ''),
            'role'                 => $_POST['role'] ?? 'PARTICIPANT',
            'address'              => trim($_POST['address'] ?? ''),
            'provenance'           => trim($_POST['provenance'] ?? ''),
            'undergraduate_degree' => trim($_POST['undergraduate_degree'] ?? ''),
            'avatar'               => $_POST['current_avatar'] ?? 'default_avatar.png'
        ];

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $fileName = 'usr_' . time() . '.' . pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], dirname(__DIR__, 2) . '/public/assets/img/avatars/' . $fileName)) {
                $data['avatar'] = $fileName;
            }
        }

        $model = new UserModel();
        try {
            if ($id > 0) {
                $success = $model->update($id, $data);
            } else {
                $data['password_hash'] = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
                $success = $model->create($data);
            }
            echo json_encode(['ok' => $success, 'msg' => 'Operación exitosa']);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function delete(): void
    {
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id === (int)$_SESSION['user']['id']) {
            echo json_encode(['ok' => false, 'msg' => 'No puede desactivarse a sí mismo']);
            return;
        }

        $model = new UserModel();
        echo json_encode(['ok' => $model->delete($id), 'msg' => 'Usuario desactivado']);
    }
}