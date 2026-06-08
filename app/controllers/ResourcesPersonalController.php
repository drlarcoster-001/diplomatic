<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * ARCHIVO: app/controllers/ResourcesPersonalController.php
 * PROPÓSITO: Administración integral del catálogo de personal operativo vinculado al programa de diplomados.
 * VERSIÓN: 1.4.0 - Carnet vertical PDF + Expediente tipo CV en PDF via DomPDF.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ResourcesPersonalModel;
use App\Services\AuditService;
use Dompdf\Dompdf;
use Dompdf\Options;

final class ResourcesPersonalController extends Controller
{
    private ResourcesPersonalModel $model;
    private int $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;

        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model  = new ResourcesPersonalModel();
        $this->userId = (int)($_SESSION['user']['id'] ?? 0);
    }

    public function index(): void
    {
        AuditService::log([
            'module'      => 'RESOURCES_PERSONAL',
            'action'      => 'ACCESS_INDEX',
            'description' => 'El usuario ingresó al directorio maestro de personal.'
        ]);

        $this->view('resources/personal/index', [
            'personal' => $this->model->getAll($_GET['search'] ?? ''),
            'search'   => $_GET['search'] ?? ''
        ]);
    }

    public function create(): void
    {
        $this->view('resources/personal/create', [
            'tipos' => $this->model->getTipos()
        ]);
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        try {
            $newId = $this->model->insertBasic($_POST, $this->userId);

            AuditService::log([
                'module'      => 'RESOURCES_PERSONAL',
                'action'      => 'CREATE_SUCCESS',
                'description' => "Creó registro de personal: {$_POST['first_name']} {$_POST['last_name']}",
                'entity_id'   => $newId
            ]);

            header("Location: /diplomatic/public/resources/personal/edit?id={$newId}&created=1&tab=datos");
        } catch (\Exception $e) {
            header('Location: /diplomatic/public/resources/personal/create?error=duplicate');
        }
        exit();
    }

    public function edit(): void
    {
        $id      = (int)($_GET['id'] ?? 0);
        $persona = $this->model->getDetails($id);

        if (!$persona) {
            header('Location: /diplomatic/public/resources/personal');
            exit();
        }

        $this->view('resources/personal/edit', [
            'persona' => $persona,
            'tipos'   => $this->model->getTipos()
        ]);
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id  = (int)$_POST['id'];
        $tab = $_POST['tab'] ?? 'datos';

        // Foto
        $fotoPath = null;
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fotoPath = $this->uploadFoto($_FILES['foto'], $id);
        }

        // CV / Hoja de resumen curricular
        $cvPath = null;
        if (!empty($_FILES['cv']['name']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
            $cvPath = $this->uploadCv($_FILES['cv'], $id);
        }

        $this->model->updateBasic($id, $_POST, $this->userId, $fotoPath, $cvPath);

        AuditService::log([
            'module'      => 'RESOURCES_PERSONAL',
            'action'      => 'UPDATE_SUCCESS',
            'description' => "Actualizó expediente del personal ID: #$id",
            'entity_id'   => $id
        ]);

        header("Location: /diplomatic/public/resources/personal/edit?id={$id}&updated=1&tab={$tab}");
        exit();
    }

    public function delete(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $id      = (int)$_POST['id'];
    $persona = $this->model->getById($id);

    if ($persona) {
        $this->model->deletePhysical($id);

        AuditService::log([
            'module'      => 'RESOURCES_PERSONAL',
            'action'      => 'DELETE_PHYSICAL',
            'description' => "Eliminó físicamente al personal: {$persona['first_name']} {$persona['last_name']}",
            'entity_id'   => $id,
            'event_type'  => 'WARNING'
        ]);
    }

    header('Location: /diplomatic/public/resources/personal?success=deleted');
    exit();
}

    public function getDetails(): void
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $id      = (int)($_GET['id'] ?? 0);
        $persona = $this->model->getDetails($id);
        echo json_encode(['ok' => (bool)$persona, 'persona' => $persona]);
        exit();
    }

    /**
     * Genera el carnet institucional VERTICAL en PDF tamaño CR80 via DomPDF.
     */
    public function generarCarnet(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        $id      = (int)($_GET['id'] ?? 0);
        $persona = $this->model->getDetails($id);

        if (!$persona) {
            header('Location: /diplomatic/public/resources/personal');
            exit();
        }

        AuditService::log([
            'module'      => 'RESOURCES_PERSONAL',
            'action'      => 'GENERATE_CARNET',
            'description' => "Generó carnet de: {$persona['first_name']} {$persona['last_name']}",
            'entity_id'   => $id
        ]);

        $p      = $persona;
        $avatar = $this->fotoBase64($p['foto'] ?? '');

        $emailRow = !empty($p['email'])
            ? '<tr><td class="lbl">Email</td><td class="val">' . htmlspecialchars($p['email']) . '</td></tr>' : '';
        $telRow = !empty($p['telefono_celular'])
            ? '<tr><td class="lbl">Teléfono</td><td class="val">' . htmlspecialchars($p['telefono_celular']) . '</td></tr>' : '';
        $desdeRow = !empty($p['fecha_inicio'])
            ? '<tr><td class="lbl">Desde</td><td class="val">' . date('d/m/Y', strtotime($p['fecha_inicio'])) . '</td></tr>' : '';

        $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>
* { margin:0; padding:0; }
body { font-family: Arial, sans-serif; width:54mm; }
@page { size: 54mm 85.6mm; margin: 0; }
table { border-collapse: collapse; }
.header { background-color:#7c3aed; width:54mm; text-align:center; padding:4mm 3mm 5mm 3mm; }
.inst-title { font-size:5.5pt; color:white; font-weight:bold; text-transform:uppercase; letter-spacing:0.5pt; line-height:1.4; margin-bottom:2mm; }
.inst-sub { font-size:4.5pt; color:rgba(255,255,255,0.75); }
.foto-wrapper { margin:2mm auto 0; width:18mm; height:18mm; }
.foto { width:18mm; height:18mm; border-radius:9mm; border:1mm solid rgba(255,255,255,0.9); }
.body { width:54mm; padding:4mm 4mm 3mm 4mm; text-align:center; background:white; }
.nombre { font-size:8pt; font-weight:bold; color:#1a1a2e; text-transform:uppercase; margin-bottom:1.5mm; line-height:1.3; }
.badge { background-color:#a855f7; color:white; font-size:5.5pt; font-weight:bold; padding:1mm 3mm; border-radius:3mm; display:inline-block; margin-bottom:3mm; }
.exp { font-size:4pt; color:#888; margin-top:1mm; margin-bottom:2mm; }
.divider { border-top:0.3mm solid #ede9fe; margin-bottom:2.5mm; }
.datos { width:46mm; text-align:left; margin:0 auto; }
.lbl { font-size:4pt; color:#a855f7; font-weight:bold; text-transform:uppercase; width:14mm; padding-bottom:1.2mm; vertical-align:top; }
.val { font-size:5pt; color:#333; padding-bottom:1.2mm; vertical-align:top; }
.footer { background-color:#7c3aed; width:54mm; height:5mm; text-align:center; vertical-align:middle; padding:1.5mm 3mm; }
.footer-txt { font-size:4pt; color:rgba(255,255,255,0.85); text-transform:uppercase; font-weight:bold; letter-spacing:0.5pt; }
</style>
</head><body>
<table width="54mm" cellpadding="0" cellspacing="0"><tr>
<td class="header">
    <div class="inst-title">Decanato de<br>Ciencias de la Salud</div>
    <div class="inst-sub">UCLA · Programa de Diplomados</div>
    <div class="foto-wrapper"><img src="' . $avatar . '" class="foto" alt=""></div>
</td></tr></table>
<table width="54mm" cellpadding="0" cellspacing="0"><tr>
<td class="body">
    <div class="nombre">' . htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) . '</div>
    <span class="badge">' . htmlspecialchars($p['tipo_nombre']) . '</span>
    <div class="exp">' . htmlspecialchars($p['expediente'] ?? '') . '</div>
    <div class="divider"></div>
    <table class="datos" cellpadding="0" cellspacing="0">
        <tr><td class="lbl">Cédula</td><td class="val">' . htmlspecialchars($p['document_id']) . '</td></tr>
        ' . $emailRow . $telRow . $desdeRow . '
    </table>
</td></tr></table>
<table width="54mm" cellpadding="0" cellspacing="0"><tr>
<td class="footer"><div class="footer-txt">Programa de Diplomados · ' . date('Y') . '</div></td>
</tr></table>
</body></html>';

        $this->streamPdf($html, [0, 0, 153.07, 242.64], "Carnet_{$p['document_id']}.pdf");
    }

    /**
     * Genera el expediente tipo CV en PDF.
     */
    public function generarExpediente(): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        $id      = (int)($_GET['id'] ?? 0);
        $persona = $this->model->getDetails($id);

        if (!$persona) {
            header('Location: /diplomatic/public/resources/personal');
            exit();
        }

        AuditService::log([
            'module'      => 'RESOURCES_PERSONAL',
            'action'      => 'GENERATE_EXPEDIENTE',
            'description' => "Generó expediente de: {$persona['first_name']} {$persona['last_name']}",
            'entity_id'   => $id
        ]);

        $p      = $persona;
        $avatar = $this->fotoBase64($p['foto'] ?? '');

        // CV adjunto en base64
        $cvHtml = '';
        if (!empty($p['cv_path'])) {
            $cvFull = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/' . ltrim($p['cv_path'], '/');
            if (file_exists($cvFull)) {
                $ext = strtolower(pathinfo($cvFull, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png'])) {
                    $mime  = ($ext === 'png') ? 'image/png' : 'image/jpeg';
                    $b64   = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($cvFull));
                    $cvHtml = '<div class="section-title">HOJA DE RESUMEN CURRICULAR</div>
                               <img src="' . $b64 . '" style="width:100%; border:0.3mm solid #ddd; border-radius:2mm;">';
                }
            }
        }

        $fechaNac  = !empty($p['fecha_nacimiento']) ? date('d/m/Y', strtotime($p['fecha_nacimiento'])) : '—';
        $estadoCivil = $p['estado_civil'] ?? '—';
        $direccion   = $p['direccion']    ?? '—';
        $email       = $p['email']        ?? '—';
        $telLocal    = $p['telefono_local']   ?? '—';
        $telCel      = $p['telefono_celular'] ?? '—';
        $grado       = $p['grado_instruccion']    ?? '—';
        $estudios    = $p['estudios_adicionales'] ?? '—';
        $fechaInicio = !empty($p['fecha_inicio']) ? date('d/m/Y', strtotime($p['fecha_inicio'])) : '—';
        $fechaFin    = !empty($p['fecha_fin'])    ? date('d/m/Y', strtotime($p['fecha_fin']))    : 'Vigente';

        $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size:10pt; color:#333; margin:0; padding:0; }
@page { size: letter portrait; margin: 0; }

.header-bar {
    background-color: #7c3aed;
    padding: 8mm 10mm;
    margin: 0 0 8mm 0;
    display: table;
    width: 100%;
}

.header-left { display:table-cell; vertical-align:middle; }
.header-right { display:table-cell; vertical-align:middle; text-align:right; }

.header-foto {
    width: 25mm;
    height: 25mm;
    border-radius: 12.5mm;
    border: 1.5mm solid rgba(255,255,255,0.9);
}

.header-nombre {
    font-size: 14pt;
    font-weight: bold;
    color: white;
    text-transform: uppercase;
    margin-bottom: 2mm;
}

.header-tipo {
    background: rgba(255,255,255,0.2);
    color: white;
    font-size: 8pt;
    padding: 1mm 3mm;
    border-radius: 3mm;
    display: inline-block;
    margin-bottom: 1.5mm;
}

.header-exp {
    font-size: 7pt;
    color: rgba(255,255,255,0.75);
}

.content-wrap { padding: 0 10mm 15mm 10mm; }

.section-title {
    font-size: 8pt;
    font-weight: bold;
    color: #7c3aed;
    text-transform: uppercase;
    letter-spacing: 1pt;
    border-bottom: 0.5mm solid #ede9fe;
    padding-bottom: 2mm;
    margin-bottom: 4mm;
    margin-top: 6mm;
}

.grid { display:table; width:100%; }
.grid-row { display:table-row; }
.grid-cell { display:table-cell; width:50%; padding-bottom:3mm; vertical-align:top; }

.field-label {
    font-size: 7pt;
    color: #a855f7;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 0.5mm;
}

.field-value {
    font-size: 9pt;
    color: #333;
}

.text-block {
    font-size: 9pt;
    color: #333;
    line-height: 1.5;
    text-align: justify;
}

.footer-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: #7c3aed;
    padding: 3mm 15mm;
    font-size: 7pt;
    color: rgba(255,255,255,0.85);
    text-align: center;
}
</style>
</head><body>

<!-- HEADER -->
<div class="header-bar">
    <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td style="vertical-align:middle;">
            <div class="header-nombre">' . htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) . '</div>
            <span class="header-tipo">' . htmlspecialchars($p['tipo_nombre']) . '</span><br>
            <span class="header-exp">Expediente: ' . htmlspecialchars($p['expediente'] ?? '—') . '</span>
        </td>
        <td style="vertical-align:middle; text-align:right; width:30mm;">
            <img src="' . $avatar . '" class="header-foto" alt="">
        </td>
    </tr>
    </table>
</div>

<!-- DATOS PERSONALES -->
<div class="content-wrap">
<div class="section-title">Datos Personales</div>
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
    <td style="width:50%; padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Cédula de Identidad</div>
        <div class="field-value">' . htmlspecialchars($p['document_id']) . '</div>
    </td>
    <td style="width:50%; padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Fecha de Nacimiento</div>
        <div class="field-value">' . $fechaNac . '</div>
    </td>
</tr>
<tr>
    <td style="padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Estado Civil</div>
        <div class="field-value">' . htmlspecialchars($estadoCivil) . '</div>
    </td>
    <td style="padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Email</div>
        <div class="field-value">' . htmlspecialchars($email) . '</div>
    </td>
</tr>
<tr>
    <td style="padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Teléfono Local</div>
        <div class="field-value">' . htmlspecialchars($telLocal) . '</div>
    </td>
    <td style="padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Teléfono Celular</div>
        <div class="field-value">' . htmlspecialchars($telCel) . '</div>
    </td>
</tr>
<tr>
    <td colspan="2" style="padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Dirección</div>
        <div class="field-value">' . htmlspecialchars($direccion) . '</div>
    </td>
</tr>
</table>

<!-- DATOS ACADÉMICOS -->
<div class="section-title">Datos Académicos</div>
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
    <td style="padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Grado de Instrucción</div>
        <div class="field-value">' . htmlspecialchars($grado) . '</div>
    </td>
</tr>
<tr>
    <td style="padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Estudios Adicionales</div>
        <div class="text-block">' . nl2br(htmlspecialchars($estudios)) . '</div>
    </td>
</tr>
</table>

<!-- FUNCIÓN -->
<div class="section-title">Función</div>
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
    <td style="width:50%; padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Tipo de Personal</div>
        <div class="field-value">' . htmlspecialchars($p['tipo_nombre']) . '</div>
    </td>
    <td style="width:50%; padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Código de Expediente</div>
        <div class="field-value">' . htmlspecialchars($p['expediente'] ?? '—') . '</div>
    </td>
</tr>
<tr>
    <td style="padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Fecha de Inicio</div>
        <div class="field-value">' . $fechaInicio . '</div>
    </td>
    <td style="padding-bottom:3mm; vertical-align:top;">
        <div class="field-label">Fecha de Fin</div>
        <div class="field-value">' . $fechaFin . '</div>
    </td>
</tr>
</table>

' . $cvHtml . '

</div>

<!-- FOOTER -->
<div class="footer-bar">
    Decanato de Ciencias de la Salud · UCLA · Programa de Diplomados · Generado: ' . date('d/m/Y H:i') . '
</div>

</body></html>';

        $this->streamPdf($html, 'letter', "Expediente_{$p['expediente']}.pdf");
    }

    public function logAccess(): void
    {
        $action = $_GET['action'] ?? 'VIEW';
        $id     = (int)($_GET['id'] ?? 0);

        AuditService::log([
            'module'      => 'RESOURCES_PERSONAL',
            'action'      => $action,
            'entity_id'   => $id,
            'description' => "Interacción de usuario: $action en ID: $id"
        ]);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit();
    }

    /**
     * Convierte foto a base64 para embeber en PDF.
     */
    private function fotoBase64(string $fotoPath): string
    {
        if (!empty($fotoPath)) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/' . ltrim($fotoPath, '/');
            if (file_exists($fullPath)) {
                $ext  = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
            }
        }
        // Avatar generado si no hay foto
        return 'https://ui-avatars.com/api/?name=P&background=a855f7&color=fff&size=200&bold=true';
    }

    /**
     * Stream del PDF via DomPDF.
     */
    private function streamPdf(string $html, $paper, string $filename): void
    {
        require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper($paper);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => false]);
        exit();
    }

    /**
     * Subida de foto de perfil.
     */
    private function uploadFoto(array $file, int $id): ?string
    {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/diplomatic/public/uploads/personal/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) return null;

        $fileName = "personal_{$id}_" . time() . ".{$ext}";
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            return "uploads/personal/" . $fileName;
        }
        return null;
    }

    /**
     * Subida de hoja de resumen curricular.
     */
    private function uploadCv(array $file, int $id): ?string
    {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/diplomatic/public/uploads/personal/cv/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($ext, $allowed)) return null;

        $fileName = "cv_{$id}_" . time() . ".{$ext}";
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            return "uploads/personal/cv/" . $fileName;
        }
        return null;
    }
}