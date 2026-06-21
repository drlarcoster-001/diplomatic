<?php
/**
 * MÓDULO: RECURSOS HUMANOS / CONCEPTOS DE NÓMINA
 * ARCHIVO: app/views/resources/conceptos_nomina/index.php
 * PROPÓSITO: Gestión de asignaciones y deducciones del catálogo de nómina.
 *            Dos pestañas: Asignaciones y Deducciones. CRUD vía AJAX.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_conceptos_nomina.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i>Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/resources" class="text-decoration-none text-muted">Recursos</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary">Conceptos de Nómina</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Conceptos de Nómina</h2>
            <p class="text-muted small mb-0">Catálogo de asignaciones y deducciones aplicables al personal.</p>
        </div>
        <a href="<?= $basePath ?>/resources" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <ul class="nav nav-tabs mb-3" id="tabsConceptos">
        <li class="nav-item">
            <button class="nav-link active fw-bold" data-tab="asignaciones">
                <i class="bi bi-plus-circle me-1"></i> Asignaciones
                <span class="badge rounded-pill ms-1" id="badgeAsig"
                      style="background:#E1F5EE;color:#085041;border:1px solid #1D9E75">
                    <?= count($asignaciones) ?>
                </span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold" data-tab="deducciones">
                <i class="bi bi-dash-circle me-1"></i> Deducciones
                <span class="badge rounded-pill ms-1" id="badgeDed"
                      style="background:#FCEBEB;color:#A32D2D;border:1px solid #E24B4A">
                    <?= count($deducciones) ?>
                </span>
            </button>
        </li>
    </ul>

    <!-- ===== PESTAÑA ASIGNACIONES ===== -->
    <div id="tabAsignaciones">
        <div class="cn-layout">
            <div class="cn-panel-form">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="cn-form-title cn-asig mb-3" id="asigModeBar">
                            <i class="bi bi-plus-circle me-1"></i> Nueva Asignación
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Nombre</label>
                            <input type="text" id="asig_nombre" class="form-control form-control-sm"
                                   placeholder="Ej: Bono de Transporte">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Tipo</label>
                            <select id="asig_tipo" class="form-select form-select-sm">
                                <option value="">Seleccione...</option>
                                <option value="SALARIO_BASE">Salario Base</option>
                                <option value="MONTO_FIJO">Monto Fijo</option>
                                <option value="FORMULA">Fórmula</option>
                            </select>
                        </div>

                        <div class="mb-3" id="asig_wrap_valor" style="display:none">
                            <label class="form-label fw-bold small text-uppercase">Monto (USD)</label>
                            <input type="number" id="asig_valor" class="form-control form-control-sm"
                                   step="0.01" min="0" placeholder="0.00">
                        </div>

                        <div class="mb-3" id="asig_wrap_formula" style="display:none">
                            <label class="form-label fw-bold small text-uppercase">Fórmula</label>
                            <input type="text" id="asig_formula" class="form-control form-control-sm"
                                   placeholder="Ej: salario * 0.10">
                            <div class="form-text">Variables: <code>salario</code>, <code>dias</code>, <code>sesiones</code></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Descripción <span class="text-muted fw-normal">(opcional)</span></label>
                            <textarea id="asig_desc" class="form-control form-control-sm" rows="2"
                                      placeholder="Descripción del concepto..."></textarea>
                        </div>

                        <hr class="my-3">
                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-sm rounded-pill fw-bold" id="btnSaveAsig">
                                <i class="bi bi-check-circle me-1"></i> <span id="btnSaveAsigTxt">Guardar Asignación</span>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm rounded-pill" id="btnCancelAsig" style="display:none">
                                Cancelar edición
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cn-panel-lista">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div id="listaAsignaciones"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== PESTAÑA DEDUCCIONES ===== -->
    <div id="tabDeducciones" style="display:none">
        <div class="cn-layout">
            <div class="cn-panel-form">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="cn-form-title cn-ded mb-3" id="dedModeBar">
                            <i class="bi bi-dash-circle me-1"></i> Nueva Deducción
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Nombre</label>
                            <input type="text" id="ded_nombre" class="form-control form-control-sm"
                                   placeholder="Ej: Préstamo, Falta Injustificada">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Tipo</label>
                            <select id="ded_tipo" class="form-select form-select-sm">
                                <option value="">Seleccione...</option>
                                <option value="MONTO_FIJO">Monto Fijo</option>
                                <option value="FORMULA">Fórmula</option>
                            </select>
                        </div>

                        <div class="mb-3" id="ded_wrap_valor" style="display:none">
                            <label class="form-label fw-bold small text-uppercase">Monto (USD)</label>
                            <input type="number" id="ded_valor" class="form-control form-control-sm"
                                   step="0.01" min="0" placeholder="0.00">
                        </div>

                        <div class="mb-3" id="ded_wrap_formula" style="display:none">
                            <label class="form-label fw-bold small text-uppercase">Fórmula</label>
                            <input type="text" id="ded_formula" class="form-control form-control-sm"
                                   placeholder="Ej: salario * 0.05">
                            <div class="form-text">Variables: <code>salario</code>, <code>dias</code>, <code>sesiones</code></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Descripción <span class="text-muted fw-normal">(opcional)</span></label>
                            <textarea id="ded_desc" class="form-control form-control-sm" rows="2"
                                      placeholder="Descripción del concepto..."></textarea>
                        </div>

                        <hr class="my-3">
                        <div class="d-grid gap-2">
                            <button class="btn btn-danger btn-sm rounded-pill fw-bold" id="btnSaveDed">
                                <i class="bi bi-check-circle me-1"></i> <span id="btnSaveDedTxt">Guardar Deducción</span>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm rounded-pill" id="btnCancelDed" style="display:none">
                                Cancelar edición
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cn-panel-lista">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div id="listaDeducciones"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CONFIRMAR ELIMINAR -->
<div class="modal fade" id="modalConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div class="cn-confirm-icon">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
                </div>
            </div>
            <div class="modal-body pt-2">
                <h5 class="fw-bold mb-2" id="confirmTitle">¿Eliminar?</h5>
                <div id="confirmMsg" class="text-muted small"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-4"
                        id="btnConfirm">Sí, eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.APP_BASE_PATH    = '<?= $basePath ?>';
    window.ASIGNACIONES_INIT = <?= json_encode(array_values($asignaciones), JSON_UNESCAPED_UNICODE) ?>;
    window.DEDUCCIONES_INIT  = <?= json_encode(array_values($deducciones),  JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_conceptos_nomina.js?v=<?= time() ?>"></script>