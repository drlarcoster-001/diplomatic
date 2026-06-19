<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/views/academic/horarios_practicas/manage.php
 * PROPÓSITO: Pestaña 1 (Grupos): crear grupos con botón Guardar claro + asignar estudiantes.
 *            Pestaña 2 (Horarios): seleccionar grupo + centro médico + fechas con calendario
 *            multi-selección (Flatpickr). Vista calendaria de todas las fechas asignadas.
 * VERSIÓN: 2.0.0 - UX reescrita. Botones de guardar claros. Calendario de fechas.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_horarios_practicas.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i>Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/academic" class="text-decoration-none text-muted">Panel Académico</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/academic/horarios-practicas" class="text-decoration-none text-muted">Horarios de Práctica</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary">
                <?= htmlspecialchars($oferta['diplomado_nombre']) ?>
            </li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark"><?= htmlspecialchars($oferta['diplomado_nombre']) ?></h2>
            <p class="text-muted small mb-0">
                <?= htmlspecialchars($oferta['cohorte_nombre']) ?>
                &nbsp;·&nbsp; <?= htmlspecialchars($oferta['general_modality']) ?>
            </p>
        </div>
        <a href="<?= $basePath ?>/academic/horarios-practicas"
           class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <ul class="nav nav-tabs mb-3" id="tabsPractica">
        <li class="nav-item">
            <button class="nav-link active fw-bold" data-tab="grupos">
                <i class="bi bi-people me-1"></i> Grupos
                <span class="badge rounded-pill ms-1" id="badgeGrupos"
                      style="background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC">
                    <?= count($grupos) ?>
                </span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold" data-tab="horarios">
                <i class="bi bi-hospital me-1"></i> Horarios de Práctica
                <span class="badge rounded-pill ms-1" id="badgeHorarios"
                      style="background:#E1F5EE;color:#085041;border:1px solid #1D9E75">
                    <?= count($horarios) ?>
                </span>
            </button>
        </li>
    </ul>

    <!-- ===== PESTAÑA 1: GRUPOS ===== -->
    <div id="tabGrupos">
        <div class="ht-layout">
            <div class="ht-panel-form">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="hp-form-title mb-3">
                            <i class="bi bi-people me-1"></i> Nuevo Grupo
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Nombre del grupo</label>
                            <input type="text" id="g_nombre" class="form-control form-control-sm"
                                   placeholder="Ej: A1, A2, Grupo Lunes...">
                            <div class="form-text text-muted">Identifica el grupo de práctica (A1, A2...)</div>
                        </div>
                        <hr class="my-3">
                        <button class="btn btn-primary btn-sm rounded-pill w-100 fw-bold" id="btnSaveGrupo">
                            <i class="bi bi-plus-circle me-1"></i> Agregar Grupo
                        </button>
                    </div>
                </div>
            </div>

            <div class="ht-panel-grilla">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div id="gruposWrap"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== PESTAÑA 2: HORARIOS ===== -->
    <div id="tabHorarios" style="display:none">
        <div class="ht-layout">
            <div class="ht-panel-form">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">

                        <div class="hp-form-title mb-3" id="horFormTitle">
                            <i class="bi bi-hospital me-1"></i> Nueva Asignación
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Grupo</label>
                            <select id="h_grupo" class="form-select form-select-sm">
                                <option value="">Seleccione un grupo...</option>
                                <?php foreach ($grupos as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Centro Médico</label>
                            <select id="h_centro" class="form-select form-select-sm">
                                <option value="">Seleccione un centro...</option>
                                <?php foreach ($centrosMedicos as $cm): ?>
                                    <option value="<?= $cm['id'] ?>"><?= htmlspecialchars($cm['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Fechas de práctica</label>
                            <input type="text" id="h_fechas" class="form-control form-control-sm"
                                   placeholder="Selecciona las fechas en el calendario..." readonly>
                            <div class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Selecciona una o varias fechas. Puedes seleccionar/deseleccionar haciendo clic.
                            </div>
                            <div id="fechasSeleccionadas" class="mt-2"></div>
                        </div>

                        <hr class="my-3">

                        <div class="d-grid gap-2">
                            <button class="btn btn-primary btn-sm rounded-pill fw-bold" id="btnSaveHorario">
                                <i class="bi bi-check-circle me-1"></i>
                                <span id="btnSaveHorarioTxt">Agregar Asignación</span>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm rounded-pill" id="btnCancelHorario" style="display:none">
                                Cancelar edición
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <div class="ht-panel-grilla">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3 px-3 pb-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold small text-uppercase text-muted">
                                <i class="bi bi-calendar3 me-1"></i> Calendario de Prácticas
                            </span>
                            <div class="d-flex gap-2 align-items-center">
                                <button class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0" id="btnCalPrev">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <span class="fw-bold small" id="calMesLabel"></span>
                                <button class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0" id="btnCalNext">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                        <div id="leyendaGrupos" class="d-flex flex-wrap gap-2 mb-2"></div>
                    </div>
                    <div class="card-body p-3 pt-0">
                        <div id="calendarioWrap"></div>
                        <div id="horariosListaWrap" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: ESTUDIANTES DEL GRUPO -->
<div class="modal fade" id="modalEstudiantes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#EEEDFE;border-bottom:1px solid #AFA9EC">
                <h5 class="modal-title fw-bold" style="color:#3C3489">
                    <i class="bi bi-people me-2"></i>
                    <span id="modalGrupoNombre">Grupo</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0" style="min-height:380px">
                    <div class="col-6 border-end">
                        <div class="p-3 border-bottom bg-light">
                            <div class="fw-bold small text-uppercase text-muted">
                                <i class="bi bi-person-check me-1"></i> Asignados
                                <span class="badge rounded-pill ms-1" id="badgeAsignados"
                                      style="background:#E1F5EE;color:#085041;border:1px solid #1D9E75">0</span>
                            </div>
                        </div>
                        <div id="listaAsignados" style="overflow-y:auto;max-height:320px"></div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border-bottom bg-light">
                            <div class="fw-bold small text-uppercase text-muted">
                                <i class="bi bi-person-plus me-1"></i> Sin grupo
                                <span class="badge rounded-pill ms-1 bg-light text-muted border" id="badgeSinGrupo">0</span>
                            </div>
                        </div>
                        <div id="listaSinGrupo" style="overflow-y:auto;max-height:320px"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CONFIRMAR -->
<div class="modal fade" id="modalConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div class="ht-confirm-icon">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
                </div>
            </div>
            <div class="modal-body pt-2">
                <h5 class="fw-bold mb-2" id="confirmTitle">¿Eliminar?</h5>
                <div id="confirmMsg" class="text-muted small lh-base"></div>
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
    window.APP_BASE_PATH  = '<?= $basePath ?>';
    window.OFFERING_ID    = <?= (int) $offeringId ?>;
    window.GRUPOS_INIT    = <?= json_encode(array_values($grupos),        JSON_UNESCAPED_UNICODE) ?>;
    window.HORARIOS_INIT  = <?= json_encode(array_values($horarios),      JSON_UNESCAPED_UNICODE) ?>;
    window.CENTROS_INIT   = <?= json_encode(array_values($centrosMedicos), JSON_UNESCAPED_UNICODE) ?>;
    window.FECHAS_INIT    = <?= json_encode(array_values($fechas),         JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="<?= $basePath ?>/assets/js/academic_horarios_practicas.js?v=<?= time() ?>"></script>