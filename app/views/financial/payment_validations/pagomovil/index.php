<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS
 * ARCHIVO: app/views/financial/payment_validations/pagomovil/index.php
 * PROPÓSITO: Panel operativo de conciliación de Pago Móvil con Ficha Técnica detallada.
 * VERSIÓN: 1.6.0 - FIX TIGRE: Ajuste de visibilidad del input de Dropzone para compatibilidad de eventos JS.
 */

declare(strict_types=1);

// Estandarización de rutas para entorno local y producción
$basePath = '/diplomatic/public';
$fechaHoy = date('d/m/Y');

/**
 * TASA BCV DINÁMICA
 * Se recibe $last_rate desde FinancialPaymentValidationsPagomovilController::index
 */
$tasaReferencialNum = $last_rate ?? 0;
$tasaReferencialStr = number_format((float)$tasaReferencialNum, 2, ',', '.');
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_payment_validations_pagomovil.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-light p-2 rounded shadow-sm border" style="font-size: 0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/financial/payment_validations" class="text-decoration-none text-muted">Bandeja de Validación</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Conciliación Pago Móvil</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Gestión de Pago Móvil (Bs.)</h2>
            <p class="text-muted small mb-0">Validación de recaudos por cuotas y conciliación bancaria inteligente.</p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/financial/payment_validations" class="btn btn-outline-secondary fw-bold shadow-sm px-3 d-flex align-items-center gap-2 rounded-pill">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <button class="btn btn-success fw-bold shadow-sm px-4 d-flex align-items-center gap-2 rounded-pill" id="btn-open-upload-modal">
                <i class="bi bi-file-earmark-excel"></i> Validar Archivo de Pago
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="info-card-pm bg-white border-start border-4 border-primary rounded shadow-sm p-3">
                <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Fecha Sistema</span>
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar3 text-primary me-2 fs-5"></i>
                    <span class="fs-5 fw-bold text-dark"><?= $fechaHoy ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-card-pm bg-white border-start border-4 border-info rounded shadow-sm p-3">
                <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Reloj Local</span>
                <div class="d-flex align-items-center">
                    <i class="bi bi-clock text-info me-2 fs-5"></i>
                    <span class="fs-5 fw-bold text-dark font-monospace" id="real-time-clock">--:--:--</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="info-card-pm bg-white border-start border-4 border-success rounded shadow-sm p-3">
                <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Tasa BCV Referencial</span>
                <div class="d-flex align-items-center">
                    <i class="bi bi-currency-exchange text-success me-2 fs-5"></i>
                    <span class="fs-5 fw-bold text-dark" id="current-tasa-display"><?= $tasaReferencialStr ?> <small class="text-muted">Bs.</small></span>
                </div>
            </div>
        </div>

    </div>

<div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-list-stars text-primary me-2"></i>Pagos Pendientes por Validar
                </h6>
                <button class="btn btn-primary btn-sm fw-bold d-none" id="btn-approve-massive">
                    <i class="bi bi-check-all me-1"></i> Aprobar Conciliados
                </button>
            </div>

            <!-- ✅ NUEVO: Form de filtros completo -->
<form id="filter-form-pagomovil" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Referencia o Estudiante</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="search-input" 
                            placeholder="Ej: 12345678...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Fecha Desde</label>
                    <input type="date" class="form-control form-control-sm" id="search-date-from">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Fecha Hasta</label>
                    <input type="date" class="form-control form-control-sm" id="search-date-to">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Ordenar por Fecha</label>
                    <select class="form-select form-select-sm" id="search-order">
                        <option value="DESC">Más reciente primero</option>
                        <option value="ASC">Más antiguo primero</option>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-bold me-2" id="btn-clear-filters">
                        <i class="bi bi-eraser me-1"></i>Limpiar
                    </button>
                    <button type="submit" class="btn btn-dark btn-sm px-4 fw-bold">
                        <i class="bi bi-funnel me-1"></i>Ver Resultados
                    </button>
                </div>
            </form>

        </div>


        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" id="table-pagomovil-pending">
                    <thead class="table-light sticky-top">
                        <tr style="font-size: 0.75rem;" class="text-uppercase text-muted">
                            <th class="ps-4">#ID</th>
                            <th>Fecha</th>
                            <th>Estudiante</th>
                            <th>Referencia</th>
                            <th class="text-end">Monto (Bs.)</th>
                            <th class="text-end">Monto ($)</th>
                            <th class="text-center">Conciliación</th>
                            <th class="text-center pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.85rem;">
                        <tr class="placeholder-row">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                Cargando registros desde el servidor...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-2 border-top-0 d-flex justify-content-center" id="pagination-container">
            </div>
    </div>
