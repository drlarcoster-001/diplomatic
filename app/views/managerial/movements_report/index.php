<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / REPORTE DE MOVIMIENTOS
 * ARCHIVO: app/views/managerial/movements_report/index.php
 * PROPÓSITO: Interfaz premium de trazabilidad 360°. Matriz horizontal dinámica.
 * Maneja la visualización de N-Cuotas detectadas en el Ledger financiero.
 * VERSIÓN: 3.5.0 - FIX: Sincronización con el Modelo 3.5.0. 
 * Soporta la visualización de la nueva columna de OBSERVACIONES para abonos parciales.
 */

declare(strict_types=1);

// Cálculo dinámico de rutas para soporte en subcarpeta /public/ o /diplomatic/public/
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$actualBase = (strpos($basePath, 'public') === false) ? $basePath . '/public' : $basePath;

/**
 * Variables inyectadas desde el Controlador:
 * @var array $offerings Lista de diplomados únicos (agrupados en el modelo).
 * @var string $title Título de la página.
 */
?>

<script> 
    const BASE_URL = "<?= $actualBase ?>"; 
</script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="<?= $actualBase ?>/assets/css/managerial_movements_report.css?v=<?= time() ?>">

<div class="container-fluid py-4 px-md-5">
    
    <nav aria-label="breadcrumb" class="mb-4 animate__animated animate__fadeIn">
        <ol class="breadcrumb py-2 px-4 bg-white rounded-pill shadow-sm border-0" style="width: fit-content;">
            <li class="breadcrumb-item small"><a href="<?= $actualBase ?>/dashboard" class="text-decoration-none text-muted fw-medium">Inicio</a></li>
            <li class="breadcrumb-item small"><a href="<?= $actualBase ?>/managerial" class="text-decoration-none text-muted fw-medium">Panel Gerencial</a></li>
            <li class="breadcrumb-item active fw-bold text-primary small" aria-current="page">Trazabilidad de Movimientos</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-5 animate__animated animate__fadeIn">
        <div>
            <h2 class="helium-title mb-1 text-primary"><?= $title ?? 'Reporte General de Movimientos' ?></h2>
            <p class="helium-subtitle">Matriz horizontal evolutiva: Auditoría detallada (Monto, Forma, Ref, Banco, Recibo, Fecha) por concepto.</p>
            <p class="helium-subtitle"><strong>"Solo pagos validados".</strong></p>
        </div>
        <div class="d-flex gap-3">
            <button type="button" id="btn-export-excel" class="btn btn-success fw-bold shadow-sm rounded-pill px-4 d-flex align-items-center" style="font-size: 13px;">
                <i class="bi bi-file-earmark-excel-fill me-2 fs-5"></i> EXPORTAR EXCEL
            </button>
            <a href="<?= $actualBase ?>/managerial" class="btn-helium-secondary text-decoration-none fw-bold shadow-sm d-flex align-items-center">
                <i class="bi bi-arrow-left me-1"></i> VOLVER AL PANEL
            </a>
        </div>
    </div>

    <div class="card card-helium border-0 shadow-sm mb-4 animate__animated animate__fadeInUp">
        <div class="card-body p-4">
            <form id="form-movements-filters" class="row g-3 align-items-end">

                <div class="col-md-12">
                    <div class="helium-label-container"><span class="helium-label">PERÍODO</span></div>
                    <select name="periodo_id" id="filter_periodo" class="helium-select shadow-none">
                        <option value="">— Todos los períodos —</option>
                        <?php foreach ($periodos as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $periodoId === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombre']) ?><?= $p['estado'] === 'Finalizado' ? ' (Finalizado)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="helium-label-container"><span class="helium-label">BÚSQUEDA DE PARTICIPANTE</span></div>
                    <div class="helium-input-wrapper">
                        <i class="bi bi-search ms-3 text-primary"></i>
                        <input type="text" name="search" id="search-input" class="helium-input" placeholder="Nombre, Apellido o Cédula...">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="helium-label-container"><span class="helium-label">DIPLOMADO / OFERTA</span></div>
                    <div class="helium-input-wrapper">
                        <select name="offering_id" id="filter_offering" class="helium-select shadow-none">
                            <option value="ALL">-- Todas las Ofertas Activas --</option>
                            <?php if(!empty($offerings)): foreach($offerings as $off): ?>
                                <option value="<?= $off['id'] ?>"><?= htmlspecialchars($off['diploma_name']) ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="helium-label-container"><span class="helium-label">GRUPO / SECCIÓN</span></div>
                    <div class="helium-input-wrapper">
                        <select name="group_id" id="filter_group" class="helium-select shadow-none" disabled>
                            <option value="ALL">Todos los Grupos</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="helium-label-container"><span class="helium-label">ESTATUS ACADÉMICO</span></div>
                    <div class="helium-input-wrapper">
                        <select name="academic_status" class="helium-select shadow-none">
                            <option value="ALL" selected>Ver Todos</option>
                            <option value="ACTIVO">ACTIVO</option>
                            <option value="SUSPENDIDO">SUSPENDIDO</option>
                            <option value="RETIRADO">RETIRADO</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn-helium-primary w-100 shadow-sm fw-bold">
                        <i class="bi bi-gear-fill me-2"></i> PROCESAR
                    </button>
                </div>

            </form>
        </div>
    </div>

    <div id="results-container" class="d-none animate__animated animate__fadeIn">
        <div class="card card-helium border-0 shadow-sm bg-white overflow-hidden">
            
            <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <h6 class="helium-table-title mb-0 text-primary" style="font-size: 13px; font-weight: 700;">TRAZABILIDAD HORIZONTAL DE RECAUDACIÓN (MONTOS EN USD)</h6>
                <div id="pagination-info" class="small text-muted fw-medium"></div>
            </div>

            <div class="card-body p-0">
                <div class="table-movements-wrapper" style="overflow-x: auto; min-height: 400px;">
                    <table class="table table-hover align-middle mb-0" id="movements-table">
                        <thead id="movements-thead">
                            </thead>
                        <tbody id="movements-tbody" class="table-body-large">
                            </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white py-4 px-4 border-top">
                
                <div id="totals-dynamic-container" class="row g-2 mb-4 d-none animate__animated animate__fadeIn">
                    </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div id="pagination-controls" class="btn-group shadow-sm"></div>
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Deslice horizontalmente para auditar el desglose de los 6 campos por concepto y las observaciones finales.
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="empty-state" class="py-5 text-center animate__animated animate__fadeIn">
        <div class="empty-icon-container mb-3">
             <div class="spinner-border text-primary opacity-50" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
        <h5 class="text-dark fw-bold">Consultando Trazabilidad...</h5>
        <p class="text-muted small mx-auto" style="max-width: 500px;">
            El sistema está preparando la matriz horizontal detallada basada en los registros financieros. 
            Utilice los filtros superiores para refinar los resultados.
        </p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="<?= $actualBase ?>/tools/exceljs/exceljs.min.js"></script>
<script src="<?= $actualBase ?>/tools/exceljs/FileSaver.min.js"></script>
<script src="<?= $actualBase ?>/assets/js/managerial_movements_report.js?v=<?= time() ?>"></script>
<script>
const LOGOS_BASE64 = {
    ucla: "<?= file_exists(dirname(__DIR__, 4) . '/public/assets/uploads/logos/logo-ucla.png') ? 'data:image/png;base64,' . base64_encode(file_get_contents(dirname(__DIR__, 4) . '/public/assets/uploads/logos/logo-ucla.png')) : '' ?>",
    medicina: "<?= file_exists(dirname(__DIR__, 4) . '/public/assets/uploads/logos/logo-medicina.jpg') ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents(dirname(__DIR__, 4) . '/public/assets/uploads/logos/logo-medicina.jpg')) : '' ?>"
};
</script>