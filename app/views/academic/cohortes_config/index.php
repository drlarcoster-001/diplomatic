<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/cohortes_config/index.php
 * Propósito: Interfaz para forzar estatus y realizar borrados físicos.
 */
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="/diplomatic/public/assets/css/academic_cohortes_config.css">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Configuración Avanzada de Cohortes</h2>
            <p class="text-danger small fw-bold"><i class="fas fa-exclamation-triangle"></i> ATENCIÓN: Acciones de alto impacto y borrado físico.</p>
        </div>
        <div>
            <a href="/diplomatic/public/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="/diplomatic/public/academic/cohortes-config" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Buscar por código o nombre..." value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden border-top border-warning border-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small fw-bold text-secondary text-uppercase">
                    <tr>
                        <th class="ps-4">Código</th>
                        <th>Nombre</th>
                        <th>Estatus Actual</th>
                        <th>Papelera</th>
                        <th class="text-end pe-4">Acciones Críticas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cohortes)): ?>
                        <tr><td colspan="5" class="text-center py-4">No hay registros.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cohortes as $c): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary"><?= $c['cohort_code'] ?></td>
                                <td class="fw-bold"><?= $c['name'] ?></td>
                                <td>
                                    <?php 
                                        $bg = match(trim(strtolower($c['cohort_status']))) {
                                            'planificada' => 'bg-secondary',
                                            'en curso'    => 'bg-primary',
                                            'finalizada'  => 'bg-success',
                                            'reabierta'   => 'bg-warning text-dark',
                                            'suspendida'  => 'bg-danger',
                                            'cancelada'   => 'bg-dark',
                                            default       => 'bg-light text-dark'
                                        };
                                    ?>
                                    <span class="badge rounded-pill <?= $bg ?>"><?= $c['cohort_status'] ?></span>
                                </td>
                                <td>
                                    <?php if ($c['is_active'] == 0): ?>
                                        <span class="badge bg-danger rounded-pill">En Papelera</span>
                                    <?php else: ?>
                                        <span class="badge bg-light border text-dark rounded-pill">Activo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group-custom shadow-sm">
                                        <button type="button" class="btn btn-status" data-id="<?= $c['id'] ?>" title="Forzar Estatus / Revivir">
                                            <i class="fas fa-sync-alt text-warning"></i>
                                        </button>
                                        <button type="button" class="btn btn-hard-delete" data-id="<?= $c['id'] ?>" data-name="<?= $c['name'] ?>" title="Borrado Físico Definitivo">
                                            <i class="fas fa-skull-crossbones text-danger"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalForceStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formStatus" action="/diplomatic/public/academic/cohortes-config/updateStatus" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-sync-alt me-2"></i> Forzar Estatus de Cohorte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Modificar el estatus desde esta pantalla anulará las reglas de negocio regulares. 
                    Si la cohorte estaba "En Papelera", se <strong>reactivará</strong> automáticamente al guardar.
                </p>
                <input type="hidden" name="id" id="status_id">
                
                <div class="mb-3">
                    <label class="form-label fw-bold small">Cohorte Seleccionada:</label>
                    <input type="text" class="form-control bg-light border-0" id="status_name" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Seleccione Nuevo Estatus:</label>
                    <select name="cohort_status" id="status_select" class="form-select form-select-lg" required>
                        <option value="Planificada">Planificada</option>
                        <option value="En curso">En curso</option>
                        <option value="Finalizada">Finalizada</option>
                        <option value="Reabierta">Reabierta (Extemporánea)</option>
                        <option value="Suspendida">Suspendida</option>
                        <option value="Cancelada">Cancelada</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning fw-bold rounded-pill px-4">Ejecutar Cambio</button>
            </div>
        </form>
    </div>
</div>

<div id="error-container" data-error="<?= htmlspecialchars($_GET['error'] ?? '') ?>" style="display:none;"></div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/diplomatic/public/assets/js/academic_cohortes_config.js"></script>