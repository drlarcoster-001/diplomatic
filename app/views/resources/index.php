<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * Archivo: app/views/resources/index.php
 * Propósito: Dashboard modular del Panel de Recursos Humanos del programa de diplomados.
 * Versión: 1.2.0 - Tarjetas reorganizadas.
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

        <!-- Tipos de Personal -->
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

        <!-- Personal -->
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

        <!-- Tipos de Contrato -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/resources/tipos-contrato" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #06b6d4 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(6, 182, 212, 0.1); color: #06b6d4;">
                        <i class="bi bi-file-earmark-ruled-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #06b6d4;">Tipos de Contrato</h5>
                    <p class="text-muted small">Catálogo de tipos y siglas para la generación de contratos institucionales.</p>
                </div>
            </a>
        </div>

        <!-- Plantillas de Contratos -->
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

        <!-- Contratos -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/resources/contratos" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-file-earmark-check-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #198754;">Contratos</h5>
                    <p class="text-muted small">Generación, historial y gestión de contratos del personal.</p>
                </div>
            </a>
        </div>

        <!-- Programar Sesiones -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/resources/sesiones" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #0f6e56 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(15, 110, 86, 0.1); color: #0f6e56;">
                        <i class="bi bi-calendar-check-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #0f6e56;">Programar Sesiones</h5>
                    <p class="text-muted small">Asignación de personal docente a horarios teóricos y prácticos por oferta.</p>
                </div>
            </a>
        </div>

        <!-- Procesar Sesiones -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/resources/procesar-sesiones" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #BA7517 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width:fit-content;background-color:rgba(186,117,23,0.1);color:#BA7517">
                        <i class="bi bi-check2-square fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color:#BA7517">Procesar Sesiones</h5>
                    <p class="text-muted small">Registro de asistencia y confirmación de sesiones dictadas.</p>
                </div>
            </a>
        </div>

        <!-- Conceptos de Nómina -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/resources/conceptos-nomina" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #533AB7 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width:fit-content;background-color:rgba(83,58,183,0.1);color:#533AB7">
                        <i class="bi bi-calculator-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color:#533AB7">Conceptos de Nómina</h5>
                    <p class="text-muted small">Catálogo de asignaciones y deducciones aplicables al personal.</p>
                </div>
            </a>
        </div>

        <!-- Nómina -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/resources/nomina" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #dc3545 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="bi bi-cash-stack fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #dc3545;">Nómina</h5>
                    <p class="text-muted small">Gestión de nóminas y generación automática de órdenes de pago por tipo de personal.</p>
                </div>
            </a>
        </div>

        <!-- Aprobar Nóminas -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/resources/aprobar-nomina" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-clipboard2-check-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #198754;">Aprobar Nóminas</h5>
                    <p class="text-muted small">Revisión y aprobación de nóminas procesadas para generar órdenes de pago.</p>
                </div>
            </a>
        </div>

    </div>
</div>