<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/views/administrative/inscriptions/create_s5.php
 * PROPÓSITO: Resumen final detallado con soporte para visualización de COMPROMISO DE PAGO (CASH).
 * VERSIÓN: 2.7.0 - Sincronización de etiquetas UI con la lógica de Estatus Limpios (REVISION/COMPROMISO).
 */
?>
<div class="wizard-step d-none" id="step5">
    <div class="text-center mb-4">
        <div class="mb-2">
            <i class="bi bi-file-earmark-check text-primary" style="font-size: 3.5rem;"></i>
        </div>
        <h4 class="fw-bold text-dark">Confirmar Registro</h4>
        <p class="text-muted small">Verifique el expediente del estudiante y los detalles del pago antes de finalizar.</p>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card h-100 border-0 bg-light rounded-4 shadow-sm p-3">
                <h6 class="smallest fw-bold text-primary text-uppercase mb-3">Estudiante</h6>
                <div class="d-flex align-items-center">
                    <img id="summaryAvatar" src="" class="rounded-circle me-3 border border-2 border-white shadow-sm" style="width: 55px; height: 55px; object-fit: cover;">
                    <div>
                        <div id="sumName" class="fw-bold text-dark text-uppercase lh-1 mb-1" style="font-size: 0.9rem;">---</div>
                        <small id="sumDoc" class="text-muted fw-bold smallest">---</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 border-0 bg-light rounded-4 shadow-sm p-3">
                <h6 class="smallest fw-bold text-primary text-uppercase mb-3">Información Académica</h6>
                <div class="mb-2">
                    <small class="text-muted smallest d-block">Título obtenido:</small>
                    <span id="sumDegree" class="fw-bold text-dark" style="font-size: 0.9rem;">---</span>
                </div>
                <div>
                    <small class="text-muted smallest d-block">Procedencia:</small>
                    <span id="sumProv" class="fw-bold text-dark" style="font-size: 0.9rem;">---</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 bg-light rounded-4 p-3 mb-3 shadow-sm">
        <h6 class="smallest fw-bold text-primary text-uppercase mb-3">Documentos en Expediente</h6>
        <div class="row g-2">
            <?php 
            $docsArr = [
                'doc_id'     => 'Cédula / ID',
                'doc_degree' => 'Título Académico',
                'doc_cv'     => 'Resumen Curricular'
            ];
            foreach ($docsArr as $id => $label): ?>
            <div class="col-md-4">
                <div class="d-flex align-items-center justify-content-between bg-white p-2 ps-3 rounded-pill border shadow-sm">
                    <span class="smallest text-dark fw-bold"><?= $label ?></span>
                    <div class="d-flex align-items-center">
                        <button type="button" 
                                class="btn btn-outline-primary rounded-circle p-0 d-flex align-items-center justify-content-center me-2 btn-preview-doc shadow-sm" 
                                data-doc-target="<?= $id ?>" 
                                style="width: 32px; height: 32px; border-width: 2px;"
                                title="Previsualizar">
                            <i class="bi bi-eye-fill" style="font-size: 0.9rem;"></i>
                        </button>
                        <i class="bi bi-check-circle-fill text-success fs-4" id="check_<?= $id ?>"></i>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card border-0 bg-white shadow-sm rounded-4 border-start border-4 border-warning p-3 mb-3" id="statusCardAdmin">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h6 class="smallest fw-bold text-warning text-uppercase mb-1" id="lblStatusTitle">Estatus del Registro</h6>
                <div id="sumStatus" class="fw-bold text-dark fs-5">PROCESANDO...</div>
            </div>
            <div class="text-end">
                <small class="text-muted d-block text-uppercase smallest fw-bold">Método de Pago</small>
                <span id="sumPayment" class="badge bg-dark rounded-pill px-3 py-2">---</span>
            </div>
        </div>

        <div id="paymentDetailsSummary" class="p-3 bg-light rounded-4 border d-none shadow-inner">
            <h6 class="smallest fw-bold text-muted text-uppercase mb-2 border-bottom pb-1" style="font-size: 0.65rem;">Detalles de la Operación</h6>
            <div id="metadataGrid" class="row g-2"></div>
        </div>

        <hr class="my-3 opacity-25">
        
        <div class="form-check form-switch d-flex align-items-center p-2 px-3 bg-light rounded-pill border border-primary-subtle">
            <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="sendNotification" name="send_notification" style="width: 2.8em; height: 1.4em; cursor: pointer;" checked>
            <label class="form-check-label text-dark fw-bold mb-0" for="sendNotification" style="cursor: pointer; font-size: 0.85rem;">
                <i class="bi bi-envelope-check-fill text-primary me-1"></i> Notificar al estudiante por correo
            </label>
        </div>

        <div class="mt-3 d-flex align-items-center text-warning smallest fw-bold text-uppercase" style="letter-spacing: 0.5px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span>El sistema validará la disponibilidad de cupos al confirmar</span>
        </div>
    </div>

    <div class="alert alert-primary border-0 rounded-4 shadow-sm d-flex align-items-center mb-0">
        <i class="bi bi-info-circle-fill fs-5 me-3 text-primary"></i>
        <p class="mb-0 smallest text-dark">
            Al confirmar, se generará el expediente administrativo. El estatus final dependerá de la validación de los documentos y la efectividad del pago registrado.
        </p>
    </div>
</div>