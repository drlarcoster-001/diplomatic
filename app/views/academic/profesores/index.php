<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/profesores/index.php
 * Propósito: Directorio maestro de profesores y visualización de fichas de perfil.
 */
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="/diplomatic/public/assets/css/academic_profesores.css">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Directorio de Profesores</h2>
            <p class="text-muted small">Gestión de personal docente, invitados y coordinadores.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/diplomatic/public/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <a href="/diplomatic/public/academic/profesores/create" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="fas fa-plus me-1"></i> Nuevo
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="/diplomatic/public/academic/profesores" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Buscar por identificación o nombre..." value="<?= htmlspecialchars($search ?? '') ?>">
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
                        <th class="ps-4">Perfil</th>
                        <th>Identificación</th>
                        <th>Nombre Completo</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($profesores)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No hay profesores registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($profesores as $p): ?>
                            <tr class="profesor-row" data-id="<?= $p['id'] ?>" style="cursor:pointer;">
                                <td class="ps-4">
                                    <?php 
                                        $avatar = !empty($p['photo_path']) ? $p['photo_path'] : 'https://ui-avatars.com/api/?name=' . urlencode($p['first_name'] . ' ' . $p['last_name']) . '&background=4e73df&color=fff&size=150'; 
                                    ?>
                                    <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="rounded-circle object-fit-cover shadow-sm" width="45" height="45">
                                </td>
                                <td class="fw-bold text-secondary"><?= htmlspecialchars($p['identification']) ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($p['full_name']) ?></td>
                                <td>
                                    <?php 
                                        $typeBadge = match($p['professor_type']) {
                                            'Docente'     => 'bg-primary',
                                            'Coordinador' => 'bg-info text-dark',
                                            'Invitado'    => 'bg-warning text-dark',
                                            'Tutor'       => 'bg-success',
                                            default       => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $typeBadge ?> rounded-pill px-3"><?= htmlspecialchars($p['professor_type']) ?></span>
                                </td>
                                <td><span class="badge bg-light text-success border border-success rounded-pill px-3">Activo</span></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="/diplomatic/public/academic/profesores/edit?id=<?= $p['id'] ?>" class="btn btn-sm btn-white border text-primary" title="Editar Expediente">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-white border text-danger btn-delete" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['full_name']) ?>" title="Eliminar">
                                            <i class="bi bi-trash"></i>
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

<div class="modal fade" id="modalProfesorPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0 py-3">
                <h5 class="modal-title fw-bold text-secondary"><i class="bi bi-person-badge me-2"></i> Ficha Resumen del Docente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-md-4 bg-primary bg-opacity-10 p-4 text-center border-end">
                        <img id="prev_photo" src="" class="rounded-circle object-fit-cover shadow mb-3 border border-white border-3" width="120" height="120" alt="Foto">
                        <h5 class="fw-bold text-dark mb-1" id="prev_name">--</h5>
                        <p class="text-primary fw-bold small mb-3" id="prev_type">--</p>
                        
                        <div class="text-start mt-4">
                            <p class="small text-muted mb-1 text-uppercase fw-bold">Identificación</p>
                            <p class="mb-3 fw-medium" id="prev_id">--</p>

                            <p class="small text-muted mb-1 text-uppercase fw-bold">Contacto</p>
                            <p class="mb-1 small"><i class="bi bi-envelope-fill text-secondary me-2"></i> <span id="prev_email">No registrado</span></p>
                            <p class="mb-0 small"><i class="bi bi-telephone-fill text-secondary me-2"></i> <span id="prev_phone">No registrado</span></p>
                        </div>
                    </div>
                    
                    <div class="col-md-8 p-4">
                        <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-secondary">Perfil Profesional</h6>
                        <p class="small text-justify text-muted mb-4" id="prev_bio">Sin biografía registrada.</p>
                        
                        <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-secondary">Especialidades Principales</h6>
                        <div id="prev_specialties" class="mb-4">
                            <span class="text-muted small">Cargando...</span>
                        </div>

                        <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-secondary">Última Formación Destacada</h6>
                        <div id="prev_formation" class="small">
                            <span class="text-muted">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <a href="#" id="btn_full_profile" class="btn btn-outline-primary rounded-pill px-4">Ver Expediente Completo</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/diplomatic/public/assets/js/academic_profesores.js"></script>