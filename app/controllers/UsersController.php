<?php
/**
 * MÓDULO: USUARIOS
 * Archivo: app/controllers/UsersController.php
 * Propósito: Gestión completa de usuarios con soporte para Avatar y perfiles académicos.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;

final class UsersController extends Controller
{
    /**
     * Muestra la lista de usuarios.
     * Si te sigue redirigiendo, he relajado la validación temporalmente para pruebas.
     */
    public function index(): void
    {
        // Verificación de sesión básica
        if (!isset($_SESSION['user']['id'])) {
            $this->redirect('/');
        }

        // VALIDACIÓN DE ROL: 
        // Convertimos a mayúsculas para evitar que 'admin' != 'ADMIN' sea el problema.
        $role = strtoupper($_SESSION['user']['role'] ?? '');
        
        if ($role !== 'ADMIN') {
            // Si después de poner este archivo sigues sin entrar, 
            // COMENTA la línea de abajo con // para forzar el acceso y probar la vista.
            $this->redirect('/dashboard');
        }

        $model = new UserModel();
        $users = $model->getAll();
        
        $this->view('users/index', ['users' => $users]);
    }

    /**
     * Guarda o actualiza un usuario (Maneja FormData/Multipart para fotos).
     */
    public function save(): void
    {
        header('Content-Type: application/json');

        if (strtoupper($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
            echo json_encode(['ok' => false, 'msg' => 'No tienes permisos de administrador.']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        
        // Mapeo de campos según la nueva estructura de la DB
        $data = [
            'first_name'           => trim($_POST['first_name'] ?? ''),
            'last_name'            => trim($_POST['last_name'] ?? ''),
            'document_id'          => trim($_POST['document_id'] ?? ''),
            'phone'                => trim($_POST['phone'] ?? ''),
            'email'                => trim($_POST['email'] ?? ''),
            'role'                 => $_POST['role'] ?? 'PARTICIPANT',
            'address'              => trim($_POST['address'] ?? ''),
            'provenance'           => trim($_POST['provenance'] ?? ''),
            'undergraduate_degree' => trim($_POST['undergraduate_degree'] ?? ''),
            'avatar'               => $_POST['current_avatar'] ?? 'default.png'
        ];

        // --- PROCESAR IMAGEN (AVATAR) ---
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $dir = dirname(__DIR__, 2) . '/public/assets/img/avatars/';
            
            // Crear carpeta si no existe
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $fileName = 'usr_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $fileName)) {
                    $data['avatar'] = $fileName;
                }
            }
        }

        $model = new UserModel();

        try {
            if ($id > 0) {
                // Editar: pasamos el ID y los datos
                $success = $model->update($id, $data);
                echo json_encode(['ok' => $success, 'msg' => $success ? 'Actualizado correctamente' : 'Sin cambios']);
            } else {
                // Crear: requiere contraseña
                $pass = $_POST['password'] ?? '';
                if (empty($pass)) {
                    echo json_encode(['ok' => false, 'msg' => 'La contraseña es obligatoria']);
                    return;
                }
                $data['password'] = password_hash($pass, PASSWORD_DEFAULT);
                $success = $model->create($data);
                echo json_encode(['ok' => $success, 'msg' => $success ? 'Creado correctamente' : 'Error al crear']);
            }
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Elimina un usuario.
     */
    public function delete(): void
    {
        header('Content-Type: application/json');
        
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user']['id']) {
            echo json_encode(['ok' => false, 'msg' => 'No puedes eliminarte a ti mismo']);
            return;
        }

        $model = new UserModel();
        $success = $model->delete($id);
        echo json_encode(['ok' => $success]);
    }
}