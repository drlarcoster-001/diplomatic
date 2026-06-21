<?php
/**
 * MÓDULO: FINANCIERO / PAGOS A PROVEEDORES
 * ARCHIVO: app/views/financial/pagos_proveedores/create.php
 * PROPÓSITO: Selecciona proveedor y fecha de pago para crear el pago BORRADOR.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$errorType = $_GET['error'] ?? '';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i>Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/financial" class="text-decoration-none text-muted">Financiero</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/financial/pagos-proveedores" class="text-decoration-none text-muted">Pagos a Proveedores</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary">Nuevo Pago</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Nuevo Pago a Proveedor</h2>
        <a href="<?= $basePath ?>/financial/pagos-proveedores" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if ($errorType === 'incompleto'): ?>
        <div class="alert alert-danger">Debes seleccionar un proveedor y una fecha de pago.</div>
    <?php elseif ($errorType === 'db'): ?>
        <div class="alert alert-danger">Ocurrió un error al crear el pago.</div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm" style="max-width:560px">
        <div class="card-body p-4">
            <form method="POST" action="<?= $basePath ?>/financial/pagos-proveedores/save">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Proveedor</label>
                    <select name="proveedor_id" class="form-select" required>
                        <option value="">Selecciona un proveedor...</option>
                        <?php foreach ($proveedores as $pr): ?>
                            <option value="<?= $pr['id'] ?>">
                                <?= htmlspecialchars($pr['nombre']) ?> — <?= htmlspecialchars($pr['rif_cedula']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold small">Fecha de Pago</label>
                    <input type="date" name="fecha_pago" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-bold">
                    <i class="bi bi-arrow-right-circle me-1"></i> Continuar
                </button>
            </form>
        </div>
    </div>
</div>