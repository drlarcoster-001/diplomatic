<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / CONTROL ACADÉMICO
 * ARCHIVO: app/views/managerial/academic_control/index.php
 * PROPÓSITO: Panel de trazabilidad 360° para el seguimiento de aspirantes y estudiantes matriculados.
 * VERSIÓN: 1.1.0 - Dinamización de estatus: Los estados de ficha se cargan desde la BD para evitar código estático.
 */

declare(strict_types=1);

// Cálculo de rutas para soporte en subcarpeta /diplomatic/public/
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$actualBase = (strpos($basePath, 'public') === false) ? $basePath . '/public' : $basePath;
?>

<script> const BASE_URL = "<?= $actualBase ?>"; </script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

<link rel="stylesheet" href="<?= $actualBase ?>/assets/css/managerial_academic_control.css?v=<?= time() ?>">

<div class="container-fluid py-4 px-md-5">
    
    <nav aria-label="breadcrumb" class="mb-4 animate__animated animate__fadeIn">
        <ol class="breadcrumb py-2 px-4 bg-white rounded-pill shadow-sm border-0" style="width: fit-content;">
            <li class="breadcrumb-item small"><a href="<?= $actualBase ?>/dashboard" class="text-decoration-none text-muted fw-medium">Inicio</a></li>
            <li class="breadcrumb-item small"><a href="<?= $actualBase ?>/managerial" class="text-decoration-none text-muted fw-medium">Panel Gerencial</a></li>
            <li class="breadcrumb-item active fw-bold text-primary small" aria-current="page">Control Académico</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-5 animate__animated animate__fadeIn">
        <div>
            <h2 class="helium-title mb-1 text-primary">Control Académico</h2>
            <p class="helium-subtitle">Seguimiento integral: Trazabilidad de inscripciones, grupos y estatus de matrícula.</p>
        </div>
        <a href="<?= $actualBase ?>/managerial" class="btn-helium-secondary text-decoration-none fw-bold">
            <i class="bi bi-arrow-left me-1"></i> VOLVER AL PANEL
        </a>
    </div>

    <div class="card card-helium border-0 shadow-sm mb-5 animate__animated animate__fadeIn">
        <div class="card-body p-4">
            <form id="form-academic-filters" class="row g-3 align-items-end">

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
                    <div class="helium-label-container"><span class="helium-label">PARTICIPANTE / CÉDULA</span></div>
                    <div class="helium-input-wrapper">
                        <i class="bi bi-search ms-3 text-primary"></i>
                        <input type="text" name="student" id="dynamic-student-search" class="helium-input" placeholder="Nombre o Documento...">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="helium-label-container"><span class="helium-label">DIPLOMADO</span></div>
                    <div class="helium-input-wrapper">
                        <select name="offering_id" id="filter_offering" class="helium-select">
                            <option value="ALL" selected>Todos los Diplomados</option>
                            <?php if(!empty($offerings)): foreach($offerings as $off): ?>
                                <option value="<?= $off['id'] ?>"><?= htmlspecialchars($off['diploma_name']) ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="helium-label-container"><span class="helium-label">GRUPO</span></div>
                    <div class="helium-input-wrapper">
                        <select name="group_id" id="filter_group" class="helium-select" disabled>
                            <option value="ALL">Todos los Grupos</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="helium-label-container"><span class="helium-label">ESTATUS DE PARTICIPANTE</span></div>
                    <div class="helium-input-wrapper">
                        <select name="participant_status" id="filter_status" class="helium-select">
                            <option value="ALL" selected>Ver Todos los Estatus</option>
                            
                            <optgroup label="Situación de Matrícula">
                                <option value="MAT_CURSANDO">MATRÍCULA ACTIVA (CURSANDO)</option>
                                <option value="MAT_PENDIENTE">NO MATRICULADO</option>
                            </optgroup>

                            <optgroup label="Estatus de Ficha (Expediente)">
                                <option value="FICHA_ACTIVA">FICHA ACTIVA (ACTIVO)</option>
                                <option value="FICHA_PENDIENTE">SIN FICHA GENERADA</option>
                                <option value="SUSPENDED">SUSPENDIDO</option>
                                <option value="RETIRADO">RETIRADO</option>
                            </optgroup>
                        </select>

                    </div>
                </div>

                <div class="col-12 d-flex justify-content-end gap-3 border-top pt-4 mt-3">
                    <button type="reset" class="btn-helium-secondary">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>REINICIAR FILTROS
                    </button>
                    <button type="submit" class="btn-helium-primary">
                        <i class="bi bi-funnel-fill me-2"></i>PROCESAR MATRIZ
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="report-results-container" class="d-none animate__animated animate__fadeIn">
        <div class="card card-helium border-0 shadow-sm bg-white">
            
            <div class="card-header bg-white py-4 px-4 border-0 d-flex justify-content-between align-items-center">
                <h6 class="helium-table-title mb-0 text-primary" style="font-size: 14px; font-weight: 700;">VISUALIZACIÓN DE DATOS ACADÉMICOS</h6>
                
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle rounded-pill px-4 fw-bold shadow-none" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-2"></i>EXPORTAR
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                        <li><button class="dropdown-item py-2 fw-bold" id="btn-export-excel"><i class="bi bi-file-earmark-excel text-success me-2"></i> Descargar Excel</button></li>
                        <li><button class="dropdown-item py-2 fw-bold" id="btn-export-pdf"><i class="bi bi-file-earmark-pdf text-danger me-2"></i> Generar PDF</button></li>
                    </ul>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="helium-table-scroll"> 
                    <table class="table table-hover align-middle mb-0" id="academic-matrix-table">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="ps-4" width="5%">NRO</th> <th class="col-participant">Participante</th>
                                <th class="text-center">Cédula</th>
                                <th class="text-center">Diplomado</th>
                                <th class="text-center">Grupo</th>
                                <th class="text-center">Trazabilidad Adm/Fin</th>
                                <th class="text-center col-code">Código</th>
                                <th class="text-center col-count">C. Insc.</th>
                                <th class="text-center col-count">C. Est.</th>
                                <th class="text-center">Ficha</th>
                                <th class="text-center pe-4">Matrícula</th>
                            </tr>
                        </thead>

                        <tbody id="matrix-tbody">
                             </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white py-4 px-4 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div id="pagination-info" class="text-muted small fw-medium"></div>
                    <div id="pagination-controls" class="btn-group shadow-sm"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="empty-state" class="py-5 text-center animate__animated animate__fadeIn">
        <div class="empty-icon-container mb-3">
            <i class="bi bi-person-badge fs-1 text-primary opacity-25"></i>
        </div>
        <h5 class="text-dark fw-bold">Matriz de Control Lista</h5>
        <p class="text-muted small mx-auto" style="max-width: 450px;">
            Utilice los filtros para generar la trazabilidad. El reporte cruzará automáticamente 
            los datos de inscripción, pagos y registros académicos.
        </p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="<?= $actualBase ?>/tools/exceljs/exceljs.min.js"></script>
<script src="<?= $actualBase ?>/tools/exceljs/FileSaver.min.js"></script>
<script src="<?= $actualBase ?>/assets/js/managerial_academic_control.js?v=<?= time() ?>"></script>