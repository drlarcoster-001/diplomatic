<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * Archivo: app/views/resources/contratos/create.php
 * Propósito: Generador de contratos — selección de personal, plantilla, campos y vista previa.
 * Versión: 1.0.0
 *
 * @var array $plantillas
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_contratos.css">

<div class="container-fluid py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources" class="text-decoration-none text-muted">Panel de Recursos</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources/contratos" class="text-decoration-none text-muted">Contratos</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#198754;">Nuevo Contrato</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Generar Nuevo Contrato</h2>
            <p class="text-muted small">Seleccione el personal y la plantilla para generar el contrato.</p>
        </div>
        <a href="<?= $basePath ?>/resources/contratos" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <form id="formContrato" action="<?= $basePath ?>/resources/contratos/generate" method="POST">

        <!-- PASO 1: Selección -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3 px-4 border-bottom">
                <i class="bi bi-1-circle me-2 text-success"></i> Seleccione Personal y Plantilla
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Buscador de personal -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">PERSONAL</label>
                        <div class="position-relative">
                            <input type="text" id="buscar-personal" class="form-control"
                                   placeholder="Buscar por nombre o cédula..." autocomplete="off">
                            <div id="personal-dropdown"
                                 class="dropdown-menu w-100 shadow-sm"
                                 style="display:none; max-height:250px; overflow-y:auto; position:absolute; z-index:1050;"></div>
                        </div>
                        <input type="hidden" name="personal_id" id="personal_id">
                        <!-- Ficha del personal seleccionado -->
                        <div id="personal-ficha" class="mt-3" style="display:none;">
                            <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#f0fdf4; border:1px solid #bbf7d0;">
                                <div>
                                    <div class="fw-bold text-dark" id="ficha-nombre"></div>
                                    <div class="small text-muted" id="ficha-tipo"></div>
                                    <div class="small" style="color:#198754;" id="ficha-expediente"></div>
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
                            <option value="" disabled selected>Seleccione una plantilla...</option>
                            <?php foreach ($plantillas as $pl): ?>
                                <option value="<?= $pl['id'] ?>">
                                    <?= htmlspecialchars($pl['tipo_siglas'] ?? '') ?> · <?= htmlspecialchars($pl['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- PASO 2: Campos personalizados (aparece dinámicamente) -->
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

        <!-- PASO 3: Vista previa (aparece dinámicamente) -->
        <div id="seccion-preview" style="display:none;">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-3-circle me-2 text-success"></i> Vista Previa del Contrato</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btn-actualizar-preview">
                        <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                    </button>
                </div>
                <div class="card-body p-0">
                    <div style="background:#e9ecef; padding:24px;">
                        <div id="preview-contenido"
                             style="background:white; max-width:800px; margin:0 auto; padding:50px 60px; box-shadow:0 4px 24px rgba(0,0,0,0.10); min-height:400px; font-family:'Segoe UI', serif; font-size:13px; line-height:1.8; color:#222;">
                            <p class="text-muted text-center">Seleccione personal y plantilla para ver la vista previa.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón generar -->
            <div class="text-end mb-4">
                <button type="submit" class="btn rounded-pill px-5 py-2 text-white shadow" style="background:#198754; font-size:1rem;">
                    <i class="bi bi-file-earmark-check me-2"></i> Generar y Guardar Contrato
                </button>
            </div>
        </div>

    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_contratos.js?v=<?= time() ?>"></script>