<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / AUDITORÍA BANCARIA
 * ARCHIVO: app/views/managerial/bank_reconciliation/index.php
 * PROPÓSITO: Tablero de Auditoría Maestra para contrastar TPago vs Inscripciones y Cuotas con filtros avanzados.
 * VERSIÓN: 1.5.5 - Sincronización de KPI: De registros fallidos a registros conciliados exitosos (Estilo Info).
 */

declare(strict_types=1);

// Cálculo dinámico de rutas para soporte en subcarpeta /public/
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$actualBase = (strpos($basePath, 'public') === false) ? $basePath . '/public' : $basePath;

/**
 * Variables inyectadas desde el Controlador:
 * Al abrirse por primera vez, las fechas suelen venir con el mes actual.
 */
$dateFrom = $data['default_date_from'] ?? date('Y-m-01');
$dateTo   = $data['default_date_to'] ?? date('Y-m-t');
?>

<script>
    const BASE_URL = "<?= $actualBase ?>";
</script>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="<?= $actualBase ?>/tools/exceljs/exceljs.min.js"></script>
<script src="<?= $actualBase ?>/tools/exceljs/FileSaver.min.js"></script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

<link rel="stylesheet" href="<?= $actualBase ?>/assets/css/managerial_bank_reconciliation.css?v=<?= time() ?>">

