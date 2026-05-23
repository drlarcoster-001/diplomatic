<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / SUSPENSIONES
 * ARCHIVO: app/views/administrative/suspensions/manage.php
 * PROPÓSITO: Grid completa con filtros de búsqueda, LED de estatus y Ficha (Popup).
 * VERSIÓN: 1.9.5 - UI: Filtros dinámicos + Indicador LED de estatus académico.
 */

declare(strict_types=1);
/** @var array $students */ // <-- Esto le dice al IDE que la variable existe y es un array
$students = $students ?? []; // <-- Esto asegura que si la variable no llega, sea un array vacío y no dé error de ejecución
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

// BLINDAJE: Obtenemos metadatos del primer registro si existen
$firstStudent  = !empty($students) ? $students[0] : [];
$diplomadoName = $firstStudent['diplomado_nombre_real'] ?? 'Diplomado no identificado';
$cohorteName   = $firstStudent['cohorte_nombre_real'] ?? 'Cohorte no identificada';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/administrative_suspension.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2 small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/administrative">Panel Administrativo</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($basePath) ?>/administrative/suspensions">Suspensiones</a></li>
            <li class="breadcrumb-item active" aria-current="page">Listado de Estudiantes</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <?= htmlspecialchars((string)$diplomadoName) ?>
            </h2>
            <p class="text-muted small mb-0">
                <i class="bi bi-layers me-1"></i> <?= htmlspecialchars((string)$cohorteName) ?> 
                <span class="mx-2 text-silver">|</span> 
                <i class="bi bi-people me-1"></i> <?= count($students) ?> Estudiantes Registrados
            </p>
        </div>
        <a href="<?= htmlspecialchars($basePath) ?>/administrative/suspensions" class="btn btn-outline-secondary shadow-sm btn-sm">
            <i class="bi bi-arrow-left"></i> Volver al Listado
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-light rounded p-3">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-secondary">Filtrar por Nombre o Apellido:</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="filterNombre" class="form-control border-start-0" placeholder="Escriba para buscar...">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary">Filtrar por Cédula:</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-card-text"></i></span>
                        <input type="text" id="filterCedula" class="form-control border-start-0" placeholder="Ej: 12345678">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-dark btn-sm w-100 shadow-sm" onclick="limpiarFiltros()">
                        <i class="bi bi-eraser-fill me-1"></i> Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaEstudiantes">
                    <thead class="bg-light text-secondary small fw-bold">
                        <tr>
                            <th class="ps-3" width="5%">Nro</th>
                            <th width="15%">Estatus Académico</th> <th width="12%">Expediente</th>
                            <th width="25%">Estudiante</th>
                            <th width="12%" class="text-center">Solvencia</th>
                            <th width="20%">Deuda Pendiente</th>
                            <th width="11%" class="text-end pe-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students)): ?>
                            <?php $nro = 1; foreach ($students as $s): 
                                $statusFinanciero = $s['estatus_financiero'];
                                $statusColor = [
                                    'INSOLVENTE' => 'danger',
                                    'POR_VENCER' => 'warning',
                                    'SOLVENTE'   => 'success'
                                ][$statusFinanciero] ?? 'secondary';
                                
                                $isSuspended = ($s['user_status'] === 'SUSPENDED');
                            ?>
                            <tr class="student-row <?= $isSuspended ? 'table-light opacity-75' : '' ?>" 
                                onclick='abrirFichaEstudiante(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= $nro ?>)' 
                                style="cursor: pointer;">
                                
                                <td class="ps-3 fw-bold text-muted"><?= $nro++ ?></td>
                                
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="d-inline-block rounded-circle me-2 shadow-sm" 
                                              style="width: 12px; height: 12px; background-color: <?= $isSuspended ? '#dc3545' : '#28a745' ?>; border: 2px solid white;"></span>
                                        <small class="fw-bold <?= $isSuspended ? 'text-danger' : 'text-success' ?>">
                                            <?= $isSuspended ? 'SUSPENDIDO' : 'ACTIVO' ?>
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge bg-white text-dark border fw-bold px-2 py-1">
                                        <?= htmlspecialchars((string)($s['expediente'] ?? 'S/C')) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark text-uppercase student-name" style="font-size: 0.85rem;">
                                        <?= htmlspecialchars((string)$s['participante']) ?>
                                    </div>
                                    <div class="text-muted small student-cedula">C.I: <?= $s['cedula'] ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $statusColor ?> rounded-pill px-3 shadow-sm">
                                        <?= $statusFinanciero ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small <?= $statusFinanciero === 'INSOLVENTE' ? 'text-danger fw-bold' : 'text-muted' ?>">
                                        <?= htmlspecialchars((string)($s['detalle_deuda'] ?? 'Sin deuda')) ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-light btn-sm border shadow-sm px-3">
                                        Gestionar <i class="bi bi-chevron-right ms-1"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No se encontraron estudiantes registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalFichaEstudiante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
    <div class="modal-header bg-dark text-white p-3">
        <h5 class="modal-title fw-bold"><i class="bi bi-person-vcard me-2"></i>Ficha del Estudiante</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body p-4">
        <div class="row g-3">
            <div class="col-6">
                <small class="text-muted d-block text-uppercase small fw-bold">Nro Registro</small>
                <span id="fNro" class="fs-5 fw-bold"></span>
            </div>
            <div class="col-6 text-end">
                <small class="text-muted d-block text-uppercase small fw-bold">Expediente</small>
                <span id="fExp" class="badge bg-light text-dark border text-wrap text-break p-2" style="font-size: 15px; max-width: 100%;"></span>
            </div>
            <div class="col-12 border-top pt-3">
                <small class="text-muted d-block text-uppercase small fw-bold">Nombres y Apellidos</small>
                <span id="fNombre" class="fs-5 fw-bold text-primary text-uppercase"></span>
            </div>
            <div class="col-12">
                <small class="text-muted d-block text-uppercase small fw-bold">Estatus Financiero</small>
                <span id="fSolvencia" class="badge rounded-pill px-3 py-2 fs-6"></span>
            </div>
            <div class="col-12 bg-light p-3 rounded border">
                <small class="text-muted d-block text-uppercase mb-2 small fw-bold">Detalle de Deuda Pendiente</small>
                <div id="fDeuda" class="small fw-bold text-dark">
                    </div>
            </div>
        </div>
    </div>
    <div class="modal-footer bg-light d-flex justify-content-between p-3">
        <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-1"></i> Cancelar
        </button>
        <div id="fAccionContainer">
            </div>
    </div>
</div>

    </div>
</div>

<script src="<?= htmlspecialchars($basePath) ?>/assets/js/administrative_suspension.js"></script>