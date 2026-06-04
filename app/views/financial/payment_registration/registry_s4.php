<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / REGISTRO DE PAGOS
 * ARCHIVO: app/views/financial/payment_registration/registry_s4.php
 * PROPÓSITO: Definición de método de pago y monto con visualización de cuotas.
 * VERSIÓN: 1.5.0 - FIX: Inclusión de botón de edición de monto para abonos parciales.
 */
?>
<div id="step4" class="wizard-step d-none animate__animated animate__fadeIn">
    
    <div class="text-center mb-5">
        <div class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 mb-2 text-uppercase fw-bold smallest">Finalizar Proceso</div>
        <h4 class="fw-bold text-dark">Método de Pago</h4>
        <p class="text-muted small mx-auto" style="max-width: 500px;">
            Seleccione la modalidad en la que se consignan los fondos.
        </p>
    </div>

    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-4 border border-dashed">
                <div>
                    <span class="text-muted smallest text-uppercase fw-bold d-block">Monto a Pagar:</span>
                    <div class="d-flex align-items-center">
                        <span class="fs-4 fw-bold text-primary me-2">$ <span id="valAmountCash">0,00</span></span>
                        <button type="button" class="btn btn-sm btn-outline-primary border-0 rounded-circle shadow-none" id="btnEditAmount" title="Modificar monto a pagar">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-dark rounded-pill px-4 btn-sm shadow-sm" id="btnViewAccount">
                    <i class="bi bi-list-check me-2"></i>Ver Cuotas Pendientes
                </button>
            </div>
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-md-5">
            <div class="card h-100 border-2 rounded-4 payment-option-card shadow-sm cursor-pointer p-4 text-center transition-all" id="btnOptCash">
                <div class="selection-check d-none position-absolute top-0 end-0 mt-3 me-3">
                    <i class="bi bi-check-circle-fill text-success fs-4"></i>
                </div>
                <div class="mb-3">
                    <div class="icon-circle bg-success bg-opacity-10 text-success mx-auto">
                        <i class="bi bi-cash-stack fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark">Efectivo / Divisa</h5>
                <p class="small text-muted mb-0">Pago recibido físicamente en taquilla.</p>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card h-100 border-2 rounded-4 payment-option-card shadow-sm cursor-pointer p-4 text-center transition-all" id="btnOptDigital">
                <div class="selection-check d-none position-absolute top-0 end-0 mt-3 me-3">
                    <i class="bi bi-check-circle-fill text-primary fs-4"></i>
                </div>
                <div class="mb-3">
                    <div class="icon-circle bg-primary bg-opacity-10 text-primary mx-auto">
                        <i class="bi bi-qr-code-scan fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark">Digital</h5>
                <p class="small text-muted mb-0">Zelle, Binance o Pago Móvil.</p>
            </div>
        </div>
    </div>
</div>