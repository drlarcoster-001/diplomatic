<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * Archivo: app/views/resources/index.php
 * Propósito: Dashboard modular del Panel de Recursos Humanos del programa de diplomados.
 * Versión: 1.0.0
 *
 * @var string $basePath
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/settings_panel.css">

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Panel de Recursos</h2>
        <p class="text-muted small">Gestión del personal operativo, contratos y nómina del programa de diplomados.</p>
    </div>

    <div class="row g-4">

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/resources/personal" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #a855f7 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(168, 85, 247, 0.1); color: #a855f7;">
                        <i class="bi bi-person-lines-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #a855f7;">Personal</h5>
                    <p class="text-muted small">Catálogo del personal vinculado al programa. Registro, carnet y gestión de expedientes.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/resources/contratos" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #0d6efd !important;">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-file-earmark-text-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #0d6efd;">Contratos</h5>
                    <p class="text-muted small">Ciclo de vida completo de contratos por tipo de personal con validación QR.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/resources/nomina" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-cash-stack fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #198754;">Nómina</h5>
                    <p class="text-muted small">Gestión de nóminas y generación automática de órdenes de pago por tipo de personal.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
    <a href="<?= htmlspecialchars($basePath) ?>/resources/contratos/plantillas" class="text-decoration-none text-dark">
        <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #0d6efd !important;">
            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                <i class="bi bi-file-earmark-text-fill fs-2"></i>
            </div>
            <h5 class="fw-bold" style="color: #0d6efd;">Plantillas de Contratos</h5>
            <p class="text-muted small">Diseño y gestión de plantillas de contratos institucionales.</p>
        </div>
    </a>
</div>

<div class="col-md-6 col-lg-3">
    <a href="<?= htmlspecialchars($basePath) ?>/resources/contratos" class="text-decoration-none text-dark">
        <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #06b6d4 !important;">
            <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(6, 182, 212, 0.1); color: #06b6d4;">
                <i class="bi bi-file-earmark-check-fill fs-2"></i>
            </div>
            <h5 class="fw-bold" style="color: #06b6d4;">Contratos</h5>
            <p class="text-muted small">Generación, historial y gestión de contratos del personal.</p>
        </div>
    </a>
</div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/resources/tipos-personal" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #f59e0b !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="bi bi-diagram-3-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #f59e0b;">Tipos de Personal</h5>
                    <p class="text-muted small">Catálogo de tipos y siglas del personal operativo del programa.</p>
                </div>
            </a>
        </div>

    </div>
</div>