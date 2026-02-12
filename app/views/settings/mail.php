<?php
/**
 * MÓDULO: CONFIGURACIÓN
 * Archivo: app/views/settings/mail.php
 * Propósito: Configuración detallada de correo con selector de protocolo y navegación.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$conf = [];
foreach ($settings ?? [] as $s) { $conf[$s['tipo_correo']] = $s; }
$ins = $conf['INSCRIPCION'] ?? [];
$doc = $conf['DOCUMENTOS'] ?? [];
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/settings.css?v=<?= time() ?>">

<div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="h4 fw-bold mb-0 text-dark">Servidores de Correo</h2>
        <p class="text-muted small mb-0">Configuración para Inscripciones y Certificados.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm px-3" href="<?= htmlspecialchars($basePath) ?>/settings">
            <i class="bi bi-arrow-left me-1"></i> Volver al Menú
        </a>
        <button type="button" class="btn btn-primary btn-sm px-4 shadow-sm" onclick="saveActiveSettings()">
            <i class="bi bi-save2 me-1"></i> Guardar Todo
        </button>
    </div>
</div>

<ul class="nav nav-pills nav-fill gap-3 mb-4 p-2 bg-light rounded shadow-sm" id="mailTabs">
    <li class="nav-item">
        <button class="nav-link active py-3 fw-bold fs-5 shadow-sm" data-bs-toggle="tab" data-bs-target="#pane-ins" type="button">
            <i class="bi bi-person-plus-fill me-2"></i> Inscripción
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link py-3 fw-bold fs-5 shadow-sm" data-bs-toggle="tab" data-bs-target="#pane-doc" type="button">
            <i class="bi bi-file-earmark-pdf-fill me-2"></i> Certificados
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="pane-ins" role="tabpanel">
        <form id="form-ins" data-basepath="<?= htmlspecialchars($basePath) ?>">
            <input type="hidden" name="tipo_correo" value="INSCRIPCION">
            <?php renderMailForm($ins, 'ins'); ?>
        </form>
    </div>
    <div class="tab-pane fade" id="pane-doc" role="tabpanel">
        <form id="form-doc" data-basepath="<?= htmlspecialchars($basePath) ?>">
            <input type="hidden" name="tipo_correo" value="DOCUMENTOS">
            <?php renderMailForm($doc, 'doc'); ?>
        </form>
    </div>
</div>

<?php function renderMailForm($data, $prefix) { ?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm p-4 border-0 rounded-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Proveedor SMTP</h5>
            <div class="row g-2 mb-4">
                <?php 
                $provs = [['id'=>'GMAIL','n'=>'Gmail','i'=>'bi-google'],['id'=>'OUTLOOK','n'=>'Outlook','i'=>'bi-microsoft'],['id'=>'YAHOO','n'=>'Yahoo','i'=>'bi-envelope'],['id'=>'CUSTOM','n'=>'Personalizada','i'=>'bi-gear']];
                foreach($provs as $p): ?>
                <div class="col-6">
                    <label class="card h-100 p-2 border shadow-sm provider-label" style="cursor:pointer">
                        <div class="d-flex flex-column align-items-center text-center gap-1">
                            <i class="bi <?= $p['i'] ?> fs-3 text-primary"></i>
                            <span class="small fw-bold"><?= $p['n'] ?></span>
                            <input type="radio" name="<?= $prefix ?>_provider" value="<?= $p['id'] ?>" class="form-check-input mt-1">
                        </div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="protocol-group mb-3 d-none">
                <label class="small fw-bold mb-1">Protocolo de Servidor</label>
                <select name="protocolo" class="form-select form-select-sm bg-light">
                    <option value="SMTP" selected>SMTP (Recomendado)</option>
                    <option value="POP3">POP3</option>
                </select>
            </div>

            <div class="row g-2">
                <div class="col-12"><label class="small fw-bold">From Email</label><input type="email" name="from_email" class="form-control" value="<?= $data['from_email'] ?? '' ?>"></div>
                <div class="col-12"><label class="small fw-bold">From Name</label><input type="text" name="from_name" class="form-control" value="<?= $data['from_name'] ?? 'Diplomatic' ?>"></div>
                <div class="col-12"><label class="small fw-bold">SMTP Host</label><input type="text" name="smtp_host" class="form-control" value="<?= $data['smtp_host'] ?? '' ?>"></div>
                <div class="col-6"><label class="small fw-bold">Puerto</label><input type="number" name="smtp_port" class="form-control" value="<?= $data['smtp_port'] ?? '465' ?>"></div>
                <div class="col-6"><label class="small fw-bold">Seguridad</label>
                    <select name="smtp_security" class="form-select">
                        <option value="SSL" <?= ($data['smtp_security'] ?? '') == 'SSL' ? 'selected' : '' ?>>SSL</option>
                        <option value="TLS" <?= ($data['smtp_security'] ?? '') == 'TLS' ? 'selected' : '' ?>>TLS</option>
                    </select>
                </div>
                <div class="col-12"><label class="small fw-bold">Usuario SMTP</label><input type="text" name="smtp_user" class="form-control" value="<?= $data['smtp_user'] ?? '' ?>"></div>
                <div class="col-12"><label class="small fw-bold">Password</label><input type="password" name="smtp_password" class="form-control" value="<?= $data['smtp_password'] ?? '' ?>"></div>
            </div>
            <button type="button" class="btn btn-outline-primary w-100 mt-4 py-2" onclick="testActiveSettings('f-<?= $prefix ?>')">
                <i class="bi bi-send-fill me-2"></i> Enviar correo de prueba
            </button>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm p-4 border-0 rounded-4 h-100">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Personalización de Plantilla</h5>
            <label class="small fw-bold">Asunto del Correo</label>
            <input type="text" name="asunto" class="form-control mb-3" value="<?= $data['asunto'] ?? '' ?>">
            <label class="small fw-bold">Contenido (HTML)</label>
            <textarea name="contenido" class="form-control" rows="18"><?= $data['contenido'] ?? '' ?></textarea>
        </div>
    </div>
</div>
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/settings.js?v=<?= time() ?>"></script>