<?php
/**
 * MÓDULO: CORRESPONDENCIA / GENERADOR DE DOCUMENTOS
 * ARCHIVO: app/views/operational/correspondencia/documentos/create.php
 * PROPÓSITO: Paso 1: elegir plantilla. Paso 2 (AJAX, sin recargar): se
 *            cargan los registros de la tabla objetivo (con checkboxes,
 *            selección múltiple) y los campos personalizados de esa
 *            plantilla (se llenan una sola vez, aplican a todo el lote).
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$errorType = $_GET['error'] ?? '';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/operational_correspondencia_documentos.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/operational" class="text-decoration-none text-muted">Correspondencia</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/operational/correspondencia/documentos" class="text-decoration-none text-muted">Documentos Generados</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#0d6efd;">Generar</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Generar Documentos</h2>
            <p class="text-muted small">Elige una plantilla, selecciona uno o varios registros y genera los documentos.</p>
        </div>
        <a href="<?= $basePath ?>/operational/correspondencia/documentos" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if ($errorType === 'incompleto'): ?>
        <div class="alert alert-danger">Elige una plantilla y al menos un registro.</div>
    <?php elseif ($errorType === 'db'): ?>
        <div class="alert alert-danger">Ocurrió un error al generar los documentos.</div>
    <?php endif; ?>

    <form id="formGenerar" action="<?= $basePath ?>/operational/correspondencia/documentos/generar" method="POST">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3 px-4 border-bottom">
                <i class="bi bi-1-circle me-2 text-primary"></i> Plantilla
            </div>
            <div class="card-body p-4">
                <select name="plantilla_id" id="plantillaSelect" class="form-select" required>
                    <option value="" disabled selected>Seleccione una plantilla...</option>
                    <?php foreach ($plantillas as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['tipo_documento']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div id="pasoDosContainer" style="display:none">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold py-3 px-4 border-bottom">
                            <i class="bi bi-2-circle me-2 text-primary"></i> Selecciona los Registros
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex gap-2 mb-3">
                                <input type="text" id="buscarRegistro" class="form-control" placeholder="Buscar...">
                                <button type="button" class="btn btn-outline-secondary text-nowrap" id="btnSeleccionarTodos">Marcar todos</button>
                            </div>
                            <div id="registrosContainer" style="max-height:420px;overflow-y:auto" class="border rounded p-2"></div>
                            <small class="text-muted d-block mt-2"><span id="contadorSeleccionados">0</span> registro(s) seleccionado(s)</small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-bold py-3 px-4 border-bottom">
                            <i class="bi bi-3-circle me-2 text-primary"></i> Campos Personalizados
                        </div>
                        <div class="card-body p-3">
                            <p class="text-muted small">Se aplican igual a todos los documentos del lote.</p>
                            <div id="camposPersonalizadosContainer"></div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary rounded-pill w-100 mt-3 py-2 shadow-sm" id="btnGenerar">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i> Generar Documentos
                    </button>
                </div>
            </div>
        </div>

        <div id="camposIdsContainer"></div>
    </form>
</div>

<script>window.APP_BASE_PATH = '<?= $basePath ?>';</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/operational_correspondencia_documentos.js?v=<?= time() ?>"></script>