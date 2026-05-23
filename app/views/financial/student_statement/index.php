<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / ESTADOS DE CUENTA
 * ARCHIVO: app/views/financial/student_statement/index.php
 * PROPÓSITO: Consulta jerárquica de movimientos (Estudiante -> Tarjetas de Diplomados -> Ledger).
 * VERSIÓN: 3.2.0 - UI/UX Refactor: Diseño horizontal, consolidación de montos y botón de retorno.
 */

declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/financial_student_statement.css">
<style>
    /* Clases de apoyo rápido para la vista */
    .bg-danger.bg-opacity-10 { background-color: rgba(220, 53, 69, 0.05) !important; }
    #btn-volver-diplomados:hover { background-color: #f8f9fa; }
</style>

<div class="container-fluid py-4">
    
    <nav aria-label="breadcrumb" class="mb-3 animate__animated animate__fadeIn">
        <ol class="breadcrumb py-2 px-4 bg-white rounded-pill shadow-sm border-0" style="width: fit-content;">
            <li class="breadcrumb-item small">
                <a href="<?= htmlspecialchars($basePath) ?>/dashboard" class="text-decoration-none text-muted fw-medium">
                    <i class="bi bi-house-door me-1"></i> Inicio
                </a>
            </li>
            <li class="breadcrumb-item small">
                <a href="<?= htmlspecialchars($basePath) ?>/financial" class="text-decoration-none text-muted fw-medium">Panel Financiero</a>
            </li>
            <li class="breadcrumb-item small active fw-bold text-primary" aria-current="page">Estados de Cuenta</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeIn">
        <div>
            <div class="d-flex align-items-center mb-1">
                <a href="<?= htmlspecialchars($basePath) ?>/financial" class="btn btn-sm btn-light rounded-circle me-3 shadow-sm">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="h4 fw-bold mb-0 text-dark">Estados de Cuenta</h2>
            </div>
            <p class="text-muted small ms-5">Seleccione un estudiante y luego el programa académico para auditar sus movimientos.</p>
        </div>
        </div>

    <div class="row mb-4 justify-content-center">
        <div class="col-lg-8">
            <div class="search-group-modern d-flex align-items-center position-relative">
                <i class="bi bi-search text-primary ms-3 fs-5"></i>
                <input type="text" id="search-input" class="form-control form-control-lg border-0 shadow-none bg-transparent" 
                        placeholder="Escriba nombre o cédula del estudiante..." autocomplete="off">
                <button class="btn btn-link text-muted d-none" type="button" id="btn-clear-input">
                    <i class="bi bi-x-circle"></i>
                </button>
                
                <div id="autocomplete-results" class="list-group position-absolute w-100 shadow-lg d-none" style="z-index: 1000;">
                    </div>
            </div>
            <input type="hidden" id="selected-student-id">
        </div>
    </div>

    <div id="enrollments-section" class="d-none animate__animated animate__fadeIn mb-5">
        <div class="d-flex align-items-center mb-3">
            <div class="section-indicator-bar me-3"></div>
            <h5 class="fw-bold mb-0 text-secondary">Diplomados Asociados</h5>
        </div>
        <div class="row g-3 flex-nowrap overflow-auto pb-3 enrollment-cards-scroll" id="enrollment-cards-container">
            </div>
    </div>

    <div id="statement-result" class="d-none animate__animated animate__fadeIn mb-5">
        
        <div class="mb-4">
            <button class="btn btn-sm btn-outline-secondary rounded-pill shadow-sm fw-bold" id="btn-volver-diplomados">
                <i class="bi bi-arrow-left"></i> Volver a Diplomados
            </button>
        </div>

        <div class="card shadow-sm border-0 mb-4 rounded-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap p-4">
                <div>
                    <h6 class="text-uppercase text-muted small fw-bold mb-1">Expediente del Estudiante</h6>
                    <h5 class="fw-bold text-dark mb-0" id="info-student-name">---</h5>
                    <p class="text-muted small mb-0" id="info-student-id">C.I: ---</p>
                </div>
                <div class="text-end mt-3 mt-md-0 border-start ps-md-4">
                    <span class="text-muted small d-block">Diplomado Seleccionado:</span>
                    <span class="text-primary fw-bold small" id="info-current-diplomado">---</span>
                    <div class="text-muted small mt-1">Última actividad: <span class="text-dark fw-bold" id="info-last-payment">---</span></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4 h-100 border-bottom border-3 border-primary">
                    <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                        <small class="text-muted d-block mb-1 text-uppercase fw-bold">Total Deuda Contratada</small>
                        <h4 class="fw-bold text-primary mb-0" id="total-amount-due">$ 0.00</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4 h-100 border-bottom border-3 border-success">
                    <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                        <small class="text-muted d-block mb-1 text-uppercase fw-bold">Total Pagado a la Fecha</small>
                        <h4 class="fw-bold text-success mb-2" id="total-amount-paid">$ 0.00</h4>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill fw-bold mx-auto mt-1 shadow-sm" id="btn-view-payments">
                            <i class="bi bi-clock-history"></i> Ver Recibos
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4 h-100 border-bottom border-3 border-danger bg-danger bg-opacity-10">
                    <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                        <small class="text-danger d-block mb-1 text-uppercase fw-bold">Saldo Pendiente</small>
                        <h4 class="fw-bold text-danger mb-0" id="total-balance">$ 0.00</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Mayor Analítico de Inscripción</h6>
                <span class="badge bg-soft-primary text-primary rounded-pill">Moneda: USD</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="table-ledger">
                    <thead class="bg-light-table">
                        <tr>
                            <th class="ps-4">Fecha</th>
                            <th>Concepto de Movimiento</th>
                            <th class="text-end">Debe (+)</th>
                            <th class="text-end">Haber (-)</th>
                            <th class="text-end">Saldo Progresivo</th>
                            <th class="text-center pe-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end" id="action-buttons">
            <button class="btn btn-primary btn-md rounded-pill px-4 shadow-sm fw-bold" id="btn-export-pdf">
                <i class="bi bi-file-pdf me-1"></i> Exportar Estado en PDF
            </button>
        </div>

    </div>

    <div id="empty-state" class="py-5 text-center animate__animated animate__fadeIn">
        <div class="empty-state-icon-container mb-3">
            <i class="bi bi-wallet2 fs-1 text-muted"></i>
        </div>
        <h5 class="text-muted fw-bold">Consultor de Solvencia Estudiantil</h5>
        <p class="text-muted small">Utilice el buscador superior para localizar un estudiante y ver sus detalles contables.</p>
    </div>

    <div class="modal fade" id="modalPayments" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-shield-check me-2 text-success"></i>Verificación de Pagos Registrados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="table-history-payments">
                            <thead class="bg-light small text-muted">
                                <tr>
                                    <th class="ps-4" style="width: 15%;">Fecha</th>
                                    <th style="width: 35%;">Concepto / Detalle</th>
                                    <th class="text-end" style="width: 15%;">Monto Bs.</th>
                                    <th class="text-end" style="width: 15%;">Monto USD ($)</th>
                                    <th class="pe-4 text-center" style="width: 20%;">Nro. Referencia</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                </tbody>
                            <tfoot class="bg-light fw-bold border-top">
                                <tr>
                                    <td colspan="2" class="text-end ps-4 py-3 text-uppercase small">Total Pagado Acumulado:</td>
                                    <td class="text-end text-primary py-3" id="total-bs-modal">Bs. 0,00</td>
                                    <td class="text-end text-success py-3 fs-6" id="total-usd-modal">$ 0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>

                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-danger rounded-pill px-4 shadow-sm" id="btn-pdf-payments">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Descargar PDF
                    </button>
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="<?= htmlspecialchars($basePath) ?>/assets/js/financial_student_statement.js"></script>