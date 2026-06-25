<?php
/**
 * MÓDULO: PORTAL DOCENTE
 * ARCHIVO: app/views/professor/index.php
 * PROPÓSITO: Dashboard de bienvenida con accesos a las 4 secciones.
 * VERSIÓN: 2.1.0 - Registrar Asistencia activada, fix </a> faltante.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/professor.css">

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Bienvenido, Prof. <?= htmlspecialchars($profesor['full_name']) ?></h2>
        <p class="text-muted small mb-0">Panel Docente — gestiona tu matrícula, asistencia y notas.</p>
    </div>

    <div class="row g-4">

        <!-- MATRÍCULA -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= $basePath ?>/professor/matricula" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center professor-card"
                     style="border-top:4px solid #198754 !important;">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3"
                         style="width:fit-content">
                        <i class="bi bi-people-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Matrícula</h5>
                    <p class="text-muted small mb-0">Listado completo de estudiantes inscritos en cada oferta.</p>
                </div>
            </a>
        </div>

        <!-- CONTROL DE ASISTENCIA -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= $basePath ?>/professor/control-asistencia" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center professor-card"
                     style="border-top:4px solid #533AB7 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3"
                         style="width:fit-content;background:#533AB720;color:#533AB7">
                        <i class="bi bi-calendar-check-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Control de Asistencia</h5>
                    <p class="text-muted small mb-0">Consulta tus sesiones y descarga la lista para el aula.</p>
                </div>
            </a>
        </div>

        <!-- REGISTRAR ASISTENCIA -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= $basePath ?>/professor/registrar-asistencia" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center professor-card"
                     style="border-top:4px solid #fd7e14 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3"
                         style="width:fit-content;background:#fd7e1420;color:#fd7e14">
                        <i class="bi bi-pencil-square fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Registrar Asistencia</h5>
                    <p class="text-muted small mb-0">Marca quién asistió a cada sesión de clase.</p>
                </div>
            </a>
        </div>


        <!-- CARGAR NOTAS -->
        <div class="col-md-6 col-lg-3">
            <a href="<?= $basePath ?>/professor/notas" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center professor-card"
                    style="border-top:4px solid #0d6efd !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3"
                        style="width:fit-content;background:#0d6efd20;color:#0d6efd">
                        <i class="bi bi-file-earmark-text-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Cargar Notas</h5>
                    <p class="text-muted small mb-0">Registra las notas de tus estudiantes por modalidad.</p>
                </div>
            </a>
        </div>

        <!-- MI HORARIO -->
        <div class="col-md-6 col-lg-2">
            <a href="<?= $basePath ?>/professor/horario" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center professor-card"
                    style="border-top:4px solid #0dcaf0 !important;">
                    <div class="p-3 rounded-circle mx-auto mb-3"
                        style="width:fit-content;background:#0dcaf020;color:#0dcaf0">
                        <i class="bi bi-calendar3 fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Mi Horario</h5>
                    <p class="text-muted small mb-0">Consulta tus horarios teóricos y prácticos.</p>
                </div>
            </a>
        </div>

    </div>
</div>