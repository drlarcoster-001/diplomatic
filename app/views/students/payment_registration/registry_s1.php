<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / VISTAS
 * ARCHIVO: app/views/students/payment_registration/registry_s1.php
 * PROPÓSITO: Visualización de perfil del estudiante autenticado (Bypass de búsqueda).
 * VERSIÓN: 1.0.0 - FEATURE: Carga automática de identidad mediante $_SESSION.
 */
?>
<div id="step1" class="wizard-step animate__animated animate__fadeIn">
    <div class="text-center mb-5">
        <h4 class="fw-bold text-dark">Mi Perfil Estudiantil</h4>
        <p class="text-muted small">Su identidad ha sido verificada por el sistema para proceder con el reporte de pago.</p>
    </div>

    <div id="searchWrapper" class="d-none">
        <input type="text" id="studentSearch" value="<?= $_SESSION['user']['document_id'] ?>">
        <div id="searchResults"></div>
    </div>

    <div id="selectedIndicator" class="animate__animated animate__bounceIn mt-2">
        <div class="card border-0 bg-primary bg-opacity-10 rounded-4 p-4 text-center mx-auto shadow-sm" style="max-width: 450px;">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <img id="displayAvatar" 
                     src="<?= $basePath ?>/assets/img/avatars/<?= $_SESSION['user']['avatar'] ?? 'default_avatar.png' ?>" 
                     class="rounded-circle border border-white border-4 shadow-sm" 
                     style="width: 100px; height: 100px; object-fit: cover;">
                
                </div>
            
            <h5 class="fw-bold text-dark mb-1" id="displayNameDisplay">
                <?= $_SESSION['user']['name'] ?>
            </h5>
            
            <p class="text-primary fw-bold small mb-0">
                Cédula: <span id="displayDocDisplay"><?= $_SESSION['user']['document_id'] ?></span>
            </p>
            
            <div class="mt-3">
                <span class="badge bg-success rounded-pill px-4 py-2 small shadow-sm">
                    <i class="bi bi-patch-check-fill me-1"></i> IDENTIDAD VERIFICADA
                </span>
            </div>
        </div>
    </div>
</div>