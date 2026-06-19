<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/controllers/AcademicCentrosMedicosController.php
 * PROPÓSITO: CRUD del catálogo de Centros Médicos. create.php y edit.php son páginas
 *            separadas (no modal). Cada POST hace redirect con mensajes flash por
 *            query string (?created=1, ?updated=1, ?success=inactivated/deleted, ?error=...).
 * VERSIÓN: 1.2.0 - Fix de rutas: basePath calculado dinámicamente desde
 *           dirname($_SERVER['SCRIPT_NAME']) en vez de hardcodear "/diplomatic/public/",
 *           igual que el patrón visto en app/views/academic/index.php.
 *
 * SUPUESTO PENDIENTE DE CONFIRMAR: los métodos exactos de App\Core\Controller
 * (requireRole, view, json) y la firma real de AuditService::log().
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AcademicCentrosMedicosModel;
use App\Services\AuditService;

class AcademicCentrosMedicosController extends Controller
{
    private AcademicCentrosMedicosModel $model;
    private string $basePath;

    public function __construct()
    {
        $allowedRoles = ['ADMIN', 'OPERATOR', 'ACADEMIC'];
        $userRole = $_SESSION['user']['role'] ?? '';
        if (!in_array($userRole, $allowedRoles, true)) {
            http_response_code(403);
            echo 'Acceso denegado.';
            exit;
        }

        $this->model = new AcademicCentrosMedicosModel();

        $root = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $this->basePath = "{$root}/academic/centros-medicos";
    }

    public function index(): void
    {
        $search = trim($_GET['search'] ?? '');
        $centros = $this->model->getAll($search);

        $this->view('academic/centros_medicos/index', [
            'centros' => $centros,
            'search'  => $search,
        ]);
    }

    public function create(): void
    {
        $this->view('academic/centros_medicos/create');
    }

    public function save(): void
    {
        $nombre    = trim($_POST['nombre'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if ($nombre === '' || $this->model->nameExists($nombre)) {
            header("Location: {$this->basePath}/create?error=duplicado");
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $id = $this->model->create(['nombre' => $nombre, 'direccion' => $direccion], $userId);

        AuditService::log($userId, 'Centros Médicos', 'CREAR', "Creó el centro médico '{$nombre}'", $id);

        header("Location: {$this->basePath}?created=1");
        exit;
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $centro = $id ? $this->model->getById($id) : null;

        if (!$centro) {
            header("Location: {$this->basePath}?error=db");
            exit;
        }

        $this->view('academic/centros_medicos/edit', ['centro' => $centro]);
    }

    public function update(): void
    {
        $id        = (int) ($_POST['id'] ?? 0);
        $nombre    = trim($_POST['nombre'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if (!$id || $nombre === '' || $this->model->nameExists($nombre, $id)) {
            header("Location: {$this->basePath}/edit?id={$id}&error=duplicado");
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $this->model->update($id, ['nombre' => $nombre, 'direccion' => $direccion], $userId);

        AuditService::log($userId, 'Centros Médicos', 'EDITAR', "Editó el centro médico '{$nombre}'", $id);

        header("Location: {$this->basePath}?updated=1");
        exit;
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $centro = $id ? $this->model->getById($id) : null;

        if (!$centro) {
            header("Location: {$this->basePath}?error=db");
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $accion = $this->model->smartDelete($id, $userId);

        AuditService::log(
            $userId,
            'Centros Médicos',
            strtoupper($accion),
            "Centro médico '{$centro['nombre']}' {$accion}",
            $id
        );

        header("Location: {$this->basePath}?success={$accion}");
        exit;
    }
}