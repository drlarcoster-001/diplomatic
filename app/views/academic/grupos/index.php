<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/grupos/index.php
 * Propósito: Listado maestro de configuración de Grupos Académicos.
 */
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="/diplomatic/public/assets/css/academic_grupos.css">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Catálogo de Grupos</h2>
            <p class="text-muted small">Configuración de modalidades y clasificaciones organizativas.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/diplomatic/public/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnOpenNuevo" data-bs-toggle="modal" data-bs-target="#modalGrupoForm">
                <i class="fas fa-plus me-1"></i> Nuevo Grupo
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="/diplomatic/public/academic/grupos" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Buscar por nombre o modalidad..." value="<?= htmlspecialchars($search ?? '') ?>">
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
                        <th class="ps-4">Nombre del Grupo</th>
                        <th>Modalidad</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grupos)): ?>
                        <tr><td colspan="5" class="text-center py-4">No hay grupos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($grupos as $g): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($g['name']) ?></td>
                                <td>
                                    <?php 
                                        $modBadge = match($g['modality']) {
                                            'Presencial' => 'bg-info',
                                            'Virtual'    => 'bg-primary',
                                            'Mixta'      => 'bg-warning text-dark',
                                            default      => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $modBadge ?> rounded-pill"><?= $g['modality'] ?></span>
                                </td>
                                <td class="text-muted small"><?= htmlspecialchars($g['description'] ?: 'Sin descripción') ?></td>
                                <td><span class="badge bg-success rounded-pill">Activo</span></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group-custom shadow-sm">
                                        <button type="button" class="btn btn-edit" data-id="<?= $g['id'] ?>" title="Editar">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button type="button" class="btn btn-delete" data-id="<?= $g['id'] ?>" data-name="<?= htmlspecialchars($g['name']) ?>" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
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

<div class="modal fade" id="modalGrupoForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formGrupo" action="/diplomatic/public/academic/grupos/save" method="POST" class="modal-content border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white fw-bold">Gestión de Grupo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="field_id">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold small text-uppercase">Nombre del Grupo</label>
                        <input type="text" name="name" id="field_name" class="form-control" placeholder="Ej. Grupo Viernes" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-uppercase">Modalidad</label>
                        <select name="modality" id="field_modality" class="form-select" required>
                            <option value="Presencial">Presencial</option>
                            <option value="Virtual">Virtual</option>
                            <option value="Mixta">Mixta</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-uppercase">Descripción (Opcional)</label>
                        <textarea name="description" id="field_desc" class="form-control" rows="3" placeholder="Detalles operativos..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar Grupo</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/diplomatic/public/assets/js/academic_grupos.js"></script>