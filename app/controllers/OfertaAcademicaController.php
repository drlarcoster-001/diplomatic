<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/Controllers/OfertaAcademicaController.php
 * PROPÓSITO: Controlador maestro encargado de orquestar el ciclo de vida completo de las convocatorias (Oferta Académica).
 * VERSIÓN: 3.44.0 - Integración de captura de Fecha de Vencimiento para el Esquema de Pagos.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OfertaAcademicaModel;
use App\Models\UserModel;
use App\Services\AuditService;

final class OfertaAcademicaController extends Controller
{
    private OfertaAcademicaModel $model;
    private int $userId;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Blindaje contra "Unexpected token <": Si no hay sesión y es AJAX, devolver JSON, no redirigir a HTML.
        if (empty($_SESSION['user']['id'])) {
            $isFetch = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
            if ($isFetch) {
                if (ob_get_level()) ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'msg' => 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.']);
                exit;
            }
            $this->redirect('/login');
        }
        
        $this->model = new OfertaAcademicaModel(); 
        $this->userId = (int)$_SESSION['user']['id'];
    }

    public function index(): void {
        AuditService::log([
            'module'      => 'ACADEMIC_OFFERING',
            'action'      => 'VIEW_INDEX',
            'description' => "El usuario " . $_SESSION['user']['name'] . " accedió al panel de Oferta Académica.",
            'event_type'  => 'NORMAL'
        ]);

        $filters = [
            'diploma_id' => $_GET['diploma_id'] ?? null,
            'cohort_id'  => $_GET['cohort_id']  ?? null,
            'status'     => $_GET['status']     ?? null,
            'periodo_id' => $_GET['periodo_id'] ?? null,
        ];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $total   = $this->model->countAll($filters);
        $pages   = (int)ceil($total / $perPage);
        $offset  = ($page - 1) * $perPage;

        $this->view('academic/oferta/index', [
            'ofertas'    => $this->model->getAll($filters, $perPage, $offset),
            'diplomados' => $this->model->getActiveDiplomas(),
            'cohortes'   => $this->model->getOperableCohorts(),
            'periodos'   => $this->model->getPeriodos(),
            'filters'    => $filters,
            'page'       => $page,
            'pages'      => $pages,
            'total'      => $total,
        ]);

        
    }

    public function create(): void {
        $this->view('academic/oferta/create', [
            'diplomados' => $this->model->getActiveDiplomas(), 
            'cohortes'   => $this->model->getOperableCohorts(), 
            'grupos'     => $this->model->getActiveGroups(), 
            'professors' => $this->model->getActiveProfessors(),
            'periodos'   => $this->model->getPeriodos(),
            'oferta'     => ['description' => '']
        ]);
    }

    /**
     * Procesa la creación de una nueva oferta y retorna JSON blindado con Try-Catch.
     */
    public function save(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido.');
            }
            
            if (empty($_POST['campuses']) || empty($_POST['groups_check']) || empty($_POST['payment_concept'])) {
                throw new \Exception('Faltan campos obligatorios de la oferta (Sedes, Grupos o Pagos).');
            }

            $data = $this->prepareData($_POST);
            $newId = $this->model->insert($data, $this->userId);
            
            if ($newId) {
                AuditService::log([
                    'module'      => 'ACADEMIC_OFFERING', 
                    'action'      => 'CREATE',
                    'description' => "Nueva oferta creada #$newId en estado BORRADOR.",
                    'event_type'  => 'SUCCESS'
                ]);
                echo json_encode(['ok' => true, 'msg' => 'La oferta ha sido creada exitosamente como Borrador.']);
            } else {
                throw new \Exception('Error crítico al registrar la oferta en la base de datos.');
            }
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        $oferta = $this->model->getById($id);
        
        if (!$oferta) $this->redirect('/academic/oferta?error=not_found');

        $oferta['description'] = $oferta['description'] ?? '';
        
        $this->view('academic/oferta/edit', [
            'oferta'     => $oferta, 
            'diplomados' => $this->model->getActiveDiplomas(), 
            'cohortes'   => $this->model->getOperableCohorts(), 
            'grupos'     => $this->model->getActiveGroups(), 
            'professors' => $this->model->getActiveProfessors(), 
            'campuses'   => $this->model->getCampusesByCohortId((int)$oferta['cohort_id'])
        ]);
    }

    /**
     * Procesa la actualización de la oferta y retorna JSON blindado con Try-Catch.
     */
    public function update(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido.');
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new \Exception('ID de oferta inválido.');

            $data = $this->prepareData($_POST);

            if ($this->model->update($id, $data, $this->userId)) {
                AuditService::log([
                    'module'      => 'ACADEMIC_OFFERING', 
                    'action'      => 'UPDATE',
                    'description' => "Se actualizó la oferta #$id (Ciclo de Venta).", 
                    'event_type'  => 'SUCCESS'
                ]);
                echo json_encode(['ok' => true, 'msg' => 'La oferta ha sido actualizada exitosamente.']);
            } else {
                throw new \Exception('Error al procesar la actualización en la base de datos.');
            }
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    public function executeOpen(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        
        $id = (int)($_POST['id'] ?? 0);
        
        if ($this->model->updateStatus($id, 'ABIERTA', $this->userId)) {
            echo json_encode(['ok' => true]);
        } else { 
            echo json_encode(['ok' => false]); 
        }
        exit;
    }

    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)$_POST['id'];
        
        if ($this->model->delete($id, $this->userId)) {
            $this->redirect('/academic/oferta?success=deleted');
        } else { 
            $this->redirect('/academic/oferta?error=delete_failed'); 
        }
    }

    public function getCohortCampuses(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($this->model->getCampusesByCohortId((int)($_GET['cohort_id'] ?? 0)));
        exit;
    }

    public function logSummaryPopup(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        AuditService::log([
            'module' => 'ACADEMIC_OFFERING', 'action' => 'VIEW_SUMMARY_POPUP', 
            'description' => "Visualización de resumen para oferta #$id.", 'event_type' => 'NORMAL'
        ]);
        echo json_encode(['ok' => true]);
        exit;
    }

    public function logLockPopup(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        AuditService::log([
            'module' => 'ACADEMIC_OFFERING', 'action' => 'VIEW_UNLOCK_POPUP', 
            'description' => "Intento de desbloqueo administrativo para oferta #$id.", 'event_type' => 'NORMAL'
        ]);
        echo json_encode(['ok' => true]);
        exit;
    }

    public function verifyAdmin(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $userModel = new UserModel();
        $result = $userModel->verifyLogin($email, $password);

        if ($result['ok'] && in_array(strtoupper($result['user']['role']), ['ADMIN', 'SUPERADMIN', 'ADMINISTRADOR'])) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Credenciales insuficientes o incorrectas.']);
        }
        exit;
    }

    public function changeStatusAdmin(): void {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        
        if ($this->model->updateStatus($id, $newStatus, $this->userId)) {
            AuditService::log([
                'module' => 'ACADEMIC_OFFERING', 'action' => 'STATUS_CHANGE_ADMIN', 
                'description' => "Cambio de estatus forzado por Admin a: $newStatus en oferta #$id.", 'event_type' => 'WARNING'
            ]);
            echo json_encode(['ok' => true]);
        } else { 
            echo json_encode(['ok' => false]); 
        }
        exit;
    }

    /**
     * --- MAPEO DE DATOS ACTUALIZADO ---
     */
    private function prepareData(array $p): array {
        $cap = (int)($p['total_capacity'] ?? 0);
        if ($cap > 399) $cap = 399; 
        
        return [
            'diploma_id'          => (int)$p['diploma_id'], 
            'cohort_id'           => (int)$p['cohort_id'], 
            'total_capacity'      => $cap,
            'registration_start'  => $p['registration_start'], 
            'registration_end'    => $p['registration_end'],
            'class_start'         => $p['class_start'], 
            'class_end'           => $p['class_end'],
            'general_modality'    => $p['general_modality'], 
            'total_cost'          => (float)$p['total_cost'],
            'description'         => trim($p['description'] ?? ''), 
            'campuses'            => $p['campuses'] ?? [],
            'groups'              => $p['groups_check'] ?? [], 
            'professor_id'        => $p['professor_id'] ?? [],
            'professor_role'      => $p['professor_role'] ?? [], 
            
            // Bloque de pagos sincronizado con la nueva Grid del JS
            'payment_concept'     => $p['payment_concept'] ?? [],
            'payment_amount'      => $p['payment_amount'] ?? [], 
            'payment_due_date'    => $p['payment_due_date'] ?? [], // <--- NUEVO CAMPO CAPTURADO
            'payment_description' => $p['payment_description'] ?? []
        ];
    }

    public function pdf(): void
{
    while (ob_get_level() > 0) ob_end_clean();
    try {
        $filters = [
            'diploma_id' => $_GET['diploma_id'] ?? null,
            'cohort_id'  => $_GET['cohort_id']  ?? null,
            'status'     => $_GET['status']      ?? null,
            'periodo_id' => $_GET['periodo_id']  ?? null,
        ];
        $ofertas       = $this->model->getAll($filters, 1000, 0);
        $periodos      = $this->model->getPeriodos();
        $nombrePeriodo = '';
        if (!empty($filters['periodo_id'])) {
            $p = array_values(array_filter($periodos, fn($p) => (int)$p['id'] === (int)$filters['periodo_id']))[0] ?? null;
            if ($p) $nombrePeriodo = $p['nombre'];
        }
        require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'Helvetica']);
        $dompdf->setPaper('letter', 'landscape');
        $pathUcla     = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-ucla.png';
        $pathMedicina = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/assets/uploads/logos/logo-medicina.jpg';
        $imgUcla      = file_exists($pathUcla)     ? 'data:image/png;base64,'  . base64_encode(file_get_contents($pathUcla))     : '';
        $imgMedicina  = file_exists($pathMedicina) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($pathMedicina)) : '';
        $fechaHoy     = date('d/m/Y H:i');
        $labelPeriodo = $nombrePeriodo ?: 'Todos los períodos';
        $filas = '';
        foreach ($ofertas as $i => $o) {
            $bg = $i % 2 === 0 ? '#ffffff' : '#f8f9fa';
            $filas .= "<tr style='background:{$bg}'>
                <td style='padding:6px 8px;border-bottom:0.5px solid #dee2e6'>{$o['diplomado_name']}</td>
                <td style='padding:6px 8px;border-bottom:0.5px solid #dee2e6;font-size:9pt'>{$o['cohort_name']}</td>
                <td style='padding:6px 8px;border-bottom:0.5px solid #dee2e6;text-align:center'>{$o['general_modality']}</td>
                <td style='padding:6px 8px;border-bottom:0.5px solid #dee2e6;text-align:center'>{$o['status']}</td>
                <td style='padding:6px 8px;border-bottom:0.5px solid #dee2e6;text-align:center'>{$o['cupos_totales']}</td>
                <td style='padding:6px 8px;border-bottom:0.5px solid #dee2e6;text-align:center'>{$o['enrolled_count']}</td>
                <td style='padding:6px 8px;border-bottom:0.5px solid #dee2e6;text-align:center'>\${$o['total_cost']}</td>
            </tr>";
        }
        $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'>
        <style>body{font-family:Helvetica;font-size:10pt;color:#212529;padding:1cm}
        table{width:100%;border-collapse:collapse}
        th{background:#212529;color:#fff;padding:8px;text-align:left;font-size:9pt}
        </style></head><body>
        <table style='width:100%;border-collapse:collapse;margin-bottom:16px;table-layout:fixed'>
            <tr>
                <td style='width:15%;text-align:left'><img src='{$imgUcla}' style='width:70px'></td>
                <td style='width:70%;text-align:center;font-weight:bold;font-size:11pt;line-height:1.4'>
                    UNIVERSIDAD CENTROCCIDENTAL &ldquo;LISANDRO ALVARADO&rdquo;<br>
                    DECANATO DE CIENCIAS DE LA SALUD<br>
                    &ldquo;Dr. PABLO ACOSTA ORTIZ&rdquo;<br>
                    COORDINACIÓN DE EXTENSIÓN
                </td>
                <td style='width:15%;text-align:right'><img src='{$imgMedicina}' style='width:70px'></td>
            </tr>
        </table>
        <div style='text-align:center;margin-bottom:16px'>
            <div style='font-weight:bold;font-size:14pt'>Reporte de Oferta Académica</div>
            <div style='font-size:9pt;color:#555'>{$labelPeriodo} — Generado: {$fechaHoy}</div>
        </div>

        <table>
            <thead><tr>
                <th>Diplomado</th><th>Cohorte</th><th>Modalidad</th>
                <th>Estatus</th><th>Cupos</th><th>Inscritos</th><th>Costo</th>
            </tr></thead>
            <tbody>{$filas}</tbody>
        </table></body></html>";
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        $dompdf->stream("Oferta_Academica_" . date('Ymd') . ".pdf", ["Attachment" => false]);
        exit;
    } catch (\Throwable $e) {
        die("Error PDF: " . $e->getMessage());
    }
}

public function getCohortesByPeriodo(): void
{
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    $periodoId = (int)($_GET['periodo_id'] ?? 0);
    echo json_encode($this->model->getCohortesByPeriodoForOferta($periodoId));
    exit;
}
}