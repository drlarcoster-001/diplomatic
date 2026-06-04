<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / PAGO MÓVIL
 * ARCHIVO: app/views/financial/cash_operations/pagomovil/index.php
 * PROPÓSITO: Panel operativo de conciliación multimoneda con grid scrollable, visor y sistema de rechazo.
 * VERSIÓN: 2.4.1 - FIX: Incorporación de botón de rechazo y estandarización de interfaz con Zelle.
 */

declare(strict_types=1);

// Estandarización de rutas para entorno local y producción
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$fechaHoy = date('d/m/Y');

// Validación de la variable inyectada por el controlador
$tasaReferencial = isset($last_rate) ? number_format((float)$last_rate, 2, ',', '.') : '0,00';
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/financial_cash_operations_pagomovil.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-light p-2 rounded shadow-sm border" style="font-size: 0.85rem;">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/financial" class="text-decoration-none text-muted">Panel Financiero</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/financial/cash-operations" class="text-decoration-none text-muted">Validación de Inscripción</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Conciliación Pago Móvil</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Gestión de Pago Móvil (Bs.)</h2>
            <p class="text-muted small mb-0">Validación de recaudos y conciliación bancaria multimoneda.</p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/cash-operations" class="btn btn-outline-secondary fw-bold shadow-sm px-3 d-flex align-items-center gap-2 rounded-pill">
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
            <span class="fs-5 fw-bold text-dark">
                <?= isset($last_rate) ? number_format((float)$last_rate, 2, ',', '.') : '0,00' ?> 
                <small class="text-muted">Bs.</small>
            </span>
        </div>
    </div>
</div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form id="filter-form-pagomovil" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Referencia o Estudiante</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="search-text" placeholder="Ej: 12345678...">
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
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-stars text-primary me-2"></i>Pagos Pendientes por Procesar</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" id="table-pagomovil-pending">
                        <thead class="table-light sticky-top">
                            <tr style="font-size: 0.75rem;" class="text-uppercase text-muted">
                                <th class="ps-4">#</th>
                                <th>Fecha</th>
                                <th>Estudiante</th>
                                <th>Referencia</th>
                                <th class="text-end">Monto (Bs.)</th>
                                <th class="text-end">Tasa BCV</th>
                                <th class="text-end">Monto ($)</th>
                                <th class="text-center">Conciliación</th>
                                <th class="text-center pe-4">Acciones</th>
                            </tr>
                        </thead>
                    <tbody style="font-size: 0.85rem;">
                        <tr class="placeholder-row">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                Cargando registros pendientes...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top py-3 text-end">
            <button type="button" class="btn btn-primary fw-bold shadow-sm d-none" id="btn-approve-massive">
                <i class="bi bi-check-all me-1"></i> Aprobar Pagos Conciliados
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUploadXlsx" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg border-top border-5 border-success">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-file-earmark-excel text-success me-2"></i>Validación de Datos Bancarios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="upload-zone-pm" id="dropzone-pm">
                    <input type="file" id="excelFile" accept=".xlsx" hidden>
                    <i class="bi bi-cloud-arrow-up fs-1 text-success mb-2"></i>
                    <p class="mb-0 fw-bold text-dark">Haga clic para seleccionar archivo</p>
                    <p class="text-muted small">o arrastre archivo de excel de pago aquí</p>
                </div>
                <div id="file-info-container" class="mt-3 d-none animate__animated animate__fadeIn">
                    <div class="d-flex align-items-center p-2 bg-light rounded border">
                        <i class="bi bi-file-check-fill text-success fs-4 me-2"></i>
                        <div class="overflow-hidden">
                            <p class="mb-0 text-dark fw-bold small text-truncate" id="selected-file-name">archivo.xlsx</p>
                            <span class="text-muted" style="font-size: 0.7rem;" id="selected-file-size">0 KB</span>
                        </div>
                        <button type="button" class="btn-close ms-auto" style="font-size: 0.6rem;" id="btn-remove-file"></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 d-flex justify-content-between">
                <button type="button" class="btn btn-danger btn-sm fw-bold px-3" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success btn-sm fw-bold px-4" id="btn-process-xlsx" disabled>
                    <i class="bi bi-gear-wide-connected me-1"></i> Procesar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalValidatePayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-2"></i>Validar Transacción de Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-lg-5 p-4 bg-light border-end">
                        <h6 class="text-uppercase fw-bold text-muted mb-4 border-bottom pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Detalles del Estudiante</h6>
                        <div class="mb-3">
                            <label class="d-block small text-muted">Estudiante</label>
                            <span id="v-estudiante" class="fw-bold text-dark fs-5">-</span>
                        </div>
                        <div class="mb-3">
                            <label class="d-block small text-muted">Banco de Origen</label>
                            <span id="v-banco" class="fw-bold text-dark">-</span>
                        </div>
                        <div class="mb-3">
                            <label class="d-block small text-muted">Teléfono</label>
                            <span id="v-telefono" class="fw-bold text-dark font-monospace">-</span>
                        </div>
                        
                        <h6 class="text-uppercase fw-bold text-muted mt-5 mb-4 border-bottom pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Detalles de la Transacción</h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="d-block small text-muted">Fecha</label>
                                <span id="v-fecha" class="fw-bold text-dark">-</span>
                            </div>
                            <div class="col-6">
                                <label class="d-block small text-muted">Referencia</label>
                                <span id="v-referencia" class="fw-bold text-primary font-monospace">-</span>
                            </div>
                            <div class="col-12 mt-4">
                                <label class="d-block small text-muted">Monto Registrado</label>
                                <span id="v-monto" class="text-dark fw-bold fs-3">-</span>
                            </div>
                        </div>

                        <div class="alert alert-info d-flex align-items-center mt-5 mb-0" id="alert-conciliacion">
                            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                            <div class="small">Verifique que los datos coincidan con el comprobante adjunto.</div>
                        </div>
                    </div>

                    <div class="col-lg-7 d-flex flex-column bg-secondary bg-opacity-10" style="min-height: 600px;">
                        <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                            <span class="small fw-bold text-muted text-uppercase"><i class="bi bi-image me-2"></i>Comprobante Digital</span>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-dark btn-sm" id="btn-zoom-in" title="Acercar"><i class="bi bi-zoom-in"></i></button>
                                <button type="button" class="btn btn-outline-dark btn-sm" id="btn-zoom-out" title="Alejar"><i class="bi bi-zoom-out"></i></button>
                                <button type="button" class="btn btn-outline-dark btn-sm" id="btn-reset-zoom" title="Reiniciar"><i class="bi bi-arrows-fullscreen"></i></button>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-auto d-flex align-items-center justify-content-center p-3" id="img-viewer-container">
                            <img src="" id="v-screenshot" class="img-fluid shadow-sm" style="transition: transform 0.2s ease; transform-origin: center center;" alt="Comprobante de pago">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top-0 p-3 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">Cerrar</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-danger fw-bold px-4" id="btn-reject-validation">
                        <i class="bi bi-x-circle-fill me-1"></i> Rechazar Pago
                    </button>
                    <button type="button" class="btn btn-success fw-bold px-5" id="btn-confirm-validation">
                        <i class="bi bi-check-circle-fill me-1"></i> Aprobar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script> const BASE_URL = '<?= $basePath ?>'; </script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/financial_cash_operations_pagomovil.js"></script>