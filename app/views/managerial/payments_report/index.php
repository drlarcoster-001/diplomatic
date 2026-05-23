<?php
/**
 * MÓDULO: PANEL GERENCIAL / REPORTE DE PAGOS
 * ARCHIVO: app/views/managerial/payments_report/index.php
 * PROPÓSITO: Interfaz premium con matriz horizontal de 12 columnas, paginación segmentada y filtros.
 * VERSIÓN: 9.3.0 
 * FIX: Paginación dinámica (25 reg), Z-index del menú Exportar, Scroll fino y etiquetas actualizadas.
 */

declare(strict_types=1);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$actualBase = (strpos($basePath, 'public') === false) ? $basePath . '/public' : $basePath;
?>

<script> const BASE_URL = "<?= $actualBase ?>"; </script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<link rel="stylesheet" href="<?= $actualBase ?>/assets/css/managerial_payments_report.css?v=<?= time() ?>">

<div class="container-fluid py-4 px-md-5">
    
    <nav aria-label="breadcrumb" class="mb-4 animate__animated animate__fadeIn">
        <ol class="breadcrumb py-2 px-4 bg-white rounded-pill shadow-sm border-0" style="width: fit-content;">
            <li class="breadcrumb-item small"><a href="<?= $actualBase ?>/dashboard" class="text-decoration-none text-muted fw-medium">Inicio</a></li>
            <li class="breadcrumb-item small"><a href="<?= $actualBase ?>/managerial" class="text-decoration-none text-muted fw-medium">Panel Gerencial</a></li>
            <li class="breadcrumb-item active fw-bold text-orange small" aria-current="page">Reporte de Pagos de Inscritos</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-5 animate__animated animate__fadeIn">
        <div>
            <h2 class="helium-title mb-1">Reporte de Pagos de Inscritos</h2>
            <p class="helium-subtitle">Matriz horizontal de recaudación validada y compromisos en tránsito.</p>
        </div>
        <a href="<?= $actualBase ?>/managerial" class="btn-helium-back fw-bold">
            <i class="bi bi-arrow-left me-1"></i> VOLVER AL PANEL
        </a>
    </div>

    <div class="card card-helium border-0 shadow-sm mb-5 animate__animated animate__fadeIn">
        <div class="card-body p-4">
                <form id="form-financial-filters" class="row g-4 align-items-end">
                
                <div class="col-md-3">
                    <div class="helium-label-container"><span class="helium-label">PARTICIPANTE / CÉDULA</span></div>
                    <div class="helium-input-wrapper">
                        <i class="bi bi-search ms-3 text-muted"></i>
                        <input type="text" name="student" id="dynamic-student-search" class="helium-input" placeholder="Buscar Estudiante...">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="helium-label-container"><span class="helium-label">DIPLOMADO / OFERTA</span></div>
                    <select name="offering_id" id="filter_offering" class="helium-select shadow-none">
                        <option value="ALL">Todas las Ofertas Activas</option>
                        <?php if(!empty($offerings)): foreach($offerings as $off): ?>
                            <option value="<?= $off['id'] ?>"><?= htmlspecialchars($off['diploma_name']) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="helium-label-container"><span class="helium-label">GRUPO / SECCIÓN</span></div>
                    <select name="group_id" id="filter_group" class="helium-select shadow-none">
                        <option value="ALL">Todos los Grupos</option>
                        </select>
                </div>

                <div class="col-md-3">
                    <div class="helium-label-container"><span class="helium-label">FILTRAR POR ESTATUS</span></div>
                    <select name="status" class="helium-select shadow-none">
                        <option value="ALL" selected>Ver Todos</option>
                        <option value="PAGADO">PAGADO</option>
                        <option value="ABONADO">ABONADO</option>
                        <option value="SIN MOVIMIENTO">SIN MOVIMIENTO</option>
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end gap-3 border-top pt-3">
                    <button type="reset" class="btn-helium-secondary px-4 fw-bold">REINICIAR</button>
                    <button type="submit" class="btn-helium-primary shadow px-5 fw-bold">
                        <i class="bi bi-gear-fill me-2"></i>PROCESAR REPORTE
                    </button>
                </div>
            </form>


        </div>
    </div>

    <div id="report-results-container" class="d-none animate__animated animate__fadeIn">
        <div class="card card-helium border-0 shadow-sm overflow-hidden bg-white">
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h6 class="helium-table-title mb-0" style="font-size: 14px; font-weight: 700;">VISUALIZACIÓN DE DATOS ONLINE (USD)</h6>
                
                <div class="dropdown">
                    <button class="btn-helium-export dropdown-toggle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="position: relative; z-index: 1060;">
                        <i class="bi bi-cloud-download me-2"></i>EXPORTAR
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2">
                        <li><button class="dropdown-item py-2 px-3 fw-bold" id="btn-export-excel"><i class="bi bi-file-earmark-excel text-success me-2"></i> a Excel</button></li>
                        <li><button class="dropdown-item py-2 px-3 fw-bold" id="btn-export-pdf"><i class="bi bi-file-earmark-pdf text-danger me-2"></i> a PDF</button></li>
                    </ul>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="helium-table-scroll"> 
                    <table class="table table-hover align-middle mb-0" id="matrix-table">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="ps-4" style="width: 15%;">Participante</th>
                                <th class="text-center" style="width: 8%;">ID/Cédula</th>
                                <th class="text-center" style="width: 12%;">Diplomado</th>

                                <th class="text-center" style="width: 10%;">Grupos</th>

                                <th class="text-center">Inscripción</th>
                                <th class="text-center">C1</th>
                                <th class="text-center">C2</th>
                                <th class="text-center">C3</th>
                                <th class="text-center">C4</th>
                                <th class="text-center">C5</th>
                                <th class="text-end pe-4 bg-light fw-bold" style="border-left: 2px solid #eee;">Abonado Real</th>
                                <th class="text-center text-orange" style="width: 15%;">Observación</th>
                                
                            </tr>
                        </thead>
                        <tbody id="matrix-tbody" class="table-body-large">
                            </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white py-4 px-4 border-top d-flex justify-content-between align-items-center">
                
                <div class="d-flex align-items-center gap-3">
                    <div id="pagination-controls" class="btn-group shadow-sm">
                        </div>
                    <div id="pagination-info" class="text-muted small fw-medium">
                        </div>
                </div>
                
                <div class="d-flex gap-5 text-end">
                    <div>
                        <small class="helium-label mb-1 d-block">Recaudación Validada</small>
                        <span id="lbl-total-aprobado" class="h4 fw-bold text-success">$ 0.00</span>
                    </div>
                    <div class="ps-4 border-start">
                        <small class="helium-label mb-1 d-block text-orange">Compromisos</small>
                        <span id="lbl-total-compromiso" class="h4 fw-bold text-orange">$ 0.00</span>
                    </div>
                    <div class="ps-4 border-start">
                        <small class="helium-label mb-1 d-block text-dark">Total Proyectado</small>
                        <span id="lbl-total-general" class="h3 fw-bold text-dark" style="letter-spacing: -1px;">$ 0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="empty-state" class="py-5 text-center">
        <div class="empty-icon-container mb-3">
            <i class="bi bi-clipboard2-data fs-2 text-muted opacity-50"></i>
        </div>
        <h5 class="text-dark fw-bold">Matriz lista para procesar</h5>
        <p class="text-muted small mx-auto" style="max-width: 450px;">
            Seleccione los filtros para generar el análisis horizontal de pagos. 
            El reporte se segmentará en bloques de 25 estudiantes para optimizar la carga.
        </p>
    </div>

</div>

<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script src="<?= $actualBase ?>/tools/exceljs/exceljs.min.js"></script>
<script src="<?= $actualBase ?>/tools/exceljs/FileSaver.min.js"></script>
<script src="<?= $actualBase ?>/assets/js/managerial_payments_report.js?v=<?= time() ?>"></script>