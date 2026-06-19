<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / PROVEEDORES
 * Archivo: app/views/financial/proveedores/index.php
 * Propósito: Directorio de proveedores externos del programa.
 * Versión: 1.0.0
 *
 * @var array  $proveedores
 * @var string $search
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_proveedores.css">

<div class="container-fluid py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/financial" class="text-decoration-none text-muted">Panel Financiero</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#fd7e14;">Proveedores</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Proveedores</h2>
            <p class="text-muted small">Catálogo de proveedores externos vinculados al programa de diplomados.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/financial" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="<?= $basePath ?>/financial/proveedores/create"
               class="btn rounded-pill px-4 shadow-sm text-white" style="background:#fd7e14;">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Proveedor
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= $basePath ?>/financial/proveedores" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Buscar por nombre, RIF o email..."
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
                        <th class="ps-4">Nombre</th>
                        <th>RIF / Cédula</th>
                        <th>Tipo</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proveedores)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No hay proveedores registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($proveedores as $p): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($p['nombre']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($p['rif_cedula']) ?></td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-1 text-white"
                                          style="background:<?= $p['tipo'] === 'Empresa' ? '#fd7e14' : '#6c757d' ?>; font-size:0.75rem;">
                                        <?= htmlspecialchars($p['tipo']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($p['email'] ?? '—') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($p['telefono'] ?? '—') ?></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="<?= $basePath ?>/financial/proveedores/edit?id=<?= $p['id'] ?>"
                                           class="btn btn-sm btn-white border text-warning" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-white border text-danger btn-delete"
                                                data-id="<?= $p['id'] ?>"
                                                data-name="<?= htmlspecialchars($p['nombre']) ?>"
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/financial_proveedores.js?v=<?= time() ?>"></script>
