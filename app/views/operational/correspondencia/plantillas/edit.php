<?php
/**
 * MÓDULO: CORRESPONDENCIA / PLANTILLAS
 * ARCHIVO: app/views/operational/correspondencia/plantillas/edit.php
 * PROPÓSITO: Igual a create.php pero precargando la plantilla existente
 *            (contenido, campos personalizados, tabla objetivo ya elegida
 *            con sus variables del sistema correspondientes).
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$errorType = $_GET['error'] ?? '';
$p = $plantilla;
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/operational_correspondencia_plantillas.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/operational" class="text-decoration-none text-muted">Correspondencia</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/operational/correspondencia/plantillas" class="text-decoration-none text-muted">Plantillas</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#0d6efd;"><?= htmlspecialchars($p['nombre']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Editar Plantilla</h2>
            <p class="text-muted small">Diseñe el formato del documento usando variables del sistema y campos personalizados.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/operational/correspondencia/plantillas" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="submitForm()">
                <i class="bi bi-check-circle me-1"></i> Guardar Cambios
            </button>
        </div>
    </div>

    <?php if ($errorType === 'incompleto'): ?>
        <div class="alert alert-danger">Completa nombre, tipo de documento y tabla objetivo.</div>
    <?php elseif ($errorType === 'db'): ?>
        <div class="alert alert-danger">Ocurrió un error al guardar los cambios.</div>
    <?php endif; ?>

    <form id="formPlantilla" action="<?= $basePath ?>/operational/correspondencia/plantillas/update" method="POST">
        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
        <input type="hidden" name="contenido" id="contenido-hidden">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3 px-4 border-bottom">
                <i class="bi bi-info-circle me-2 text-primary"></i> Información de la Plantilla
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">NOMBRE DE LA PLANTILLA</label>
                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($p['nombre']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">TIPO DE DOCUMENTO</label>
                        <select name="tipo_documento" class="form-select" required>
                            <?php foreach ($tipos as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $p['tipo_documento']===$key?'selected':'' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">TABLA OBJETIVO</label>
                        <select name="tabla_objetivo" id="tablaObjetivo" class="form-select" required>
                            <?php foreach ($tablas as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $p['tabla_objetivo']===$key?'selected':'' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-3">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold py-3 px-4 border-bottom small text-uppercase text-secondary">
                        <i class="bi bi-gear me-2"></i> Variables del Sistema
                    </div>
                    <div class="card-body p-3">
                        <p class="text-muted small mb-3">Haga clic en una variable para insertarla en el editor.</p>
                        <div class="d-flex flex-column gap-1" id="camposSistemaContainer"></div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold py-3 px-4 border-bottom small text-uppercase text-secondary">
                        <i class="bi bi-plus-circle me-2"></i> Campos Personalizados
                    </div>
                    <div class="card-body p-3">
                        <p class="text-muted small mb-3">Defina campos adicionales. Se insertarán como <code>{nombre_campo}</code>.</p>
                        <div id="campos-container"></div>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill w-100 mt-2" id="btnAgregarCampo">
                            <i class="bi bi-plus-lg me-1"></i> Agregar Campo
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 px-4 border-bottom">
                        <span class="fw-bold"><i class="bi bi-file-text me-2 text-primary"></i> Editor de Contenido</span>
                    </div>
                    <div class="card-body p-0">
                        <div id="quill-editor" style="min-height:580px; font-size:14px; font-family:'Segoe UI', sans-serif;"></div>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

<script>
    window.APP_BASE_PATH      = '<?= $basePath ?>';
    window.contenidoInicial   = <?= json_encode($p['contenido'] ?? '') ?>;
    window.camposPersonalizadosIniciales = <?= json_encode($p['campos_personalizados_arr'] ?? []) ?>;
    window.tablaObjetivoInicial = <?= json_encode($p['tabla_objetivo']) ?>;
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script src="<?= $basePath ?>/assets/js/operational_correspondencia_plantillas.js?v=<?= time() ?>"></script>