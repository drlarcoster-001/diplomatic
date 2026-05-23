<?php
/**
 * MÓDULO: ADMINISTRATIVO / VERIFICACIÓN DE DOCUMENTOS
 * ARCHIVO: app/views/administrative/document_verification/index.php
 * PROPÓSITO: Panel para auditar recaudos y formalizar la creación del Estudiante.
 * VERSIÓN: 1.2.0 - Actualizado con Pestañas (Tabs) y Navegación Anti-Pánico.
 */

declare(strict_types=1);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/administrative_document_verification.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <nav aria-label="breadcrumb" class="m-0">
            <ol class="breadcrumb bg-light p-2 rounded shadow-sm border mb-0" style="font-size: 0.85rem;">
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door"></i> Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/administrative" class="text-decoration-none text-muted">Panel Administrativo</a></li>
                <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Verificación de Documentos</li>
            </ol>
        </nav>
        
        
        <div class="d-flex gap-2">
            <a href="<?= htmlspecialchars($basePath) ?>/administrative/document-verification/imprimir" 
                target="_blank" 
                class="btn btn-outline-danger btn-sm fw-bold shadow-sm px-3">
                    <i class="bi bi-printer-fill me-1"></i> Imprimir listado
                </a>

            <a href="<?= htmlspecialchars($basePath) ?>/administrative" class="btn btn-outline-secondary btn-sm fw-bold shadow-sm">
                <i class="bi bi-arrow-left-circle me-1"></i> Volver al Panel
            </a>
        </div>
    </div>

    <div class="mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Auditoría de Recaudos</h2>
        <p class="text-muted small mb-0">Formalización de Expedientes y Matrícula de Estudiantes.</p>
    </div>

    <ul class="nav nav-tabs mb-4 border-bottom-0" id="statusTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-dark bg-white border-bottom-0 shadow-sm" id="tab-revision" data-bs-toggle="tab" data-status="REVISION" type="button" role="tab">
                <i class="bi bi-exclamation-circle-fill text-danger me-1"></i> En Revisión (Prioridad)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-muted bg-light" id="tab-compromiso" data-bs-toggle="tab" data-status="COMPROMISO" type="button" role="tab">
                <i class="bi bi-clock-history text-warning me-1"></i> Compromiso (En espera)
            </button>
        </li>
    </ul>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form id="filter-form-docs" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small fw-bold text-muted">Buscar Participante o Diplomado</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="search-text" placeholder="Ej: Terapia Respiratoria, Cédula, Nombre...">
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-bold me-2" id="btn-clear-filters">
                        <i class="bi bi-eraser me-1"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-dark btn-sm px-4 fw-bold">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-folder-check text-primary me-2"></i>Expedientes por Formalizar</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" id="table-docs-pending">
                    <thead class="table-light sticky-top">
                        <tr style="font-size: 0.75rem;" class="text-uppercase text-muted">
                            <th class="ps-4">Fecha Solicitud</th>
                            <th>Participante</th>
                            <th>Cédula</th>
                            <th>Diplomado</th>
                            <th>Pago Financiero</th>
                            <th class="text-center pe-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.85rem;">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVerifyDocs" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-medical me-2"></i>Auditoría de Expediente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-lg-4 p-4 bg-light border-end">
                        <h6 class="text-uppercase fw-bold text-muted mb-3 border-bottom pb-2" style="font-size: 0.75rem;">Datos del Participante</h6>

                        <div class="mb-3">
                            <label class="d-block small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Diplomado Inscrito:</label>
                            <span id="v-diplomado" class="d-block fw-bold text-primary" style="font-size: 0.9rem;">---</span>
                        </div>

                        <div class="mb-2">
                            <label class="d-block small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Nombre:</label>
                            <span id="v-participante" class="d-block fw-bold text-dark fs-5">---</span>
                        </div>

                        <div class="mb-2">
                            <label class="d-block small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">ID / Cédula:</label>
                            <span id="v-cedula" class="text-dark fw-medium font-monospace">---</span>
                        </div>

                        <div class="mb-2">
                            <label class="d-block small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Correo:</label>
                            <span id="v-email" class="d-block text-dark fw-medium">---</span>
                        </div>

                        <div class="mb-2">
                            <label class="d-block small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Grado:</label>
                            <span id="v-grado" class="d-block text-dark fw-medium text-uppercase">---</span>
                        </div>

                        <div class="mb-3">
                            <label class="d-block small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Nro de Teléfono:</label>
                            <div class="d-flex align-items-center gap-2">
                                <span id="v-telefono" class="d-block text-dark fw-bold">---</span>
                                <!-- Botón de WhatsApp Integrado -->
                                <button type="button" id="btn-wa-notify" class="btn btn-sm btn-success rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 26px; height: 26px;" title="Notificar por WhatsApp">
                                    <i class="bi bi-whatsapp" style="font-size: 0.8rem;"></i>
                                </button>
                            </div>
                        </div>

                    
                        
                        <div class="mt-3 p-2 border rounded bg-white">
                            <span class="small fw-bold text-muted d-block mb-1">Estatus Financiero</span>
                            <span id="v-pago-status" class="badge bg-secondary">VERIFICANDO...</span>
                        </div>

                        <h6 class="text-uppercase fw-bold text-muted mt-4 mb-3 border-bottom pb-2" style="font-size: 0.75rem;">Archivos Adjuntos</h6>
                        
                        <div class="list-group list-group-flush border rounded shadow-sm">
                            <button type="button" class="list-group-item list-group-item-action btn-doc-selector active" data-doctype="cedula">
                                <i class="bi bi-person-vcard me-2 text-primary"></i>Documento de Identidad <span class="text-danger">*</span>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action btn-doc-selector" data-doctype="titulo">
                                <i class="bi bi-mortarboard me-2 text-success"></i>Título Universitario <span class="text-danger">*</span>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action btn-doc-selector" data-doctype="cv">
                                <i class="bi bi-file-earmark-person me-2 text-secondary"></i>Resumen Curricular
                            </button>
                        </div>
                        <small class="d-block mt-2 text-muted" style="font-size: 0.7rem;"><span class="text-danger">*</span> Requisito Obligatorio</small>
                    </div>

                    <div class="col-lg-8 p-3 bg-secondary bg-opacity-10 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-muted text-uppercase" id="visor-title">Vista Previa del Documento</span>
                            <a href="#" id="btn-download-doc" target="_blank" class="btn btn-sm btn-outline-dark" style="display: none;">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Abrir Externo
                            </a>
                        </div>
                        <div class="pdf-viewer-container" id="pdf-container">
                            <div class="text-white text-center">
                                <i class="bi bi-file-earmark-text display-1 d-block mb-3"></i>
                                <h5>Seleccione un documento a la izquierda</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white border-top p-3 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-danger fw-bold shadow-sm" id="btn-reject-docs">
                        <i class="bi bi-x-octagon-fill me-1"></i> Rechazar
                    </button>
                    <button type="button" class="btn btn-outline-warning text-dark fw-bold shadow-sm ms-2" id="btn-observe-docs">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Observar
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success fw-bold px-4 shadow-sm" id="btn-approve-docs">
                        <i class="bi bi-check-circle-fill me-1"></i> Aprobar y Formalizar
                    </button>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script> const BASE_URL = '<?= $basePath ?>'; </script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/administrative_document_verification.js"></script>