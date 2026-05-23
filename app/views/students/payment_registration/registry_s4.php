<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / REGISTRO DE PAGOS
 * ARCHIVO: app/views/students/payment_registration/registry_s4.php
 * PROPÓSITO: Selección de método de pago (Digital o Promesa de Efectivo).
 * VERSIÓN: 1.0.2 - FIX: Orden de tarjetas y efectos hover para interfaz viva.
 */
?>
<style>
    /* Efectos visuales para darle vida a las tarjetas */
    .payment-option-card {
        transition: all 0.3s ease;
        border: 2px solid #e9ecef; /* Borde gris sutil por defecto */
    }
    
    /* Hover genérico y para Digital (Azul) */
    .payment-option-card:hover {
        transform: translateY(-5px);
        border-color: #0d6efd !important;
        box-shadow: 0 10px 20px rgba(13, 110, 253, 0.15) !important;
    }
    
    /* Hover específico para Efectivo (Verde) */
    #btnOptCash:hover {
        border-color: #198754 !important;
        box-shadow: 0 10px 20px rgba(25, 135, 84, 0.15) !important;
    }

    /* Clases activas cuando se seleccionan mediante JS */
    .payment-option-card.active {
        border-color: #0d6efd !important;
        background-color: rgba(13, 110, 253, 0.05);
    }
    #btnOptCash.active {
        border-color: #198754 !important;
        background-color: rgba(25, 135, 84, 0.05);
    }
</style>

<div id="step4" class="wizard-step d-none animate__animated animate__fadeIn">
    
    <div class="text-center mb-5">
        <div class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 mb-2 text-uppercase fw-bold smallest">Finalizar Reporte</div>
        <h4 class="fw-bold text-dark">Método de Pago</h4>
        <p class="text-muted small mx-auto" style="max-width: 500px;">
            Indique cómo desea realizar su abono. Puede registrar una promesa de pago en efectivo o reportar un pago electrónico.
        </p>
    </div>

    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-4 border border-dashed shadow-sm">
                <div>
                    <span class="text-muted smallest text-uppercase fw-bold d-block">Monto a Reportar:</span>
                    <div class="d-flex align-items-center">
                        <span class="fs-4 fw-bold text-primary me-2">$ <span id="valAmountCash">0,00</span></span>
                        <button type="button" class="btn btn-sm btn-outline-primary border-0 rounded-circle shadow-none" id="btnEditAmount" title="Modificar monto manualmente">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-dark rounded-pill px-4 btn-sm shadow-sm fw-bold" id="btnViewAccount">
                    <i class="bi bi-list-check me-2"></i>Ver Mis Cuotas
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
                    <div class="icon-circle bg-success bg-opacity-10 text-success mx-auto" style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; border-radius: 20px;">
                        <i class="bi bi-cash-stack fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-1">Efectivo</h5>
                <p class="smallest text-muted mb-0">Registrar promesa para pagar en taquilla.</p>
                <div class="mt-3">
                    <span class="badge bg-success bg-opacity-10 text-success smallest rounded-pill px-3">PAGO PRESENCIAL</span>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card h-100 border-2 rounded-4 payment-option-card shadow-sm cursor-pointer p-4 text-center transition-all" id="btnOptDigital">
                <div class="selection-check d-none position-absolute top-0 end-0 mt-3 me-3">
                    <i class="bi bi-check-circle-fill text-primary fs-4"></i>
                </div>
                <div class="mb-3">
                    <div class="icon-circle bg-primary bg-opacity-10 text-primary mx-auto" style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; border-radius: 20px;">
                        <i class="bi bi-qr-code-scan fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-1">Pago Electrónico</h5>
                <p class="smallest text-muted mb-0">Zelle, Binance Pay o Pago Móvil.</p>
                <div class="mt-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary smallest rounded-pill px-3">VALIDACIÓN 24-48H</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5 p-3 text-center">
        <div class="alert alert-warning border-0 rounded-4 d-inline-block p-2 px-4 shadow-sm">
            <p class="smallest text-dark mb-0">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                <strong>Importante:</strong> Los reportes están sujetos a validación administrativa antes de acreditarse a su cuenta.
            </p>
        </div>
    </div>
</div>