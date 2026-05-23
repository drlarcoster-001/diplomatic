<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/campuses/index.php
 * Propósito: Interfaz maestra para la administración de sedes con navegación jerárquica.
 * Version: 1.2.0 - Integración de Breadcrumbs y dinamización de rutas con basePath.
 */

// Definición de ruta base para consistencia en enlaces
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_cohortes.css">

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
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Sedes</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Administración de Sedes</h2>
            <p class="text-muted small">Gestión de ubicaciones físicas y plataformas virtuales.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnOpenNuevoCampus" data-bs-toggle="modal" data-bs-target="#modalCampusForm">
                <i class="fas fa-plus me-1"></i> Nueva Sede
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= $basePath ?>/academic/campuses" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Buscar sede por nombre..." value="<?= htmlspecialchars($search ?? '') ?>">
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
                        <th class="ps-4" style="width: 80px;">ID</th>
                        <th>Nombre de la Sede</th>
                        <th>Fecha de Registro</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($campuses)): ?>
                        <tr><td colspan="4" class="text-center py-4">No se encontraron sedes registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($campuses as $c): ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?= $c['id'] ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($c['name']) ?></td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-white border text-primary btn-edit-campus" data-id="<?= $c['id'] ?>" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-white border text-danger btn-delete-campus" data-id="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" title="Eliminar">
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

<div class="modal fade" id="modalCampusForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formCampus" action="<?= $basePath ?>/academic/campuses/save" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white fw-bold">Información de la Sede</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="campus_field_id">
                <div class="mb-3">
                    <label class="form-label fw-bold small">NOMBRE DE LA SEDE</label>
                    <input type="text" name="name" id="campus_field_name" class="form-control" placeholder="Ej: Sede Principal, Campus Virtual..." required>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/academic_campuses.js?v=<?= time() ?>"></script>