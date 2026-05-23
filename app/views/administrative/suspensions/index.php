<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / SUSPENSIONES
 * ARCHIVO: app/views/administrative/suspensions/index.php
 * PROPÓSITO: Panel principal con tarjetas centradas al 50% de ancho, apiladas verticalmente.
 * VERSIÓN: 1.3.0 - Layout: Tarjetas centradas al 50% (col-md-6) con apilamiento vertical.
 */

declare(strict_types=1);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/administrative_suspension.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/administrative">Panel Administrativo</a></li>
            <li class="breadcrumb-item active" aria-current="page">Suspensiones</li>
        </ol>
    </nav>

    <div class="row mb-4 justify-content-center">
        <div class="col-md-6 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-0 text-dark">Gestión de Suspensiones</h2>
                <p class="text-muted small mb-0">Control de morosidad por Diplomado.</p>
            </div>
            <a href="<?= htmlspecialchars($basePath) ?>/administrative" class="btn btn-light border btn-sm shadow-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="row g-3 flex-column align-items-center">
        <?php if (!empty($offerings)): ?>
            <?php foreach ($offerings as $o): ?>
                <div class="col-12 col-md-6"> <a href="<?= htmlspecialchars($basePath) ?>/administrative/suspensions/manage?id=<?= $o['offering_id'] ?>" 
                       class="text-decoration-none card-offering-link">
                        <div class="card border-0 shadow-sm hover-shadow transition-all overflow-hidden">
                            <div class="card-body p-0 d-flex align-items-center">
                                <div class="status-indicator <?= $o['total_insolventes'] > 0 ? 'bg-danger' : 'bg-success' ?>" style="width: 6px; align-self: stretch;"></div>
                                
                                <div class="p-3 d-flex justify-content-between align-items-center w-100">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box bg-light rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="bi bi-mortarboard-fill fs-4 <?= $o['total_insolventes'] > 0 ? 'text-danger' : 'text-primary' ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark text-uppercase" style="font-size: 0.9rem;"><?= htmlspecialchars($o['diplomado_name']) ?></h6>
                                            <span class="text-secondary" style="font-size: 0.8rem;"><?= htmlspecialchars($o['cohorte_name']) ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <div class="h5 fw-bold <?= $o['total_insolventes'] > 0 ? 'text-danger' : 'text-success' ?> mb-0">
                                            <?= $o['total_insolventes'] ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 9px; text-transform: uppercase;">Insolventes</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-md-6">
                <div class="alert alert-light border text-center py-4">
                    <i class="bi bi-info-circle d-block mb-2 fs-3"></i>
                    No hay cohortes activas con alumnos inscritos.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?= htmlspecialchars($basePath) ?>/assets/js/administrative_suspension.js"></script>