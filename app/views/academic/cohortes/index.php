<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/cohortes/index.php
 * Propósito: Gestión integral de cohortes con blindaje estético y funcional de acciones según el estado del ciclo de vida.
 * Version: 1.2.1 - Versión Maestra Estable. Mejora Visual: Sustitución de texto descriptivo por icono de seguridad con tooltip.
 */
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<link rel="stylesheet" href="/diplomatic/public/assets/css/academic_cohortes.css">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Cohortes Académicas</h2>
            <p class="text-muted small">Gestión de períodos institucionales.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/diplomatic/public/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnOpenNuevo" data-bs-toggle="modal" data-bs-target="#modalCohortForm">
                <i class="fas fa-plus me-1"></i> Nueva Cohorte
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="/diplomatic/public/academic/cohortes" class="row g-3">
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

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small fw-bold text-secondary text-uppercase">
                    <tr>
                        <th class="ps-4">Código</th>
                        <th>Nombre de la Cohorte</th>
                        <th>Sede(s)</th> 
                        <th>Período (Inicio - Fin)</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cohortes)): ?>
                        <tr><td colspan="6" class="text-center py-4">No hay registros de cohortes activos.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cohortes as $c): ?>
                            <?php $statusClean = strtolower(trim($c['cohort_status'])); ?>
                            <tr class="cohorte-row" data-id="<?= $c['id'] ?>" style="cursor:pointer;">
                                <td class="ps-4 fw-bold text-primary"><?= $c['cohort_code'] ?></td>
                                <td class="fw-bold"><?= $c['name'] ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($c['campus_names'] ?? 'No definida') ?></td>
                                <td class="small"><?= date('d/m/Y', strtotime($c['start_date'])) ?> - <?= date('d/m/Y', strtotime($c['end_date'])) ?></td>
                                <td>
                                    <?php 
                                        $bg = match($statusClean) {
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
                                <td class="text-end pe-4">
                                    <?php if ($statusClean === 'planificada'): ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-white border text-primary btn-edit" data-id="<?= $c['id'] ?>" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-white border text-danger btn-delete" data-id="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-inline-block text-muted pe-3" title="Registro Bloqueado: Gestión restringida al módulo de Configuración Avanzada">
                                            <i class="bi bi-lock-fill" style="font-size: 1.1rem; opacity: 0.5;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCohortForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formCohort" action="/diplomatic/public/academic/cohortes/save" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white fw-bold">Gestión de Cohorte</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="field_id">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">CÓDIGO</label>
                        <input type="text" name="cohort_code" id="field_code" class="form-control" placeholder="Ej: 2026-I" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold small">NOMBRE</label>
                        <input type="text" name="name" id="field_name" class="form-control" placeholder="Nombre descriptivo del período" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">INICIO</label>
                        <input type="date" name="start_date" id="field_start" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">FIN</label>
                        <input type="date" name="end_date" id="field_end" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">APERTURA INSCRIPCIÓN</label>
                        <input type="date" name="enrollment_start" id="field_enroll_start" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">CIERRE INSCRIPCIÓN</label>
                        <input type="date" name="enrollment_end" id="field_enroll_end" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">SEDE(S) ASIGNADA(S)</label>
                        <select name="campus_ids[]" id="field_campuses" class="form-select select2-multiple" multiple="multiple" data-placeholder="Seleccione sedes..." required>
                            <?php if(!empty($campuses)): ?>
                                <?php foreach($campuses as $cp): ?>
                                    <option value="<?= $cp['id'] ?>"><?= htmlspecialchars($cp['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">DESCRIPCIÓN / OBSERVACIONES</label>
                        <textarea name="description" id="field_desc" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Guarda</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalCohortPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title text-white">Ficha de Cohorte</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h4 id="prev_name" class="fw-bold text-primary mb-1"></h4>
                <p id="prev_code" class="text-muted small mb-3"></p>
                <hr>
                <div class="row g-3">
                    <div class="col-6 small text-muted">INICIO: <b id="prev_start" class="text-dark"></b></div>
                    <div class="col-6 small text-muted">FIN: <b id="prev_end" class="text-dark"></b></div>
                    <div class="col-6 small text-muted">INSCRIPCIÓN: <b id="prev_enroll_start" class="text-dark"></b></div>
                    <div class="col-6 small text-muted">CIERRE: <b id="prev_enroll_end" class="text-dark"></b></div>
                    <div class="col-12 small text-muted">SEDE(S): <b id="prev_campus" class="text-dark"></b></div>
                    <div class="col-12 small text-muted">OBSERVACIONES: <p id="prev_desc" class="text-dark mb-0 small"></p></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn_start_action" class="btn btn-success btn-sm rounded-pill px-3">Iniciar Ciclo</button>
                <button type="button" id="btn_close_action" class="btn btn-danger btn-sm rounded-pill px-3">Finalizar Ciclo</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/diplomatic/public/assets/js/academic_cohortes.js?v=<?= time() ?>"></script>