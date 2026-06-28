<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/periodos/index.php
 * Propósito: Gestión integral de períodos institucionales. Un período agrupa múltiples cohortes bajo un mismo contexto operativo y controla la ventana global de inscripciones del programa. Hasta tres períodos pueden estar activos simultáneamente en distintas fases.
 * Version: 1.1.0
 */
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="/diplomatic/public/assets/css/academic_periodos.css">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Períodos Institucionales</h2>
            <p class="text-muted small">Gestión de períodos académicos que agrupan cohortes del programa.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/diplomatic/public/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <button class="btn btn-success rounded-pill px-4 shadow-sm" id="btnOpenNuevo" data-bs-toggle="modal" data-bs-target="#modalPeriodoForm">
                <i class="fas fa-plus me-1"></i> Nuevo Período
            </button>
        </div>
    </div>

    <!-- Buscador -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="/diplomatic/public/academic/periodos" class="row g-3">
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

    <!-- Tabla principal -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small fw-bold text-secondary text-uppercase">
                    <tr>
                        <th class="ps-4">Código</th>
                        <th>Nombre del Período</th>
                        <th>Inscripciones</th>
                        <th>Inicio - Fin de Período</th>
                        <th>Cohortes</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($periodos)): ?>
                        <tr><td colspan="7" class="text-center py-4">No hay períodos institucionales registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($periodos as $p): ?>
                            <?php $estadoClean = strtolower(trim($p['estado'])); ?>
                            <tr class="periodo-row" data-id="<?= $p['id'] ?>" style="cursor:pointer;">
                                <td class="ps-4 fw-bold text-success"><?= htmlspecialchars($p['periodo_code']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($p['nombre']) ?></td>
                                <td class="small">
                                    <?php if (!empty($p['apertura_inscripcion']) && !empty($p['cierre_inscripcion'])): ?>
                                        <?= date('d/m/Y', strtotime($p['apertura_inscripcion'])) ?> - <?= date('d/m/Y', strtotime($p['cierre_inscripcion'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">No definida</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary rounded-pill"><?= (int)($p['total_cohortes'] ?? 0) ?></span>
                                </td>
                                <td>
                                    <?php
                                        $bg = match($estadoClean) {
                                            'planificado' => 'bg-secondary',
                                            'activo'      => 'bg-success',
                                            'finalizado'  => 'bg-dark',
                                            default       => 'bg-light text-dark'
                                        };
                                    ?>
                                    <span class="badge rounded-pill <?= $bg ?>"><?= htmlspecialchars($p['estado']) ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if (true): ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-white border text-success btn-edit" data-id="<?= $p['id'] ?>" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($estadoClean === 'activo'): ?>
                                            <a href="/diplomatic/public/academic/periodos/changeStatus?id=<?= $p['id'] ?>&status=Planificado"
                                            class="btn btn-sm btn-white border text-secondary btn-change-status"
                                            data-label="Planificado" data-color="#6c757d" title="Pasar a Planificado">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </a>

                                            <a href="/diplomatic/public/academic/periodos/changeStatus?id=<?= $p['id'] ?>&status=Finalizado"
                                            class="btn btn-sm btn-white border text-dark btn-change-status"
                                            data-label="Finalizado" data-color="#212529" title="Finalizar período">
                                                <i class="bi bi-lock-fill"></i>
                                            </a>

                                            <a href="/diplomatic/public/academic/periodos/changeStatus?id=<?= $p['id'] ?>&status=Activo"
                                            class="btn btn-sm btn-white border text-success btn-change-status"
                                            data-label="Activo" data-color="#198754" title="Activar período">
                                                <i class="bi bi-play-fill"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-white border text-danger btn-delete" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['nombre']) ?>" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>

                                    <?php else: ?>
                                        <div class="d-inline-block text-muted pe-3" title="Período Finalizado: No permite modificaciones">
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

<!-- Modal: Crear / Editar Período -->
<div class="modal fade" id="modalPeriodoForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formPeriodo" action="/diplomatic/public/academic/periodos/save" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-white fw-bold" id="modalPeriodoTitle">Registrar Nuevo Período</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="field_id">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">CÓDIGO</label>
                        <input type="text" name="periodo_code" id="field_code" class="form-control" placeholder="Ej: 2026-COHORTE-15" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold small">NOMBRE</label>
                        <input type="text" name="nombre" id="field_nombre" class="form-control" placeholder="Ej: Año 2026 - Cohorte 15" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">INICIO DE PERÍODO</label>
                        <input type="date" name="fecha_inicio" id="field_inicio" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">FIN DE PERÍODO</label>
                        <input type="date" name="fecha_fin" id="field_fin" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">APERTURA INSCRIPCIÓN</label>
                        <input type="date" name="apertura_inscripcion" id="field_apertura" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">CIERRE INSCRIPCIÓN</label>
                        <input type="date" name="cierre_inscripcion" id="field_cierre" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">DESCRIPCIÓN / OBSERVACIONES</label>
                        <textarea name="descripcion" id="field_descripcion" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success rounded-pill px-4">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Ficha de Período -->
<div class="modal fade" id="modalPeriodoPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title text-white">Ficha del Período</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h4 id="prev_nombre" class="fw-bold text-success mb-1"></h4>
                <p id="prev_code" class="text-muted small mb-3"></p>
                <hr>
                <div class="row g-3">
                    <div class="col-6 small text-muted">INICIO DE PERÍODO: <b id="prev_inicio" class="text-dark"></b></div>
                    <div class="col-6 small text-muted">FIN DE PERÍODO: <b id="prev_fin" class="text-dark"></b></div>
                    <div class="col-6 small text-muted">APERTURA INSCRIPCIÓN: <b id="prev_apertura" class="text-dark"></b></div>
                    <div class="col-6 small text-muted">CIERRE INSCRIPCIÓN: <b id="prev_cierre" class="text-dark"></b></div>
                    <div class="col-6 small text-muted">COHORTES VINCULADAS: <b id="prev_cohortes" class="text-dark"></b></div>
                    <div class="col-6 small text-muted">ESTADO: <b id="prev_estado" class="text-dark"></b></div>
                    <div class="col-12 small text-muted">OBSERVACIONES: <p id="prev_descripcion" class="text-dark mb-0 small"></p></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn_activar" class="btn btn-success btn-sm rounded-pill px-3">Activar Período</button>
                <button type="button" id="btn_finalizar" class="btn btn-danger btn-sm rounded-pill px-3">Finalizar Período</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/diplomatic/public/assets/js/academic_periodos.js?v=<?= time() ?>"></script>