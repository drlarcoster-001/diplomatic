<?php
/**
 * MÓDULO: ACADÉMICO / IMPORTADOR
 * ARCHIVO: app/controllers/AcademicImportadorController.php
 * PROPÓSITO: index() muestra el formulario de importación.
 *            importar() ejecuta la clonación del período.
 * VERSIÓN: 1.0.0 - Creación inicial.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\AcademicImportadorController;
 *   $router->get('/academic/importador',          [AcademicImportadorController::class, 'index']);
 *   $router->post('/academic/importador/importar', [AcademicImportadorController::class, 'importar']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AcademicImportadorModel;
use Throwable;

class AcademicImportadorController extends Controller
{
    private AcademicImportadorModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $allowed = ['ADMIN', 'ACADEMIC'];
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed, true)) {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new AcademicImportadorModel();
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): void
    {
        $this->view('academic/importador/index', [
    'periodos'      => $this->model->getPeriodos(),
    'importaciones' => $this->model->getImportaciones(),
    'result'        => null,
]);
    }

    // =========================================================================
    // IMPORTAR
    // =========================================================================

    public function importar(): void
    {
        $userId          = (int) $_SESSION['user']['id'];
        $periodoOrigenId = (int) ($_POST['periodo_origen_id'] ?? 0);

        // Validaciones básicas
        if (!$periodoOrigenId) {
            $this->volverConError('Debes seleccionar un período origen.');
            return;
        }

        $periodoCode = trim($_POST['periodo_code'] ?? '');
        $nombre      = trim($_POST['nombre']       ?? '');
        $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
        $fechaFin    = trim($_POST['fecha_fin']    ?? '');

        if (!$periodoCode || !$nombre || !$fechaInicio || !$fechaFin) {
            $this->volverConError('Completa todos los campos del nuevo período.');
            return;
        }

        if ($this->model->existePeriodoCode($periodoCode)) {
            $this->volverConError("El código '{$periodoCode}' ya existe.");
            return;
        }

        // Verificar que el período origen no haya sido importado ya
        if ($this->model->existeImportacionOrigen($periodoOrigenId)) {
            $this->volverConError("Este período ya fue importado. Reversa la importación anterior antes de volver a importar.");
            return;
        }

        try {
            // Crear nuevo período
            $periodoData = [
                'periodo_code'         => $periodoCode,
                'nombre'               => $nombre,
                'fecha_inicio'         => $fechaInicio,
                'fecha_fin'            => $fechaFin,
                'apertura_inscripcion' => trim($_POST['apertura_inscripcion'] ?? ''),
                'cierre_inscripcion'   => trim($_POST['cierre_inscripcion']   ?? ''),
                'descripcion'          => trim($_POST['descripcion']          ?? ''),
            ];

            $periodoDestinoId = $this->model->insertPeriodo($periodoData, $userId);
            $periodoDestino   = $this->model->getPeriodoById($periodoDestinoId);

            // Ejecutar importación
            $result = $this->model->importar($periodoOrigenId, $periodoDestinoId, $periodoDestino, $userId);
$result['periodo_destino'] = $periodoDestino;

if ($result['success']) {
    $this->model->registrarImportacion($periodoOrigenId, $periodoDestinoId, $userId);
}

$this->view('academic/importador/index', [
    'periodos'      => $this->model->getPeriodos(),
    'importaciones' => $this->model->getImportaciones(),
    'result'        => $result,
]);

        } catch (Throwable $e) {
            $this->volverConError('Error: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    private function volverConError(string $msg): void
    {
$this->view('academic/importador/index', [
    'periodos'      => $this->model->getPeriodos(),
    'importaciones' => $this->model->getImportaciones(),
    'result'        => ['success' => false, 'error' => $msg, 'log' => []],
]);
    }


    public function reversar(): void
{
    $userId            = (int) $_SESSION['user']['id'];
    $periodoDestinoId  = (int) ($_POST['periodo_destino_id'] ?? 0);

    if (!$periodoDestinoId) {
        $this->jsonFinal(['success' => false, 'message' => 'Período no válido.']);
        return;
    }

    $result = $this->model->reversarImportacion($periodoDestinoId);

    $this->jsonFinal([
        'success' => $result['success'],
        'message' => $result['success']
            ? 'Importación reversada correctamente.'
            : ($result['error'] ?? 'Error al reversar.'),
        'log'     => $result['log'] ?? [],
    ]);
}

private function jsonFinal(array $payload): void
{
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
}