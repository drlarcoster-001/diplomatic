<?php
/**
 * MÓDULO: RECURSOS HUMANOS / NÓMINA
 * ARCHIVO: app/views/resources/nomina/manage.php
 * PROPÓSITO: Gestión de una nómina: buscar y agregar personal según el tipo,
 *            copiar/escribir salario base, agregar asignaciones y deducciones
 *            del catálogo, ver totales por persona y procesar la nómina.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$n = $nomina;
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_nomina.css">

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
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/resources/nomina" class="text-decoration-none text-muted">Nómina</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary"><?= htmlspecialchars($n['nombre']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <?= htmlspecialchars($n['nombre']) ?>
                <span class="badge rounded-pill ms-2" id="badgeEstado"
                      style="font-size:11px;background:#FCEBEB;border:1px solid #E24B4A;color:#A32D2D">
                    <?= $n['estado'] ?>
                </span>
            </h2>
            <p class="text-muted small mb-0">
                <?= str_replace('_', ' ', $n['tipo']) ?>
                &nbsp;·&nbsp; Fecha de pago: <?= date('d/m/Y', strtotime($n['fecha_pago'])) ?>
                &nbsp;·&nbsp; Tasa BCV: <strong><?= number_format($tasaBcv, 4) ?></strong>
            </p>
        </div>
        <a href="<?= $basePath ?>/resources/nomina" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if ($n['estado'] !== 'BORRADOR'): ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span><i class="bi bi-info-circle me-2"></i>Esta nómina ya fue procesada. No se puede modificar.</span>
            <?php if ($n['estado'] === 'PROCESADA'): ?>
                <button class="btn btn-sm btn-outline-warning rounded-pill px-3" id="btnReversarNomina">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reversar a Borrador
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>

    <!-- COLA DE PROFESORES PENDIENTES (solo POR_SESION) -->
    <div class="card border-0 shadow-sm mb-3" id="cardColaSesiones" style="display:none">
        <div class="card-body p-3">
            <div class="n-section-title mb-3">
                <i class="bi bi-hourglass-split me-1"></i> Profesores con Sesiones Pendientes de Pago
            </div>
            <div id="colaSesiones"></div>
        </div>
    </div>

    <!-- BUSCADOR DE PERSONAL -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="n-section-title mb-3">
                <i class="bi bi-person-plus me-1"></i> Agregar Personal
            </div>
            <div class="position-relative">
                <input type="text" id="buscarPersonal" class="form-control"
                       placeholder="Buscar por nombre o cédula..." autocomplete="off">
                <div id="personalDropdown" class="n-dropdown" style="display:none"></div>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <!-- GRID DE PERSONAL -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="n-section-title">
                    <i class="bi bi-people me-1"></i> Personal en esta Nómina
                    <span class="badge rounded-pill ms-1" id="badgeTotalPersonal"
                          style="background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC">
                        <?= count($personal) ?>
                    </span>
                </div>
                <?php if ($n['estado'] === 'BORRADOR'): ?>
                    <button class="btn btn-danger rounded-pill px-4 fw-bold" id="btnProcesar">
                        <i class="bi bi-check2-circle me-1"></i> Procesar Nómina
                    </button>
                <?php endif; ?>
            </div>

            <div id="gridPersonal"></div>

            <div class="n-totales-bar mt-3" id="totalesBar"></div>
        </div>
    </div>
</div>

<!-- MODAL: AGREGAR PERSONAL (escribir/copiar salario) -->
<div class="modal fade" id="modalAddPersonal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#EEEDFE;border-bottom:1px solid #AFA9EC">
                <h5 class="modal-title fw-bold" style="color:#3C3489">
                    <i class="bi bi-person-plus me-2"></i> Agregar a la Nómina
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div id="modalPersonalInfo" class="n-aviso mb-3"></div>

                <label class="form-label fw-bold small text-uppercase">Salario Base (USD)</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" id="modalSalario" class="form-control" step="0.01" min="0" placeholder="0.00">
                    <button class="btn btn-outline-secondary" id="btnCopiarContrato" type="button" title="Copiar del contrato">
                        <i class="bi bi-clipboard-check"></i> Copiar
                    </button>
                </div>
                <div class="form-text" id="montoContratoTxt"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-4" id="btnConfirmAddPersonal">
                    <i class="bi bi-check-circle me-1"></i> Agregar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: ASIGNACIONES / DEDUCCIONES -->
<div class="modal fade" id="modalConceptos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#f8f9fa">
                <h5 class="modal-title fw-bold" id="modalConceptosNombre">Conceptos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-6 border-end">
                        <div class="p-3 border-bottom bg-light">
                            <div class="fw-bold small text-uppercase" style="color:#085041">
                                <i class="bi bi-plus-circle me-1"></i> Asignaciones
                            </div>
                        </div>
                        <div class="p-3">
                            <select id="selectAsignacion" class="form-select form-select-sm mb-2">
                                <option value="">Seleccionar del catálogo...</option>
                            </select>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">$</span>
                                <input type="number" id="montoAsignacion" class="form-control" step="0.01" placeholder="Monto">
                                <button class="btn btn-success" id="btnAddAsigItem"><i class="bi bi-plus"></i></button>
                            </div>
                            <div id="listaAsigItems"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border-bottom bg-light">
                            <div class="fw-bold small text-uppercase" style="color:#A32D2D">
                                <i class="bi bi-dash-circle me-1"></i> Deducciones
                            </div>
                        </div>
                        <div class="p-3">
                            <select id="selectDeduccion" class="form-select form-select-sm mb-2">
                                <option value="">Seleccionar del catálogo...</option>
                            </select>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">$</span>
                                <input type="number" id="montoDeduccion" class="form-control" step="0.01" placeholder="Monto">
                                <button class="btn btn-danger" id="btnAddDedItem"><i class="bi bi-plus"></i></button>
                            </div>
                            <div id="listaDedItems"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: EDITAR SALARIO -->
<div class="modal fade" id="modalEditSalario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#FAEEDA;border-bottom:1px solid #BA7517">
                <h5 class="modal-title fw-bold" style="color:#633806">
                    <i class="bi bi-pencil-square me-2"></i> Editar Salario Base
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div id="editSalarioInfo" class="n-aviso mb-3"></div>

                <label class="form-label fw-bold small text-uppercase">Nuevo Salario Base (USD)</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" id="editSalarioInput" class="form-control" step="0.01" min="0" placeholder="0.00">
                    <button class="btn btn-outline-secondary" id="btnCopiarContratoEdit" type="button" title="Copiar del contrato">
                        <i class="bi bi-clipboard-check"></i> Copiar
                    </button>
                </div>
                <div class="form-text" id="montoContratoEditTxt"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning btn-sm rounded-pill px-4 fw-bold" id="btnConfirmEditSalario">
                    <i class="bi bi-check-circle me-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.APP_BASE_PATH    = '<?= $basePath ?>';
    window.NOMINA_ID        = <?= (int) $nominaId ?>;
    window.NOMINA_TIPO      = <?= json_encode($n['tipo']) ?>;
    window.NOMINA_ESTADO    = <?= json_encode($n['estado']) ?>;
    window.PERSONAL_INIT    = <?= json_encode(array_values($personal),     JSON_UNESCAPED_UNICODE) ?>;
    window.CAT_ASIGNACIONES = <?= json_encode(array_values($asignaciones), JSON_UNESCAPED_UNICODE) ?>;
    window.CAT_DEDUCCIONES  = <?= json_encode(array_values($deducciones),  JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_nomina_manage.js?v=<?= time() ?>"></script>