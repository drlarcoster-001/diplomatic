<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/views/administrative/index.php
 * PROPÓSITO: Interfaz de dashboard modular con rutas sincronizadas para excepciones y flujo regular.
 * VERSIÓN: 1.1.4 - Fix: Sincronización de ruta para Documentos Rechazados (URI match: /rejected).
 */

declare(strict_types=1);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/administrative.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <div class="mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Panel Administrativo</h2>
        <p class="text-muted small">Control central de procesos de inscripción, matrícula oficial, expedientes, certificaciones y correcciones.</p>
    </div>

    <div class="row g-4">
        
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/administrative/inscriptions" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #0d6efd !important;">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-clipboard-plus-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #0d6efd;">Inscripciones</h5>
                    <p class="text-muted small">Gestión de aspirantes, validación de documentos y procesos de admisión inicial.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/administrative/document-verification" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #fd7e14 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(253, 126, 20, 0.1); color: #fd7e14;">
                        <i class="bi bi-file-earmark-check-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #fd7e14;">Verificación de Documentos</h5>
                    <p class="text-muted small">Auditoría de requisitos cargados, aprobación de recaudos y gestión de pendientes.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/administrative/matriculations" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-journal-bookmark-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #198754;">Matrícula Académica</h5>
                    <p class="text-muted small">Gestión de cohortes, actas de notas finales y control de estados regulares.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/administrative/students/directory" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #0dcaf0 !important;">
                    <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-people-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #0dcaf0;">Estudiantes</h5>
                    <p class="text-muted small">Consulta de expedientes, historial de contacto y base de datos central de alumnos.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/administrative/certificates" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #ffc107 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="bi bi-file-earmark-text-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #e6ac00;">Constancias</h5>
                    <p class="text-muted small">Generación de constancias de inscripción, estudio y documentos oficiales.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/administrative/annulments" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #b02a37 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(176, 42, 55, 0.1); color: #b02a37;">
                        <i class="bi bi-trash3-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #b02a37;">Anulación de Inscripciones</h5>
                    <p class="text-muted small">Reversión de procesos, cancelaciones y eliminación de registros por error administrativo.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/administrative/reactivations" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #dc3545 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="bi bi-arrow-counterclockwise fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #dc3545;">Reactivar Matrículas</h5>
                    <p class="text-muted small">Restaurar el estado activo a matrículas previamente finalizadas o suspendidas.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/administrative/rejected" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #f07167 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(240, 113, 103, 0.1); color: #f07167;">
                        <i class="bi bi-file-earmark-x-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #f07167;">Doc. Rechazados</h5>
                    <p class="text-muted small">Listado consolidado de expedientes que requieren corrección de recaudos.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/administrative/suspensions" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item" style="border-top: 4px solid #6c757d !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3" style="width: fit-content; background-color: rgba(108, 117, 125, 0.1); color: #6c757d;">
                        <i class="bi bi-person-x-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold" style="color: #495057;">Suspender Estudiantes</h5>
                    <p class="text-muted small">Suspensión manual de alumnos con envío automático de notificación vía WhatsApp y Correo.</p>
                </div>
            </a>
        </div>

    </div>
</div>