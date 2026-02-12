<?php
/**
 * MÓDULO - app/views/settings/mail.php
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$conf = [];
foreach ($settings ?? [] as $s) { $conf[$s['tipo_correo']] = $s; }
$ins = $conf['INSCRIPCION'] ?? [];
$doc = $conf['DOCUMENTOS'] ?? [];
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/settings.css?v=<?= time() ?>">

<div class="mb-4 d-flex align-items-center justify-content-between">
    <div>
        <h2 class="h4 fw-bold mb-0">Servidores de Correo</h2>
        <p class="text-muted small mb-0">Configura SMTP y plantillas dinámicas.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm px-3" href="<?= htmlspecialchars($basePath) ?>/settings">Volver</a>
        <button type="button" class="btn btn-primary btn-sm px-4 shadow-sm" onclick="saveActiveSettings()">Guardar Todo</button>
    </div>
</div>

<ul class="nav nav-pills nav-fill gap-2 mb-4 p-1 bg-light rounded shadow-sm">
    <li class="nav-item"><button class="nav-link active py-2 fw-bold" data-bs-toggle="tab" data-bs-target="#pane-ins" type="button">Inscripción</button></li>
    <li class="nav-item"><button class="nav-link py-2 fw-bold" data-bs-toggle="tab" data-bs-target="#pane-doc" type="button">Certificados</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="pane-ins" role="tabpanel">
        <form id="form-ins" data-basepath="<?= htmlspecialchars($basePath) ?>">
            <input type="hidden" name="tipo_correo" value="INSCRIPCION">
            <?php renderF($ins, 'ins'); ?>
        </form>
    </div>
    <div class="tab-pane fade" id="pane-doc" role="tabpanel">
        <form id="form-doc" data-basepath="<?= htmlspecialchars($basePath) ?>">
            <input type="hidden" name="tipo_correo" value="DOCUMENTOS">
            <?php renderF($doc, 'doc'); ?>
        </form>
    </div>
</div>

<?php function renderF($d, $p) { ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm p-4 border-0 rounded-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Configuración SMTP</h5>
            <div class="row g-2 mb-3">
                <?php $vs = [['GMAIL','bi-google'],['OUTLOOK','bi-microsoft'],['YAHOO','bi-envelope'],['CUSTOM','bi-gear']]; 
                foreach($vs as $v): ?>
                <div class="col-6">
                    <label class="card h-100 p-2 border shadow-sm text-center" style="cursor:pointer">
                        <i class="bi <?= $v[1] ?> fs-3 text-primary"></i><br>
                        <span class="small fw-bold"><?= $v[0] ?></span>
                        <input type="radio" name="<?= $p ?>_provider" value="<?= $v[0] ?>" class="form-check-input mt-1">
                    </label>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="protocol-group mb-3 d-none">
                <label class="small fw-bold mb-1 text-primary">Protocolo</label>
                <select name="protocolo" class="form-select form-select-sm bg-light">
                    <option value="SMTP" selected>SMTP</option>
                    <option value="POP3">POP3</option>
                </select>
            </div>

            <div class="row g-2">
                <div class="col-12"><label class="small fw-bold">From Email</label><input type="email" name="from_email" class="form-control" value="<?= $d['from_email'] ?? '' ?>"></div>
                <div class="col-12"><label class="small fw-bold">From Name</label><input type="text" name="from_name" class="form-control" value="<?= $d['from_name'] ?? '' ?>"></div>
                <div class="col-12"><label class="small fw-bold">SMTP Host</label><input type="text" name="smtp_host" class="form-control" value="<?= $d['smtp_host'] ?? '' ?>"></div>
                <div class="col-6"><label class="small fw-bold">Puerto</label><input type="number" name="smtp_port" class="form-control" value="<?= $d['smtp_port'] ?? '465' ?>"></div>
                <div class="col-6"><label class="small fw-bold">Seguridad</label>
                    <select name="smtp_security" class="form-select">
                        <option value="SSL" <?= ($d['smtp_security']??'')=='SSL'?'selected':''?>>SSL</option>
                        <option value="TLS" <?= ($d['smtp_security']??'')=='TLS'?'selected':''?>>TLS</option>
                    </select>
                </div>
                <div class="col-12"><label class="small fw-bold">Usuario</label><input type="text" name="smtp_user" class="form-control" value="<?= $d['smtp_user'] ?? '' ?>"></div>
                <div class="col-12"><label class="small fw-bold">Password</label><input type="password" name="smtp_password" class="form-control" value="<?= $d['smtp_password'] ?? '' ?>"></div>
            </div>
            <button type="button" class="btn btn-outline-primary w-100 mt-4 py-2" onclick="testActiveSettings('form-<?= $p ?>', 'connection')">
                <i class="bi bi-send-check me-1"></i> Probar Conexión
            </button>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm p-4 border-0 rounded-4 h-100">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Personalización</h5>
            <div class="alert alert-info py-2 small mb-3">
                <strong>Etiquetas:</strong> {nombre}, {apellido}, {plataforma}, {link_activacion}, {nombre_diplomado}, {link_descarga}.
            </div>
            <label class="small fw-bold">Asunto</label>
            <input type="text" name="asunto" class="form-control mb-3" value="<?= $d['asunto'] ?? '' ?>">
            <label class="small fw-bold">Cuerpo (HTML)</label>
            <textarea name="contenido" class="form-control mb-3" rows="18"><?= $d['contenido'] ?? '' ?></textarea>
            <button type="button" class="btn btn-outline-success w-100 py-2 mt-auto" onclick="testActiveSettings('form-<?= $p ?>', 'template')">
                <i class="bi bi-envelope-paper-fill me-1"></i> Probar esta plantilla
            </button>
        </div>
    </div>
</div>
<?php } ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/settings.js?v=<?= time() ?>"></script>