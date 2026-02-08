<?php
/**
 * MÓDULO: USUARIOS
 * Archivo: app/controllers/UsersController.php
 * Cambio: Validación de password solo para CREAR.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;

final class UsersController extends Controller
{
    public function index(): void
    {
        if (empty($_SESSION['user']['id'])) $this->redirect('/');
        if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') $this->redirect('/dashboard');

        $model = new UserModel();
        $users = $model->getAll();
        $this->view('users/index', ['users' => $users]);
    }

    public function save(): void
    {
        header('Content-Type: application/json');

        if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') {
            echo json_encode(['ok' => false, 'msg' => 'Acceso denegado.']); return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        
        $data = [
            'first_name'  => trim($input['first_name'] ?? ''),
            'last_name'   => trim($input['last_name'] ?? ''),
            'document_id' => trim($input['cedula'] ?? ''),
            'phone'       => trim($input['phone'] ?? ''),
            'email'       => trim($input['email'] ?? ''),
            'role'        => trim($input['role'] ?? '')
        ];

        // Validaciones generales
        if (!$data['first_name'] || !$data['document_id'] || !$data['email']) {
            echo json_encode(['ok' => false, 'msg' => 'Nombre, Cédula y Correo son obligatorios.']); return;
        }

        $model = new UserModel();

        // Verificar duplicados (excluyendo el propio ID si estamos editando)
        if ($model->exists($data['email'], $data['document_id'], $id)) {
            echo json_encode(['ok' => false, 'msg' => 'El correo o la cédula ya están registrados.']); return;
        }

        if ($id > 0) {
            // --- EDITAR ---
            // No tocamos password aquí.
            if ($model->update($id, $data)) {
                echo json_encode(['ok' => true, 'msg' => 'Usuario actualizado correctamente.']);
            } else {
                echo json_encode(['ok' => false, 'msg' => 'No se detectaron cambios o hubo un error.']);
            }
        } else {
            // --- CREAR ---
            // Aquí SI pedimos contraseña
            $password = $input['password'] ?? '';
            if (empty($password)) {
                echo json_encode(['ok' => false, 'msg' => 'La contraseña es obligatoria para nuevos usuarios.']); return;
            }
            
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);

            if ($model->create($data)) {
                echo json_encode(['ok' => true, 'msg' => 'Usuario creado exitosamente.']);
            } else {
                echo json_encode(['ok' => false, 'msg' => 'Error al crear usuario.']);
            }
        }
    }

    public function delete(): void
    {
        header('Content-Type: application/json');
        if (($_SESSION['user']['role'] ?? '') !== 'ADMIN') { echo json_encode(['ok' => false]); return; }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);

        if ($id === (int)$_SESSION['user']['id']) {
            echo json_encode(['ok' => false, 'msg' => 'No puedes borrarte a ti mismo.']); return;
        }

        $model = new UserModel();
        if ($id > 0 && $model->delete($id)) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false]);
        }
    }
}