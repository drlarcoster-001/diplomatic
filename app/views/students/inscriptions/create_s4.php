<?php
/**
 * MÓDULO: PANEL FINANCIERO / INSCRIPCIONES
 * ARCHIVO: app/views/students/inscriptions/create_s4.php
 * PROPÓSITO: Registro de pago con inyección dinámica de datos de la base de datos y soporte para deselección.
 * VERSIÓN: 1.6.8 - Fix de visibilidad de badge X, soporte responsivo y estandarización de encabezados.
 */

// 1. PASO DE DATOS DE LA BASE DE DATOS AL JAVASCRIPT
$configPM = [
    'banco'    => $paymentSettings['pago_movil']['extra_info'] ?? 'BANCO MERCANTIL',
    'telefono' => $paymentSettings['pago_movil']['identifier'] ?? '04245024183',
    'cedula'   => $paymentSettings['pago_movil']['identification'] ?? '14399195'
];
?>

<script>
    window.PAYMENT_CONFIG = {
        pago_movil: {
            banco: "<?= $configPM['banco'] ?>",
            telefono: "<?= $configPM['telefono'] ?>",
            cedula: "<?= $configPM['cedula'] ?>"
        }
    };
</script>

<div class="wizard-step-content d-none" id="step4">
    <div class="mb-4 text-center">
        <h5 class="fw-bold text-primary">Paso 4: Registro de Pago Inicial</h5>
        <p class="text-muted small mb-3">Indique la modalidad del primer abono administrativo del estudiante.</p>
        
        <?php 
            // Blindaje para evitar que el ID de la oferta llegue en 0 al JS
            $idSeguro = (int)($offering['id'] ?? $selectedOfferingId ?? $_GET['id'] ?? 0);
        ?>
        
        <div class="mb-4 text-center">
            <button type="button" 
                    class="btn btn-primary text-white rounded-pill px-4 fw-bold shadow-sm btn-plan-pagos" 
                    onclick="verPlanDePagosPaso4(<?= $idSeguro ?>)"
                    style="font-size: 0.9rem; padding: 10px 25px;">
                <i class="bi bi-journal-check me-2"></i> VER PLAN DE PAGO
            </button>
        </div>

        <div class="mt-4 animate__animated animate__fadeIn">
            <label class="smallest fw-bold text-muted text-uppercase mb-1" style="letter-spacing: 0.5px;">Monto Total Sugerido para la inscripción ($)</label>
            <div class="d-flex justify-content-center">
                <div class="input-group shadow-sm" style="max-width: 220px;">
                    <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3 text-success">
                        <i class="bi bi-currency-dollar fw-bold"></i>
                    </span>
                    <input type="text" 
                           class="form-control border-start-0 fw-bold text-success fs-5 text-center rounded-end-pill bg-white" 
                           id="display_amount_readonly" 
                           value="0.00" 
                           readonly 
                           style="cursor: default; pointer-events: none;">
                </div>
            </div>
            <p class="smallest text-muted mt-2 mb-0 italic">
                <i class="bi bi-info-circle me-1"></i> Este monto es el sugerido para inscribirse.
            </p>
        </div>
    </div>

    <div class="row g-4 justify-content-center mb-4 mt-1">
        
        <div class="col-md-5 col-12">
            <div class="card payment-option-card border-2 rounded-4 p-4 text-center cursor-pointer shadow-sm h-100 d-flex flex-column align-items-center justify-content-center position-relative" id="btnOptCash">
                
                <div class="btn-deselect d-none" onclick="event.stopPropagation(); deselectMethod();" title="Quitar selección">
                    <i class="bi bi-x-lg"></i>
                </div>

                <div class="mb-3">
                    <i class="bi bi-cash-stack display-5 text-success"></i>
                </div>
                <h6 class="fw-bold text-dark">Efectivo (CASH)</h6>
                <p class="smallest text-muted mb-0 text-uppercase">CONCILIACIÓN FÍSICA EN RECEPCIÓN POSTERIOR AL REGISTRO.</p>
                
                <div id="displayAmountCash" class="mt-2 d-none">
                    <span class="badge bg-success rounded-pill px-3 shadow-sm">Monto Abonado: $<span id="valAmountCash">0.00</span></span>
                </div>

                <div class="selection-check d-none mt-3">
                    <i class="bi bi-check-circle-fill text-success fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-md-5 col-12">
            <div class="card payment-option-card border-2 rounded-4 p-4 text-center cursor-pointer shadow-sm h-100 d-flex flex-column align-items-center justify-content-center position-relative" id="btnOptDigital">
                
                <div class="btn-deselect d-none" onclick="event.stopPropagation(); deselectMethod();" title="Quitar selección">
                    <i class="bi bi-x-lg"></i>
                </div>

                <div class="mb-3">
                    <i class="bi bi-phone-vibrate display-5 text-primary"></i>
                </div>
                <h6 class="fw-bold text-dark">PAGO MÓVIL</h6>
                <p class="smallest text-muted mb-0 text-uppercase">Pago inmediato a cuenta bancaria</p>
                
                <div class="selection-check d-none mt-3">
                    <i class="bi bi-check-circle-fill text-primary fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="payment_method_type" id="payment_method_type" value="">
    <input type="hidden" name="payment_metadata" id="payment_metadata" value="">
    <input type="hidden" name="amount" id="amount" value="60.00">

    <div id="digitalSummary" class="alert alert-primary border-0 d-none rounded-4 shadow-sm text-center p-3">
        <i class="bi bi-shield-check me-2"></i>
        <span>Monto Abonado vía <strong id="lblDigital">---</strong> configurado correctamente.</span>
    </div>