</div>

<div class="modal fade" id="modalUploadXlsx" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg border-top border-5 border-success rounded-4">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-file-earmark-excel text-success me-2"></i>Validación de Datos Bancarios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="upload-zone-pm" id="dropzone-pm" style="border: 2px dashed #ccc; padding: 40px; border-radius: 15px; cursor: pointer; text-align: center; background: #fafafa;">
                    <input type="file" id="excelFile" accept=".xlsx" style="display: none;">
                    <i class="bi bi-cloud-arrow-up fs-1 text-success mb-2"></i>
                    <p class="mb-0 fw-bold text-dark">Haga clic para seleccionar archivo</p>
                    <p class="text-muted small">o arrastre archivo de excel de pago aquí</p>
                </div>
                <div id="file-info-container" class="mt-3 d-none animate__animated animate__fadeIn">
                    <div class="d-flex align-items-center p-3 bg-light rounded border border-success">
                        <i class="bi bi-file-check-fill text-success fs-4 me-2"></i>
                        <div class="overflow-hidden flex-grow-1">
                            <p class="mb-0 text-dark fw-bold small text-truncate" id="selected-file-name">archivo.xlsx</p>
                            <span class="text-muted" style="font-size: 0.7rem;" id="selected-file-size">0 KB</span>
                        </div>
                        <button type="button" class="btn-close ms-2" style="font-size: 0.6rem;" id="btn-remove-file"></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 d-flex justify-content-between">
                <button type="button" class="btn btn-danger btn-sm fw-bold px-4 rounded-pill" data-bs-dismiss="modal">CANCELAR</button>
                <button type="button" class="btn btn-success btn-sm fw-bold px-4 rounded-pill shadow-sm" id="btn-process-xlsx" disabled>PROCESAR</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalValidatePayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold small text-uppercase"><i class="bi bi-shield-check me-2"></i>Verificación Técnica de Transacción</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-lg-5 p-4 bg-light border-end" style="max-height: 85vh; overflow-y: auto;">
                        <h6 class="text-uppercase fw-bold text-muted mb-4 border-bottom pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">
                            <i class="bi bi-person-badge me-2"></i>Ficha del Reporte Estudiantil
                        </h6>

                        <div class="mb-4">
                            <label class="d-block small text-muted text-uppercase fw-bold smallest">Estudiante Académico</label>
                            <span id="v-estudiante" class="fw-bold text-dark fs-5">---</span>
                        </div>

                        <div class="card border-0 shadow-sm rounded-3 mb-3">
                            <div class="card-body p-3">
                                <h6 class="smallest fw-bold text-primary text-uppercase mb-3 border-bottom pb-1">Datos de Origen del Pago</h6>
                                
                                <div class="mb-2">
                                    <label class="d-block smallest text-muted text-uppercase fw-bold">Banco Emisor</label>
                                    <i class="bi bi-bank text-muted me-1"></i>
                                    <span id="v-banco-emisor" class="fw-bold text-dark small">---</span>
                                </div>

                                <div class="mb-2">
                                    <label class="d-block smallest text-muted text-uppercase fw-bold">Teléfono / Cuenta Emisora</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-phone text-muted me-1"></i>
                                        <span id="v-telefono-emisor" class="fw-bold text-dark small font-monospace">---</span>
                                        <button type="button" class="btn btn-sm p-0 border-0" id="btn-whatsapp-modal" title="Enviar WhatsApp" style="color: #25D366; font-size: 1.2rem;">
                                            <i class="bi bi-whatsapp"></i>
                                        </button>
                                    </div>
                                </div>

                                <!--{{-- Modal WhatsApp --}}-->
                                <div class="modal fade" id="modalWhatsapp" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header" style="background-color: #25D366;">
                                                <h5 class="modal-title fw-bold text-white"><i class="bi bi-whatsapp me-2"></i>Enviar WhatsApp</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <p class="small text-muted mb-3">Para: <strong id="wa-nombre-display"></strong> — <span class="font-monospace" id="wa-telefono-display"></span></p>
                                                <label class="form-label small fw-bold">Mensaje personalizado</label>
                                                <textarea id="wa-mensaje" class="form-control" rows="4" placeholder="Escriba el mensaje aquí..."></textarea>
                                                <div class="mt-3 p-3 bg-light rounded small text-muted" style="font-size: 0.78rem;">
                                                    <strong>Vista previa:</strong><br>
                                                    Buenas <span id="wa-preview-nombre" class="fw-bold"></span><br>
                                                    Le escribimos de parte de la <strong>Plataforma de Diplomados</strong> para informarte que:<br>
                                                    <em id="wa-preview-msg" class="text-dark"></em><br><br>
                                                    Atentamente,<br>
                                                    <strong>Coordinación de Diplomados</strong>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="button" class="btn btn-sm fw-bold px-4" style="background-color: #25D366; color: white;" id="btn-wa-send">
                                                    <i class="bi bi-whatsapp me-1"></i> Abrir WhatsApp
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label class="d-block smallest text-muted text-uppercase fw-bold">Nombre del Titular</label>
                                    <i class="bi bi-person-check text-muted me-1"></i>
                                    <span id="v-titular-cuenta" class="fw-bold text-dark small">---</span>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm rounded-3 mb-4">
                            <div class="card-body p-3">
                                <h6 class="smallest fw-bold text-success text-uppercase mb-3 border-bottom pb-1">Detalle de Operación Financiera</h6>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="d-block smallest text-muted text-uppercase fw-bold">Nro. Referencia</label>
                                        <span id="v-referencia" class="fw-bold text-primary font-monospace fs-5">---</span>
                                    </div>
                                    <div class="col-6">
                                        <label class="d-block smallest text-muted text-uppercase fw-bold">Fecha Operación</label>
                                        <span id="v-fecha" class="fw-bold text-dark small">---</span>
                                    </div>
                                </div>

                                <div class="pt-2 border-top">
                                    <label class="d-block smallest text-muted text-uppercase fw-bold">Monto Validado (Nativo)</label>
                                    <div class="d-flex align-items-baseline">
                                        <span id="v-monto-bs" class="fw-bold text-dark fs-3">0,00</span>
                                        <small class="text-muted ms-1 fw-bold">Bs.</small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="badge bg-success text-white px-2 py-1 fs-6 shadow-sm" id="v-monto-usd">
                                            $ 0,00
                                        </span>
                                        <small class="smallest text-muted">Tasa Aplicada: <span id="v-tasa-usada" class="fw-bold">0,00</span></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert d-flex align-items-center border-0 shadow-sm mb-0" id="alert-conciliacion">
                            <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                            <div class="small">Iniciando análisis de correspondencia bancaria...</div>
                        </div>
                    </div>

                    <div class="col-lg-7 d-flex flex-column bg-secondary bg-opacity-10" style="min-height: 600px;">
                        <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                            <span class="small fw-bold text-muted text-uppercase"><i class="bi bi-image me-2"></i>Comprobante Digital</span>
                            <div class="btn-group shadow-sm">
                                <button type="button" class="btn btn-outline-dark btn-sm" id="btn-zoom-in" title="Acercar"><i class="bi bi-zoom-in"></i></button>
                                <button type="button" class="btn btn-outline-dark btn-sm" id="btn-zoom-out" title="Alejar"><i class="bi bi-zoom-out"></i></button>
                                <button type="button" class="btn btn-outline-dark btn-sm" id="btn-reset-zoom" title="Reiniciar"><i class="bi bi-arrows-fullscreen"></i></button>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-auto d-flex align-items-center justify-content-center p-3" id="img-viewer-container" style="background: #222 url('https://www.transparenttextures.com/patterns/carbon-fibre.png');">
                            <img src="" id="v-screenshot" class="img-fluid shadow-lg rounded" style="transition: transform 0.2s ease; transform-origin: center center; max-height: 85vh;" alt="Comprobante">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-white border-top-0 p-3 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary fw-bold px-4 rounded-pill" data-bs-dismiss="modal">CERRAR</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-danger fw-bold px-4 rounded-pill shadow-sm" id="btn-reject-validation">
                        <i class="bi bi-x-circle-fill me-1"></i> RECHAZAR
                    </button>
                    <button type="button" class="btn btn-success fw-bold px-5 rounded-pill shadow-lg" id="btn-confirm-validation">
                        <i class="bi bi-check-circle-fill me-1"></i> APROBAR PAGO
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script> const BASE_URL = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/assets/js/financial_payment_validations_notifications.js"></script>
<script src="<?= $basePath ?>/assets/js/financial_payment_validations_pagomovil.js"></script>
