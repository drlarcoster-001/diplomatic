<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/views/academic/centros_medicos/edit.php
 * PROPÓSITO: Formulario de edición de un Centro Médico existente (página separada, no modal).
 * VERSIÓN: 1.2.0 - Fix de rutas de assets (sin "/public/" duplicado) y breadcrumb agregado.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_centros_medicos.css">

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
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/academic/centros-medicos" class="text-decoration-none text-muted">Centros Médicos</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Editar</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Editar Centro Médico</h2>
            <p class="text-muted small">Actualizar los datos del centro médico.</p>
        </div>
        <a href="<?= $basePath ?>/academic/centros-medicos" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="<?= $basePath ?>/academic/centros-medicos/update" method="POST" class="row g-3">
                <input type="hidden" name="id" value="<?= (int) $centro['id'] ?>">
                <div class="col-12">
                    <label class="form-label fw-bold small text-uppercase">Nombre</label>
                    <input type="text" name="nombre" class="form-control" maxlength="150" required autofocus
                           value="<?= htmlspecialchars($centro['nombre']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold small text-uppercase">Dirección</label>
                    <input type="text" name="direccion" class="form-control" maxlength="255"
                           value="<?= htmlspecialchars($centro['direccion'] ?? '') ?>">
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Actualizar</button>
                    <a href="<?= $basePath ?>/academic/centros-medicos" class="btn btn-secondary rounded-pill px-4">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/academic_centros_medicos.js?v=<?= time() ?>"></script>