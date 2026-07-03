<?php
/**
 * MÓDULO: CONFIGURACIÓN / RESPALDO DEL SISTEMA
 * ARCHIVO: app/views/settings/database/index.php
 * PROPÓSITO: Vista de respaldo con un solo botón que dispara la secuencia
 *            automática de descargas (Sistema, Público, Uploads, Raíz,
 *            Enrollments y SQL troceados) vía JS. Las tarjetas de
 *            componentes son informativas, no disparan descargas
 *            individuales.
 * VERSIÓN: 11.0.0 - Botón único con secuencia automática y troceo dinámico.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/settings_backup.css">

<div class="container-fluid py-4" style="max-width:960px">

    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb" style="font-size:0.82rem">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i>Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/settings" class="text-decoration-none text-muted">Configuración</a></li>
            <li class="breadcrumb-item active fw-bold text-primary">Respaldo del Sistema</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-5">
        <div class="d-flex align-items-center gap-3">
            <div class="backup-icon-wrap" style="background:linear-gradient(135deg,#533AB7,#7B5EE8)">
                <i class="bi bi-shield-lock-fill text-white fs-5"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-bold">Respaldo del Sistema</h4>
                <p class="text-muted small mb-0">Un solo clic genera y descarga el respaldo completo, en el orden correcto.</p>
            </div>
        </div>
        <a href="<?= $basePath ?>/settings" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger rounded-3 mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <div class="section-title">Componentes del respaldo</div>

    <div class="row g-3 mb-4">

        <div class="col-md-6">
            <div class="backup-card bg-white p-4 d-flex gap-3 align-items-start">
                <div class="backup-icon-wrap flex-shrink-0" style="background:#f0fdf4">
                    <i class="bi bi-database-fill" style="color:#198754;font-size:1.2rem"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark mb-1">Base de Datos SQL</div>
                    <div class="text-muted small">Dump completo de <code><?= htmlspecialchars($dbName) ?></code>, troceado automáticamente por tamaño.</div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="backup-card bg-white p-4 d-flex gap-3 align-items-start">
                <div class="backup-icon-wrap flex-shrink-0" style="background:#f4f3ff">
                    <i class="bi bi-code-square" style="color:#533AB7;font-size:1.2rem"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark mb-1">Sistema (Código Fuente)</div>
                    <div class="text-muted small mb-2">app/ + tools/</div>
                    <div class="folder-size-badge"><?= $sizeSistema ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="backup-card bg-white p-4 d-flex gap-3 align-items-start">
                <div class="backup-icon-wrap flex-shrink-0" style="background:#eff8ff">
                    <i class="bi bi-folder2-open" style="color:#0d6efd;font-size:1.2rem"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark mb-1">Público</div>
                    <div class="text-muted small mb-2">public/ (assets, logos, recursos web — sin uploads)</div>
                    <div class="folder-size-badge"><?= $sizePublico ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="backup-card bg-white p-4 d-flex gap-3 align-items-start">
                <div class="backup-icon-wrap flex-shrink-0" style="background:#fff7ed">
                    <i class="bi bi-file-earmark-text-fill" style="color:#f59e0b;font-size:1.2rem"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark mb-1">Documentos Generales</div>
                    <div class="text-muted small mb-2">constancias, contratos, correspondencia, personal, tesorería</div>
                    <div class="folder-size-badge"><?= $sizeUploads ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="backup-card bg-white p-4 d-flex gap-3 align-items-start">
                <div class="backup-icon-wrap flex-shrink-0" style="background:#fef2f2">
                    <i class="bi bi-people-fill" style="color:#dc2626;font-size:1.2rem"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark mb-1">Enrollments</div>
                    <div class="text-muted small mb-2">Documentos de inscripción, troceado en <?= $totalPartesEnrollments ?> parte(s) de ~80MB.</div>
                    <div class="folder-size-badge"><?= $sizeEnrollments ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="backup-card bg-white p-4 d-flex gap-3 align-items-start">
                <div class="backup-icon-wrap flex-shrink-0" style="background:#f4f4f8">
                    <i class="bi bi-file-earmark-fill" style="color:#533AB7;font-size:1.2rem"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark mb-1">Raíz del Proyecto</div>
                    <div class="text-muted small">README.md, estructura.txt, modulos parte 2.txt</div>
                </div>
            </div>
        </div>

    </div>

    <!-- BOTÓN ÚNICO -->
    <div class="backup-card bg-white p-4 d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="fw-bold text-dark mb-1">Generar Respaldo Completo</div>
            <div class="text-muted small">Descarga automática de todos los componentes en el orden correcto, incluyendo instrucciones de restauración.</div>
        </div>
        <button type="button" id="btnRespaldoCompleto" class="btn-download-main">
            <i class="bi bi-cloud-arrow-down-fill me-2"></i> Generar Respaldo Completo
        </button>
    </div>

    <!-- AVISO -->
    <div class="alert rounded-3 d-flex gap-3" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" style="color:#d97706"></i>
        <div class="small">
            <strong>No cierres esta pestaña mientras se genera el respaldo.</strong>
            El proceso descarga varios archivos en secuencia; el navegador puede pedir
            permiso para descargas múltiples la primera vez.
        </div>
    </div>

</div>

<script>
    window.DIPLOMATIC_BASE_PATH = <?= json_encode($basePath) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/settings_backup.js?v=<?= time() ?>"></script>