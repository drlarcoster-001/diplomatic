<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: app/views/administrative/inscriptions/create_s3.php
 * PROPÓSITO: Interfaz Paso 3 - Gestión de Documentos Digitales (PDF) con soporte Ver/Eliminar.
 * VERSIÓN: 2.2.1 - Integración con arquitectura de altura fija y soporte doc_cv. Sincronizado 2026.
 */
?>
<div class="wizard-step d-none" id="step3">
    <div class="mb-4">
        <h6 class="fw-bold text-primary"><i class="bi bi-file-earmark-pdf me-2"></i> Paso 3: Documentos Digitales</h6>
        <p class="text-muted small">Cargue los requisitos obligatorios en formato <strong>PDF digitalizado</strong>. Puede visualizar o corregir los archivos antes de finalizar.</p>
    </div>

    <div class="list-group list-group-flush border-top border-bottom mb-4">
        
        <?php 
        $docs = [
            'doc_id'     => [
                'label' => 'Documento de Identidad', 
                'icon'  => 'bi-file-earmark-person',
                'desc'  => 'Cédula o Pasaporte legible.'
            ],
            'doc_degree' => [
                'label' => 'Título Universitario', 
                'icon'  => 'bi-file-earmark-check',
                'desc'  => 'Título de pregrado fondo negro o pergamino.'
            ],
            'doc_cv'     => [
                'label' => 'Currículum Vitae', 
                'icon'  => 'bi-file-earmark-medical',
                'desc'  => 'Resumen curricular actualizado (Opcional).'
            ]
        ];
        foreach ($docs as $id => $info): 
        ?>
        <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-0 bg-transparent transition-all">
            <div class="d-flex align-items-center">
                <div class="icon-box me-3 bg-light rounded-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                    <i class="bi <?= $info['icon'] ?> fs-4 text-secondary"></i>
                </div>
                <div>
                    <span class="d-block fw-bold text-dark mb-0"><?= $info['label'] ?></span>
                    <small class="text-muted smallest-text" id="status_<?= $id ?>"><?= $info['desc'] ?></small>
                </div>
            </div>
            
            <div class="d-flex gap-2 align-items-center">
                <input type="file" name="<?= $id ?>" id="input_<?= $id ?>" class="d-none" accept="application/pdf">
                
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold action-upload shadow-sm" data-target="<?= $id ?>">
                    <i class="bi bi-upload me-1"></i> Subir
                </button>

                <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold d-none action-view shadow-sm" data-target="<?= $id ?>" title="Visualizar PDF">
                    <i class="bi bi-eye"></i> Ver
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 fw-bold d-none action-delete shadow-sm" data-target="<?= $id ?>" title="Eliminar y volver a subir">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>

    </div>

    <div class="alert alert-light border-0 small text-muted rounded-4 p-3 shadow-sm mt-auto">
        <i class="bi bi-info-circle-fill text-primary me-2"></i>
        Asegúrese de que los archivos no estén protegidos por contraseña y sean legibles para evitar retrasos en la validación administrativa.
    </div>
</div>