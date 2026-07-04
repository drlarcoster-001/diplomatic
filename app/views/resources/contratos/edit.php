<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * Archivo: app/views/resources/contratos/edit.php
 * Propósito: Editar un contrato existente — reasignar personal y/o
 *            plantilla, con vista previa en tiempo real. Regenera el
 *            número de contrato y el PDF al guardar.
 * Versión: 1.0.0
 *
 * @var array $contrato
 * @var array $plantillas
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_contratos.css">

<div class="container-fluid py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources" class="text-decoration-none text-muted">Panel de Recursos</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources/contratos" class="text-decoration-none text-muted">Contratos</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#198754;">Editar Contrato</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Editar Contrato <span class="text-muted small">· <?= htmlspecialchars($contrato['numero_contrato']) ?></span></h2>
            <p class="text-muted small">Corrige el personal asignado y/o la plantilla. El número de contrato se regenerará automáticamente.</p>
        </div>
        <a href="<?= $basePath ?>/resources/contratos" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="alert rounded-3 d-flex gap-3" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" style="color:#d97706"></i>
        <div class="small">
            Al guardar se regenera el <strong>número de contrato</strong> y el <strong>PDF</strong> con los datos nuevos.
            El PDF anterior será reemplazado.
        </div>
    </div>

    <form id="formContrato" action="<?= $basePath ?>/resources/contratos/update" method="POST">
        <input type="hidden" name="id" value="<?= (int)$contrato['id'] ?>">

        <!-- PASO 1: Selección -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3 px-4 border-bottom">
                <i class="bi bi-1-circle me-2 text-success"></i> Personal y Plantilla
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Buscador de personal -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">PERSONAL</label>
                        <div class="position-relative">
                            <input type="text" id="buscar-personal" class="form-control"
                                   placeholder="Buscar por nombre o cédula..." autocomplete="off"
                                   value="<?= htmlspecialchars($contrato['first_name'] . ' ' . $contrato['last_name']) ?>">
                            <div id="personal-dropdown"
                                 class="dropdown-menu w-100 shadow-sm"
                                 style="display:none; max-height:250px; overflow-y:auto; position:absolute; z-index:1050;"></div>
                        </div>
                        <input type="hidden" name="personal_id" id="personal_id" value="<?= (int)$contrato['personal_id'] ?>">
                        <div id="personal-ficha" class="mt-3">
                            <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#f0fdf4; border:1px solid #bbf7d0;">
                                <div>
                                    <div class="fw-bold text-dark" id="ficha-nombre"><?= htmlspecialchars($contrato['first_name'] . ' ' . $contrato['last_name']) ?> · CI: <?= htmlspecialchars($contrato['document_id']) ?></div>
                                    <div class="small text-muted" id="ficha-tipo"><?= htmlspecialchars($contrato['tipo_personal_nombre'] ?? '') ?></div>
                                    <div class="small" style="color:#198754;" id="ficha-expediente"><?= htmlspecialchars($contrato['expediente'] ?? '') ?></div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill ms-auto" id="btn-limpiar-personal">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Selector de plantilla -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">PLANTILLA DE CONTRATO</label>
                        <select id="select-plantilla" name="template_id" class="form-select">
                            <option value="" disabled>Seleccione una plantilla...</option>
                            <?php foreach ($plantillas as $pl): ?>
                                <option value="<?= $pl['id'] ?>" <?= (int)$pl['id'] === (int)$contrato['template_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pl['tipo_siglas'] ?? '') ?> · <?= htmlspecialchars($pl['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- PASO 2: Campos personalizados -->
        <div id="seccion-campos" style="display:none;">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3 px-4 border-bottom">
                    <i class="bi bi-2-circle me-2 text-success"></i> Complete los Campos Personalizados
                </div>
                <div class="card-body p-4">
                    <div id="campos-dinamicos" class="row g-3"></div>
                </div>
            </div>
        </div>

        <!-- PASO 3: Vista previa -->
        <div id="seccion-preview">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-3-circle me-2 text-success"></i> Vista Previa del Contrato</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btn-actualizar-preview">
                        <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                    </button>
                </div>
                <div class="card-body p-0">
                    <div style="background:#e9ecef; padding:24px;">
                        <div id="preview-contenido" class="ql-editor"
                             style="background:white; max-width:800px; margin:0 auto; padding:50px 60px; box-shadow:0 4px 24px rgba(0,0,0,0.10); min-height:400px; font-family:'Segoe UI', serif; font-size:13px; line-height:1.8; color:#222;">
                            <?= $contrato['contenido_final'] ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end mb-4">
                <button type="submit" class="btn rounded-pill px-5 py-2 text-white shadow" style="background:#198754; font-size:1rem;">
                    <i class="bi bi-check-circle me-2"></i> Guardar Cambios
                </button>
            </div>
        </div>

    </form>
</div>

<script>
    // Datos existentes del contrato, para que el JS precargue campos
    // personalizados ya guardados sin necesidad de re-escribirlos.
    window.CONTRATO_CAMPOS_VALORES = <?= json_encode($contrato['campos_valores'] ?? []) ?>;
    window.CONTRATO_TEMPLATE_ID    = <?= (int)$contrato['template_id'] ?>;
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_contratos.js?v=<?= time() ?>"></script>