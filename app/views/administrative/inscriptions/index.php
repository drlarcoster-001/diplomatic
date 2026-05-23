<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/views/administrative/inscriptions/index.php
 * PROPÓSITO: Catálogo de Ofertas Académicas para inicio de inscripción manual (Grid de 3 columnas).
 * VERSIÓN: 1.3.0 - Ajuste de rejilla a 3 columnas y optimización de jerarquía visual.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/administrative_inscriptions.css?v=<?= time() ?>">

<div class="container-fluid py-4 px-lg-5">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-white p-3 rounded-pill shadow-sm border">
            <li class="breadcrumb-item small"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i>Inicio</a></li>
            <li class="breadcrumb-item small"><a href="<?= $basePath ?>/administrative" class="text-decoration-none text-muted">Administración</a></li>
            <li class="breadcrumb-item small active fw-bold text-primary" aria-current="page">Catálogo de Inscripciones</li>
        </ol>
    </nav>

    <div class="row align-items-center mb-5 g-3">
        <div class="col-lg-5">
            <h2 class="fw-black text-dark mb-1 tracking-tight">OFERTAS DISPONIBLES</h2>
            <p class="text-muted small mb-0">Gestión de inscripciones manuales para diplomados activos.</p>
        </div>
        
        <div class="col-lg-4">
            <div class="input-group shadow-sm rounded-pill overflow-hidden border-2 border-primary-subtle">
                <span class="input-group-text bg-white border-0"><i class="bi bi-search text-primary"></i></span>
                <input type="text" id="searchOffering" class="form-control border-0 ps-0 py-2" placeholder="Buscar diplomado o cohorte...">
            </div>
        </div>

        <div class="col-lg-3 text-lg-end">
            <a href="<?= htmlspecialchars($basePath) ?>/administrative" class="btn btn-white rounded-pill px-4 shadow-sm fw-bold border-2 border-light-subtle transition-all">
                <i class="bi bi-arrow-left me-1"></i> Volver a Panel Administrativo
            </a>
        </div>
    </div>

    <div class="row g-4" id="offeringsGrid">
        <?php if (!empty($openOfferings)): ?>
            <?php foreach ($openOfferings as $offer): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm offering-card transition-all position-relative" 
                         onclick="location.href='<?= htmlspecialchars($basePath) ?>/administrative/inscriptions/create?offering_id=<?= $offer['id'] ?>'">
                        
                        <div class="offering-header-line"></div>
                        
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <span class="badge-status-open">
                                    <i class="bi bi-circle-fill me-1 blink"></i> ABIERTA
                                </span>
                                <span class="badge bg-light text-muted border rounded-pill px-2 small">ID: <?= $offer['id'] ?></span>
                            </div>
                            
                            <h5 class="card-title-main fw-bold text-dark mb-2">
                                <?= htmlspecialchars($offer['diploma_name']) ?>
                            </h5>
                            
                            <?php if (!empty($offer['grupos_nombres'])): ?>
                                <div class="mb-3 p-2 bg-light rounded border border-light-subtle">
                                    <div class="text-primary fw-bold" style="font-size: 0.8rem;">
                                        <i class="bi bi-people-fill me-1"></i> <?= htmlspecialchars($offer['grupos_nombres']) ?>
                                    </div>
                                    <?php if (!empty($offer['grupos_descripciones'])): ?>
                                        <div class="text-muted mt-1" style="font-size: 0.75rem; line-height: 1.3;">
                                            <i class="bi bi-clock me-1"></i> <?= htmlspecialchars($offer['grupos_descripciones']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="text-primary-emphasis fw-semibold small mb-4 d-flex align-items-center">
                                <i class="bi bi-calendar3-event me-2"></i> <?= htmlspecialchars($offer['cohort_name']) ?>
                            </div>

                            <div class="offering-stats mt-auto pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-secondary small">Ocupación de Aula:</span>
                                    <?php 
                                        $available = (int)$offer['total_capacity'] - (int)$offer['enrolled_count'];
                                        $percent = ($offer['total_capacity'] > 0) ? ($offer['enrolled_count'] / $offer['total_capacity']) * 100 : 0;
                                    ?>
                                    <span class="fw-bold small <?= ($available <= 5) ? 'text-danger' : 'text-dark' ?>">
                                        <?= $available ?> cupos libres
                                    </span>
                                </div>
                                
                                <div class="progress mb-3" style="height: 8px;">
                                    <div class="progress-bar <?= ($percent > 85) ? 'bg-danger' : 'bg-primary' ?>" 
                                         role="progressbar" style="width: <?= $percent ?>%"></div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted smallest text-uppercase fw-bold">Cierre:</span>
                                    <span class="text-dark small fw-bold"><?= date('d/m/Y', strtotime($offer['registration_end'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-light border-0 p-3">
                            <button class="btn btn-primary w-100 rounded-pill fw-bold shadow-sm py-2 btn-enroll-action">
                                <i class="bi bi-plus-circle me-1"></i> Inscribir ahora
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="py-5 bg-white rounded-4 shadow-sm border-2 border-dashed">
                    <i class="bi bi-inboxes display-1 text-muted opacity-25"></i>
                    <h5 class="mt-4 fw-bold text-dark">No hay ofertas académicas disponibles</h5>
                    <p class="text-muted">Asegúrese de que existan ofertas con estatus "ABIERTA" en el sistema.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?= htmlspecialchars($basePath) ?>/assets/js/administrative_inscriptions.js"></script>