<div class="container-fluid py-4 px-md-5">
    
    <nav aria-label="breadcrumb" class="mb-4 animate__animated animate__fadeIn">
        <ol class="breadcrumb py-2 px-4 bg-white rounded-pill shadow-sm border-0" style="width: fit-content;">
            <li class="breadcrumb-item small"><a href="<?= $actualBase ?>/dashboard" class="text-decoration-none text-muted fw-medium">Inicio</a></li>
            <li class="breadcrumb-item small"><a href="<?= $actualBase ?>/managerial" class="text-decoration-none text-muted fw-medium">Panel Gerencial</a></li>
            <li class="breadcrumb-item active fw-bold text-primary small" aria-current="page">Auditoría Bancaria</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeIn">
        <div>
            <h2 class="helium-title mb-1 text-primary">Auditoría de Pagos Móviles</h2>
            <p class="helium-subtitle">Monitor de movimientos reales en cuenta (CSV) vs. Reportes recibidos.</p>
        </div>
        <div class="d-flex gap-3">
            <button type="button" id="btn-export-audit-excel" class="btn btn-success fw-bold shadow-sm rounded-pill px-4 d-flex align-items-center" style="font-size: 13px;">
                <i class="bi bi-file-earmark-excel-fill me-2 fs-5"></i> EXPORTAR EXCEL
            </button>
            <a href="<?= $actualBase ?>/managerial" class="btn-helium-secondary text-decoration-none fw-bold shadow-sm d-flex align-items-center">
                <i class="bi bi-arrow-left me-1"></i> VOLVER AL PANEL
            </a>
        </div>
    </div>

    <div class="row g-4 mb-5 animate__animated animate__fadeInUp">
        <div class="col-md-6 col-xl-3">
            <div class="card card-helium kpi-card border-0 shadow-sm">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-bank"></i>
                    </div>
                    <div>
                        <p class="kpi-title mb-1">Total Banco (CSV)</p>
                        <h4 class="kpi-value text-dark mb-0 fw-bold" id="kpi-total-banco">Bs. 0.00</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card card-helium kpi-card border-0 shadow-sm">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <p class="kpi-title mb-1">Total Conciliado</p>
                        <h4 class="kpi-value text-success mb-0 fw-bold" id="kpi-total-conciliado">Bs. 0.00</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card card-helium kpi-card border-0 shadow-sm">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="kpi-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-exclamation-octagon"></i>
                    </div>
                    <div>
                        <p class="kpi-title mb-1">Monto Huérfano</p>
                        <h4 class="kpi-value text-warning mb-0 fw-bold" id="kpi-total-huerfano">Bs. 0.00</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card card-helium kpi-card border-0 shadow-sm">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="kpi-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div>
                        <p class="kpi-title mb-1">Conciliados (Registros)</p>
                        <h4 class="kpi-value text-info mb-0 fw-bold" id="kpi-total-conteo-conciliado">0</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-helium border-0 shadow-sm mb-5 animate__animated animate__fadeIn">
        <div class="card-body p-4">
            <form id="form-reconciliation-filters" class="row g-3 align-items-end">
                
                <div class="col-md-2">
                    <div class="helium-label-container"><span class="helium-label">FECHA INICIO</span></div>
                    <div class="helium-input-wrapper">
                        <i class="bi bi-calendar3 ms-3 text-primary"></i>
                        <input type="date" name="date_from" class="helium-input" value="<?= $dateFrom ?>">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="helium-label-container"><span class="helium-label">FECHA FIN</span></div>
                    <div class="helium-input-wrapper">
                        <i class="bi bi-calendar3 ms-3 text-primary"></i>
                        <input type="date" name="date_to" class="helium-input" value="<?= $dateTo ?>">
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="helium-label-container"><span class="helium-label">BÚSQUEDA POR REFERENCIA, ESTUDIANTE, TELF O MONTO</span></div>
                    <div class="helium-input-wrapper">
                        <i class="bi bi-search ms-3 text-primary"></i>
                        <input type="text" name="search" class="helium-input" placeholder="Ej: 5771368, Juan Pérez, 0412..., 28901.93">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="helium-label-container"><span class="helium-label">ETAPA FINANCIERA</span></div>
                    <div class="helium-input-wrapper">
                        <select name="etapa_financiera" class="helium-select">
                            <option value="ALL" selected>-- Todas las Etapas --</option>
                            <option value="INSCRIPCIÓN">INSCRIPCIÓN</option>
                            <option value="PAGO DE CUOTA">PAGO DE CUOTA</option>
                            <option value="HUÉRFANO">HUÉRFANO (Por Identificar)</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="helium-label-container"><span class="helium-label">ESTATUS EN SISTEMA</span></div>
                    <div class="helium-input-wrapper">
                        <select name="status_sistema" class="helium-select">
                            <option value="ALL" selected>-- Ver Todos --</option>
                            <option value="APPROVED">✅ YA APROBADO</option>
                            <option value="PENDING">⏳ PENDIENTE POR VALIDAR</option>
                            <option value="REJECTED">❌ RECHAZADO</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="helium-label-container"><span class="helium-label">ESTATUS CONCILIACIÓN</span></div>
                    <div class="helium-input-wrapper">
                        <select name="status_conciliacion" class="helium-select">
                            <option value="ALL" selected>-- Ver Todos --</option>
                            <option value="CONCILIADO">✅ CONCILIADO</option>
                            <option value="NO ENCONTRADO">⚠️ NO ENCONTRADO EN SISTEMA</option>
                        </select>
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-end border-top pt-4 mt-3">
                    <div class="d-flex gap-3">
                        <button type="reset" class="btn-helium-secondary">
                            <i class="bi bi-eraser-fill me-2"></i>REINICIAR
                        </button>
                        <button type="submit" class="btn-helium-primary">
                            <i class="bi bi-funnel-fill me-2"></i>APLICAR FILTROS
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="reconciliation-results-container" class="d-none animate__animated animate__fadeIn">
        <div class="card card-helium border-0 shadow-sm bg-white">
            
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h6 class="helium-table-title mb-0 text-primary" style="font-size: 14px; font-weight: 700;">VISUALIZACIÓN DE MATRIZ DE AUDITORÍA MAESTRA</h6>
                <div class="small fw-medium text-muted">
                    <i class="bi bi-info-circle me-1"></i> Contraste de transacciones bancarias vs reportes
                </div>
            </div>

            <div class="card-body p-0">
                <div class="helium-table-scroll"> 
                    <table class="table table-hover align-middle mb-0" id="reconciliation-matrix-table">
                        <thead class="bg-light sticky-top">
                            <tr class="small text-uppercase fw-bold text-muted">
                                <th class="ps-4">Fecha Banco</th>
                                <th>N° Referencia</th>
                                <th class="text-center">Teléfono</th>
                                <th class="text-end pe-4">Monto Banco</th>
                                <th>Estudiante / Registro</th>
                                <th>Etapa</th>
                                <th class="text-center">Estatus Sistema</th>
                                <th class="text-center pe-4">Cruce Bancario</th>
                            </tr>
                        </thead>
                        <tbody id="matrix-tbody">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="empty-state" class="py-5 text-center animate__animated animate__fadeIn">
        <div class="empty-icon-container mb-3">
            <i class="bi bi-clipboard-data fs-1 text-primary opacity-25"></i>
        </div>
        <h5 class="text-dark fw-bold">Esperando parámetros de consulta...</h5>
        <p class="text-muted small mx-auto" style="max-width: 450px;">
            El sistema contrastará los archivos del banco contra Inscripciones y Pagos de Cuotas. 
            Ajuste los filtros para visualizar la matriz de auditoría.
        </p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $actualBase ?>/assets/js/managerial_bank_reconciliation.js?v=<?= time() ?>"></script>