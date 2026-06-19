<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/views/academic/horarios_teoricos/manage.php
 * PROPÓSITO: Grilla interactiva de horarios teóricos para UNA oferta específica.
 *            Panel izquierdo: formulario (sin select de oferta, ya está en contexto).
 *            Panel derecho: grilla semanal con bloques clickeables y botón X.
 *            Todo vía AJAX sin recargar página. Responsive: grilla abajo en móvil/tablet.
 * VERSIÓN: 3.0.0 - Creación. Antes era index.php, ahora scoped a offering_id.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_horarios_teoricos.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size: 0.85rem;">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i> Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/academic" class="text-decoration-none text-muted">Panel Académico</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/academic/horarios-teoricos" class="text-decoration-none text-muted">Horarios Teóricos</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">
                <?= htmlspecialchars($oferta['diplomado_nombre']) ?>
            </li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark"><?= htmlspecialchars($oferta['diplomado_nombre']) ?></h2>
            <p class="text-muted small mb-0">
                <?= htmlspecialchars($oferta['cohorte_nombre']) ?>
                &nbsp;·&nbsp;
                <?= htmlspecialchars($oferta['general_modality']) ?>
            </p>
        </div>
        <a href="<?= $basePath ?>/academic/horarios-teoricos" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="ht-layout">

        <div class="ht-panel-form">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">

                    <div id="modeBar" class="ht-mode-bar ht-mode-new mb-3">
                        <i class="bi bi-plus-circle me-1"></i>
                        <span id="modeTxt">Nuevo horario</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Día de la semana</label>
                        <select id="f_dia" class="form-select form-select-sm">
                            <option value="">Seleccione...</option>
                            <?php foreach (['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'] as $dia): ?>
                                <option value="<?= $dia ?>"><?= $dia ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Hora inicio</label>
                            <input type="time" id="f_ini" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Hora fin</label>
                            <input type="time" id="f_fin" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Duración</label>
                        <div id="durBadge" class="ht-dur-badge">
                            <i class="bi bi-clock me-1"></i>
                            <span id="durTxt">--</span>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm rounded-pill flex-fill" id="btnGuardar">
                            <i class="bi bi-check-lg me-1"></i> Guardar
                        </button>
                        <button class="btn btn-outline-secondary btn-sm rounded-pill" id="btnCancelar" style="display:none">
                            Cancelar
                        </button>
                    </div>

                    <div class="mt-2" id="wrapBtnEliminar" style="display:none">
                        <button class="btn btn-sm w-100 rounded-pill ht-btn-danger" id="btnEliminarForm">
                            <i class="bi bi-trash me-1"></i> Eliminar este horario
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="ht-panel-grilla">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div id="grillaWrap"></div>
                    <p class="text-muted small text-center mt-2 mb-0">
                        Haz clic en un bloque para editarlo &nbsp;·&nbsp;
                        <i class="bi bi-x-circle"></i> elimina ese bloque
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div class="ht-confirm-icon">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
                </div>
            </div>
            <div class="modal-body pt-2">
                <h5 class="fw-bold mb-2">¿Eliminar este horario?</h5>
                <div id="confirmMsg" class="text-muted small lh-base"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-4"
                        id="btnConfirmDelete">Sí, eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.APP_BASE_PATH  = '<?= $basePath ?>';
    window.OFFERING_ID    = <?= (int) $offeringId ?>;
    window.HORARIOS_INIT  = <?= json_encode(array_values($horarios), JSON_UNESCAPED_UNICODE) ?>;
    window.DIPLOMADO_NOMBRE = <?= json_encode($oferta['diplomado_nombre']) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/academic_horarios_teoricos.js?v=<?= time() ?>"></script>