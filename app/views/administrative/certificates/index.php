<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / CONSTANCIAS
 * ARCHIVO: app/views/administrative/certificates/index.php
 * PROPÓSITO: Generador de constancias con flujo idéntico al módulo financiero.
 * VERSIÓN: 4.5.0 - UI Premium: Homologación total y corrección de jerarquía de secciones.
 */

declare(strict_types=1);

// Definición de base path para recursos estáticos y rutas
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/administrative_certificates.css">

<div class="container-fluid py-4">
    
    <nav aria-label="breadcrumb" class="mb-4 animate__animated animate__fadeIn">
        <ol class="breadcrumb py-2 px-4 bg-white rounded-pill shadow-sm border-0" style="width: fit-content;">
            <li class="breadcrumb-item small">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted fw-medium">Inicio</a>
            </li>
            <li class="breadcrumb-item small">
                <a href="<?= $basePath ?>/administrative" class="text-decoration-none text-muted fw-medium">Panel Administrativo</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-warning small" aria-current="page">
                Constancias Oficiales
            </li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeIn">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <i class="bi bi-file-earmark-text-fill text-warning me-2"></i> Generador de Constancias
            </h2>
            <p class="text-muted small mb-0">Emisión y validación digital de documentos institucionales.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4 animate__animated animate__fadeIn">
        <div class="card-body p-3">
            <div class="position-relative">
                <div class="input-group input-group-lg border rounded-pill overflow-hidden bg-white shadow-sm">
                    <span class="input-group-text bg-white border-0 text-muted ps-3">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="search-input" class="form-control border-0 shadow-none bg-transparent py-3" 
                           placeholder="Escriba nombre o cédula del estudiante..." autocomplete="off">
                    <button class="btn btn-white border-0 text-muted d-none" type="button" id="btn-clear-input">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
                <div id="autocomplete-results" class="list-group position-absolute w-100 shadow-lg d-none mt-2" 
                     style="z-index: 1050; border-radius: 15px; overflow: hidden; border: 1px solid #eee;">
                </div>
            </div>
        </div>
    </div>

    <div id="certificates-area" class="d-none animate__animated animate__fadeIn">
        
        <div id="enrollments-section">
            <div class="d-flex align-items-center mb-3 ms-2">
                <i class="bi bi-collection-play-fill text-warning me-2"></i>
                <h6 class="text-muted fw-bold text-uppercase small mb-0">Diplomados del Estudiante</h6>
            </div>
            <div id="programs-list" class="row g-3 mb-5">
                </div>
        </div>

        <div id="options-section" class="d-none animate__animated animate__fadeIn mb-5">
            <div class="mb-3">
                <button class="btn btn-sm btn-light border rounded-pill shadow-sm fw-bold px-3 text-muted" id="btn-volver-diplomados">
                    <i class="bi bi-arrow-left me-1"></i> Volver a Diplomados
                </button>
            </div>

            <div class="card shadow-sm border-0 rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-light border-0 py-3 px-4">
                    <span class="text-muted small d-block text-uppercase fw-bold">Programa Académico Seleccionado:</span>
                    <h5 class="text-dark fw-bold mb-0" id="selected-program-name">---</h5>
                </div>

                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100 border rounded-4 action-card-btn shadow-sm" id="btn-generate-inscripcion" style="cursor: pointer;">
                                <div class="card-body text-center p-4">
                                    <div class="icon-circle bg-light text-primary mb-3">
                                        <i class="bi bi-person-badge-fill fs-2"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">Constancia de Inscripción</h5>
                                    <p class="text-muted small mb-0">Certifica que el alumno se encuentra formalmente matriculado en el programa.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100 border rounded-4 action-card-btn shadow-sm" id="btn-generate-estudios" style="cursor: pointer;">
                                <div class="card-body text-center p-4">
                                    <div class="icon-circle bg-light text-success mb-3">
                                        <i class="bi bi-mortarboard-fill fs-2"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">Constancia de Estudios</h5>
                                    <p class="text-muted small mb-0">Certifica el estatus de alumno regular y activo durante el periodo actual.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="empty-state" class="py-5 text-center animate__animated animate__fadeIn">
        <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
            <i class="bi bi-file-earmark-pdf fs-1 text-muted opacity-25"></i>
        </div>
        <h5 class="text-muted fw-bold">No hay selección activa</h5>
        <p class="text-muted small mx-auto" style="max-width: 400px;">
            Inicie la búsqueda de un expediente para habilitar la gestión de constancias y el archivo digital.
        </p>
    </div>

    <div class="modal fade" id="modalPreview" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered"> 
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-dark" id="modalPreviewTitle">
                        <i class="bi bi-eye-fill text-warning me-2"></i> Vista Previa
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-0 bg-dark position-relative" style="height: 75vh;">
                    <div id="pdf-loader" class="position-absolute top-50 start-50 translate-middle text-white text-center">
                        <div class="spinner-border text-warning mb-2" role="status"></div>
                        <p class="small fw-bold">Generando documento oficial con QR...</p>
                    </div>
                    <iframe id="pdf-preview-frame" src="" style="width: 100%; height: 100%; border: none; opacity: 0;"></iframe>
                </div>

                <div class="modal-footer border-0 bg-light py-3 d-flex justify-content-between align-items-center">
                    <div class="ps-3 d-none d-md-block" style="max-width: 55%;">
                        <small class="text-muted">
                            <i class="bi bi-info-circle-fill text-primary me-1"></i>
                            <strong>Validación:</strong> Al <b>Descargar</b> o <b>Enviar</b>, el código QR se activará y el documento se registrará en el archivo histórico.
                        </small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm me-2" id="btn-download-pdf">
                            <i class="bi bi-cloud-arrow-down-fill me-2"></i> Descargar PDF
                        </button>
                        <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" id="btn-send-email">
                            <i class="bi bi-envelope-paper-fill me-2"></i> Enviar al Correo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/administrative_certificates.js?v=<?= time() ?>"></script>