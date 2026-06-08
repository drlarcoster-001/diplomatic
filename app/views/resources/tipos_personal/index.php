<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * Archivo: app/views/resources/tipos_personal/index.php
 * Propósito: Catálogo de tipos de personal con siglas institucionales.
 * Versión: 1.0.0
 *
 * @var array  $tipos
 * @var string $search
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_tipos_personal.css">

<div class="container-fluid py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources" class="text-decoration-none text-muted">Panel de Recursos</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#f59e0b;">Tipos de Personal</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Tipos de Personal</h2>
            <p class="text-muted small">Catálogo de tipos y siglas del personal operativo del programa.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/resources" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <button class="btn rounded-pill px-4 shadow-sm text-white" style="background:#f59e0b;"
                    id="btnNuevoTipo" data-bs-toggle="modal" data-bs-target="#modalTipoForm">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Tipo
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= $basePath ?>/resources/tipos-personal" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Buscar por nombre o siglas..." value="<?= htmlspecialchars($search ?? '') ?>">
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
                        <th class="ps-4" style="width:60px;">#</th>
                        <th style="width:100px;">Siglas</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th class="text-center" style="width:100px;">Personal</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tipos)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No hay tipos de personal registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tipos as $t): ?>
                            <tr>
                                <td class="ps-4 text-muted small">#<?= $t['id'] ?></td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 text-white fw-bold badge-siglas" style="background:#f59e0b;">
                                        <?= htmlspecialchars($t['siglas']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($t['nombre']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($t['descripcion'] ?? '—') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary rounded-pill"><?= (int)$t['total_personal'] ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-white border text-warning btn-edit"
                                                data-id="<?= $t['id'] ?>" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-white border text-danger btn-delete"
                                                data-id="<?= $t['id'] ?>"
                                                data-name="<?= htmlspecialchars($t['nombre']) ?>"
                                                data-count="<?= (int)$t['total_personal'] ?>"
                                                title="Eliminar">
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

<!-- Modal Crear / Editar -->
<div class="modal fade" id="modalTipoForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formTipo" action="<?= $basePath ?>/resources/tipos-personal/save" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white border-0" style="background:#f59e0b;">
                <h5 class="modal-title fw-bold text-white" id="modalTipoTitle">Nuevo Tipo de Personal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="field_id">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">NOMBRE</label>
                        <input type="text" name="nombre" id="field_nombre" class="form-control"
                               placeholder="Ej: Profesor teórico" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">SIGLAS <span class="text-muted">(3 letras)</span></label>
                        <input type="text" name="siglas" id="field_siglas" class="form-control text-uppercase fw-bold"
                               placeholder="PRO" maxlength="3" required
                               style="letter-spacing:3px; font-size:1.1rem; text-align:center;">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">DESCRIPCIÓN <span class="text-muted">(opcional)</span></label>
                        <textarea name="descripcion" id="field_descripcion" class="form-control" rows="2"
                                  placeholder="Descripción del rol..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn rounded-pill px-4 text-white" style="background:#f59e0b;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_tipos_personal.js?v=<?= time() ?>"></script>