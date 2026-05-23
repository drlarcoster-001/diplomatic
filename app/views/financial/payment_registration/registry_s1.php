<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VISTAS
 * ARCHIVO: app/views/financial/payment_registration/registry_s1.php
 * PROPÓSITO: Interfaz de búsqueda de estudiantes inscritos.
 * VERSIÓN: 1.2.1 - FIX: Visibilidad inicial forzada para evitar que el CSS del wizard lo oculte.
 */
?>
<div id="step1" class="wizard-step animate__animated animate__fadeIn" style="display: block;">
    <div class="text-center mb-5">
        <h4 class="fw-bold text-dark">Localizar Estudiante</h4>
        <p class="text-muted small">Ingrese la cédula o nombre del participante para iniciar el proceso de cobro.</p>
    </div>

    <div id="searchWrapper" class="position-relative" style="max-width: 600px; margin: 0 auto;">
        <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border">
            <span class="input-group-text bg-white border-0 ps-4">
                <i class="bi bi-search text-primary"></i>
            </span>
            <input type="text" id="studentSearch" class="form-control border-0 py-3" placeholder="Buscar por nombre o documento..." autocomplete="off">
        </div>
        
        <div id="searchResults" class="list-group shadow-lg d-none mt-2 rounded-4 border-0"></div>
    </div>

    <div id="selectedIndicator" class="d-none animate__animated animate__bounceIn mt-4">
        <div class="card border-0 bg-primary bg-opacity-10 rounded-4 p-4 text-center mx-auto" style="max-width: 450px;">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <img id="displayAvatar" src="<?= $basePath ?>/assets/img/avatars/default_avatar.png" class="rounded-circle border border-white border-4 shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                <button type="button" id="btnRemoveStudent" class="btn btn-danger btn-sm rounded-circle position-absolute bottom-0 end-0 shadow">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <h5 class="fw-bold text-dark mb-1" id="displayNameDisplay"></h5>
            <p class="text-primary fw-bold small mb-0">Cédula: <span id="displayDocDisplay"></span></p>
            <div class="mt-2"><span class="badge bg-success rounded-pill px-3 small">ESTUDIANTE ACTIVO</span></div>
        </div>
    </div>
</div>