<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL
 * ARCHIVO: app/views/students/student_statement/index.php
 * PROPÓSITO: Vista principal sin estilos embebidos. Alineación de datos al tope y botones al fondo.
 * VERSIÓN: 2.4.0 - UI/UX Refactor: Homologación Premium con diseño horizontal y PDF al fondo.
 */

declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/student_statement.css">

<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-3 animate__animated animate__fadeIn">
        <ol class="breadcrumb py-2 px-4 bg-white rounded-pill shadow-sm border-0" style="width: fit-content;">
            <li class="breadcrumb-item small"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
            <li class="breadcrumb-item small"><a href="<?= $basePath ?>/students" class="text-decoration-none text-muted">Panel Estudiantil</a></li>
            <li class="breadcrumb-item small active fw-bold text-primary" aria-current="page">Mi Estado de Cuenta</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeIn">
        <div class="d-flex align-items-center">
            <a href="<?= htmlspecialchars($basePath) ?>/students" class="btn btn-sm btn-light rounded-circle me-3 shadow-sm border">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h2 class="h4 fw-bold mb-0 text-dark">Mi Estado de Cuenta</h2>
                <p class="text-muted small mb-0">Gestión de solvencia y movimientos académicos.</p>
            </div>
        </div>
        <button id="btn-show-selector" class="btn btn-outline-primary btn-sm rounded-pill px-4 shadow-sm fw-bold d-none">
            <i class="bi bi-arrow-repeat me-2"></i>Cambiar de Programa
        </button>
    </div>

    <div id="program-selector-area" class="mb-5 animate__animated animate__fadeIn">
        <h6 class="text-muted small fw-bold text-uppercase mb-3"><i class="bi bi-collection me-2"></i>Selecciona un programa para consultar</h6>
        <div class="row g-3">
            <?php if (empty($programs)): ?>
                <div class="col-12 text-center py-5">
                    <div class="bg-light rounded-4 p-5">
                        <i class="bi bi-exclamation-circle fs-1 text-muted"></i>
                        <h5 class="mt-3 text-muted">No se encontraron inscripciones activas.</h5>
                        <p class="text-muted small">Si recién pagaste tu inscripción, espera a que Control de Estudios valide tu expediente.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($programs as $prog): ?>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 program-card h-100" data-offering-id="<?= $prog['offering_id'] ?>" style="cursor: pointer;">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="icon-box-prog me-3 bg-light text-primary rounded-circle p-3"><i class="bi bi-journal-bookmark-fill fs-4"></i></div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($prog['diplomado']) ?></h6>
                                    <small class="text-muted">Cohorte: <?= htmlspecialchars($prog['cohorte']) ?></small>
                                </div>
                                <div class="text-primary opacity-50"><i class="bi bi-chevron-right"></i></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="statement-result" class="d-none animate__animated animate__fadeIn mb-5">
        
        <div class="card shadow-sm border-0 mb-4 rounded-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap p-4">
                <div>
                    <h6 class="text-uppercase text-muted small fw-bold mb-1">Mi Expediente</h6>
                    <h5 class="fw-bold text-dark mb-0" id="info-student-name">---</h5>
                    <p class="text-muted small mb-0" id="info-student-id">C.I: --- | Código: <span id="info-codigo-student">---</span></p>
                </div>
                <div class="text-end mt-3 mt-md-0 border-start ps-md-4">
                    <span class="text-muted small d-block">Diplomado Seleccionado:</span>
                    <span class="text-primary fw-bold small" id="info-current-diplomado">---</span>
                    <div class="text-muted small mt-1">Última actividad: <span class="text-dark fw-bold" id="info-last-payment">---</span></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4 h-100 border-bottom border-3 border-primary">
                    <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                        <small class="text-muted d-block mb-1 text-uppercase fw-bold">Total Deuda Contratada</small>
                        <h4 class="fw-bold text-primary mb-0" id="total-amount-due">$ 0.00</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4 h-100 border-bottom border-3 border-success">
                    <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                        <small class="text-muted d-block mb-1 text-uppercase fw-bold">Total Pagado a la Fecha</small>
                        <h4 class="fw-bold text-success mb-2" id="total-amount-paid">$ 0.00</h4>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill fw-bold mx-auto mt-1 shadow-sm" id="btn-view-payments">
                            <i class="bi bi-clock-history"></i> Ver Mis Recibos
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 rounded-4 h-100 border-bottom border-3 border-danger bg-danger bg-opacity-10">
                    <div class="card-body p-3 text-center d-flex flex-column justify-content-center">
                        <small class="text-danger d-block mb-1 text-uppercase fw-bold">Saldo Pendiente</small>
                        <h4 class="fw-bold text-danger mb-0" id="total-balance">$ 0.00</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Movimientos Académicos</h6>
                <span class="badge bg-soft-primary text-primary rounded-pill border">Moneda: USD</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="table-ledger">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Fecha</th>
                            <th>Concepto de Cargo / Abono</th>
                            <th class="text-end">Debe (+)</th>
                            <th class="text-end">Haber (-)</th>
                            <th class="text-end">Saldo</th>
                            <th class="text-center pe-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="small"></tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end" id="action-buttons">
            <button class="btn btn-primary btn-md rounded-pill px-4 shadow-sm fw-bold" id="btn-export-pdf">
                <i class="bi bi-file-pdf me-1"></i> Exportar Estado en PDF
            </button>
        </div>
    </div>


