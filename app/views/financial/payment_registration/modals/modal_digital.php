<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / MODALES
 * ARCHIVO: app/views/financial/payment_registration/modals/modal_digital.php
 * PROPÓSITO: Captura de datos para métodos digitales (Pago Móvil, Zelle, Binance).
 * VERSIÓN: 1.4.0 - FIX: screenshotContainer con d-none inicial para evitar visibilidad prematura.
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
                        <h5 class="modal-title fw-bold mb-0" id="modalDigitalLabel">Detalle de Pago Digital</h5>
                        <p class="smallest mb-0 text-white-50 text-uppercase tracking-wider">Reporte estricto de comprobante</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
            <input type="hidden" id="student_ci_hidden" value="">
            
                <div class="mb-4">
                    <label class="form-label smallest fw-bold text-uppercase text-muted">Canal de Recepción</label>
                    <select class="form-select form-select-lg rounded-pill border-2 shadow-sm" id="digitalMethod">
                        <option value="">-- Seleccione el método --</option>
                        <option value="PAGOMOVIL">Pago Móvil (Bs.)</option>
                        <option value="ZELLE">Zelle (USD)</option>
                        <option value="BINANCE">Binance (USDT)</option>
                    </select>
                </div>

                <div id="dynamicFields" class="d-none animate__animated animate__fadeIn"></div>

                <div id="screenshotContainer" class="mt-4 pt-3 border-top border-dashed d-none">
                    <label class="form-label smallest fw-bold text-uppercase text-muted mb-2">Adjuntar Comprobante / Capture</label>
                    <input type="file" class="form-control form-control-lg rounded-pill border-2 shadow-sm" id="pay_screenshot" name="pay_screenshot" accept="image/png, image/jpeg, image/jpg">
                    <div class="mt-2 ps-3">
                        <small class="text-muted smallest"><i class="bi bi-info-circle-fill text-primary me-1"></i>Formatos permitidos: JPG, PNG. Máx 2MB.</small>
                    </div>
                </div>
                
            </div>

            <div class="modal-footer border-0 p-4 bg-light rounded-bottom-4 d-flex justify-content-between">
                <button type="button" class="btn btn-link text-muted text-decoration-none fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm" id="btnConfirmDigital">
                    Vincular Pago <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
            
        </div>
    </div>
</div>