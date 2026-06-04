<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/views/administrative/inscriptions/create_s2.php
 * PROPÓSITO: Interfaz Paso 2 - Validación de información académica y perfil base del participante.
 * VERSIÓN: 2.1.1 - Sincronización con arquitectura de altura fija 2.5.0 y blindaje de lectura (Readonly).
 */
?>
<div class="wizard-step d-none" id="step2">
    <div class="mb-4">
        <h6 class="fw-bold text-primary">
            <i class="bi bi-person-badge me-2"></i> Paso 2: Información de Base
        </h6>
        <p class="text-muted small">
            Estos datos son extraídos automáticamente del perfil del usuario. Verifique que la información sea correcta antes de proceder.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="p-3 border rounded-4 bg-white shadow-sm">
                <label class="form-label smallest fw-bold text-muted text-uppercase d-block mb-2">
                    Carrera de Pregrado / Título
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-mortarboard text-primary"></i></span>
                    <input type="text" name="undergraduate_degree" id="undergraduate_degree" 
                           class="form-control bg-light border-0 fw-bold text-dark" 
                           placeholder="No especificado" readonly>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-3 border rounded-4 bg-white shadow-sm">
                <label class="form-label smallest fw-bold text-muted text-uppercase d-block mb-2">
                    Lugar de Procedencia
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-geo-alt text-primary"></i></span>
                    <input type="text" name="provenance" id="provenance" 
                           class="form-control bg-light border-0 fw-bold text-dark" 
                           placeholder="No especificado" readonly>
                </div>
            </div>
        </div>
    </div>

    <div id="profileWarning" class="alert alert-warning mt-5 border-0 shadow-sm d-none rounded-4 p-4 animate__animated animate__headShake">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
            <div>
                <strong class="d-block mb-1">Perfil Académico Incompleto</strong>
                <span class="small">El estudiante seleccionado no posee una carrera registrada. Debe actualizar el perfil del usuario en el módulo de Personas para habilitar la inscripción en este programa.</span>
            </div>
        </div>
    </div>

    <div class="mt-auto pt-4 border-top-0">
        <p class="text-muted smallest text-center">
            <i class="bi bi-shield-check me-1"></i> Los datos marcados como "solo lectura" están vinculados a la base de datos central de estudiantes.
        </p>
    </div>
</div>