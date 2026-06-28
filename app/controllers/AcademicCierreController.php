<?php
/**
 * MÓDULO: ACADÉMICO / CIERRE ACADÉMICO
 * ARCHIVO: app/controllers/AcademicCierreController.php
 * PROPÓSITO: index() lista ofertas. manage() grid con 3 notas obligatorias,
 *            contacto profesor por modalidad. cerrar() ejecuta el cierre.
 * VERSIÓN: 1.2.0 - 3 notas siempre obligatorias. Profesor por modalidad.
 *
 * RUTAS Bootstrap.php:
 *   use App\Controllers\AcademicCierreController;
 *   $router->get('/academic/cierre',         [AcademicCierreController::class, 'index']);
 *   $router->get('/academic/cierre/manage',  [AcademicCierreController::class, 'manage']);
 *   $router->post('/academic/cierre/cerrar', [AcademicCierreController::class, 'cerrar']);
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AcademicCierreModel;
use App\Services\AuditService;
use Throwable;

class AcademicCierreController extends Controller
{
    private AcademicCierreModel $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $allowed = ['ADMIN', 'ACADEMIC'];
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed, true)) {
            header('Location: /diplomatic/public/login');
            exit;
        }
        $this->model = new AcademicCierreModel();
    }

    public function index(): void
    {
        $search  = trim($_GET['search'] ?? '');
        $ofertas = $this->model->getOfertas($search);

        $this->view('academic/cierre/index', [
            'ofertas'   => $ofertas,
            'search'    => $search,
            'historial' => $this->model->getHistorialReversas($search),
        ]);
    }

    public function manage(): void
    {
        $offeringId = (int) ($_GET['offering_id'] ?? 0);
        $oferta     = $offeringId ? $this->model->getOferta($offeringId) : null;

        if (!$oferta) {
            header('Location: /diplomatic/public/academic/cierre?error=notfound');
            exit;
        }

        $notaMinima  = $this->model->getNotaMinima($offeringId);
        $estudiantes = $this->model->getEstudiantes($offeringId);
        $profesores  = $this->model->getProfesoresPorModalidad($offeringId);
        $totalCosto  = (float) $oferta['total_cost'];

        foreach ($estudiantes as &$e) {
            $e['total_paid'] = (float) $e['total_paid'];
            $e['solvente']   = $e['total_paid'] >= $totalCosto;
            $e['saldo_falta'] = $totalCosto - $e['total_paid'];

            // Nota final — requiere las 3
            $notaTeorica  = $e['nota_teorica']  !== null ? (float)$e['nota_teorica']  : 0;
            $notaPractica = $e['nota_practica'] !== null ? (float)$e['nota_practica'] : 0;
            $notaVirtual  = $e['nota_virtual']  !== null ? (float)$e['nota_virtual']  : 0;
            $e['nota_final'] = (int) round(($notaTeorica + $notaPractica + $notaVirtual) / 3);

            $e['aprobado']      = $e['nota_final'] !== null && $e['nota_final'] >= $notaMinima;
            $e['ced_ok']        = !empty($e['doc_id_card']);
            $e['tit_ok']        = !empty($e['doc_degree']);
            $e['cv_ok']         = !empty($e['doc_cv']);
            $e['expediente_ok'] = $e['ced_ok'] && $e['tit_ok'] && $e['cv_ok'];
            $e['apto']          = $e['solvente'] && $e['aprobado'] && $e['expediente_ok'];

            $nombre           = $e['first_name'] . ' ' . $e['last_name'];
            $phone            = preg_replace('/[^0-9]/', '', $e['phone'] ?? '');
            $e['phone_clean'] = $phone;

            $e['wa_solvencia'] = null;
            if (!$e['solvente'] && $phone) {
                $e['wa_solvencia'] = urlencode(
                    "Estimado/a {$nombre}, tiene un saldo pendiente de $" .
                    number_format($e['saldo_falta'], 2) .
                    ". Por favor regularice su situación para proceder con el cierre académico."
                );
            }

            $faltanDocs = [];
            if (!$e['ced_ok']) $faltanDocs[] = 'Cédula';
            if (!$e['tit_ok']) $faltanDocs[] = 'Título';
            if (!$e['cv_ok'])  $faltanDocs[] = 'CV';

            $e['wa_expediente'] = null;
            if (!empty($faltanDocs) && $phone) {
                $e['wa_expediente'] = urlencode(
                    "Estimado/a {$nombre}, le faltan los siguientes documentos: " .
                    implode(', ', $faltanDocs) .
                    ". Por favor consígnelos para proceder con el cierre académico."
                );
            }
        }
        unset($e);

        $todosAptos = $this->model->todosAptos($offeringId, $notaMinima);

        $this->view('academic/cierre/manage', [
            'oferta'      => $oferta,
            'offeringId'  => $offeringId,
            'estudiantes' => $estudiantes,
            'profesores'  => $profesores,
            'notaMinima'  => $notaMinima,
            'totalCosto'  => $totalCosto,
            'todosAptos'  => $todosAptos,
        ]);
    }

    public function cerrar(): void
    {
        try {
            $offeringId = (int) ($_POST['offering_id'] ?? 0);
            $oferta     = $offeringId ? $this->model->getOferta($offeringId) : null;

            if (!$oferta) {
                $this->jsonFinal(['success' => false, 'message' => 'Oferta no encontrada.'], 404);
                return;
            }
            if ($oferta['cohort_status'] !== 'En curso') {
                $this->jsonFinal(['success' => false, 'message' => 'La cohorte debe estar En curso para cerrar el acta.'], 422);
                return;
            }
            if ($oferta['cohort_status'] !== 'En curso') {
                $this->jsonFinal(['success' => false, 'message' => 'La cohorte debe estar En curso para cerrar el acta.'], 422);
                return;
            }

            $userId = $_SESSION['user']['id'];
            $this->model->cerrarOferta($offeringId, $userId);

            AuditService::log($userId, 'CierreAcademico', 'CERRAR',
                "Cerró oferta {$offeringId} — {$oferta['diplomado_nombre']}", $offeringId);

            $this->jsonFinal(['success' => true, 'message' => 'Oferta cerrada correctamente.']);

        } catch (Throwable $e) {
            $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function reversar(): void
{
    try {
        $offeringId = (int) ($_POST['offering_id'] ?? 0);
        $oferta     = $offeringId ? $this->model->getOferta($offeringId) : null;

        if (!$oferta) {
            $this->jsonFinal(['success' => false, 'message' => 'Oferta no encontrada.'], 404);
            return;
        }
        if ($oferta['status'] !== 'CERRADA') {
            $this->jsonFinal(['success' => false, 'message' => 'La oferta no está cerrada.'], 422);
            return;
        }
        if ($oferta['cohort_status'] !== 'Finalizada') {
            $this->jsonFinal(['success' => false, 'message' => 'La cohorte debe estar Finalizada para reversar el cierre.'], 422);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $motivo = trim($_POST['motivo'] ?? '');
        if ($motivo === '') {
            $this->jsonFinal(['success' => false, 'message' => 'Debes indicar el motivo de la reversa.'], 422);
            return;
        }
        $this->model->reversarCierre($offeringId, $userId, $motivo);

        AuditService::log($userId, 'CierreAcademico', 'REVERSAR',
            "Reversó cierre de oferta {$offeringId} — {$oferta['diplomado_nombre']}", $offeringId);

        $this->jsonFinal(['success' => true, 'message' => 'Cierre reversado. La oferta volvió a estado ABIERTA.']);
    } catch (Throwable $e) {
        $this->jsonFinal(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

    private function jsonFinal(array $payload, int $code = 200): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        try { echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); }
        catch (Throwable $e) { echo json_encode(['success' => false, 'message' => 'Error JSON.']); }
        exit;
    }


    
}