<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / EGRESOS
 * Archivo: app/views/financial/gasto_categorias/index.php
 * Propósito: Catálogo de categorías de gasto institucional.
 * Versión: 1.0.0
 *
 * @var array  $categorias
 * @var string $search
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_gasto_categorias.css">

<div class="container-fluid py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/financial" class="text-decoration-none text-muted">Panel Financiero</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#198754;">Categorías de Gasto</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Categorías de Gasto</h2>
            <p class="text-muted small">Clasificación contable de alto nivel para los egresos institucionales.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/financial" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <button class="btn rounded-pill px-4 shadow-sm text-white" style="background:#198754;"
                    id="btnNueva" data-bs-toggle="modal" data-bs-target="#modalForm">
                <i class="bi bi-plus-lg me-1"></i> Nueva Categoría
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= $basePath ?>/financial/gasto-categorias" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Buscar por código o nombre..."
                               value="<?= htmlspecialchars($search ?? '') ?>">
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
                        <th class="ps-4" style="width:80px;">Código</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th class="text-center" style="width:120px;">Conceptos</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorias)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No hay categorías registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($categorias as $c): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge rounded-pill px-3 py-2 text-white fw-bold" style="background:#198754; letter-spacing:1px;">
                                        <?= htmlspecialchars($c['codigo']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($c['nombre']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($c['descripcion'] ?? '—') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary rounded-pill"><?= (int)$c['total_conceptos'] ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-white border text-success btn-edit"
                                                data-id="<?= $c['id'] ?>" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-white border text-danger btn-delete"
                                                data-id="<?= $c['id'] ?>"
                                                data-name="<?= htmlspecialchars($c['nombre']) ?>"
                                                data-count="<?= (int)$c['total_conceptos'] ?>"
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
<div class="modal fade" id="modalForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formCategoria" action="<?= $basePath ?>/financial/gasto-categorias/save" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white border-0" style="background:#198754;">
                <h5 class="modal-title fw-bold text-white" id="modalTitle">Nueva Categoría de Gasto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="field_id">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">CÓDIGO</label>
                        <input type="text" name="codigo" id="field_codigo" class="form-control fw-bold text-center"
                               placeholder="01" maxlength="3" required
                               style="letter-spacing:2px; font-size:1.1rem;">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label small fw-bold">NOMBRE</label>
                        <input type="text" name="nombre" id="field_nombre" class="form-control"
                               placeholder="Ej: Gastos de Personal" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">DESCRIPCIÓN <span class="text-muted">(opcional)</span></label>
                        <textarea name="descripcion" id="field_descripcion" class="form-control" rows="2"
                                  placeholder="Descripción de la categoría..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn rounded-pill px-4 text-white" style="background:#198754;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/financial_gasto_categorias.js?v=<?= time() ?>"></script>