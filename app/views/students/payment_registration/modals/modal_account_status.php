<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / MODALES
 * ARCHIVO: app/views/students/payment_registration/modals_account_status.php
 * PROPÓSITO: Visor de solo lectura del estado de cuenta consolidado del estudiante.
 * VERSIÓN: 1.1.0 - Refactorización a visor de solo lectura con métricas totales (Monto, Pagado, Pendiente). Eliminación de interactividad.
 */

declare(strict_types=1);
?>
<div class="modal fade" id="modalAccountStatus" tabindex="-1" aria-labelledby="modalAccountStatusLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            
            <div class="modal-header border-0 bg-primary text-white rounded-top-4 p-4 d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center">
                    <div class="icon-circle bg-white bg-opacity-25 me-3" style="width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-journal-text fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalAccountStatusLabel">Mi Estado de Cuenta</h5>
                        <p class="smallest mb-0 text-white-50 text-uppercase tracking-wider">Estatus General de pagos</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0" style="background-color: #f8f9fa;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead>
                            <tr class="bg-light text-uppercase">
                                <th class="ps-4 border-0 text-muted smallest fw-bold tracking-wider">CONCEPTO</th>
                                <th class="border-0 text-center text-muted smallest fw-bold tracking-wider">MONTO ($)</th>
                                <th class="border-0 text-center text-muted smallest fw-bold tracking-wider">PAGADO</th>
                                <th class="border-0 text-center text-muted smallest fw-bold tracking-wider">PENDIENTE</th>
                                <th class="pe-4 border-0 text-center text-muted smallest fw-bold tracking-wider">ESTATUS</th>
                            </tr>
                        </thead>
                        <tbody id="accountStatusBody">
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                                    <span class="ms-2 text-muted fw-bold small">Cargando mis cuotas...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-top p-4 bg-white rounded-bottom-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                
                <div class="d-flex gap-3 w-100 justify-content-start overflow-auto pb-2 pb-md-0">
                    <div class="text-start bg-light px-3 py-2 rounded-3 border shadow-sm flex-fill">
                        <span class="text-muted small d-block fw-bold text-uppercase smallest tracking-wider">Monto Total</span>
                        <span class="fs-5 fw-bold text-primary">$ <span id="modalTotalAmount">0.00</span></span>
                    </div>
                    
                    <div class="text-start bg-success bg-opacity-10 px-3 py-2 rounded-3 border border-success border-opacity-25 shadow-sm flex-fill">
                        <span class="text-success small d-block fw-bold text-uppercase smallest tracking-wider">Total Pagado</span>
                        <span class="fs-5 fw-bold text-success">$ <span id="modalTotalPaid">0.00</span></span>
                    </div>
                    
                    <div class="text-start bg-danger bg-opacity-10 px-3 py-2 rounded-3 border border-danger border-opacity-25 shadow-sm flex-fill">
                        <span class="text-danger small d-block fw-bold text-uppercase smallest tracking-wider">Total Pendiente</span>
                        <span class="fs-5 fw-bold text-danger">$ <span id="modalTotalPending">0.00</span></span>
                    </div>
                </div>
                
                <div class="d-flex w-100 justify-content-end mt-2 mt-md-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4 fw-bold shadow-sm" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
            
        </div>
    </div>
</div>