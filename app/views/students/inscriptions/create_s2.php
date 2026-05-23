<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: app/views/students/inscriptions/create_s2.php
 * PROPÓSITO: Interfaz de Información de Base (Paso 2) con validación de perfil académico.
 * VERSIÓN: 1.2.8 - FIX: Agregado atributo 'name' para persistencia en base de datos.
 */

// Lógica de validación de campos obligatorios
$degree = $student['undergraduate_degree'] ?? '';
$provenance = $student['provenance'] ?? '';
$isProfileComplete = (!empty($degree) && !empty($provenance));
?>

<div class="wizard-step-content d-none" id="step2">
    <div class="mb-4">
        <h5 class="fw-bold text-primary d-flex align-items-center">
            <i class="bi bi-person-badge me-2"></i> Paso 2: Información de Base
        </h5>
        <p class="text-muted small">Estos datos son extraídos automáticamente del perfil del usuario. Verifique que la información sea correcta antes de proceder.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <label class="smallest fw-bold text-secondary mb-2">CARRERA DE PREGRADO / TÍTULO</label>
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <i class="bi bi-mortarboard text-primary me-3 fs-5"></i>
                    <input type="text" name="undergraduate_degree_s2" class="form-control border-0 bg-transparent fw-bold p-0 text-dark" 
                           id="undergraduate_degree_s2" value="<?= htmlspecialchars($degree) ?>" readonly>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <label class="smallest fw-bold text-secondary mb-2">LUGAR DE PROCEDENCIA</label>
                <div class="d-flex align-items-center p-3 bg-light rounded-3">
                    <i class="bi bi-geo-alt text-primary me-3 fs-5"></i>
                    <input type="text" name="provenance_s2" class="form-control border-0 bg-transparent fw-bold p-0 text-dark" 
                           id="provenance_s2" value="<?= htmlspecialchars($provenance) ?>" readonly>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$isProfileComplete): ?>
        <div class="mt-5 p-4 bg-danger-subtle border border-danger rounded-4 text-center">
            <h5 class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Información Incompleta</h5>
            <p class="text-dark mb-3">
                Detectamos que su perfil no cuenta con la información académica base requerida. 
                <strong>Debe comenzar de nuevo la inscripción luego de actualizar el usuario.</strong>
            </p>
            <a href="<?= $urlBase ?>/profile" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-pencil-square me-2"></i> Actualizar Usuario
            </a>
        </div>
        <input type="hidden" id="profile_complete_flag" value="0">
    <?php else: ?>
        <div class="mt-4 text-center">
            <p class="text-muted smallest">
                <i class="bi bi-shield-lock me-1"></i> LOS DATOS MARCADOS COMO "SOLO LECTURA" ESTÁN VINCULADOS A LA BASE DE DATOS CENTRAL DE ESTUDIANTES.
            </p>
            <input type="hidden" id="profile_complete_flag" value="1">
        </div>
    <?php endif; ?>
</div>