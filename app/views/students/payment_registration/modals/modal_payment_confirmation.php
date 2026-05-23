<?php
/**
 * MÓDULO: FINANCIERO / ESTUDIANTES
 * ARCHIVO: app/views/students/payment_registration/modals/modal_payment_confirmation.php
 * PROPÓSITO: Modal de confirmación previa al envío del pago con soporte para previsualización de comprobante.
 * VERSIÓN: 1.1.1 - Restauración de diseño original (2 columnas) y ajuste de jerarquía de montos en frontend.
 */

declare(strict_types=1);
?>
<div class="modal fade" id="modalPaymentConfirmation" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            
            <div class="modal-header border-0 bg-dark text-white rounded-top-4 p-3">
                <h5 class="modal-title fw-bold mb-0"><i class="bi bi-shield-check me-2"></i> Confirmación de Reporte</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3 border h-100">
                            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Resumen de la Operación</h6>
                            
                            <div class="conf-label">ESTUDIANTE:</div>
                            <div class="conf-value text-primary fs-6" id="conf_student">Cargando...</div>

                            <div class="conf-label mt-2">BANCO ORIGEN:</div>
                            <div class="conf-value" id="conf_bank">Cargando...</div>

                            <div class="conf-label mt-2">TELÉFONO / CORREO:</div>
                            <div class="conf-value" id="conf_phone">Cargando...</div>

                            <div class="conf-label mt-2">REFERENCIA:</div>
                            <div class="conf-value text-danger font-monospace fs-6" id="conf_ref">Cargando...</div>

                            <div class="conf-label mt-2">FECHA DE PAGO:</div>
                            <div class="conf-value fw-bold text-primary" id="conf_date">Cargando...</div>

                            <div class="mt-4 pt-3 border-top">
                                <div class="conf-label text-secondary" id="conf_rate_label">TASA: 0.00</div>
                                
                                <div class="conf-label text-dark mt-2">MONTO BS:</div>
                                <div class="conf-value fs-6" id="conf_bs">Bs. 0.00</div>
                                
                                <div class="conf-label text-success mt-2">MONTO USD:</div>
                                <div class="conf-value fs-5 text-success fw-bold" id="conf_usd">$ 0.00</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="h-100 d-flex flex-column">
                            <h6 class="fw-bold text-muted mb-2"><i class="bi bi-image me-1"></i> Comprobante (Pase el ratón para zoom)</h6>
                            <div class="img-zoom-container w-100 flex-grow-1 position-relative">
                                <span class="position-absolute text-muted small" id="conf_img_placeholder">Cargando imagen...</span>
                                <img src="" id="conf_image_preview" class="img-zoom-preview d-none w-100 h-100 object-fit-contain" alt="Comprobante de pago">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-outline-secondary fw-bold rounded-pill px-4" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-success fw-bold rounded-pill px-5 shadow-sm" id="btnConfirmFinalSubmit">
                    <i class="bi bi-send-check me-2"></i> Confirmar y Enviar
                </button>
            </div>
            
        </div>
    </div>
</div>