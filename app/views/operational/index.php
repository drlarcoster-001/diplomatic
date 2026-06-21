<?php
/**
 * MÓDULO: GESTIÓN OPERATIVA
 * ARCHIVO: app/views/operational/index.php
 * PROPÓSITO: Dashboard principal del módulo operativo con acceso a gestión de staff y noticias.
 * VERSIÓN: 1.2.0 - Unificación estética con el estándar de tarjetas del Panel Financiero.
 */

declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/operational.css">

<div class="container-fluid py-4">
    <div class="mb-4 animate__animated animate__fadeIn">
        <h2 class="h4 fw-bold mb-0 text-dark">Panel Operativo</h2>
        <p class="text-muted small">Sincronización de contenidos y gestión de comunicación con el portal institucional.</p>
    </div>

    <div class="row g-4">
        
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/operational/professors" class="card-operational-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #dc3545 !important;">
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-person-video3 fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-danger">Profesores</h5>
                    <p class="text-muted small mb-0">Gestión de staff docente: carga de biografías, fotos y publicación de perfiles en la web.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/operational/news" class="card-operational-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #0d6efd !important;">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-newspaper fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-primary">Cartelera / Noticias</h5>
                    <p class="text-muted small mb-0">Publicación de noticias, eventos y comunicados académicos en el portal principal.</p>
                </div>
            </a>
        </div>

        <!--Modulo de Correspondencia-->
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/operational/correspondencia/plantillas" class="card-operational-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #6f42c1 !important;">
                    <div class="bg-purple bg-opacity-10 p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(111,66,193,0.1) !important;">
                        <i class="bi bi-file-earmark-text fs-2" style="color: #6f42c1;"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #6f42c1;">Correspondencia</h5>
                    <p class="text-muted small mb-0">Plantillas para cartas, memos, oficios, actas, reconocimientos y constancias.</p>
                </div>
            </a>
        </div>

        <!--Modulo de Documentos Generados (Correspondencia)-->
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/operational/correspondencia/documentos" class="card-operational-link d-block h-100 text-decoration-none">
                <div class="card h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-file-earmark-arrow-down fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-success">Documentos Generados</h5>
                    <p class="text-muted small mb-0">Genera cartas, memos, oficios y demás documentos a partir de las plantillas, e imprime o reimprime lo ya emitido.</p>
                </div>
            </a>
        </div>

        

    </div>
</div>