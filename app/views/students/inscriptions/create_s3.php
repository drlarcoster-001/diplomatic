<?php
/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: app/views/students/inscriptions/create_s3.php
 * PROPÓSITO: Interfaz de gestión de documentos con Modal de previsualización integrado.
 * VERSIÓN: 1.1.5 - FIX: Inclusión de advertencia visual de límite de tamaño (2MB).
 */
?>

<div class="wizard-step-content d-none" id="step3">
    <div class="mb-4">
        <h5 class="fw-bold text-primary d-flex align-items-center">
            <i class="bi bi-file-earmark-pdf me-2"></i> Paso 3: Documentos Digitales
        </h5>
        <p class="text-muted small">Cargue los requisitos obligatorios en formato <strong>PDF digitalizado</strong>. El tamaño máximo permitido por archivo es de <strong class="text-danger">2 Megabytes (2MB)</strong>.</p>
    </div>

    <div class="document-upload-list border-top border-bottom mb-4">
        <?php 
        $docs = [
            'doc_id'     => [
                'title' => 'Documento de Identidad', 
                'desc' => 'Cédula o Pasaporte legible. <span class="text-danger fw-bold smallest"><i class="bi bi-exclamation-triangle-fill"></i> Máx 2MB</span>'
            ],
            'doc_degree' => [
                'title' => 'Título Universitario', 
                'desc' => 'Título de pregrado fondo negro o pergamino. <span class="text-danger fw-bold smallest"><i class="bi bi-exclamation-triangle-fill"></i> Máx 2MB</span>'
            ],
            'doc_cv'     => [
                'title' => 'Currículum Vitae', 
                'desc' => 'Resumen curricular actualizado (Opcional). <span class="text-danger fw-bold smallest"><i class="bi bi-exclamation-triangle-fill"></i> Máx 2MB</span>'
            ]
        ];

        foreach ($docs as $key => $info): ?>
        <div class="d-flex align-items-center justify-content-between py-3 border-bottom doc-row" id="row_<?= $key ?>">
            <div class="d-flex align-items-center">
                <div class="icon-box bg-light rounded-3 p-3 me-3">
                    <i class="bi bi-file-earmark-text text-secondary fs-4"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-dark"><?= $info['title'] ?></h6>
                    <p class="text-muted small mb-0 doc-desc"><?= $info['desc'] ?></p>
                    <p class="text-success small mb-0 fw-bold d-none doc-details" style="font-size: 0.85rem;"></p>
                </div>
            </div>

            <div class="doc-actions d-flex align-items-center">
                <button type="button" class="btn btn-outline-primary rounded-pill px-4 btn-subir" data-id="<?= $key ?>">
                    <i class="bi bi-upload me-1"></i> Subir
                </button>

                <div class="d-none btn-group-managed align-items-center">
                    <button type="button" class="btn btn-info text-white rounded-pill px-3 me-2 btn-ver" data-id="<?= $key ?>">
                        <i class="bi bi-eye me-1"></i> Ver
                    </button>
                    <button type="button" class="btn btn-outline-danger rounded-circle p-0 d-flex align-items-center justify-content-center btn-eliminar" 
                            data-id="<?= $key ?>" style="width: 38px; height: 38px;">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>

                <input type="file" name="file_<?= $key ?>" id="file_<?= $key ?>" class="d-none input-file-real" accept=".pdf">
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-info border-0 rounded-4 shadow-sm d-flex align-items-center p-4">
        <i class="bi bi-info-circle-fill fs-4 me-3 text-primary"></i>
        <p class="mb-0 small text-dark">Asegúrese de que los archivos sean legibles para evitar retrasos. Si su PDF es muy pesado, puede usar herramientas como <a href="https://www.ilovepdf.com/es/comprimir_pdf" target="_blank" class="fw-bold">iLovePDF</a> para reducirlo.</p>
    </div>
</div>

<div class="modal fade" id="modalViewPDF" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-dark" id="modalPDFTitle">Previsualización de Documento</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="ratio ratio-16x9 bg-light rounded-3 overflow-hidden shadow-inner">
                    <iframe src="" id="iframePDF" style="border:0; height: 600px;"></iframe>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>