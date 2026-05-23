<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE CUOTAS - EFECTIVO
 * ARCHIVO: app/views/financial/payment_validations/efectivo/index.php
 * PROPÓSITO: Panel de ventanilla para cobro de cuotas mensuales y aplicación de cascada Ledger.
 * VERSIÓN: 1.0.0
 */

declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/financial_payment_validations_efectivo.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-light p-2 rounded shadow-sm border" style="font-size: 0.85rem;">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/financial" class="text-decoration-none text-muted">Panel Financiero</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Caja: Cobro de Cuotas</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Recepción de Efectivo (Ventanilla)</h2>
            <p class="text-muted small mb-0">Búsqueda de alumnos con deudas pendientes en el Ledger.</p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="<?= htmlspecialchars($basePath) ?>/financial/payment_validations" class="btn btn-outline-secondary fw-bold shadow-sm px-3 d-flex align-items-center gap-2 rounded-pill">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form id="filter-form-efectivo" class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label class="form-label small fw-bold text-muted">Buscar Estudiante para Cobrar</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="search-text" placeholder="Ingrese Cédula o Nombre del alumno...">
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold w-100">
                        <i class="bi bi-person-check me-1"></i> Consultar Deuda
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-people-fill text-primary me-2"></i>Alumnos con Compromisos Pendientes</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" id="table-efectivo-pending">
                    <thead class="table-light sticky-top">
                        <tr style="font-size: 0.75rem;" class="text-uppercase text-muted">
                            <th class="ps-4">Cédula</th>
                            <th>Estudiante</th>
                            <th class="text-end">Deuda Total ($)</th>
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

<div class="modal fade" id="modalValidateEfectivo" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2"></i>Procesar Pago en Ventanilla</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4 bg-light p-3 rounded border g-0">
                    <div class="col-md-7 border-end">
                        <h6 class="text-uppercase small fw-bold text-muted mb-2">Alumno</h6>
                        <span id="v-estudiante" class="d-block fw-bold text-dark fs-5">-</span>
                        <span id="v-cedula" class="text-muted small">-</span>
                    </div>
                    <div class="col-md-5 text-end">
                        <h6 class="text-uppercase small fw-bold text-muted mb-2">Total Pendiente en Ledger</h6>
                        <span id="v-deuda-total" class="d-block fw-bold text-danger fs-2">$ 0.00</span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Moneda recibida</label>
                        <select class="form-select fw-bold" id="input-currency">
                            <option value="USD">Dólares (USD)</option>
                            <option value="VES">Bolívares (VES)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Monto a abonar</label>
                        <input type="number" class="form-control form-control-lg fw-bold text-success" id="input-amount" placeholder="0.00" step="0.01">
                    </div>
                </div>

                <h6 class="fw-bold mb-3"><i class="bi bi-calculator me-2"></i>Calculadora de Billetes (USD)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle bill-table">
                        <thead class="table-light text-center small fw-bold">
                            <tr>
                                <th>Billete</th>
                                <th width="140">Cantidad</th>
                                <th class="text-end">Subtotal ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ([100, 50, 20, 10, 5, 1] as $den): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark">Denominación $<?= $den ?></td>
                                <td>
                                    <input type="number" class="form-control form-control-sm bill-input text-center fw-bold" 
                                           data-den="<?= $den ?>" value="0" min="0" oninput="window.calculateTotal()">
                                </td>
                                <td class="text-end pe-3 subtotal-display" id="sub-<?= $den ?>">$ 0.00</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="total-cash-box d-flex justify-content-between align-items-center mt-3 p-3 bg-dark text-white rounded">
                    <div class="ps-2">
                        <span class="d-block small text-warning fw-bold">TOTAL CONTADO</span>
                        <span id="monto-contado" class="fs-1 fw-bold text-white">$ 0.00</span>
                    </div>
                    <button class="btn btn-outline-warning btn-sm" onclick="window.matchAmount()">
                        <i class="bi bi-arrow-up-circle me-1"></i> Usar este monto
                    </button>
                </div>
            </div>

            <div class="modal-footer bg-white border-top p-3">
                <button type="button" class="btn btn-outline-danger fw-bold px-4 me-auto" id="btn-reject-cash">
                    <i class="bi bi-x-circle me-1"></i> Rechazar Pago
                </button>
                <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success fw-bold px-5 shadow-sm" id="btn-confirm-cash">
                    <i class="bi bi-check-circle-fill me-1"></i> Registrar Pago y Aplicar Cascada
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script> const BASE_URL = '<?= $basePath ?>'; </script>
<script src="<?= htmlspecialchars($basePath) ?>/assets/js/financial_payment_validations_efectivo.js"></script>