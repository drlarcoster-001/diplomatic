<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * ARCHIVO: app/controllers/ResourcesContratosController.php
 * PROPÓSITO: Generación, historial y gestión de contratos institucionales del personal operativo.
 * VERSIÓN: 1.2.0 - Agrega edit()/update() para reasignar personal y/o
 *          plantilla de un contrato existente (regenera número y
 *          contenido), y delete() para eliminación permanente (registro
 *          + PDF físico). Mantiene el fix de chroot de DomPDF y el
 *          encabezado institucional movido al pie de página.
 *
 * RUTAS NUEVAS Bootstrap.php:
 *   $router->get('/resources/contratos/edit',   [ResourcesContratosController::class, 'edit']);
 *   $router->post('/resources/contratos/update', [ResourcesContratosController::class, 'update']);
 *   $router->post('/resources/contratos/delete', [ResourcesContratosController::class, 'delete']);
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
        $camposData = $this->parseCamposPost($_POST);

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
     * Formulario de edición: reasignar personal y/o plantilla de un
     * contrato existente.
     */
    public function edit(): void
    {
        $id       = (int)($_GET['id'] ?? 0);
        $contrato = $this->model->getById($id);

        if (!$contrato) {
            header('Location: /diplomatic/public/resources/contratos');
            exit();
        }

        AuditService::log([
            'module'      => 'CONTRATOS',
            'action'      => 'EDIT_FORM',
            'description' => "Abrió edición del contrato: {$contrato['numero_contrato']}",
            'entity_id'   => $id
        ]);

        $this->view('resources/contratos/edit', [
            'contrato'   => $contrato,
            'plantillas' => $this->model->getPlantillas()
        ]);
    }

    /**
     * Procesa la reasignación de personal/plantilla: regenera el número
     * de contrato y el contenido final, y vuelve a generar el PDF.
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id          = (int)$_POST['id'];
        $personalId  = (int)$_POST['personal_id'];
        $templateId  = (int)$_POST['template_id'];
        $persona     = $this->model->getPersonalById($personalId);
        $plantilla   = $this->model->getPlantillaById($templateId);

        if (!$persona || !$plantilla) {
            header("Location: /diplomatic/public/resources/contratos/edit?id={$id}&error=invalid");
            exit();
        }

        $siglas         = $plantilla['tipo_siglas'] ?? 'GEN';
        $numeroContrato = $this->model->generarNumeroContrato($siglas, $persona['document_id']);

        $camposData = $this->parseCamposPost($_POST);

        $contenido = $this->model->sustituirVariablesSistema($plantilla['contenido'], $persona, $numeroContrato);
        $contenido = $this->model->sustituirCamposPersonalizados($contenido, $camposData);

        $this->model->update($id, [
            'numero_contrato' => $numeroContrato,
            'template_id'     => $templateId,
            'personal_id'     => $personalId,
            'contenido_final' => $contenido,
        ]);
        $this->model->syncFieldValues($id, $camposData);

        AuditService::log([
            'module'      => 'CONTRATOS',
            'action'      => 'UPDATE_SUCCESS',
            'description' => "Actualizó contrato #{$id} → nuevo número: {$numeroContrato}",
            'entity_id'   => $id
        ]);

        // Regenerar el PDF con los datos nuevos
        $pdfPath = $this->generarPdf($id, $contenido, $numeroContrato, $persona, $plantilla);
        if ($pdfPath) {
            $this->model->savePdfPath($id, $pdfPath);
        }

        header("Location: /diplomatic/public/resources/contratos?updated=1");
        exit();
    }

    /**
     * Elimina un contrato de forma permanente (registro + PDF físico).
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id       = (int)$_POST['id'];
        $contrato = $this->model->getById($id);

        if ($contrato) {
            $pdfPath = $this->model->delete($id);

            if ($pdfPath) {
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/' . ltrim($pdfPath, '/');
                if (is_file($fullPath)) @unlink($fullPath);
            }

            AuditService::log([
                'module'      => 'CONTRATOS',
                'action'      => 'DELETE_SUCCESS',
                'description' => "Eliminó permanentemente el contrato: {$contrato['numero_contrato']}",
                'entity_id'   => $id,
                'event_type'  => 'WARNING'
            ]);
        }

        header('Location: /diplomatic/public/resources/contratos?deleted=1');
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
     * Extrae y estructura los campos personalizados del POST (compartido
     * entre generate() y update()).
     */
    private function parseCamposPost(array $post): array
    {
        $campos    = [];
        $fieldIds  = $post['field_id']    ?? [];
        $fieldNoms = $post['field_nombre'] ?? [];
        $fieldVals = $post['field_valor']  ?? [];

        foreach ($fieldIds as $i => $fieldId) {
            $campos[] = [
                'field_id'     => (int)$fieldId,
                'nombre_campo' => $fieldNoms[$i] ?? '',
                'valor'        => $fieldVals[$i] ?? ''
            ];
        }

        return $campos;
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
            $options->setChroot([dirname(__DIR__, 2)]);

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
        $options->setChroot([dirname(__DIR__, 2)]);

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

        // DomPDF a veces colapsa a altura cero los párrafos vacíos que
        // Quill guarda como <p><br></p> (usados para espaciado antes de
        // firmas, por ejemplo). Se normalizan a un párrafo con altura
        // mínima explícita para que el espacio en blanco sí se respete.
        $contenido = preg_replace(
            '/<p([^>]*)>(\s*<br\s*\/?>\s*)?<\/p>/i',
            '<p$1 style="min-height:1em;">&nbsp;</p>',
            $contenido
        );

        return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 11pt; color: #222; padding: 20mm 25mm 25mm 25mm; line-height: 1.7; }
@page { size: letter portrait; margin: 0; }

.contenido { text-align: justify; }
.contenido h1 { font-size: 14pt; margin-bottom: 4mm; }
.contenido h2 { font-size: 12pt; margin-bottom: 3mm; }
.contenido h3 { font-size: 11pt; margin-bottom: 3mm; }
.contenido p  { margin-bottom: 3mm; }
.contenido strong { font-weight: bold; }
.contenido em { font-style: italic; }
.contenido ul, .contenido ol { margin: 2mm 0 3mm 8mm; }
.contenido li { margin-bottom: 1mm; }

/* Clases que genera el editor Quill — DomPDF no las conoce por defecto */
.ql-align-center  { text-align: center; }
.ql-align-right   { text-align: right; }
.ql-align-justify { text-align: justify; }
.ql-align-left    { text-align: left; }
.ql-indent-1 { padding-left: 3em; }
.ql-indent-2 { padding-left: 6em; }
.ql-indent-3 { padding-left: 9em; }
.ql-font-serif     { font-family: Georgia, "Times New Roman", serif; }
.ql-font-monospace { font-family: "Courier New", monospace; }
.ql-size-small { font-size: 0.75em; }
.ql-size-large { font-size: 1.5em; }
.ql-size-huge  { font-size: 2.5em; }

.footer { position: fixed; bottom: 10mm; left: 25mm; right: 25mm; border-top: 0.3mm solid #ccc; padding-top: 3mm; font-size: 8pt; color: #888; display: table; width: 100%; }
.footer-left  { display: table-cell; text-align: left; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>

<div class="contenido">
' . $contenido . '
</div>

<div class="footer">
    <span class="footer-left">DECANATO DE CIENCIAS DE LA SALUD — UCLA · Programa de Diplomados · ' . $tipo . '</span>
    <span class="footer-right">N° Contrato: ' . htmlspecialchars($numero) . ' · Fecha: ' . $fecha . '</span>
</div>

</body>
</html>';
    }
}