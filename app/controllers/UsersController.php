<?php
/**
 * MÓDULO: USUARIOS
 * Archivo: app/controllers/UsersController.php
 * Propósito: Controlar la lógica y generar datos de prueba.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class UsersController extends Controller
{
    public function index(): void
    {
        // Validación de sesión básica
        if (empty($_SESSION['user']['id'])) {
            $this->redirect('/');
        }

        // DATOS DE EJEMPLO (DUMMY DATA) - SIMULANDO LA BASE DE DATOS
        $users = [
            ['id'=>1, 'name'=>'Admin General', 'email'=>'admin@diplomatic.local', 'role'=>'ADMIN', 'status'=>'ACTIVE', 'created_at'=>'2024-01-10'],
            ['id'=>2, 'name'=>'Roberto Académico', 'email'=>'coord.academico@diplomatic.local', 'role'=>'ACADEMIC', 'status'=>'ACTIVE', 'created_at'=>'2024-02-14'],
            ['id'=>3, 'name'=>'Laura Finanzas', 'email'=>'cobranzas@diplomatic.local', 'role'=>'FINANCIAL', 'status'=>'ACTIVE', 'created_at'=>'2024-03-01'],
            ['id'=>4, 'name'=>'Carlos Estudiante', 'email'=>'estudiante1@gmail.com', 'role'=>'PARTICIPANT', 'status'=>'INACTIVE', 'created_at'=>'2024-03-20'],
            ['id'=>5, 'name'=>'Ana Secretaria', 'email'=>'ana.sec@diplomatic.local', 'role'=>'ACADEMIC', 'status'=>'ACTIVE', 'created_at'=>'2024-04-05'],
        ];

        // Enviamos los datos a la vista
        $this->view('users/index', ['users' => $users]);
    }

    public function create(): void
    {
        // Acción temporal para el botón "Nuevo"
        // En el futuro aquí cargarás el formulario real
        $this->redirect('/users'); 
    }
}