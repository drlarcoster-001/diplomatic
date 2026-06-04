<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/profesores/create.php
 * Propósito: Formulario de registro inicial para nuevos docentes.
 * Version: 1.2.0 - Integración de Breadcrumbs y estandarización de rutas dinámicas.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

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
                <a href="<?= $basePath ?>/academic/profesores" class="text-decoration-none text-muted">Profesores</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Nuevo Profesor</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Nuevo Registro de Docente</h2>
            <p class="text-muted small">Inicie el registro básico para habilitar el expediente profesional.</p>
        </div>
        <a href="<?= $basePath ?>/academic/profesores" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-x-circle me-1"></i> Cancelar
        </a>
    </div>

    <div class="card border-0 shadow-sm col-lg-6 mx-auto">
        <div class="card-body p-5">
            <form action="<?= $basePath ?>/academic/profesores/save" method="POST" autocomplete="off">
                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase">Número de Identificación</label>
                    <input type="text" name="identification" class="form-control form-control-lg" placeholder="DNI / Cédula / Pasaporte" required autofocus>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-bold text-uppercase">Nombres</label>
                        <input type="text" name="first_name" class="form-control form-control-lg" placeholder="Ej: Juan Antonio" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label small fw-bold text-uppercase">Apellidos</label>
                        <input type="text" name="last_name" class="form-control form-control-lg" placeholder="Ej: Pérez García" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-primary">TIPO DE PROFESOR</label>
                    <select name="professor_type" class="form-select form-select-lg" required>
                        <option value="" selected disabled>Seleccione...</option>
                        <option value="Docente">Docente</option>
                        <option value="Coordinador">Coordinador</option>
                        <option value="Invitado">Invitado</option>
                        <option value="Tutor">Tutor</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow">
                    Crear Perfil <i class="bi bi-arrow-right-circle ms-2"></i>
                </button>
            </form>
        </div>
    </div>
</div>