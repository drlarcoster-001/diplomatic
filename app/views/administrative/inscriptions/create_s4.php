<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/views/administrative/inscriptions/create_s4.php
 * PROPÓSITO: Registro de pago inicial (Paso 4) - Interfaz de selección de modalidad.
 * VERSIÓN: 2.9.0 - FIX: Inyección dinámica de datos de transferencia para JS.
 */

// 1. CAPTURA DE CONFIGURACIÓN (Desde el controlador $paymentSettings)
$configPM = [
    'banco'    => $paymentSettings['pago_movil']['extra_info'] ?? 'BANCO MERCANTIL',
    'telefono' => $paymentSettings['pago_movil']['identifier'] ?? '04245024183',
    'cedula'   => $paymentSettings['pago_movil']['identification'] ?? '14399195'
];
?>

<script>
    window.PAYMENT_DATA = {
        pago_movil: {
            banco: "<?= $configPM['banco'] ?>",
            telefono: "<?= $configPM['telefono'] ?>",
            cedula: "<?= $configPM['cedula'] ?>"
        }
    };
</script>

<div class="wizard-step d-none" id="step4">
   <input type="hidden" id="user_name_hidden" value="<?= $_SESSION['user']['name'] ?? 'Participante'; ?>">

    <div class="mb-4 text-center">
        <h5 class="fw-bold text-primary">Paso 4: Registro de Pago Inicial</h5>
        <p class="text-muted small">Indique la modalidad del primer abono administrativo del estudiante.</p>
    </div>

    <div class="text-center mb-4">
        <button type="button" class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold btn-sm" data-bs-toggle="modal" data-bs-target="#modalPaymentPlan">
            <i class="bi bi-calendar-check me-2"></i> Ver Plan de Pagos de la Oferta
        </button>
    </div>

    <div class="row g-4 justify-content-center mb-4">
        <div class="col-md-5">
            <div class="card payment-option-card border-2 rounded-4 p-4 text-center cursor-pointer shadow-sm h-100 d-flex flex-column align-items-center justify-content-center" id="btnOptCash">
                <div class="mb-3">
                    <i class="bi bi-cash-stack display-5 text-success"></i>
                </div>
                <h6 class="fw-bold text-dark">Efectivo / Presencial</h6>
                <p class="smallest text-muted mb-0 text-uppercase">Conciliación física en la institución posterior al registro.</p>
                
                <div id="displayAmountCash" class="mt-2 d-none">
                    <span class="badge bg-success rounded-pill px-3 shadow-sm">Abonado: <span id="valAmountCash">0.00</span> $</span>
                </div>

                <div class="selection-check d-none mt-3">
                    <i class="bi bi-check-circle-fill text-success fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card payment-option-card border-2 rounded-4 p-4 text-center cursor-pointer shadow-sm h-100 d-flex flex-column align-items-center justify-content-center" id="btnOptDigital">
                <div class="mb-3">
                    <i class="bi bi-phone-vibrate display-5 text-primary"></i>
                </div>
                <h6 class="fw-bold text-dark">Medios Digitales</h6>
                <p class="smallest text-muted mb-0 text-uppercase">Zelle, Binance Pay o Pago Móvil Interbancario.</p>
                <div class="selection-check d-none mt-3">
                    <i class="bi bi-check-circle-fill text-primary fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <div id="digitalSummary" class="alert alert-success border-0 d-none rounded-4 shadow-sm text-center p-3">
        <i class="bi bi-shield-check me-2"></i>
        <span>Monto Abonado vía <strong id="lblDigital">---</strong> configurado correctamente.</span>
    </div>
</div>

<div class="modal fade" id="modalDigital" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white border-0 p-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-credit-card-2-front me-2"></i>
                    <h6 class="modal-title fw-bold mb-0">Detalles del Pago Digital</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="smallest fw-bold text-muted text-uppercase mb-2 d-block">Seleccione Canal</label>
                    <select class="form-select rounded-3 border-2 shadow-none" id="digitalMethod">
                        <option value="">-- Seleccionar --</option>
                        <option value="ZELLE">Zelle (USD)</option>
                        <option value="BINANCE">Binance Pay (USDT)</option>
                        <option value="PAGOMOVIL">Pago Móvil (Bs.)</option>
                    </select>
                </div>
                <div id="dynamicFields" class="bg-light p-3 rounded-4 border border-primary border-opacity-10 d-none"></div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm" id="btnConfirmDigital">
                    Confirmar Monto Abonado
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPaymentPlan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h6 class="modal-title fw-bold smallest text-uppercase mb-0">Plan de Pagos de la Oferta</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 small">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3 py-2">Concepto</th>
                                <th class="text-center py-2">Vence</th>
                                <th class="text-end pe-3 py-2">Monto</th>
                            </tr>
                        </thead>
                        <tbody id="paymentPlanBody"></tbody>
                        <tfoot class="fw-bold bg-light">
                            <tr>
                                <td colspan="2" class="ps-3 py-2 text-uppercase">Total Compromiso:</td>
                                <td class="text-end pe-3 text-primary py-2" id="totalPlanAmount">---</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>