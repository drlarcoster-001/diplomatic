<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/cohortes/index.php
 * Propósito: Listado maestro con vista previa tipo documento serio.
 */
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cohortes)): ?>
                        <tr><td colspan="6" class="text-center py-4">No hay registros.</td></tr>
                    <?php else: ?>
                        <?php foreach ($cohortes as $c): ?>
                            <tr class="cohorte-row" data-id="<?= $c['id'] ?>" style="cursor:pointer;">
                                <td class="ps-4 fw-bold text-primary"><?= $c['cohort_code'] ?></td>
                                <td class="fw-bold"><?= $c['name'] ?></td>
                                <td><?= date('d/m/Y', strtotime($c['start_date'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($c['end_date'])) ?></td>
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
                                <td class="text-end pe-4">
                                    <?php if (trim(strtolower($c['cohort_status'])) === 'planificada'): ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-white border text-primary btn-edit" data-id="<?= $c['id'] ?>" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-white border text-danger btn-delete" data-id="<?= $c['id'] ?>" data-name="<?= $c['name'] ?>" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <i class="bi bi-lock-fill text-muted" title="Registro bloqueado (Solo lectura)"></i>
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
        <form id="formCohort" action="/diplomatic/public/academic/cohortes/save" method="POST" class="modal-content border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white fw-bold">Gestión de Cohorte</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="field_id">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">CÓDIGO</label>
                        <input type="text" name="cohort_code" id="field_code" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold small">NOMBRE</label>
                        <input type="text" name="name" id="field_name" class="form-control" required>
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
                        <label class="form-label fw-bold small">SEDE</label>
                        <input type="text" name="base_campus" id="field_campus" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">DESCRIPCIÓN</label>
                        <textarea name="description" id="field_desc" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalCohortPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-light border-0 py-2">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body doc-container">
                <div class="doc-header-title">
                    <span id="prev_code"></span> - <span id="prev_name"></span>
                </div>
                <div class="row bg-light p-3 rounded mb-3">
                    <div class="col-12"><h6 class="doc-label text-primary" style="margin-top:0;">Detalles del Período</h6></div>
                    <div class="col-6"><strong>Inicio:</strong> <span id="prev_start"></span></div>
                    <div class="col-6"><strong>Fin:</strong> <span id="prev_end"></span></div>
                    <div class="col-12 mt-2"><strong>Inscripciones:</strong> <span id="prev_enroll_start"></span> al <span id="prev_enroll_end"></span></div>
                    <div class="col-12 mt-2"><strong>Sede:</strong> <span id="prev_campus"></span></div>
                </div>
                <span class="doc-label">Descripción:</span>
                <div class="doc-content" id="prev_desc"></div>
            </div>
            <div class="modal-footer bg-light border-0 justify-content-center">
                <button id="btn_start_action" class="btn btn-success rounded-pill px-4 shadow-sm" style="display:none;"><i class="fas fa-play me-1"></i> Iniciar Ciclo</button>
                <button id="btn_close_action" class="btn btn-dark rounded-pill px-4 shadow-sm" style="display:none;"><i class="fas fa-flag-checkered me-1"></i> Finalizar Ciclo</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/diplomatic/public/assets/js/academic_cohortes.js"></script>