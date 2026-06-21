<?php
/**
 * MÓDULO: FINANCIERO / ÓRDENES DE PAGO
 * ARCHIVO: app/views/financial/ordenes_pago/create.php
 * PROPÓSITO: Crear una orden de pago directa (sin pasar por Nómina ni Pagos
 *            a Proveedores). Cae directo en PENDIENTE de aprobación.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
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
                <a href="<?= $basePath ?>/financial/ordenes-pago" class="text-decoration-none text-muted">Órdenes de Pago</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary">Nueva Orden Directa</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Nueva Orden de Pago Directa</h2>
        <a href="<?= $basePath ?>/financial/ordenes-pago" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="alert alert-info" style="max-width:560px">
        <i class="bi bi-info-circle me-1"></i>
        Esta orden se crea directamente, sin pasar por Nómina ni Pagos a Proveedores, y queda
        de inmediato en estado <strong>PENDIENTE</strong> de aprobación.
    </div>

    <?php if ($errorType === 'incompleto'): ?>
        <div class="alert alert-danger" style="max-width:560px">Completa todos los campos.</div>
    <?php elseif ($errorType === 'db'): ?>
        <div class="alert alert-danger" style="max-width:560px">Ocurrió un error al crear la orden.</div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm" style="max-width:560px">
        <div class="card-body p-4">
            <form method="POST" action="<?= $basePath ?>/financial/ordenes-pago/save">
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
                <div class="mb-3">
                    <label class="form-label fw-bold small">Concepto</label>
                    <input type="text" name="concepto" class="form-control" placeholder="Ej: Reparación de aire acondicionado" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Monto (USD)</label>
                    <input type="number" name="monto" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold small">Fecha de Pago</label>
                    <input type="date" name="fecha_pago" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-bold">
                    <i class="bi bi-check-circle me-1"></i> Crear Orden de Pago
                </button>
            </form>
        </div>
    </div>
</div>