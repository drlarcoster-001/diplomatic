<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / ZELLE
 * ARCHIVO: app/views/financial/cash_operations/zelle/index.php
 * PROPÓSITO: Panel operativo para validación de pagos Zelle con visor de auditoría y protocolo de seguridad.
 * VERSIÓN: 1.1.2 - FIX: Inclusión de columna correo, botón de rechazar y ajuste de copies.
 */

declare(strict_types=1);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$fechaHoy = date('d/m/Y');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/financial_cash_operations_zelle.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-light p-2 rounded shadow-sm border" style="font-size: 0.85rem;">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/financial" class="text-decoration-none text-muted">Panel Financiero</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/financial/cash-operations" class="text-decoration-none text-muted">Validación de Inscripción</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Conciliación Zelle</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Gestión de Pagos Zelle (USD)</h2>
            <p class="text-muted small mb-0">Validación visual de recaudos financieros.</p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/cash-operations" class="btn btn-outline-secondary fw-bold shadow-sm px-3 d-flex align-items-center gap-2 rounded-pill">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form id="filter-form-zelle" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Buscar Estudiante o Referencia</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="search-text" placeholder="Ej: Confirmación #">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Desde</label>
                    <input type="date" class="form-control form-control-sm" id="date-from">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Hasta</label>
                    <input type="date" class="form-control form-control-sm" id="date-to">
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-bold me-2" id="btn-clear-filters">
                        <i class="bi bi-eraser me-1"></i>Limpiar
                    </button>
                    <button type="submit" class="btn btn-dark btn-sm px-4 fw-bold">
                        <i class="bi bi-funnel me-1"></i>Filtrar Registros
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-check text-primary me-2"></i>Zelles Pendientes por Procesar</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" id="table-zelle-pending">
                    <thead class="table-light sticky-top">
                        <tr style="font-size: 0.75rem;" class="text-uppercase text-muted">
                            <th class="ps-4">Fecha Pago</th>
                            <th>Estudiante</th>
                            <th>Titular de la cuenta</th>
                            <th>Correo de cuenta</th>
                            <th>Confirmación #</th>
                            <th class="text-end">Monto ($)</th>
                            <th class="text-center pe-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.85rem; cursor: pointer;">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalValidateZelle" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-shield-check me-2"></i>Auditoría de Pago Zelle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-lg-5 p-4 bg-light border-end">
                        <h6 class="text-uppercase fw-bold text-muted mb-4 border-bottom pb-2" style="font-size: 0.75rem;">Detalles del Estudiante</h6>
                        <div class="mb-3">
                            <label class="d-block small text-muted">Estudiante Inscrito</label>
                            <span id="v-estudiante" class="fw-bold text-dark fs-5">-</span>
                        </div>
                        <div class="mb-3">
                            <label class="d-block small text-muted">Titular de Cuenta Zelle</label>
                            <span id="v-titular" class="fw-bold text-dark">-</span>
                        </div>
                        <div class="mb-3">
                            <label class="d-block small text-muted">Correo de Cuenta</label>
                            <span id="v-correo" class="fw-bold text-dark">-</span>
                        </div>
                        
                        <h6 class="text-uppercase fw-bold text-muted mt-5 mb-4 border-bottom pb-2" style="font-size: 0.75rem;">Datos de la Transacción</h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="d-block small text-muted">Fecha del Pago</label>
                                <span id="v-fecha" class="fw-bold text-dark">-</span>
                            </div>
                            <div class="col-6">
                                <label class="d-block small text-muted">Conf. # Reportado</label>
                                <span id="v-referencia-display" class="fw-bold text-primary font-monospace">-</span>
                            </div>
                            <div class="col-12 mt-3">
                                <label class="d-block small text-muted">Monto Total</label>
                                <span id="v-monto" class="text-success fw-bold fs-2">$ 0.00</span>
                            </div>
                        </div>

                        <div class="mt-5 p-3 border rounded bg-white shadow-sm border-start border-4 border-danger">
                            <label class="form-label fw-bold text-danger small">
                                <i class="bi bi-shield-lock-fill me-1"></i> PROTOCOLO DE SEGURIDAD
                            </label>
                            <p class="text-muted mb-2" style="font-size: 0.75rem;">
                                Ingrese el número de confirmación que visualiza en la imagen para habilitar la aprobación:
                            </p>
                            <input type="text" id="input-verify-reference" class="form-control reference-input-audit" placeholder="--------" autocomplete="off">
                        </div>
                    </div>

                    <div class="col-lg-7 d-flex flex-column bg-secondary bg-opacity-10" style="min-height: 600px;">
                        <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                            <span class="small fw-bold text-muted text-uppercase"><i class="bi bi-image me-2"></i>Comprobante Digital</span>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-dark btn-sm" id="btn-zoom-in"><i class="bi bi-zoom-in"></i></button>
                                <button type="button" class="btn btn-outline-dark btn-sm" id="btn-zoom-out"><i class="bi bi-zoom-out"></i></button>
                                <button type="button" class="btn btn-outline-dark btn-sm" id="btn-reset-zoom"><i class="bi bi-arrows-fullscreen"></i></button>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-auto d-flex align-items-center justify-content-center p-3" id="img-viewer-container">
                            <img src="" id="v-screenshot" class="img-fluid shadow" style="transition: transform 0.2s ease;" alt="Capture de Zelle">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top p-3 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">Cerrar</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-danger fw-bold px-4" id="btn-reject-validation">
                        <i class="bi bi-x-circle-fill me-1"></i> Rechazar Pago
                    </button>
                    <button type="button" class="btn btn-primary fw-bold px-5" id="btn-confirm-validation" disabled>
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
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/financial_cash_operations_zelle.js?v=<?= time() ?>"></script>