<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/index.php
 * Propósito: Interfaz de dashboard modular para acceso a entidades académicas.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="/diplomatic/public/assets/css/settings_panel.css">

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Gestión Académica</h2>
        <p class="text-muted small">Administración de programas, períodos, grupos y personal docente.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/diplomados" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-mortarboard-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Diplomados</h5>
                    <p class="text-muted small">Catálogo base de programas académicos.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/cohortes" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-calendar-check-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Cohortes</h5>
                    <p class="text-muted small">Configuración de ejercicios y cronogramas de pago.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/grupos" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item">
                    <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-people-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Grupos y Cupos</h5>
                    <p class="text-muted small">Control de modalidades y límites de inscripción.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/academic/profesores" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item">
                    <div class="bg-dark bg-opacity-10 text-dark p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-person-badge-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Profesores</h5>
                    <p class="text-muted small">Registro y asignación de docentes especialistas.</p>
                </div>
            </a>
        </div>
    </div>
</div>