</div>

<div class="modal fade" id="modalDigital" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                <h6 class="modal-title fw-bold text-uppercase smallest">
                    <i class="bi bi-credit-card-2-front me-2"></i>Detalles del Pago Digital
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label smallest fw-bold text-muted text-uppercase">Seleccione Pago Movil</label>
                    <select class="form-select rounded-3" id="digitalMethod">
                        <option value="">-- Seleccionar --</option>
                        <!--<option value="ZELLE">Zelle (USD)</option>
                        <option value="BINANCE">Binance Pay (USDT)</option>-->
                        <option value="PAGOMOVIL">Pago Móvil (Bs.)</option>
                    </select>
                </div>

                <div id="dynamicFields" class="d-none"></div>

            </div>
            <div class="modal-footer border-0 p-3">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold btn-sm shadow-sm" id="btnConfirmDigital">
                    Confirmar Pago
                </button>
            </div>
        </div>
    </div>
</div>


<script>
// "La Trampa Maestra": Filtra por nombre, no por ID
document.addEventListener('input', function (e) {
    // Buscamos por el atributo NAME que es lo que sí tienes
    if (e.target.name === 'pm_ref' || e.target.name === 'z_ref' || e.target.name === 'b_order') {
        
        // 1. Quitamos todo lo que no sea número
        let valorLimpio = e.target.value.replace(/[^0-9]/g, '');
        
        // 2. Cortamos a 20 caracteres máximo
        if (valorLimpio.length > 20) {
            valorLimpio = valorLimpio.substring(0, 20);
        }
        
        // 3. Devolvemos el valor limpio al campo
        e.target.value = valorLimpio;
    }
}, true); // El 'true' es para que capture el evento sí o sí

// También bloqueamos el "Pegar" basura
document.addEventListener('paste', function (e) {
    if (e.target.name === 'pm_ref') {
        let paste = (e.clipboardData || window.clipboardData).getData('text');
        if (!/^\d+$/.test(paste)) {
            e.preventDefault();
            const clean = paste.replace(/[^0-9]/g, '').substring(0, 20);
            e.target.value = clean;
        }
    }
}, true);
</script>

<script>
// Cuando el modal de pago digital se abra, seleccionamos Pago Móvil automáticamente
const modalDigital = document.getElementById('modalDigital');
if (modalDigital) {
    modalDigital.addEventListener('shown.bs.modal', function () {
        const select = document.getElementById('digitalMethod');
        if (select.value === "") {
            select.value = "PAGOMOVIL";
            // Disparamos el evento 'change' para que tu sistema renderice los campos automáticamente
            select.dispatchEvent(new Event('change'));
        }
    });
}
</script>