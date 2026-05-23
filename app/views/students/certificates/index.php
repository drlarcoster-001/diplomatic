<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / CONSTANCIAS
 * ARCHIVO: app/views/students/certificates/index.php
 * PROPÓSITO: Interfaz del estudiante para selección de programas y generación autónoma de constancias.
 * VERSIÓN: 2.0.1 - Normalización de flujo de sesión (Self-context), eliminación de buscador y parche crítico contra Backdrop residual.
 */

declare(strict_types=1);

// Detección de ruta base para soporte de subcarpeta /diplomatic/public/
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/students_certificates.css">

<style>
    /* PARCHE ANTIBLOQUEO (UX CRÍTICO): 
       Fuerza al navegador a ignorar capas oscuras residuales si el modal no está activo.
       Resuelve el error de "pantalla bloqueada" tras cerrar el visor de PDF.
    */
    body:not(.modal-open) .modal-backdrop {
        display: none !important;
        z-index: -1 !important;
    }

    /* Garantiza scroll funcional tras la interacción con el Iframe */
    body {
        overflow-y: auto !important;
        padding-right: 0 !important;
    }

    .modal {
        background: rgba(0, 0, 0, 0.4); /* Backdrop controlado manualmente */
    }

    .card-cert-student {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card-cert-student:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
    }
</style>

<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-3 animate__animated animate__fadeIn">
        <ol class="breadcrumb py-2 px-4 bg-white rounded-pill shadow-sm border-0" style="width: fit-content;">
            <li class="breadcrumb-item small"><a href="<?= htmlspecialchars($basePath) ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
            <li class="breadcrumb-item small active fw-bold text-primary" aria-current="page">Mis Certificados</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeIn">
        <div class="d-flex align-items-center">
            <a href="<?= htmlspecialchars($basePath) ?>/dashboard" class="btn btn-sm btn-light rounded-circle me-3 shadow-sm border">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h2 class="h4 fw-bold mb-0 text-dark">Mis Constancias Digitales</h2>
                <p class="text-muted small mb-0">Genera y valida tus documentos institucionales oficiales.</p>
            </div>
        </div>
        
        <button id="btn-show-selector" class="btn btn-outline-primary btn-sm rounded-pill px-4 shadow-sm fw-bold d-none">
            <i class="bi bi-arrow-repeat me-2"></i>Seleccionar otro programa
        </button>
    </div>

    <div id="program-selector-area" class="mb-5 animate__animated animate__fadeIn">
        <h6 class="text-muted small fw-bold text-uppercase mb-3">
            <i class="bi bi-collection me-2"></i>Programas en los que estás inscrito
        </h6>
        
        <div id="loading-programs" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted small mt-2 fw-bold">Accediendo a tu expediente académico...</p>
        </div>

        <div id="programs-container" class="row g-3 d-none"></div>
    </div>

    <div id="certificates-area" class="d-none animate__animated animate__fadeIn">
        <div class="alert bg-white border-start border-4 border-primary shadow-sm rounded-4 mb-4 p-3 d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                <i class="bi bi-mortarboard-fill fs-4"></i>
            </div>
            <div>
                <span class="text-muted small d-block">Documentación para:</span>
                <strong class="h5 mb-0 text-dark" id="selected-program-name">---</strong>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm card-cert-student p-3" id="btn-cert-inscripcion" style="cursor:pointer;">
                    <div class="d-flex align-items-center">
                        <div class="icon-box-cert me-3 bg-info bg-opacity-10 text-info" style="width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="bi bi-file-earmark-check"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-dark">Planilla de Inscripción</h5>
                            <p class="text-muted small mb-0">Comprobante de registro formal en el programa.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm card-cert-student p-3" id="btn-cert-estudios" style="cursor:pointer;">
                    <div class="d-flex align-items-center">
                        <div class="icon-box-cert me-3 bg-primary bg-opacity-10 text-primary" style="width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1 text-dark">Constancia de Estudios</h5>
                            <p class="text-muted small mb-0">Documento que acredita que eres alumno regular.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 text-center">
            <p class="text-muted small">
                <i class="bi bi-info-circle me-1"></i> 
                Todos los documentos generados incluyen un <strong>Código QR de Validación Institucional</strong>.
            </p>
        </div>
    </div>

    <div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered"> 
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-dark" id="modalPreviewTitle">
                        <i class="bi bi-shield-check me-2 text-primary"></i> Validación de Documento
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-0 bg-secondary position-relative" style="height: 70vh;">
                    <div id="pdf-loader" class="position-absolute top-50 start-50 translate-middle text-center text-white">
                        <div class="spinner-border text-light mb-2" role="status"></div>
                        <p class="small fw-bold">Generando previsualización segura...</p>
                    </div>
                    <iframe id="pdf-preview-frame" src="" style="width: 100%; height: 100%; border: none; position: relative; z-index: 2; opacity: 0; transition: opacity 0.3s ease;"></iframe>
                </div>

                <div class="modal-footer border-0 bg-light py-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <div>
                        <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm me-2" id="btn-download-pdf">
                            <i class="bi bi-download me-2"></i>Descargar Oficial
                        </button>
                        <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" id="btn-send-email">
                            <i class="bi bi-send-check me-2"></i>Enviar a mi Correo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/students_certificates.js?v=<?= time() ?>"></script>