<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/controllers/DiplomadosController.php
 * Propósito: Administración de diplomados, generación de PDF y eliminación segura.
 * Version: 1.6.4 - RUTA REAL /tools/ y Blindaje con Try-Catch en eliminación.
 */

namespace App\Controllers;

// --- CARGA DE LIBRERÍA BASADA EN TU ESTRUCTURA REAL ---
// Ruta validada según estructura20260316.txt en la carpeta tools
$dompdfAutoloader = dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';

if (file_exists($dompdfAutoloader)) {
    require_once $dompdfAutoloader;
} else {
    // Fallback de seguridad para entornos locales XAMPP
    $fallbackPath = 'C:/xampp/htdocs/diplomatic/tools/dompdf/autoload.inc.php';
    if (file_exists($fallbackPath)) {
        require_once $fallbackPath;
    } else {
        die("Error de Sistema: No se pudo localizar Dompdf en la ruta: " . $dompdfAutoloader);
    }
}

use App\Core\Controller;
use App\Models\DiplomadosModel;
use App\Services\AuditService;
use Dompdf\Dompdf;
use Dompdf\Options;

class DiplomadosController extends Controller
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            header('Location: /diplomatic/public/login');
            exit();
        }
        if (!in_array($user['role'], ['ADMIN', 'OPERATOR', 'ACADEMIC'])) {
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
            'description' => 'Ingreso al listado maestro de diplomados.'
        ]);

        $search = $_GET['search'] ?? '';
        $this->view('academic/diplomados/index', [
            'diplomados' => $this->model->getAll($search),
            'search' => $search
        ]);
    }

    /**
     * EXPORTAR FICHA TÉCNICA A PDF
     */
    public function exportPdf(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $diplomado = $this->model->getById($id);

        if (!$diplomado) {
            die("Error: Diplomado no encontrado.");
        }

        $requirements = $this->model->getRequirements($id);
        $conditions = $this->model->getConditions($id);

        // --- DISEÑO DEL PDF ---
        $html = '
        <html>
        <head>
            <style>
                body { font-family: "Helvetica", sans-serif; color: #333; line-height: 1.4; padding: 20px; }
                .header { text-align: center; border-bottom: 2px solid #003366; padding-bottom: 10px; margin-bottom: 20px; }
                .title { font-size: 22px; font-weight: bold; color: #003366; text-transform: uppercase; margin: 0; }
                .code { font-size: 12px; color: #666; margin-top: 5px; }
                .section-label { font-weight: bold; color: #003366; font-size: 13px; margin-top: 20px; display: block; text-decoration: underline; }
                .content { font-size: 13px; margin-top: 5px; text-align: justify; }
                ul { font-size: 13px; margin-top: 5px; padding-left: 20px; }
                li { margin-bottom: 5px; }
                .footer { margin-top: 40px; border-top: 1px solid #eee; padding-top: 10px; text-align: center; font-weight: bold; font-size: 14px; }
                .watermark { position: absolute; top: 40%; left: 20%; font-size: 80px; color: rgba(200,200,200,0.1); transform: rotate(-45deg); z-index: -1; }
            </style>
        </head>
        <body>
            <div class="header">
                <p style="margin:0; font-size:10px; text-transform:uppercase;">Instituto de Altos Estudios Diplomáticos</p>
                <h1 class="title">Ficha Técnica de Programa</h1>
                <p class="code">DOCUMENTO OFICIAL: IAED-FT-'.strtoupper($diplomado['code']).'-'.date('Y').'</p>
            </div>
            <div class="watermark">OFICIAL</div>
            <div class="section-label">NOMBRE DEL PROGRAMA:</div>
            <div class="content" style="font-size: 16px; font-weight: bold;">'.htmlspecialchars($diplomado['name']).'</div>
            <div class="section-label">DIRIGIDO A:</div>
            <div class="content">'.nl2br(htmlspecialchars($diplomado['directed_to'] ?? 'Personal profesional y público general.' )).'</div>
            <div class="section-label">DESCRIPCIÓN Y OBJETIVOS:</div>
            <div class="content">'.nl2br(htmlspecialchars($diplomado['description'] ?? 'No especificada.' )).'</div>
            <div class="section-label">REQUISITOS DE INGRESO:</div>
            <ul>';
            foreach ($requirements as $req) {
                $html .= '<li>'.htmlspecialchars($req['requirement_text']).'</li>';
            }
            if(empty($requirements)) $html .= '<li>Sin requisitos especiales definidos.</li>';
        $html .= '</ul>
            <div class="section-label">CONDICIONES GENERALES:</div>
            <ul>';
            foreach ($conditions as $cond) {
                $html .= '<li>'.htmlspecialchars($cond['condition_text']).'</li>';
            }
            if(empty($conditions)) $html .= '<li>Sujeto a normativa institucional vigente.</li>';
        $html .= '</ul>
            <div class="footer">
                CARGA HORARIA TOTAL: '.($diplomado['total_hours'] ?? 0).' HORAS ACADÉMICAS.
            </div>
        </body>
        </html>';

        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $dompdf->stream("Ficha_Tecnica_".str_replace(' ', '_', $diplomado['name']).".pdf", [
                "Attachment" => false 
            ]);
            
            AuditService::log([
                'module' => 'ACADEMIC_DIPLOMADOS', 'action' => 'EXPORT_PDF',
                'description' => "Exportación de ficha técnica PDF: {$diplomado['name']}", 'entity_id' => $id
            ]);
            
        } catch (\Exception $e) {
            die("Error en generación PDF: " . $e->getMessage());
        }
        exit;
    }

    public function create(): void { $this->view('academic/diplomados/create'); }

    public function ajaxAutoSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)($_POST['id'] ?? 0);
        $code = substr(trim($_POST['code'] ?? ''), 0, 25);
        if (empty($code)) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'Código requerido']);
            exit;
        }
        $data = [
            'code' => $code,
            'name' => $_POST['name'] ?: 'Borrador: ' . $code,
            'total_hours' => (int)($_POST['total_hours'] ?? 0),
            'description' => $_POST['description'] ?? null,
            'directed_to' => $_POST['directed_to'] ?? null,
            'status' => 'BORRADOR',
            'updated_by' => $_SESSION['user']['id']
        ];
        try {
            if ($id > 0) {
                $this->model->update($id, $data); $newId = $id;
            } else {
                $data['created_by'] = $_SESSION['user']['id'];
                if ($this->model->isCodeDuplicate($code)) {
                    echo json_encode(['ok' => false, 'error' => 'duplicate']); exit;
                }
                $newId = $this->model->insert($data);
            }
            echo json_encode(['ok' => true, 'id' => $newId]);
        } catch (\Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'code' => substr(trim($_POST['code']), 0, 25), 'name' => $_POST['name'],
            'total_hours' => (int)($_POST['total_hours'] ?? 0), 'description' => $_POST['description'] ?? null,
            'directed_to' => $_POST['directed_to'] ?? null, 'status' => $_POST['status'] ?? 'BORRADOR',
            'updated_by' => $_SESSION['user']['id']
        ];
        if ($id > 0) {
            $this->model->update($id, $data);
            $this->syncMeta($id, $_POST['requirements'] ?? [], $_POST['conditions'] ?? []);
            $msg = 'updated=1';
        } else {
            $data['created_by'] = $_SESSION['user']['id'];
            $id = $this->model->insert($data);
            $this->syncMeta($id, $_POST['requirements'] ?? [], $_POST['conditions'] ?? []);
            $msg = 'success=1';
        }
        header("Location: /diplomatic/public/academic/diplomados?{$msg}");
        exit;
    }

    /**
     * ELIMINACIÓN SEGURA CON PROTECCIÓN CONTRA ERRORES SQL
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                // 1. Verificación lógica de dependencias (Model ya incluye Ofertas)
                if ($this->model->hasDependencies($id)) {
                    header('Location: /diplomatic/public/academic/diplomados?error=has_dependencies');
                    exit();
                }

                // 2. Intento de eliminación física
                if ($this->model->deletePhysical($id)) {
                    AuditService::log([
                        'module' => 'ACADEMIC_DIPLOMADOS',
                        'action' => 'DELETE_PHYSICAL',
                        'description' => "Eliminación física del diplomado ID: $id"
                    ]);
                    header('Location: /diplomatic/public/academic/diplomados?deleted=1');
                } else {
                    header('Location: /diplomatic/public/academic/diplomados?error=1');
                }
            } catch (\Exception $e) {
                // Si la base de datos lanza un error de restricción no capturado lógicamente, 
                // redirigimos al SweetAlert de dependencias en lugar de mostrar el Fatal Error de SQL.
                header('Location: /diplomatic/public/academic/diplomados?error=has_dependencies');
            }
        }
        exit();
    }

    public function getDetails(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $diplomado = $this->model->getById($id);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => (bool)$diplomado, 'diplomado' => $diplomado,
            'requirements' => $this->model->getRequirements($id),
            'conditions' => $this->model->getConditions($id)
        ]);
        exit();
    }

    private function syncMeta(int $id, array $reqs, array $conds): void
    {
        $this->model->saveRequirements($id, $reqs); $this->model->saveConditions($id, $conds);
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $diplomado = $this->model->getById($id);
        if (!$diplomado) { header('Location: /diplomatic/public/academic/diplomados'); exit(); }
        $this->view('academic/diplomados/edit', [
            'diplomado' => $diplomado,
            'requirements' => $this->model->getRequirements($id),
            'conditions' => $this->model->getConditions($id)
        ]);
    }
}