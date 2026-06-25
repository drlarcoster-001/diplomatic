<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / OPERACIONES DE EGRESO
 * ARCHIVO: app/views/financial/egresos/index.php
 * PROPÓSITO: Subindex de módulos de egreso: Dashboard Financiero y Libro de Egresos.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/financial.css">

<div class="container-fluid py-4">

    <!-- MIGA DE PAN -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i>Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/financial" class="text-decoration-none text-muted">Panel Financiero</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary">Operaciones de Egreso</li>
        </ol>
    </nav>

    <!-- ENCABEZADO -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Operaciones de Egreso</h2>
            <p class="text-muted small">Dashboard financiero y registro detallado de egresos.</p>
        </div>
        <a href="<?= htmlspecialchars($basePath) ?>/financial" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-4">

        <!-- Dashboard Financiero -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/dashboard" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #6f42c1 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(111,66,193,0.1); color: #6f42c1;">
                        <i class="bi bi-bar-chart-line-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #6f42c1;">Dashboard</h5>
                    <p class="text-muted small mb-0">Resumen de ingresos, egresos y saldo del período actual.</p>
                </div>
            </a>
        </div>

        <!-- Libro de Egresos -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/libro-egresos" class="card-financial-link d-block h-100">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #dc3545 !important;">
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-journal-minus fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-danger">Libro de Egresos</h5>
                    <p class="text-muted small mb-0">Registro detallado de todos los pagos realizados.</p>
                </div>
            </a>
        </div>

    </div>
</div>