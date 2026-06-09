<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * Archivo: app/views/resources/contratos_plantillas/edit.php
 * Propósito: Formulario de edición de plantilla con editor WYSIWYG y campos personalizados.
 * Versión: 1.2.0
 *
 * @var array $plantilla
 * @var array $tipos
 * @var array $campos_sistema
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$p        = $plantilla;
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_contratos_plantillas.css">

<div class="container-fluid py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources" class="text-decoration-none text-muted">Panel de Recursos</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources/contratos/plantillas" class="text-decoration-none text-muted">Plantillas</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#0d6efd;">Editar Plantilla</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Editar Plantilla</h2>
            <p class="text-muted small">ID: #<?= $p['id'] ?> · Creada: <?= date('d/m/Y', strtotime($p['created_at'])) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/resources/contratos/plantillas" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="submitForm()">
                <i class="bi bi-check-circle me-1"></i> Guardar Cambios
            </button>
        </div>
    </div>

    <form id="formPlantilla" action="<?= $basePath ?>/resources/contratos/plantillas/update" method="POST">
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <input type="hidden" name="contenido" id="contenido-hidden">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3 px-4 border-bottom">
                <i class="bi bi-info-circle me-2 text-primary"></i> Información de la Plantilla
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">NOMBRE DE LA PLANTILLA</label>
                        <input type="text" name="nombre" class="form-control"
                               value="<?= htmlspecialchars($p['nombre']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">TIPO DE CONTRATO</label>
                        <select name="tipo_contrato_id" class="form-select" required>
                            <option value="" disabled>Seleccione...</option>
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $p['tipo_contrato_id'] == $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['siglas']) ?> — <?= htmlspecialchars($t['nombre']) ?>
                                </option>
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
                        <div class="d-flex flex-column gap-1">
                            <?php foreach ($campos_sistema as $variable => $descripcion): ?>
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary text-start rounded-pill px-3 btn-insertar"
                                        data-variable="<?= htmlspecialchars($variable) ?>"
                                        title="<?= htmlspecialchars($descripcion) ?>">
                                    <code style="font-size:0.75rem;"><?= htmlspecialchars($variable) ?></code>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold py-3 px-4 border-bottom small text-uppercase text-secondary">
                        <i class="bi bi-plus-circle me-2"></i> Campos Personalizados
                    </div>
                    <div class="card-body p-3">
                        <p class="text-muted small mb-3">Defina campos adicionales. Se insertarán como <code>{nombre_campo}</code>.</p>
                        <div id="campos-container">
                            <?php foreach ($p['campos'] as $i => $campo): ?>
                                <div class="campo-row mb-2" data-index="<?= $i ?>">
                                    <div class="input-group input-group-sm mb-1">
                                        <input type="text" name="campo_etiqueta[]"
                                               class="form-control campo-etiqueta"
                                               placeholder="Etiqueta"
                                               value="<?= htmlspecialchars($campo['etiqueta']) ?>">
                                        <button type="button" class="btn btn-outline-primary btn-sm btn-insertar-custom"
                                                data-etiqueta="<?= htmlspecialchars($campo['nombre_campo']) ?>"
                                                title="Insertar en editor">
                                            <i class="bi bi-arrow-right"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-campo">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                    <select name="campo_tipo[]" class="form-select form-select-sm">
                                        <option value="texto"  <?= $campo['tipo'] === 'texto'  ? 'selected' : '' ?>>Texto</option>
                                        <option value="numero" <?= $campo['tipo'] === 'numero' ? 'selected' : '' ?>>Número</option>
                                        <option value="fecha"  <?= $campo['tipo'] === 'fecha'  ? 'selected' : '' ?>>Fecha</option>
                                        <option value="moneda" <?= $campo['tipo'] === 'moneda' ? 'selected' : '' ?>>Moneda</option>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        </div>
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
    window.contenidoInicial = <?= json_encode($p['contenido']) ?>;
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script src="<?= $basePath ?>/assets/js/resources_contratos_plantillas.js?v=<?= time() ?>"></script>