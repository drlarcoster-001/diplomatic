<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / MODALES
 * ARCHIVO: app/views/financial/payment_registration/modals/modal_account_status.php
 * PROPÓSITO: Reporte informativo de Estado de Cuenta (Historial y Saldos).
 * VERSIÓN: 2.0.0 - UPGRADE: Cambio de Selección Múltiple a Vista de Reporte Informativo.
 */
declare(strict_types=1);
?>
<div class="modal fade" id="modalAccountStatus" tabindex="-1" aria-labelledby="modalAccountStatusLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            
            <div class="modal-header border-0 bg-dark text-white rounded-top-4 p-4 d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center">
                    <div class="icon-circle bg-white bg-opacity-25 me-3" style="width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-file-earmark-text fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="modalAccountStatusLabel">Estado de Cuenta</h5>
                        <p class="smallest mb-0 text-white-50 text-uppercase tracking-wider">Historial de Compromisos y Saldos</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0" style="background-color: #f8f9fa;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead>
                            <tr class="bg-light">
                                <th class="ps-4 border-0 text-center text-muted smallest fw-bold tracking-wider" style="width: 60px;">ID</th>
                                <th class="border-0 text-muted smallest fw-bold tracking-wider">CONCEPTO</th>
                                <th class="border-0 text-center text-muted smallest fw-bold tracking-wider">MONTO</th>
                                <th class="border-0 text-center text-muted smallest fw-bold tracking-wider">PAGO</th>
                                <th class="border-0 text-center text-muted smallest fw-bold tracking-wider">PENDIENTE</th>
                                <th class="pe-4 border-0 text-center text-muted smallest fw-bold tracking-wider">ESTATUS</th>
                            </tr>
                        </thead>
                        <tbody id="accountStatusBody">
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                                    <span class="ms-2 text-muted fw-bold small">Consultando registros...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-top p-4 bg-white rounded-bottom-4 d-flex justify-content-between align-items-center">
                <div class="d-flex gap-3">
                    <div class="text-start bg-light px-3 py-2 rounded-3 border">
                        <span class="text-muted smallest d-block fw-bold text-uppercase tracking-wider">Total Diplomado</span>
                        <span class="fs-5 fw-bold text-dark">$ <span id="totalDiplomado">0.00</span></span>
                    </div>
                    <div class="text-start bg-light px-3 py-2 rounded-3 border border-success-subtle">
                        <span class="text-success smallest d-block fw-bold text-uppercase tracking-wider">Total Pagado</span>
                        <span class="fs-5 fw-bold text-success">$ <span id="totalPagado">0.00</span></span>
                    </div>
                    <div class="text-start bg-light px-3 py-2 rounded-3 border border-danger-subtle">
                        <span class="text-danger smallest d-block fw-bold text-uppercase tracking-wider">Total Pendiente</span>
                        <span class="fs-5 fw-bold text-danger">$ <span id="totalPendiente">0.00</span></span>
                    </div>
                </div>
                
                <button type="button" class="btn btn-secondary rounded-pill px-5 fw-bold shadow-sm" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
            
        </div>
    </div>
</div>