<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * ARCHIVO: app/controllers/PersonalController.php
 * PROPÓSITO: Administración integral del catálogo de personal operativo vinculado al programa de diplomados.
 * VERSIÓN: 1.3.0 - Carnet vertical en PDF tamaño CR80 via DomPDF.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PersonalModel;
use App\Services\AuditService;
use Dompdf\Dompdf;
use Dompdf\Options;

final class PersonalController extends Controller
{
    private PersonalModel $model;
    private int $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['user'] ?? null;

        if (!$user || !in_array($user['role'], ['ADMIN', 'OPERATOR'])) {
            header('Location: /diplomatic/public/dashboard');
            exit();
        }

        $this->model  = new PersonalModel();
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

        $fotoPath = null;
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fotoPath = $this->uploadFoto($_FILES['foto'], $id);
        }

        $this->model->updateBasic($id, $_POST, $this->userId, $fotoPath);

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
            $this->model->smartDelete($id, $this->userId);

            AuditService::log([
                'module'      => 'RESOURCES_PERSONAL',
                'action'      => 'INACTIVATE',
                'description' => "Inactivó al personal: {$persona['first_name']} {$persona['last_name']}",
                'entity_id'   => $id,
                'event_type'  => 'WARNING'
            ]);
        }

        header('Location: /diplomatic/public/resources/personal?success=inactivated');
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
     * Diseño: Header morado con foto centrada, datos debajo, footer morado.
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

        $p = $persona;

        // Foto en base64
        $avatar = '';
        if (!empty($p['foto'])) {
            $fotoPath = $_SERVER['DOCUMENT_ROOT'] . '/diplomatic/public/' . ltrim($p['foto'], '/');
            if (file_exists($fotoPath)) {
                $ext    = strtolower(pathinfo($fotoPath, PATHINFO_EXTENSION));
                $mime   = ($ext === 'png') ? 'image/png' : 'image/jpeg';
                $avatar = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fotoPath));
            }
        }
        if (empty($avatar)) {
            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($p['first_name'] . '+' . $p['last_name']) . '&background=ffffff&color=a855f7&size=200&bold=true';
        }

        // Filas de datos opcionales
        $emailRow = !empty($p['email'])
            ? '<tr><td class="lbl">Email</td><td class="val">' . htmlspecialchars($p['email']) . '</td></tr>'
            : '';
        $telRow = !empty($p['telefono_celular'])
            ? '<tr><td class="lbl">Teléfono</td><td class="val">' . htmlspecialchars($p['telefono_celular']) . '</td></tr>'
            : '';
        $desdeRow = !empty($p['fecha_inicio'])
            ? '<tr><td class="lbl">Desde</td><td class="val">' . date('d/m/Y', strtotime($p['fecha_inicio'])) . '</td></tr>'
            : '';

        // CR80 vertical: 54mm ancho x 85.6mm alto
        // En puntos a 72dpi: 153.07 x 242.64
        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; }
body { font-family: Arial, sans-serif; width:54mm; }
@page { size: 54mm 85.6mm; margin: 0; }

.header {
    background-color: #7c3aed;
    width: 54mm;
    text-align: center;
    padding: 4mm 3mm 5mm 3mm;
}

.inst-title {
    font-size: 5.5pt;
    color: white;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
    line-height: 1.4;
    margin-bottom: 2mm;
}

.inst-sub {
    font-size: 4.5pt;
    color: rgba(255,255,255,0.75);
}

.foto-wrapper {
    margin: 2mm auto 0;
    width: 18mm;
    height: 18mm;
}

.foto {
    width: 18mm;
    height: 18mm;
    border-radius: 9mm;
    border: 1mm solid rgba(255,255,255,0.9);
}

.body {
    width: 54mm;
    padding: 4mm 4mm 3mm 4mm;
    text-align: center;
    background: white;
}

.nombre {
    font-size: 8pt;
    font-weight: bold;
    color: #1a1a2e;
    text-transform: uppercase;
    margin-bottom: 1.5mm;
    line-height: 1.3;
}

.badge {
    background-color: #a855f7;
    color: white;
    font-size: 5.5pt;
    font-weight: bold;
    padding: 1mm 3mm;
    border-radius: 3mm;
    display: inline-block;
    margin-bottom: 3mm;
}

.divider {
    border-top: 0.3mm solid #ede9fe;
    margin-bottom: 2.5mm;
}

.datos {
    width: 46mm;
    text-align: left;
    margin: 0 auto;
}

.lbl {
    font-size: 4pt;
    color: #a855f7;
    font-weight: bold;
    text-transform: uppercase;
    width: 14mm;
    padding-bottom: 1.2mm;
    vertical-align: top;
}

.val {
    font-size: 5pt;
    color: #333;
    padding-bottom: 1.2mm;
    vertical-align: top;
}

.footer {
    background-color: #7c3aed;
    width: 54mm;
    height: 5mm;
    text-align: center;
    vertical-align: middle;
    padding: 1.5mm 3mm;
}

.footer-txt {
    font-size: 4pt;
    color: rgba(255,255,255,0.85);
    text-transform: uppercase;
    font-weight: bold;
    letter-spacing: 0.5pt;
}
</style>
</head>
<body>

<!-- HEADER -->
<table width="54mm" cellpadding="0" cellspacing="0">
<tr>
<td class="header">
    <div class="inst-title">Decanato de<br>Ciencias de la Salud</div>
    <div class="inst-sub">UCLA · Programa de Diplomados</div>
    <div class="foto-wrapper">
        <img src="' . $avatar . '" class="foto" alt="">
    </div>
</td>
</tr>
</table>

<!-- BODY -->
<table width="54mm" cellpadding="0" cellspacing="0">
<tr>
<td class="body">
    <div class="nombre">' . htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) . '</div>
    <span class="badge">' . htmlspecialchars($p['tipo_nombre']) . '</span>
    <div class="divider"></div>
    <table class="datos" cellpadding="0" cellspacing="0">
        <tr><td class="lbl">Cédula</td><td class="val">' . htmlspecialchars($p['document_id']) . '</td></tr>
        ' . $emailRow . $telRow . $desdeRow . '
    </table>
</td>
</tr>
</table>

<!-- FOOTER -->
<table width="54mm" cellpadding="0" cellspacing="0">
<tr>
<td class="footer">
    <div class="footer-txt">Programa de Diplomados · ' . date('Y') . '</div>
</td>
</tr>
</table>

</body>
</html>';

        require_once dirname(__DIR__, 2) . '/tools/dompdf/autoload.inc.php';
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        // 54mm x 85.6mm en puntos (1mm = 2.8346 pts)
        $dompdf->setPaper([0, 0, 153.07, 242.64]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        $dompdf->stream("Carnet_{$p['document_id']}.pdf", ['Attachment' => false]);
        exit();
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

    private function uploadFoto(array $file, int $id): ?string
    {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/diplomatic/public/uploads/personal/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) return null;

        $fileName = "personal_{$id}_" . time() . ".{$ext}";
        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            return "uploads/personal/" . $fileName;
        }

        return null;
    }
}