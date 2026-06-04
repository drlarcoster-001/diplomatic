<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / MODALES
 * ARCHIVO: app/views/students/payment_registration/modals_digital.php
 * PROPÓSITO: Captura de datos de pagos electrónicos por parte del estudiante.
 * VERSIÓN: 1.0.1 - FIX: Alineación y validación de IDs para sincronización JS.
 */

declare(strict_types=1);
?>
<div class="modal fade" id="modalDigital" tabindex="-1" aria-labelledby="modalDigitalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            
            <div class="modal-header border-0 bg-primary text-white p-4 rounded-top-4 d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center">
                    <div class="icon-circle bg-white bg-opacity-25 me-3" style="width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-qr-code-scan fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalDigitalLabel">Reportar Pago Móvil</h5>
                        <p class="smallest mb-0 text-white-50 text-uppercase tracking-wider">Carga de comprobante electrónico</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                
                <div class="mb-4">
                    <label class="form-label smallest fw-bold text-uppercase text-muted">¿Cómo realizaste el pago?</label>
                    <select class="form-select form-select-lg rounded-pill border-2 shadow-sm" id="digitalMethod">
                        <option value="">-- Seleccione el método --</option>
                        <option value="PAGOMOVIL">Pago Móvil (Bs.)</option>
                        <!--<option value="ZELLE">Zelle (USD)</option>
                        <option value="BINANCE">Binance Pay (USDT)</option>-->
                    </select>
                </div>

                <div id="dynamicFields" class="d-none animate__animated animate__fadeIn"></div>

                <div id="screenshotContainer" class="mt-4 pt-3 border-top border-dashed d-none animate__animated animate__fadeIn">
                    <label class="form-label smallest fw-bold text-uppercase text-muted mb-2">Adjuntar Capture / Comprobante</label>
                    <input type="file" class="form-control form-control-lg rounded-pill border-2 shadow-sm" id="pay_screenshot" name="pay_screenshot" accept="image/png, image/jpeg, image/jpg">
                    <div class="mt-2 ps-3">
                        <small class="text-muted smallest">
                            <i class="bi bi-info-circle-fill text-primary me-1"></i>
                            Formatos: JPG, PNG. Asegúrese de que la referencia sea legible.
                        </small>
                    </div>
                </div>
                
            </div>

            <div class="modal-footer border-0 p-4 bg-light rounded-bottom-4 d-flex justify-content-between">
                <button type="button" class="btn btn-link text-muted text-decoration-none fw-bold" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm" id="btnConfirmDigital">
                    Confirmar Datos <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
            
        </div>
    </div>
</div>

<script>
document.getElementById('modalDigital')?.addEventListener('shown.bs.modal', function () {
    const select = document.getElementById('digitalMethod');
    if (select && select.value === '') {
        select.value = 'PAGOMOVIL';
        select.dispatchEvent(new Event('change'));
    }
});
</script>