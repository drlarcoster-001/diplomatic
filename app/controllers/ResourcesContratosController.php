<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * ARCHIVO: app/controllers/ResourcesContratosController.php
 * PROPÓSITO: Generación, historial y gestión de contratos institucionales del personal operativo.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ResourcesContratosModel;
use App\Services\AuditService;
use Dompdf\Dompdf;
use Dompdf\Options;

final class ResourcesContratosController extends Controller
{
    private ResourcesContratosModel $model;
    private int $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;

        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model  = new ResourcesContratosModel();
        $this->userId = (int)($_SESSION['user']['id'] ?? 0);
    }

    /**
     * Grid principal de contratos generados.
     */
    public function index(): void
    {
        AuditService::log([
            'module'      => 'CONTRATOS',
            'action'      => 'ACCESS_INDEX',
            'description' => 'El usuario ingresó al historial de contratos.'
        ]);

        $search = $_GET['search'] ?? '';
        $this->view('resources/contratos/index', [
            'contratos' => $this->model->getAll($search),
            'search'    => $search
        ]);
    }

    /**
     * Formulario de generación de contrato.
     */
    public function create(): void
    {
        $this->view('resources/contratos/create', [
            'plantillas' => $this->model->getPlantillas()
        ]);
    }

    /**
     * Busca personal para el selector AJAX.
     */
    public function buscarPersonal(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $term    = $_GET['term'] ?? '';
        $results = $this->model->buscarPersonal($term);
        echo json_encode(['ok' => true, 'data' => $results]);
        exit();
    }

    /**
     * Obtiene la plantilla con sus campos para el formulario AJAX.
     */
    public function getPlantilla(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id       = (int)($_GET['id'] ?? 0);
        $plantilla = $this->model->getPlantillaById($id);
        echo json_encode(['ok' => (bool)$plantilla, 'plantilla' => $plantilla]);
        exit();
    }

    /**
     * Obtiene datos del personal para la vista previa AJAX.
     */
    public function getPersonal(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id      = (int)($_GET['id'] ?? 0);
        $persona = $this->model->getPersonalById($id);
        echo json_encode(['ok' => (bool)$persona, 'persona' => $persona]);
        exit();
    }

    /**
     * Genera el contrato, lo guarda en BD y genera el PDF.
     */
    public function generate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $personalId  = (int)$_POST['personal_id'];
        $templateId  = (int)$_POST['template_id'];
        $persona     = $this->model->getPersonalById($personalId);
        $plantilla   = $this->model->getPlantillaById($templateId);

        if (!$persona || !$plantilla) {
            header('Location: /diplomatic/public/resources/contratos/create?error=invalid');
            exit();
        }

        // Generar número de contrato
        $siglas          = $plantilla['tipo_siglas'] ?? 'GEN';
        $numeroContrato  = $this->model->generarNumeroContrato($siglas, $persona['document_id']);

        // Construir campos personalizados con sus valores
        $camposData = [];
        $fieldIds   = $_POST['field_id']    ?? [];
        $fieldVals  = $_POST['field_valor'] ?? [];

        foreach ($fieldIds as $i => $fieldId) {
            $camposData[] = [
                'field_id'    => (int)$fieldId,
                'nombre_campo' => $_POST['field_nombre'][$i] ?? '',
                'valor'        => $fieldVals[$i] ?? ''
            ];
        }

        // Sustituir variables del sistema
        $contenido = $this->model->sustituirVariablesSistema(
            $plantilla['contenido'],
            $persona,
            $numeroContrato
        );

        // Sustituir campos personalizados
        $contenido = $this->model->sustituirCamposPersonalizados($contenido, $camposData);

        // Guardar contrato en BD
        $contratoId = $this->model->insert([
            'numero_contrato' => $numeroContrato,
            'template_id'     => $templateId,
            'personal_id'     => $personalId,
            'contenido_final' => $contenido,
            'campos'          => $camposData
        ], $this->userId);

        AuditService::log([
            'module'      => 'CONTRATOS',
            'action'      => 'GENERATE_SUCCESS',
            'description' => "Generó contrato {$numeroContrato} para: {$persona['first_name']} {$persona['last_name']}",
            'entity_id'   => $contratoId
        ]);

        // Generar PDF y guardar
        $pdfPath = $this->generarPdf($contratoId, $contenido, $numeroContrato, $persona, $plantilla);
        if ($pdfPath) {
            $this->model->savePdfPath($contratoId, $pdfPath);
        }

        header("Location: /diplomatic/public/resources/contratos?created=1");
        exit();
    }

    /**
     * Descarga el PDF de un contrato existente.
     */
    public function descargarPdf(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        $id       = (int)($_GET['id'] ?? 0);
        $contrato = $this->model->getById($id);

        if (!$contrato) {
            header('Location: /diplomatic/public/resources/contratos');
            exit();
        }

        AuditService::log([
            'module'      => 'CONTRATOS',
            'action'      => 'DOWNLOAD_PDF',
            'description' => "Descargó PDF del contrato: {$contrato['numero_contrato']}",
            'entity_id'   => $id
        ]);

        $this->streamPdf($contrato['contenido_final'], $contrato['numero_contrato'], $contrato);
    }

    /**
     * Cambia el estado de un contrato.
     */
    public function changeStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id     = (int)$_POST['id'];
        $estado = $_POST['estado'] ?? '';

        $estadosValidos = ['Borrador', 'Activo', 'Finalizado', 'Rescindido'];
        if (!in_array($estado, $estadosValidos)) {
            header('Location: /diplomatic/public/resources/contratos?error=invalid_status');
            exit();
        }

        $this->model->changeStatus($id, $estado);

        AuditService::log([
            'module'      => 'CONTRATOS',
            'action'      => 'STATUS_CHANGE',
            'description' => "Cambió estado del contrato ID: #$id a: $estado",
            'entity_id'   => $id
        ]);

        header('Location: /diplomatic/public/resources/contratos?updated=1');
        exit();
    }

    /**
     * Retorna detalles del contrato en JSON.
     */
    public function getDetails(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id       = (int)($_GET['id'] ?? 0);
        $contrato = $this->model->getById($id);
        echo json_encode(['ok' => (bool)$contrato, 'contrato' => $contrato]);
        exit();
    }

    /**
     * Genera y guarda el PDF del contrato en el servidor.
     */
    private function generarPdf(int $contratoId, string $contenido, string $numero, array $persona, array $plantilla): ?string
    {
        try {
            require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';

            $html = $this->buildPdfHtml($contenido, $numero, $persona, $plantilla);

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');

            $dompdf = new Dompdf($options);
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();

            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/diplomatic/public/uploads/contratos/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $fileName = "contrato_{$numero}.pdf";
            file_put_contents($uploadDir . $fileName, $dompdf->output());

            return "uploads/contratos/{$fileName}";
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Hace stream del PDF directamente al navegador.
     */
    private function streamPdf(string $contenido, string $numero, array $contrato): void
    {
        require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';

        $persona  = $this->model->getPersonalById($contrato['personal_id']);
        $plantilla = $this->model->getPlantillaById($contrato['template_id']);

        $html = $this->buildPdfHtml($contenido, $numero, $persona ?? [], $plantilla ?? []);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        $dompdf->stream("Contrato_{$numero}.pdf", ['Attachment' => false]);
        exit();
    }

    /**
     * Construye el HTML completo del PDF del contrato.
     */
    private function buildPdfHtml(string $contenido, string $numero, array $persona, array $plantilla): string
    {
        $nombre   = htmlspecialchars(($persona['first_name'] ?? '') . ' ' . ($persona['last_name'] ?? ''));
        $cedula   = htmlspecialchars($persona['document_id'] ?? '');
        $fecha    = date('d/m/Y');
        $tipo     = htmlspecialchars($plantilla['tipo_nombre'] ?? '');

        return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 11pt; color: #222; padding: 20mm 25mm 25mm 25mm; line-height: 1.7; }
@page { size: letter portrait; margin: 0; }

.header { text-align: center; margin-bottom: 10mm; border-bottom: 0.5mm solid #333; padding-bottom: 5mm; }
.header h1 { font-size: 13pt; font-weight: bold; text-transform: uppercase; color: #1a1a2e; }
.header p { font-size: 9pt; color: #555; margin-top: 2mm; }

.numero { text-align: right; font-size: 9pt; color: #777; margin-bottom: 8mm; }

.contenido { text-align: justify; }
.contenido h1 { font-size: 14pt; margin-bottom: 4mm; }
.contenido h2 { font-size: 12pt; margin-bottom: 3mm; }
.contenido h3 { font-size: 11pt; margin-bottom: 3mm; }
.contenido p  { margin-bottom: 3mm; }
.contenido strong { font-weight: bold; }
.contenido em { font-style: italic; }
.contenido ul, .contenido ol { margin: 2mm 0 3mm 8mm; }
.contenido li { margin-bottom: 1mm; }

.footer { position: fixed; bottom: 10mm; left: 25mm; right: 25mm; border-top: 0.3mm solid #ccc; padding-top: 3mm; font-size: 8pt; color: #888; display: table; width: 100%; }
.footer-left  { display: table-cell; text-align: left; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>

<div class="header">
    <h1>Decanato de Ciencias de la Salud — UCLA</h1>
    <p>Programa de Diplomados · ' . $tipo . '</p>
</div>

<div class="numero">N° Contrato: <strong>' . htmlspecialchars($numero) . '</strong> · Fecha: ' . $fecha . '</div>

<div class="contenido">
' . $contenido . '
</div>

<div class="footer">
    <span class="footer-left">' . $nombre . ' · CI: ' . $cedula . '</span>
    <span class="footer-right">Contrato N° ' . htmlspecialchars($numero) . '</span>
</div>

</body>
</html>';
    }
}