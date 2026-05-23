<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: app/views/students/inscriptions/create_s5.php
 * PROPÓSITO: Resumen final detallado con soporte específico para COMPROMISO DE PAGO (CASH).
 * VERSIÓN: 1.3.1 - Sincronización de IDs para visualización dinámica de monto en efectivo.
 */
?>

<div class="wizard-step-content d-none" id="step5">
    <div class="text-center mb-4">
        <div class="mb-2">
            <i class="bi bi-shield-check text-primary" style="font-size: 3.5rem;"></i>
        </div>
        <h4 class="fw-bold text-dark">Confirmar Registro</h4>
        <p class="text-muted small">Verifique el expediente del estudiante y los detalles del pago antes de finalizar.</p>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card border-0 bg-light rounded-4 p-3 h-100 shadow-sm">
                <label class="smallest fw-bold text-primary text-uppercase mb-2 d-block">Estudiante</label>
                <div class="d-flex align-items-center mt-2">
                    <img src="<?= $urlBase ?>/assets/img/avatars/<?= htmlspecialchars($avatar) ?>" 
                         class="rounded-circle border border-2 border-white shadow-sm me-3" 
                         style="width: 55px; height: 55px; object-fit: cover;">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark text-uppercase" style="font-size: 0.9rem;"><?= htmlspecialchars($displayName) ?></h6>
                        <p class="text-muted smallest mb-0">Cédula: <?= htmlspecialchars($documentId) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 bg-light rounded-4 p-3 h-100 shadow-sm">
                <label class="smallest fw-bold text-primary text-uppercase mb-2 d-block">Información Académica</label>
                <div class="mt-2">
                    <div class="mb-2">
                        <small class="text-muted smallest d-block">Título obtenido:</small>
                        <span class="fw-bold text-dark" id="resume_degree" style="font-size: 0.9rem;">---</span>
                    </div>
                    <div>
                        <small class="text-muted smallest d-block">Lugar de Procedencia:</small>
                        <span class="fw-bold text-dark" id="resume_provenance" style="font-size: 0.9rem;">---</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 bg-light rounded-4 p-3 mb-3 shadow-sm">
        <label class="smallest fw-bold text-primary text-uppercase mb-3 d-block">Documentos Subidos</label>
        <div class="row g-2">
            <?php 
            $resumeDocs = [
                'doc_id'     => 'Documento de Identidad',
                'doc_degree' => 'Título Universitario',
                'doc_cv'     => 'Currículum Vitae'
            ];
            foreach ($resumeDocs as $slug => $label): ?>
            <div class="col-md-4">
                <div class="d-flex align-items-center justify-content-between bg-white p-2 ps-3 rounded-pill border shadow-sm transition-all">
                    <span class="smallest text-dark fw-bold"><?= $label ?></span>
                    <div class="d-flex align-items-center">
                        <button type="button" 
                                class="btn btn-outline-primary rounded-circle p-0 d-none btn-preview-resume d-flex align-items-center justify-content-center me-2 shadow-sm" 
                                data-id="<?= $slug ?>" 
                                style="width: 32px; height: 32px; border-width: 2px;"
                                title="Previsualizar">
                            <i class="bi bi-eye-fill" style="font-size: 0.9rem;"></i>
                        </button>
                        <i class="bi bi-check-circle-fill text-secondary fs-4" id="check_resume_<?= $slug ?>"></i>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card border-0 border-start border-4 border-warning rounded-4 p-4 shadow-sm bg-white mb-3" id="resume_status_card">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <label class="smallest fw-bold text-warning text-uppercase mb-1 d-block" style="letter-spacing: 1px;">Estatus del Registro</label>
                <h5 class="fw-bold text-dark mb-0" id="resume_status_text">EN REVISIÓN</h5>
            </div>
            <div class="text-end">
                <label class="smallest fw-bold text-muted text-uppercase mb-1 d-block">Método</label>
                <span class="badge bg-dark rounded-pill px-3 py-2 text-uppercase" id="resume_method_badge">---</span>
            </div>
        </div>

        <div id="payment_detail_box" class="p-3 border rounded-4 bg-light d-none shadow-inner">
            <label class="smallest fw-bold text-muted text-uppercase mb-2 d-block" style="font-size: 0.65rem;">Detalles de la operación</label>
            <div id="payment_table_content">
                </div>
        </div>

        <div class="mt-3 d-flex align-items-center text-warning smallest fw-bold">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span>PRONTO RECIBIRÁ UN CORREO CON EL ESTATUS DE LA INSCRIPCIÓN</span>
        </div>
    </div>

    <div class="alert alert-primary border-0 rounded-4 shadow-sm d-flex align-items-center p-3 mb-0">
        <i class="bi bi-info-circle-fill fs-5 me-3 text-primary"></i>
        <p class="mb-0 smallest text-dark">
            Al confirmar, el expediente quedará guardado en estatus <strong>EN REVISIÓN</strong> hasta que la coordinación valide la integridad de los documentos y requisitos.
        </p>
    </div>
</div>
<div class="modal fade" id="modalViewReceipt" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg bg-dark">
            <div class="modal-header border-0 p-3 pb-0 d-flex justify-content-between align-items-center position-absolute w-100" style="z-index: 10;">
                <h6 class="modal-title fw-bold text-white text-uppercase smallest" style="text-shadow: 0 2px 4px rgba(0,0,0,0.8);">Comprobante de Pago</h6>
                <button type="button" class="btn-close btn-close-white shadow-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 text-center" style="position: relative; height: 75vh; overflow: hidden;" id="receiptContainer">
                <img id="receiptImage" src="" style="width: 100%; height: 100%; object-fit: contain; cursor: grab; transform: scale(1) translate(0px, 0px); transition: transform 0.1s ease;" alt="Capture de Pago">
            </div>
            <div class="modal-footer bg-dark border-0 p-2 justify-content-center">
                <small class="text-white-50"><i class="bi bi-arrows-move me-1"></i> Arrastra para mover o usa la rueda para hacer zoom</small>
            </div>
        </div>
    </div>
</div>