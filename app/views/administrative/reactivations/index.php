<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / EXCEPCIONES
 * ARCHIVO: app/views/administrative/reactivations/index.php
 * PROPÓSITO: Panel de selección de cohortes para reactivación masiva con filtro de búsqueda.
 * VERSIÓN: 2.1.0 - Implementación de buscador de cohortes y diseño de tarjetas horizontales.
 */

declare(strict_types=1);
$basePath = '/diplomatic/public';
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/administrative_matriculations.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb" class="m-0">
            <ol class="breadcrumb bg-light p-2 rounded shadow-sm border mb-0" style="font-size: 0.85rem;">
                <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door"></i> Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= $basePath ?>/administrative" class="text-decoration-none text-muted">Panel Administrativo</a></li>
                <li class="breadcrumb-item active text-danger fw-bold" aria-current="page">Reactivar Matrículas</li>
            </ol>
        </nav>
        <a href="<?= $basePath ?>/administrative" class="btn btn-outline-secondary btn-sm fw-bold shadow-sm">
            <i class="bi bi-arrow-left-circle me-1"></i> Volver
        </a>
    </div>

    <div class="mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Reactivación Académica Masiva</h2>
        <p class="text-muted small mb-0">Seleccione un diplomado para restaurar el estatus de todos los estudiantes a <b>CURSANDO</b>.</p>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 py-2" id="search-cohort" placeholder="Buscar diplomado o cohorte para reactivar...">
            </div>
        </div>
    </div>

    <div class="row" id="cohorts-container">
        <div class="col-12">
            <?php if (!empty($data['cohorts'])): ?>
                <?php foreach ($data['cohorts'] as $cohort): ?>
                    
                    <div class="card mb-3 diplomado-card cohort-card-horizontal shadow-sm overflow-hidden animate__animated animate__fadeIn">
                        <div class="d-flex align-items-stretch" style="min-height: 100px;">
                            
                            <a href="<?= $basePath ?>/administrative/reactivations/manage?id=<?= $cohort['offering_internal_id'] ?>" 
                               class="flex-grow-1 text-decoration-none text-dark p-3 p-md-4 card-main-action d-flex align-items-center">
                                <div>
                                    <h4 class="fw-bold mb-1 text-dark cohort-title" style="font-size: 1.15rem;">
                                        <?= htmlspecialchars($cohort['diplomado_name']) ?>
                                    </h4>
                                    <h5 class="text-muted mt-2 mb-0" style="font-size: 0.9rem;">
                                        <span class="badge bg-light text-danger border me-2">
                                            <i class="bi bi-hash"></i> <?= htmlspecialchars($cohort['cohort_code']) ?>
                                        </span>
                                        <i class="bi bi-person-x-fill text-secondary"></i> <?= $cohort['total_reactivables'] ?> Registros inactivos/egresados
                                    </h5>
                                </div>
                            </a>

                            <div class="d-flex align-items-center justify-content-center border-start bg-light px-3 px-md-4">
                                <i class="bi bi-chevron-right fs-4 text-muted"></i>
                            </div>

                        </div>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded shadow-sm border">
                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-dark">Todo está al día</h5>
                    <p class="text-muted">No se encontraron cohortes con estudiantes en estados que requieran reactivación.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/administrative_reactivations.js"></script>