<div class="modal fade" id="modalReceiptDetails" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-search me-2"></i>Auditoría de Transacción</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-lg-5 p-4 bg-light border-end">
                            <h6 class="text-uppercase fw-bold text-muted mb-4 border-bottom pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Detalles del Movimiento</h6>
                            <div class="mb-3">
                                <label class="d-block small text-muted">Estudiante</label>
                                <span id="v-estudiante" class="fw-bold text-dark fs-5">---</span>
                            </div>
                            <div class="mb-3">
                                <label class="d-block small text-muted">Concepto de Pago</label>
                                <span id="v-concepto" class="fw-bold text-dark">---</span>
                            </div>
                            
                            <h6 class="text-uppercase fw-bold text-muted mt-5 mb-4 border-bottom pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Desglose Financiero</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="d-block small text-muted">Fecha Aplicada</label>
                                    <span id="v-fecha" class="fw-bold text-dark">---</span>
                                </div>
                                <div class="col-6">
                                    <label class="d-block small text-muted">Referencia</label>
                                    <span id="v-referencia" class="fw-bold text-primary font-monospace">---</span>
                                </div>
                                <div class="col-12 mt-4">
                                    <label class="d-block small text-muted">Monto Registrado</label>
                                    <span class="text-dark fw-bold fs-3">
                                        <span id="v-monto-bs">0,00 Bs.</span> 
                                        <span class="badge bg-success ms-2 fs-6" id="v-monto-usd">$ 0.00</span>
                                        <div class="small text-muted fw-normal mt-1" style="font-size: 0.75rem;">Tasa Aplicada al Pago: <span id="v-tasa">0.00</span> Bs.</div>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7 d-flex flex-column bg-secondary bg-opacity-10" style="min-height: 600px;">
                            <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-muted text-uppercase"><i class="bi bi-file-earmark-text me-2"></i>Documento Adjunto</span>
                                <div class="btn-group" id="zoom-controls">
                                    <button type="button" class="btn btn-outline-dark btn-sm" id="btn-zoom-in" title="Acercar"><i class="bi bi-zoom-in"></i></button>
                                    <button type="button" class="btn btn-outline-dark btn-sm" id="btn-zoom-out" title="Alejar"><i class="bi bi-zoom-out"></i></button>
                                    <button type="button" class="btn btn-outline-dark btn-sm" id="btn-reset-zoom" title="Restaurar"><i class="bi bi-arrows-fullscreen"></i></button>
                                </div>
                            </div>
                            <div class="flex-grow-1 overflow-hidden d-flex align-items-center justify-content-center p-3 position-relative" id="img-viewer-container" style="background-color: #e9ecef;">
                                <img src="" id="v-screenshot" class="img-fluid shadow-sm d-none" style="transition: transform 0.2s; transform-origin: center center; cursor: grab;" alt="Comprobante de pago">
                                <iframe src="" id="v-pdf" class="shadow-sm d-none" style="width: 100%; height: 100%; border: none;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top p-3">
                    <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">Cerrar Visor</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalReceiptDetails" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-search me-2"></i>Auditoría de Transacción</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-lg-5 p-4 bg-light border-end">
                            <h6 class="text-uppercase fw-bold text-muted mb-4 border-bottom pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Detalles del Movimiento</h6>
                            <div class="mb-3">
                                <label class="d-block small text-muted">Estudiante</label>
                                <span id="v-estudiante" class="fw-bold text-dark fs-5">---</span>
                            </div>
                            <div class="mb-3">
                                <label class="d-block small text-muted">Concepto de Pago</label>
                                <span id="v-concepto" class="fw-bold text-dark">---</span>
                            </div>
                            
                            <h6 class="text-uppercase fw-bold text-muted mt-5 mb-4 border-bottom pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Desglose Financiero</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="d-block small text-muted">Fecha Aplicada</label>
                                    <span id="v-fecha" class="fw-bold text-dark">---</span>
                                </div>
                                <div class="col-6">
                                    <label class="d-block small text-muted">Referencia</label>
                                    <span id="v-referencia" class="fw-bold text-primary font-monospace">---</span>
                                </div>
                                <div class="col-12 mt-4">
                                    <label class="d-block small text-muted">Monto Registrado</label>
                                    <span class="text-dark fw-bold fs-3">
                                        <span id="v-monto-bs">0,00 Bs.</span> 
                                        <span class="badge bg-success ms-2 fs-6" id="v-monto-usd">$ 0.00</span>
                                        <div class="small text-muted fw-normal mt-1" style="font-size: 0.75rem;">Tasa Aplicada al Pago: <span id="v-tasa">0.00</span> Bs.</div>
                                    </span>
                                </div>
                            </div>
                            <div class="alert alert-success d-flex align-items-center mt-4 mb-0 border-0 shadow-sm">
                                <i class="bi bi-check-circle-fill me-3 fs-3"></i> 
                                <div><h6 class="mb-0 fw-bold">Transacción Aprobada</h6><small>Este comprobante ya fue conciliado.</small></div>
                            </div>
                        </div>

                        <div class="col-lg-7 d-flex flex-column bg-secondary bg-opacity-10" style="min-height: 600px;">
                            <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-muted text-uppercase"><i class="bi bi-file-earmark-text me-2"></i>Documento Adjunto</span>
                                <div class="btn-group" id="zoom-controls">
                                    <button type="button" class="btn btn-outline-dark btn-sm" id="btn-zoom-in" title="Acercar"><i class="bi bi-zoom-in"></i></button>
                                    <button type="button" class="btn btn-outline-dark btn-sm" id="btn-zoom-out" title="Alejar"><i class="bi bi-zoom-out"></i></button>
                                    <button type="button" class="btn btn-outline-dark btn-sm" id="btn-reset-zoom" title="Restaurar"><i class="bi bi-arrows-fullscreen"></i></button>
                                </div>
                            </div>
                            <div class="flex-grow-1 overflow-hidden d-flex align-items-center justify-content-center p-3 position-relative" id="img-viewer-container" style="background-color: #e9ecef;">
                                <img src="" id="v-screenshot" class="img-fluid shadow-sm d-none" style="transition: transform 0.2s; transform-origin: center center; cursor: grab;" alt="Comprobante de pago">
                                <iframe src="" id="v-pdf" class="shadow-sm d-none" style="width: 100%; height: 100%; border: none;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalReceiptDetails" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-search me-2"></i>Auditoría de Transacción</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-lg-5 p-4 bg-light border-end">
                            <h6 class="text-uppercase fw-bold text-muted mb-4 border-bottom pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Detalles del Movimiento</h6>
                            <div class="mb-3">
                                <label class="d-block small text-muted">Estudiante</label>
                                <span id="v-estudiante" class="fw-bold text-dark fs-5">---</span>
                            </div>
                            <div class="mb-3">
                                <label class="d-block small text-muted">Concepto de Pago</label>
                                <span id="v-concepto" class="fw-bold text-dark">---</span>
                            </div>
                            
                            <h6 class="text-uppercase fw-bold text-muted mt-5 mb-4 border-bottom pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Desglose Financiero</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="d-block small text-muted">Fecha Aplicada</label>
                                    <span id="v-fecha" class="fw-bold text-dark">---</span>
                                </div>
                                <div class="col-6">
                                    <label class="d-block small text-muted">Referencia</label>
                                    <span id="v-referencia" class="fw-bold text-primary font-monospace">---</span>
                                </div>
                                <div class="col-12 mt-4">
                                    <label class="d-block small text-muted">Monto Registrado</label>
                                    <span class="text-dark fw-bold fs-3">
                                        <span id="v-monto-bs">0,00 Bs.</span> 
                                        <span class="badge bg-success ms-2 fs-6" id="v-monto-usd">$ 0.00</span>
                                        <div class="small text-muted fw-normal mt-1" style="font-size: 0.75rem;">Tasa Aplicada al Pago: <span id="v-tasa">0.00</span> Bs.</div>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7 d-flex flex-column bg-secondary bg-opacity-10" style="min-height: 600px;">
                            <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-muted text-uppercase"><i class="bi bi-file-earmark-text me-2"></i>Documento Adjunto</span>
                                <div class="btn-group" id="zoom-controls">
                                    <button type="button" class="btn btn-outline-dark btn-sm" id="btn-zoom-in" title="Acercar"><i class="bi bi-zoom-in"></i></button>
                                    <button type="button" class="btn btn-outline-dark btn-sm" id="btn-zoom-out" title="Alejar"><i class="bi bi-zoom-out"></i></button>
                                    <button type="button" class="btn btn-outline-dark btn-sm" id="btn-reset-zoom" title="Restaurar"><i class="bi bi-arrows-fullscreen"></i></button>
                                </div>
                            </div>
                            <div class="flex-grow-1 overflow-hidden d-flex align-items-center justify-content-center p-3 position-relative" id="img-viewer-container" style="background-color: #e9ecef;">
                                <img src="" id="v-screenshot" class="img-fluid shadow-sm d-none" style="transition: transform 0.2s; transform-origin: center center; cursor: grab;" alt="Comprobante de pago">
                                <iframe src="" id="v-pdf" class="shadow-sm d-none" style="width: 100%; height: 100%; border: none;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top p-3">
                    <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">Cerrar Visor</button>
                </div>
            </div>
        </div>
    </div>
    ```

Una vez que guardes el archivo **`index.php`**, presiona **`Ctrl + F5`** en tu navegador para forzar a que descargue el HTML nuevo. Haz clic en el ojito y ahora sí se levantará con todo su esplendor. ¡Pruébalo y me confirmas!
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="<?= htmlspecialchars($basePath) ?>/assets/js/student_statement.js?v=<?= time() ?>"></script>