<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/diplomados/create.php
 * Propósito: Formulario de registro con breadcrumbs e integración de Panel Académico.
 * Version: 1.4.9 - Inclusión de navegación jerárquica y rutas dinámicas.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_diplomados.css">

<div class="container py-4">
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
                <a href="<?= $basePath ?>/academic/diplomados" class="text-decoration-none text-muted">Catálogo</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Registrar Diplomado</li>
        </ol>
    </nav>

    <form action="<?= $basePath ?>/academic/diplomados/save" method="POST" id="formDiplomado">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 fw-bold mb-0 text-dark">Registrar Diplomado</h2>
                <p class="text-muted small">Defina el código institucional para iniciar el borrador del programa.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= $basePath ?>/academic/diplomados" class="btn btn-outline-secondary rounded-pill px-4">Volver</a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-save me-1"></i> Guardar
                </button>
            </div>
        </div>

        <div class="alert alert-info border-0 shadow-sm mb-4 small">
            <i class="bi bi-info-circle-fill me-2"></i> 
            <strong>Modo Borrador:</strong> Puede iniciar el registro solo con el Código y el Nombre. Use el botón <i class="bi bi-plus-circle"></i> para añadir requisitos o condiciones cuando esté listo.
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Código Institucional</label>
                                <input type="text" name="code" class="form-control" placeholder="Ej: TR-2026" maxlength="25" required>
                                <div class="form-text" style="font-size: 0.7rem;">Máximo 25 caracteres.</div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Nombre del Diplomado</label>
                                <textarea name="name" class="form-control" rows="2" placeholder="Nombre completo..." required></textarea>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label small fw-bold">Descripción / Objetivo</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="¿Cuál es el propósito científico?"></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Perfil del Aspirante (Dirigido a:)</label>
                                <textarea name="directed_to" class="form-control" rows="3" placeholder="Ej: Médicos, Enfermeros..."></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Carga Horaria Total</label>
                                <div class="input-group">
                                    <input type="number" name="total_hours" id="total_hours" class="form-control" value="0" min="0" max="2400" required>
                                    <span class="input-group-text bg-light text-muted small">Horas</span>
                                </div>
                                <div class="form-text" style="font-size: 0.7rem;">Rango permitido: 0 - 2400 horas.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-primary">Condiciones del Contrato</h6>
                        <button type="button" class="btn btn-sm btn-dark rounded-circle" onclick="addRow('condicionesContainer', 'conditions')">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <div class="card-body pt-0" id="condicionesContainer"></div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-primary">Requisitos de Ingreso</h6>
                        <button type="button" class="btn btn-sm btn-dark rounded-circle" onclick="addRow('requisitosContainer', 'requirements')">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <div class="card-body pt-0" id="requisitosContainer"></div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/academic_diplomados.js?v=<?= time() ?>"></script>