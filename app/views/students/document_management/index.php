<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL
 * ARCHIVO: app/views/students/document_management/index.php
 * PROPÓSITO: Gestión de recaudos. Incluye Card de Programa, Lista de Requisitos y Alerta de Compresión.
 * VERSIÓN: 1.9.6 - Fix: Re-inserción de alerta informativa (iLovePDF) y estandarización visual.
 */

declare(strict_types=1);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$enrollments = $enrollments ?? [];
$current_docs = $current_docs ?? null;
$selected_id = $selected_id ?? null;
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/students_document_management.css">

<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb py-2 px-4 bg-white rounded-pill shadow-sm border-0" style="width: fit-content;">
            <li class="breadcrumb-item small"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
            <li class="breadcrumb-item small"><a href="<?= $basePath ?>/students" class="text-decoration-none text-muted">Panel Estudiantil</a></li>
            <li class="breadcrumb-item small active fw-bold text-primary" aria-current="page">Gestión de Documentos</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="<?= htmlspecialchars($basePath) ?>/students" class="btn btn-sm btn-light rounded-circle me-3 shadow-sm border">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-0 text-dark">Gestión de Documentos</h4>
                <p class="text-muted small mb-0">Suba sus requisitos obligatorios para la conformación del expediente.</p>
            </div>
        </div>
        <?php if (isset($selected_id)): ?>
            <button onclick="location.href='<?= $basePath ?>/students/documents'" class="btn btn-outline-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                <i class="bi bi-arrow-repeat me-2"></i>Cambiar de Programa
            </button>
        <?php endif; ?>
    </div>

    <div id="program-selector-area" class="mb-4 <?= isset($selected_id) ? 'd-none' : '' ?>">
        <h6 class="text-muted small fw-bold text-uppercase mb-3"><i class="bi bi-collection me-2"></i>Selecciona un programa para gestionar</h6>
        <div class="row g-3">
            <?php foreach ($enrollments as $enroll): ?>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 program-card h-100" 
                         onclick="location.href='<?= $basePath ?>/students/documents?id=<?= $enroll['enrollment_id'] ?>'" 
                         style="cursor: pointer;">
                        <div class="card-body d-flex align-items-center p-3">
                            <div class="icon-box bg-light text-primary rounded-circle p-3 me-3">
                                <i class="bi bi-journal-check fs-4"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($enroll['diploma_name']) ?></h6>
                                <small class="text-muted">Cohorte: <?= htmlspecialchars($enroll['cohort_name'] ?? 'N/A') ?></small>
                            </div>
                            <div class="text-primary opacity-50"><i class="bi bi-chevron-right"></i></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (isset($selected_id) && $current_docs): ?>
    <div class="animate__animated animate__fadeIn">
        
        <div class="card shadow-sm border-0 rounded-4 bg-white mb-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap p-4">
                <div>
                    <h6 class="text-uppercase text-muted small fw-bold mb-1">Programa Académico Seleccionado</h6>
                    <h5 class="fw-bold text-dark mb-0">
                        <?php foreach($enrollments as $e) { if($e['enrollment_id'] == $selected_id) echo htmlspecialchars($e['diploma_name']); } ?>
                    </h5>
                </div>
                <div class="text-end mt-3 mt-md-0 border-start ps-md-4">
                    <h6 class="text-uppercase text-muted small fw-bold mb-1">Estatus Expediente</h6>
                    <span class="badge <?= $current_docs['document_status'] === 'COMPLETE' ? 'bg-success' : 'bg-danger' ?> rounded-pill px-3 shadow-sm">
                        <i class="bi <?= $current_docs['document_status'] === 'COMPLETE' ? 'bi-patch-check-fill' : 'bi-exclamation-triangle-fill' ?> me-1"></i>
                        <?= $current_docs['document_status'] === 'COMPLETE' ? 'EXPEDIENTE COMPLETO' : 'EXPEDIENTE INCOMPLETO' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h5 class="fw-bold text-primary d-flex align-items-center">
                <i class="bi bi-file-earmark-pdf me-2"></i> Requisitos Digitales
            </h5>
            <p class="text-muted small">Formatos permitidos: <strong>PDF</strong>. Tamaño máximo: <strong>2MB</strong> por archivo.</p>
        </div>

        <div class="document-upload-list border-top border-bottom mb-4 bg-white px-3 shadow-sm rounded-4">
            <?php 
            $docs_map = [
                'doc_id_card' => ['title' => 'Documento de Identidad *', 'desc' => 'Cédula o Pasaporte legible.'],
                'doc_degree'  => ['title' => 'Título Universitario *', 'desc' => 'Título de pregrado (Fondo negro o pergamino).'],
                'doc_cv'      => ['title' => 'Curriculum Vitae (Opcional)', 'desc' => 'Resumen curricular actualizado.']
            ];

            foreach ($docs_map as $key => $info): 
                $path = $current_docs[$key];
                $hasFile = !empty($path);
            ?>
            <div class="d-flex align-items-center justify-content-between py-4 border-bottom doc-row" id="row_<?= $key ?>">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-light rounded-3 p-3 me-3 text-<?= $hasFile ? 'success' : 'secondary' ?>">
                        <i class="bi <?= $hasFile ? 'bi-file-earmark-check-fill' : 'bi-file-earmark-pdf' ?> fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><?= $info['title'] ?></h6>
                        <p class="text-muted small mb-0"><?= $info['desc'] ?> <span class="text-danger fw-bold smallest">Máx 2MB</span></p>
                    </div>
                </div>

                <div class="doc-actions">
                    <?php if (!$hasFile): ?>
                        <button type="button" class="btn btn-outline-primary rounded-pill px-4 btn-sm fw-bold" onclick="document.getElementById('file_<?= $key ?>').click()">
                            <i class="bi bi-upload me-1"></i> Subir
                        </button>
                    <?php else: ?>
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-info text-white rounded-pill px-3 me-2 btn-sm fw-bold" 
                                    onclick="previewPDF('<?= $basePath . '/' . $path ?>', '<?= $info['title'] ?>')">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger rounded-circle d-flex align-items-center justify-content-center" 
                                    onclick="confirmDelete('<?= $selected_id ?>', '<?= $key ?>', '<?= $info['title'] ?>')" style="width: 38px; height: 38px;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="file_<?= $key ?>" class="d-none" accept=".pdf" onchange="handleFileUpload('<?= $selected_id ?>', '<?= $key ?>')">
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="alert alert-info border-0 rounded-4 shadow-sm d-flex align-items-center p-4 mb-4">
            <i class="bi bi-info-circle-fill fs-4 me-3 text-primary"></i>
            <p class="mb-0 small text-dark">Asegúrese de que los archivos sean legibles. Si su PDF pesa más de 2MB, puede usar <a href="https://www.ilovepdf.com/es/comprimir_pdf" target="_blank" class="fw-bold text-primary text-decoration-none">iLovePDF</a> para reducir su tamaño antes de subirlo.</p>
        </div>

        <div class="d-flex justify-content-end gap-3 mt-4">
            <a href="<?= $basePath ?>/students" class="btn btn-light rounded-pill px-5 fw-bold text-muted border shadow-sm text-decoration-none">
                Cancelar
            </a>
            <button type="button" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold" onclick="saveFinalChanges()">
                <i class="bi bi-save me-1"></i> Guardar Cambios
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalViewPDF" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h6 class="modal-title fw-bold text-dark" id="modalPDFTitle">Previsualización de Documento</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="ratio ratio-16x9 bg-light rounded-3 overflow-hidden border">
                    <iframe src="" id="iframePDF" style="border:0; height: 600px;"></iframe>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function previewPDF(url, title) {
        document.getElementById('modalPDFTitle').innerText = title;
        document.getElementById('iframePDF').src = url;
        new bootstrap.Modal(document.getElementById('modalViewPDF')).show();
    }
</script>
<script src="<?= $basePath ?>/assets/js/students_document_management.js?v=<?= time() ?>"></script>