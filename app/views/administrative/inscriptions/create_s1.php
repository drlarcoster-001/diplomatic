<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/views/administrative/inscriptions/create_s1.php
 * PROPÓSITO: Buscador de Estudiantes con estética Google y tarjeta de selección.
 * VERSIÓN: 2.3.6 - FIX: Candado de seguridad en onerror para prevenir bucle infinito de redirección 404.
 */
?>
<div class="wizard-step" id="step1">
    <div class="text-center mb-5">
        <h4 class="fw-bold text-dark">Localizar Estudiante</h4>
        <p class="text-muted small">Ingrese la cédula o nombre para buscar al estudiante en el sistema.</p>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-7">
            <div class="position-relative" id="searchWrapper">
                <div class="google-search-container d-flex align-items-center shadow-sm border rounded-pill bg-white px-4">
                    <i class="bi bi-search text-muted me-3"></i>
                    <input type="text" id="studentSearch" 
                           class="form-control form-control-lg border-0 shadow-none ps-0 py-3" 
                           placeholder="Buscar estudiante..." 
                           autocomplete="off"
                           style="background: transparent;">
                </div>
                
                <div id="searchResults" class="google-results-overlay list-group shadow-lg mt-2 d-none"></div>
            </div>

            <div id="selectedIndicator" class="mt-4 d-none">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="bg-white border shadow-sm rounded-4 p-3 d-flex align-items-center position-relative" style="min-width: 350px;">
                        
                        <img id="displayAvatar" src="<?= $basePath ?>/assets/img/avatars/default_avatar.png" 
                             class="rounded-circle border shadow-sm me-3" 
                             style="width: 55px; height: 55px; object-fit: cover;"
                             onerror="this.onerror=null; this.src='<?= $basePath ?>/assets/img/avatars/default_avatar.png';">
                        
                        <div class="text-start flex-grow-1">
                            <div class="fw-bold text-dark lh-1 mb-1" id="displayNameDisplay">---</div>
                            <small class="text-muted d-block">Cédula: <span id="displayDocDisplay" class="fw-bold">---</span></small>
                            <span class="badge bg-success-subtle text-success border border-success-subtle smallest mt-1">
                                <i class="bi bi-check2-circle me-1"></i> Seleccionado
                            </span>
                        </div>

                        <button type="button" class="btn btn-link text-danger p-1 ms-3" id="btnRemoveStudent" title="Quitar Estudiante">
                            <i class="bi bi-x-circle-fill fs-4"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            </div>
    </div>
</div>