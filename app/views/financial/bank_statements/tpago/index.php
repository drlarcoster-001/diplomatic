<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / ESTADOS DE CUENTA BANCARIOS
 * ARCHIVO: app/views/financial/bank_statements/tpago/index.php
 * PROPÓSITO: Vista de administración y carga de transacciones T-Pago.
 * VERSIÓN: 1.0.0 - Creación inicial con grid paginado, filtros y carga de archivo.
 */

declare(strict_types=1);
$basePath = '/diplomatic/public';
$fechaHoy = date('d/m/Y');
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_bank_statements.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">

    <!--{{-- Breadcrumb --}}-->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-light p-2 rounded shadow-sm border" style="font-size: 0.85rem;">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door"></i> Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/financial" class="text-decoration-none text-muted">Panel Financiero</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/financial/bank_statements" class="text-decoration-none text-muted">Estados de Cuenta</a>
            </li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">T-Pago</li>
        </ol>
    </nav>

    <!--{{-- Encabezado --}}-->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <i class="bi bi-phone-fill text-success me-2"></i>Archivo T-Pago
            </h2>
            <p class="text-muted small mb-0">Administración de transacciones de Pago Móvil registradas en el sistema.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/financial/bank_statements" class="btn btn-outline-secondary fw-bold rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <button class="btn btn-success fw-bold rounded-pill px-4 shadow-sm" id="btn-open-upload-modal">
                <i class="bi bi-file-earmark-excel me-1"></i> Cargar Archivo
            </button>
        </div>
    </div>

    <!--{{-- Tarjetas informativas --}}-->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="bg-white border-start border-4 border-success rounded shadow-sm p-3">
                <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Fecha Sistema</span>
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar3 text-success me-2 fs-5"></i>
                    <span class="fs-5 fw-bold text-dark"><?= $fechaHoy ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white border-start border-4 border-info rounded shadow-sm p-3">
                <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Total Registros</span>
                <div class="d-flex align-items-center">
                    <i class="bi bi-database text-info me-2 fs-5"></i>
                    <span class="fs-5 fw-bold text-dark" id="total-registros">---</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white border-start border-4 border-primary rounded shadow-sm p-3">
                <span class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Tabla</span>
                <div class="d-flex align-items-center">
                    <i class="bi bi-table text-primary me-2 fs-5"></i>
                    <span class="small fw-bold text-dark font-monospace">tbl_financial_bank_transactions_mobile</span>
                </div>
            </div>
        </div>
    </div>

    <!--{{-- Grid --}}-->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-list-stars text-success me-2"></i>Transacciones T-Pago
                </h6>
            </div>

            <!--{{-- Filtros --}}-->
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Fecha</label>
                    <input type="date" class="form-control form-control-sm" id="filter-date">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Referencia</label>
                    <input type="text" class="form-control form-control-sm" id="filter-reference" placeholder="Ej: 48312505566">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Monto Bs.</label>
                    <input type="number" class="form-control form-control-sm" id="filter-amount" placeholder="Ej: 13297.76" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Teléfono</label>
                    <input type="text" class="form-control form-control-sm" id="filter-phone" placeholder="Ej: 4121234567">
                </div>
                <div class="col-md-3 text-end">
                    <button class="btn btn-outline-secondary btn-sm fw-bold me-2 px-3" id="btn-clear-filters">
                        <i class="bi bi-eraser me-1"></i>Limpiar
                    </button>
                    <button class="btn btn-dark btn-sm fw-bold px-4" id="btn-search">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" id="table-tpago">
                    <thead class="table-light sticky-top" style="font-size: 0.75rem;">
                        <tr class="text-uppercase text-muted">
                            <th class="ps-4">#ID</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Referencia</th>
                            <th>Teléfono</th>
                            <th>Banco</th>
                            <th class="text-end">Monto Bs.</th>
                            <th class="text-end pe-4">Registrado</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.85rem;">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="spinner-border text-success" role="status"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-white border-top py-2 d-flex justify-content-between align-items-center" id="pagination-container"></div>
    </div>
</div>

<!--{{-- Modal Carga de Archivo --}}-->
<div class="modal fade" id="modalUploadXlsx" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg border-top border-5 border-success rounded-4">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-file-earmark-excel text-success me-2"></i>Cargar Archivo T-Pago
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-muted small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Seleccione el archivo Excel descargado desde el portal bancario T-Pago. Solo se guardarán registros nuevos (NC) que no existan en el sistema.
                </p>
                <div class="upload-zone" id="dropzone-tpago" style="border: 2px dashed #ccc; padding: 40px; border-radius: 15px; cursor: pointer; text-align: center; background: #fafafa;">
                    <input type="file" id="excelFile" accept=".xlsx" style="display: none;">
                    <i class="bi bi-cloud-arrow-up fs-1 text-success mb-2 d-block"></i>
                    <p class="mb-0 fw-bold text-dark">Haga clic para seleccionar archivo</p>
                    <p class="text-muted small">o arrastre el archivo Excel aquí</p>
                </div>
                <div id="file-info-container" class="mt-3 d-none animate__animated animate__fadeIn">
                    <div class="d-flex align-items-center p-3 bg-light rounded border border-success">
                        <i class="bi bi-file-check-fill text-success fs-4 me-2"></i>
                        <div class="overflow-hidden flex-grow-1">
                            <p class="mb-0 text-dark fw-bold small text-truncate" id="selected-file-name">archivo.xlsx</p>
                            <span class="text-muted" style="font-size: 0.7rem;" id="selected-file-size">0 KB</span>
                        </div>
                        <button type="button" class="btn-close ms-2" style="font-size: 0.6rem;" id="btn-remove-file"></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 d-flex justify-content-between">
                <button type="button" class="btn btn-danger btn-sm fw-bold px-4 rounded-pill" data-bs-dismiss="modal">CANCELAR</button>
                <button type="button" class="btn btn-success btn-sm fw-bold px-4 rounded-pill shadow-sm" id="btn-process-xlsx" disabled>PROCESAR</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script> const BASE_URL = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/assets/js/financial_bank_statements_tpago.js"></script>