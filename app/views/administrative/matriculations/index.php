<?php
/**
 * MÓDULO: ADMINISTRATIVO / MATRÍCULAS
 * ARCHIVO: app/views/administrative/matriculations/index.php
 * PROPÓSITO: Panel principal de Matrícula Académica (Alternativa 2: Tarjeta Dividida).
 * VERSIÓN: 2.2.1 - Código HTML limpio con separación de preocupaciones (CSS/JS externos).
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
                <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Matrícula Académica</li>
            </ol>
        </nav>
        <a href="<?= $basePath ?>/administrative" class="btn btn-outline-secondary btn-sm fw-bold shadow-sm">
            <i class="bi bi-arrow-left-circle me-1"></i> Volver
        </a>
    </div>

    <div class="mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Matrícula Académica</h2>
        <p class="text-muted small mb-0">Haga clic en el diplomado para gestionar notas o en el ícono de impresora para la asistencia.</p>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 py-2" id="search-cohort" placeholder="Buscar diplomado o cohorte...">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <?php if (!empty($data['cohorts'])): ?>
                <?php foreach ($data['cohorts'] as $cohort): ?>
                    
                    <div class="card mb-3 diplomado-card cohort-card-horizontal shadow-sm overflow-hidden">
                        <div class="d-flex align-items-stretch" style="min-height: 100px;">
                            
                            <a href="<?= $basePath ?>/administrative/matriculations/manage?id=<?= $cohort['offering_internal_id'] ?>" 
                               class="flex-grow-1 text-decoration-none text-dark p-3 p-md-4 card-main-action d-flex align-items-center"
                               title="Gestionar Calificaciones">
                                <div>
                                    <h4 class="fw-bold mb-1 text-dark" style="font-size: 1.15rem;">
                                        <?= htmlspecialchars($cohort['diplomado_name']) ?>
                                    </h4>
                                    <h5 class="text-muted mt-2 mb-0" style="font-size: 0.9rem;">
                                        <span class="badge bg-light text-primary border me-2">
                                            <i class="bi bi-hash"></i> <?= htmlspecialchars($cohort['cohort_code']) ?>
                                        </span>
                                        <i class="bi bi-people-fill text-secondary"></i> <?= $cohort['total_matriculados'] ?> Alumnos matriculados
                                    </h5>
                                </div>
                            </a>

                            <a href="<?= $basePath ?>/administrative/matriculations/imprimirListado?id=<?= $cohort['offering_internal_id'] ?>" 
                               target="_blank" 
                               class="d-flex align-items-center justify-content-center border-start card-print-action px-3 px-md-4 text-decoration-none" 
                               title="Imprimir Lista de Asistencia">
                                <i class="bi bi-printer fs-3"></i>
                            </a>

                        </div>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded shadow-sm border">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-dark">No hay diplomados activos</h5>
                    <p class="text-muted">Actualmente no existen cohortes con matrículas para gestionar.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script> const BASE_URL = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/assets/js/administrative_matriculations.js"></script>