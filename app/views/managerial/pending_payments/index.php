<?php
/**
 * MÓDULO: PANEL GERENCIAL / PAGOS PENDIENTES
 * ARCHIVO: app/views/managerial/pending_payments/index.php
 * PROPÓSITO: Interfaz premium para auditoría de pagos en tránsito, con filtros de origen y exportación.
 * VERSIÓN: 1.1.0 - Fix de CSS base incluido, botón verde y estructura de filtros.
 */

declare(strict_types=1);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$actualBase = (strpos($basePath, 'public') === false) ? $basePath . '/public' : $basePath;
?>

<script> const BASE_URL = "<?= $actualBase ?>"; </script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<link rel="stylesheet" href="<?= $actualBase ?>/assets/css/managerial_payments_report.css?v=<?= time() ?>">
<link rel="stylesheet" href="<?= $actualBase ?>/assets/css/managerial_pending_payments.css?v=<?= time() ?>">

<div class="container-fluid py-4 px-md-5">
    
    <nav aria-label="breadcrumb" class="mb-4 animate__animated animate__fadeIn">
        <ol class="breadcrumb py-2 px-4 bg-white rounded-pill shadow-sm border-0" style="width: fit-content;">
            <li class="breadcrumb-item small"><a href="<?= $actualBase ?>/dashboard" class="text-decoration-none text-muted fw-medium">Inicio</a></li>
            <li class="breadcrumb-item small"><a href="<?= $actualBase ?>/managerial" class="text-decoration-none text-muted fw-medium">Panel Gerencial</a></li>
            <li class="breadcrumb-item active fw-bold text-success small" aria-current="page">Pagos Pendientes</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-5 animate__animated animate__fadeIn">
        <div>
            <h2 class="helium-title mb-1">Control de Pagos Pendientes</h2>
            <p class="helium-subtitle">Auditoría de recibos, transferencias y efectivo en tránsito por verificar.</p>
        </div>
        <a href="<?= $actualBase ?>/managerial" class="btn-helium-back fw-bold">
            <i class="bi bi-arrow-left me-1"></i> VOLVER AL PANEL
        </a>
    </div>

    <div class="card card-helium border-0 shadow-sm mb-5 animate__animated animate__fadeIn">
        <div class="card-body p-4">
            <form id="form-pending-filters" class="row g-4 align-items-end">
                
                <div class="col-md-5">
                    <div class="helium-label-container"><span class="helium-label">BUSCAR PARTICIPANTE / CÉDULA / REFERENCIA</span></div>
                    <div class="helium-input-wrapper">
                        <i class="bi bi-search ms-3 text-muted"></i>
                        <input type="text" name="student" id="dynamic-student-search" class="helium-input" placeholder="Buscar...">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="helium-label-container"><span class="helium-label">DIPLOMADO / OFERTA</span></div>
                    <select name="offering_id" class="helium-select shadow-none">
                        <option value="ALL">Todas las Ofertas Académicas Activas</option>
                        <?php if(!empty($offerings)): foreach($offerings as $off): ?>
                            <option value="<?= $off['id'] ?>"><?= htmlspecialchars($off['diploma_name']) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="helium-label-container"><span class="helium-label">FILTRAR POR ORIGEN</span></div>
                    <select name="origin" class="helium-select shadow-none">
                        <option value="ALL" selected>Ver Todos los Trámites</option>
                        <option value="INSCRIPTION">Solo Inscripciones</option>
                        <option value="INSTALLMENT">Solo Pagos Regulares (Cuotas)</option>
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end gap-3 border-top pt-3">
                    <button type="button" id="btn-reset-filters" class="btn-helium-secondary px-4 fw-bold">REINICIAR</button>
                    <button type="submit" class="btn btn-success shadow px-5 fw-bold" style="border-radius: 8px;">
                        <i class="bi bi-funnel-fill me-2"></i>FILTRAR PAGOS
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="report-results-container" class="d-none animate__animated animate__fadeIn">
        <div class="card card-helium border-0 shadow-sm overflow-hidden bg-white">
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h6 class="helium-table-title mb-0" style="font-size: 14px; font-weight: 700;">BANDEJA DE PAGOS EN TRÁNSITO</h6>
                
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
                    <table class="table table-hover align-middle mb-0" id="pending-table">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="text-center" style="width: 8%;">Cédula</th>
                                <th class="ps-3" style="width: 18%;">Participante</th>
                                <th class="text-center" style="width: 15%;">Diplomado</th>
                                <th class="text-center">Origen</th>
                                <th class="text-center">Método</th>
                                <th class="text-end">Monto Orig.</th>
                                <th class="text-center">Tasa</th>
                                <th class="text-end fw-bold" style="border-left: 2px solid #eee;">Equiv. USD</th>
                                <th class="text-center" style="width: 15%;">Observación</th>
                            </tr>
                        </thead>
                        <tbody id="pending-tbody" class="table-body-large">
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
                    <div class="ps-4 border-start">
                        <small class="helium-label mb-1 d-block text-dark">Total Flotante (Pendiente)</small>
                        <span id="lbl-total-pending" class="h3 fw-bold text-dark" style="letter-spacing: -1px;">$ 0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="empty-state" class="py-5 text-center">
        <div class="empty-icon-container mb-3">
            <i class="bi bi-inboxes fs-2 text-muted opacity-50"></i>
        </div>
        <h5 class="text-dark fw-bold">Bandeja de Auditoría Lista</h5>
        <p class="text-muted small mx-auto" style="max-width: 450px;">
            Seleccione los filtros superiores para listar los pagos reportados por los estudiantes que aún no han sido procesados por administración.
        </p>
    </div>

</div>

<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script src="<?= $actualBase ?>/tools/exceljs/exceljs.min.js"></script>
<script src="<?= $actualBase ?>/tools/exceljs/FileSaver.min.js"></script>
<script src="<?= $actualBase ?>/assets/js/managerial_pending_payments.js?v=<?= time() ?>"></script>