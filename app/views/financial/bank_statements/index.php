<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / ESTADOS DE CUENTA BANCARIOS
 * ARCHIVO: app/views/financial/bank_statements/index.php
 * PROPÓSITO: Vista principal del módulo con acceso a T-Pago y Movimientos Mercantil.
 * VERSIÓN: 1.0.0 - Creación inicial del módulo.
 */

declare(strict_types=1);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/financial_bank_statements.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">

    <!--{{-- Breadcrumb --}}-->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-light p-2 rounded shadow-sm border" style="font-size: 0.85rem;">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door"></i> Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/financial" class="text-decoration-none text-muted">Panel Financiero</a>
            </li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Estados de Cuenta Bancarios</li>
        </ol>
    </nav>

    <!--{{-- Encabezado --}}-->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <i class="bi bi-bank me-2" style="color: #0dcaf0;"></i>Estados de Cuenta Bancarios
            </h2>
            <p class="text-muted small mb-0">Carga y administración de archivos bancarios para conciliación de pagos.</p>
        </div>
        <a href="<?= $basePath ?>/financial" class="btn btn-outline-secondary fw-bold rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!--{{-- Tarjetas --}}-->
    <div class="row g-4 justify-content-center">

        <!--{{-- Tarjeta T-Pago --}}-->
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/bank_statements/tpago" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center bank-card" style="border-top: 4px solid #198754 !important;">
                    <div class="mx-auto mb-3 p-3 rounded-circle" style="width: fit-content; background-color: rgba(25, 135, 84, 0.1);">
                        <i class="bi bi-phone-fill fs-1 text-success"></i>
                    </div>
                    <h5 class="fw-bold text-success mb-2">Archivo T-Pago</h5>
                    <p class="text-muted small mb-0">
                        Gestión de transacciones de Pago Móvil (T-Pago). Visualiza, filtra y carga los estados de cuenta bancarios de transferencias móviles.
                    </p>
                    <div class="mt-3">
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill small">
                            <i class="bi bi-table me-1"></i> tbl_financial_bank_transactions_mobile
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!--{{-- Tarjeta Movimientos --}}-->
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/bank_statements/movimientos" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center bank-card" style="border-top: 4px solid #0dcaf0 !important;">
                    <div class="mx-auto mb-3 p-3 rounded-circle" style="width: fit-content; background-color: rgba(13, 202, 240, 0.1);">
                        <i class="bi bi-bank fs-1" style="color: #0dcaf0;"></i>
                    </div>
                    <h5 class="fw-bold mb-2" style="color: #0dcaf0;">Archivo de Movimientos</h5>
                    <p class="text-muted small mb-0">
                        Gestión de movimientos bancarios del Banco Mercantil. Visualiza, filtra y carga los estados de cuenta con detalle de operaciones.
                    </p>
                    <div class="mt-3">
                        <span class="badge bg-opacity-10 px-3 py-2 rounded-pill small" style="background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                            <i class="bi bi-table me-1"></i> tbl_financial_bank_transactions_account
                        </span>
                    </div>
                </div>
            </a>
        </div>

    </div>
</div>