<?php
/**
 * MODULE: SETTINGS & CONFIGURATION
 * File: app/views/settings/mail.php
 * Propósito: Interfaz para configuración SMTP y plantillas con vista previa extendida y funcional.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$conf = [];
foreach ($settings ?? [] as $s) { 
    $conf[$s['tipo_correo']] = $s; 
}

$inscripcion = $conf['INSCRIPCION'] ?? [];
$certificados = $conf['DOCUMENTOS'] ?? [];
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/settings_mail.css?v=<?= time() ?>">

<div class="mb-4 d-flex align-items-center justify-content-between animation-fade-in">
    <div>
        <h2 class="h4 fw-bold mb-0 text-dark">Servidores de Correo</h2>
        <p class="text-muted small mb-0">Gestión técnica de salida y personalización de mensajes dinámicos.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm px-3 shadow-sm border-2" href="<?= htmlspecialchars($basePath) ?>/settings">Volver</a>
        <button type="button" class="btn btn-primary btn-sm px-4 shadow-sm fw-bold" onclick="saveActiveSettings()">
            <i class="bi bi-save me-1"></i> Guardar
        </button>
    </div>
</div>

<ul class="nav nav-pills nav-fill gap-2 mb-4 p-1 bg-light rounded-3 border" id="mailTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active py-2 fw-bold" data-bs-toggle="tab" data-bs-target="#pane-ins" type="button">Inscripción</button>
    </li>
    <li class="nav-item">
        <button class="nav-link py-2 fw-bold" data-bs-toggle="tab" data-bs-target="#pane-doc" type="button">Certificados</button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="pane-ins">
        <form id="form-ins" data-basepath="<?= htmlspecialchars($basePath) ?>">
            <input type="hidden" name="tipo_correo" value="INSCRIPCION">
            <?php renderSmtpUI($inscripcion, 'ins'); ?>
        </form>
    </div>
    <div class="tab-pane fade" id="pane-doc">
        <form id="form-doc" data-basepath="<?= htmlspecialchars($basePath) ?>">
            <input type="hidden" name="tipo_correo" value="DOCUMENTOS">
            <?php renderSmtpUI($certificados, 'doc'); ?>
        </form>
    </div>
</div>

<?php 
function renderSmtpUI($data, $prefix) { 
    $isIns = ($prefix === 'ins');
?>
<div class="row g-4 d-flex align-items-stretch">
    <div class="col-lg-5 d-flex flex-column">
        <div class="card shadow-sm border-0 rounded-4 flex-grow-1 overflow-hidden border">
            <div class="card-header bg-dark py-3 border-0 text-white fw-bold small uppercase">CONFIGURACIÓN SMTP</div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <label class="x-small fw-bold text-primary mb-1 d-block">IDENTIDAD</label>
                    <input type="text" name="from_name" class="form-control mb-2 border-2 shadow-none" placeholder="Nombre que verá el cliente" value="<?= $data['from_name'] ?? '' ?>">
                    <input type="email" name="from_email" class="form-control mb-3 border-2 shadow-none" placeholder="correo@ejemplo.com" value="<?= $data['from_email'] ?? '' ?>">
                    
                    <label class="x-small fw-bold text-primary mb-1 d-block">SERVIDOR Y PUERTO</label>
                    <input type="text" name="smtp_host" class="form-control mb-2 border-2 shadow-none" placeholder="smtp.ejemplo.com" value="<?= $data['smtp_host'] ?? '' ?>">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <input type="number" name="smtp_port" class="form-control border-2 shadow-none" value="<?= $data['smtp_port'] ?? '465' ?>">
                        </div>
                        <div class="col-6">
                            <select name="smtp_security" class="form-select border-2 shadow-none">
                                <option value="SSL" <?= ($data['smtp_security'] ?? '') === 'SSL' ? 'selected' : '' ?>>SSL</option>
                                <option value="TLS" <?= ($data['smtp_security'] ?? '') === 'TLS' ? 'selected' : '' ?>>TLS</option>
                            </select>
                        </div>
                    </div>

                    <label class="x-small fw-bold text-primary mb-1 d-block">AUTENTICACIÓN</label>
                    <input type="text" name="smtp_user" class="form-control mb-2 border-2 shadow-none" placeholder="Usuario/Email" value="<?= $data['smtp_user'] ?? '' ?>">
                    <div class="input-group">
                        <input type="password" name="smtp_password" class="form-control border-2 shadow-none" placeholder="Contraseña" value="<?= $data['smtp_password'] ?? '' ?>">
                        <button class="btn btn-outline-secondary border-2" type="button" onclick="togglePassword(this)"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <button type="button" class="btn btn-dark w-100 mt-4 fw-bold py-2 shadow-sm" onclick="executeTest('<?= $prefix ?>', 'connection')">PROBAR CONEXIÓN</button>
            </div>
        </div>
    </div>

    <div class="col-lg-7 d-flex flex-column">
        <div class="card shadow-sm border-0 rounded-4 flex-grow-1 border">
            <div class="card-header bg-primary py-3 border-0 text-white fw-bold small uppercase">CONTENIDO DEL MENSAJE</div>
            <div class="card-body p-4 d-flex flex-column">
                <label class="small fw-bold mb-1">Asunto del Correo</label>
                <input type="text" name="asunto" class="form-control mb-3 border-2 shadow-none" value="<?= $data['asunto'] ?? '' ?>">
                
                <label class="small fw-bold mb-1">Cuerpo HTML</label>
                <textarea name="contenido" class="form-control mb-3 border-2 flex-grow-1 shadow-none" style="min-height: 250px; resize: none;"><?= $data['contenido'] ?? '' ?></textarea>
                
                <div class="bg-light p-3 rounded-3 border mb-3">
                    <span class="x-small fw-bold d-block mb-2 text-muted">ETIQUETAS DINÁMICAS:</span>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-white border px-2 py-1" onclick="insertTag(this, '{nombre}')">{nombre}</button>
                        <button type="button" class="btn btn-sm btn-white border px-2 py-1" onclick="insertTag(this, '{apellido}')">{apellido}</button>
                        <?php if($isIns): ?>
                            <button type="button" class="btn btn-sm btn-warning border-warning px-2 py-1 fw-bold shadow-sm" onclick="insertTag(this, '{link_inscripcion}')">{link_inscripcion}</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-success text-white border-success px-2 py-1 fw-bold shadow-sm" onclick="insertTag(this, '{link_descarga}')">{link_descarga}</button>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary w-100 py-2 fw-bold" onclick="previewEmailPopup('<?= $prefix ?>')">VISTA PREVIA</button>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<div class="modal fade" id="modalPreviewEmail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" id="previewDialog">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-envelope-check-fill"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0">Vista Previa Institucional</h6>
                        <small class="text-muted" id="preview-subject-text">Asunto: Cargando...</small>
                    </div>
                </div>
                <div class="ms-auto">
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="toggleFullscreenPreview()" title="Maximizar">
                        <i class="bi bi-arrows-fullscreen"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body bg-light bg-opacity-50 p-0" style="background-color: #f4f6f9 !important;">
                <div class="preview-mail-container py-5 px-3">
                    <div id="mail-render-area" class="bg-white shadow-sm mx-auto p-4 p-md-5 border rounded-1" style="max-width: 800px; min-height: 600px; color: #333;">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top justify-content-end gap-2">
                <button type="button" class="btn btn-primary fw-bold" onclick="sendTestFromPreview()">Enviar Prueba</button>
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cerrar Vista</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/settings_email.js?v=<?= time() ?>"></script>