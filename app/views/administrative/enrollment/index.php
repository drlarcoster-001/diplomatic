<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/views/administrative/enrollment/index.php
 * PROPÓSITO: Control de matrícula oficial por cohorte y diplomado.
 * VERSIÓN: 1.0.0
 */
?>
<link rel="stylesheet" href="/diplomatic/public/assets/css/administrative_enrollment.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size: 0.85rem;">
            <li class="breadcrumb-item"><a href="/diplomatic/public/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="/diplomatic/public/administrative" class="text-decoration-none text-muted">Panel Administrativo</a></li>
            <li class="breadcrumb-item active fw-bold text-success" aria-current="page">Matrícula</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Matrícula Estudiantil</h2>
            <p class="text-muted small">Relación oficial de alumnos inscritos en programas académicos.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/diplomatic/public/administrative" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">Volver</a>
            <button class="btn btn-success rounded-pill px-4 shadow-sm"><i class="bi bi-file-earmark-excel me-1"></i> Exportar</button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body row g-3">
            <div class="col-md-4">
                <select class="form-select form-select-sm" id="filterDiplomaEnroll">
                    <option value="">-- Todos los Diplomados --</option>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select form-select-sm" id="filterCohortEnroll">
                    <option value="">-- Todas las Cohortes --</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" placeholder="Buscar por expediente o nombre...">
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-enroll-green small fw-bold text-uppercase">
                    <tr>
                        <th class="ps-4">Expediente</th>
                        <th>Estudiante</th>
                        <th>Programa / Cohorte</th>
                        <th class="text-center">Estado Acad.</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <tr class="text-center"><td colspan="5" class="py-5 text-muted italic">Cargando registros oficiales...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="/diplomatic/public/assets/js/administrative_enrollment.js"></script>