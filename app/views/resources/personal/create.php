<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * Archivo: app/views/resources/personal/create.php
 * Propósito: Formulario de registro inicial de nuevo personal operativo.
 * Versión: 1.0.0
 *
 * @var array $tipos
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<div class="container-fluid py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources" class="text-decoration-none text-muted">Panel de Recursos</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources/personal" class="text-decoration-none text-muted">Personal</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#a855f7;">Nuevo Registro</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Nuevo Registro de Personal</h2>
            <p class="text-muted small">Inicie el registro básico para habilitar el expediente completo.</p>
        </div>
        <a href="<?= $basePath ?>/resources/personal" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-x-circle me-1"></i> Cancelar
        </a>
    </div>

    <div class="card border-0 shadow-sm col-lg-6 mx-auto" style="border-top: 4px solid #a855f7 !important;">
        <div class="card-body p-5">
            <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
                <div class="alert alert-danger rounded-3 mb-4">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Ya existe un registro con esa cédula de identidad.
                </div>
            <?php endif; ?>

            <form action="<?= $basePath ?>/resources/personal/save" method="POST" autocomplete="off">

                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase">Cédula de Identidad</label>
                    <input type="text" name="document_id" class="form-control form-control-lg" placeholder="Ej: V-12345678" required autofocus>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-bold text-uppercase">Nombres</label>
                        <input type="text" name="first_name" class="form-control form-control-lg" placeholder="Ej: María José" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-bold text-uppercase">Apellidos</label>
                        <input type="text" name="last_name" class="form-control form-control-lg" placeholder="Ej: González Pérez" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase" style="color:#a855f7;">Tipo de Personal</label>
                    <select name="tipo_personal_id" class="form-select form-select-lg" required>
                        <option value="" disabled selected>Seleccione el tipo...</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase">Fecha de Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control form-control-lg" value="<?= date('Y-m-d') ?>">
                </div>

                <button type="submit" class="btn btn-lg w-100 rounded-pill shadow text-white" style="background:#a855f7;">
                    Crear Expediente <i class="bi bi-arrow-right-circle ms-2"></i>
                </button>
            </form>
        </div>
    </div>
</div>