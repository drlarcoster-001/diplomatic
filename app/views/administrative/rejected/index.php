<?php
/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / DOCUMENTOS RECHAZADOS
 * ARCHIVO: app/views/administrative/rejected/index.php
 * PROPÓSITO: Interfaz oficial de auditoría con buscador multicriterio, breadcrumb jerárquico y visor de expedientes.
 * VERSIÓN: 1.4.0 - Restauración de Título, Formulario de Filtro exacto y sincronización de columnas del Dump.
 */

declare(strict_types=1);
$basePath = '/diplomatic/public';
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/administrative_rejected.css">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-light p-2 rounded shadow-sm border mb-0" style="font-size: 0.85rem;">
                <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= $basePath ?>/administrative" class="text-decoration-none text-muted">Panel Administrativo</a></li>
                <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Documentos Rechazados</li>
            </ol>
        </nav>

        <a href="<?= $basePath ?>/administrative" class="btn btn-outline-secondary btn-sm fw-bold px-3 shadow-sm">
            <i class="bi bi-arrow-left-circle me-1"></i> Volver
        </a>
    </div>

    <div class="mb-4">
        <h2 class="h4 fw-bold mb-1 text-dark">Documentos Rechazados</h2>
        <p class="text-muted small mb-0">Auditoría centralizada de expedientes con recaudos invalidados.</p>
    </div>

    <div class="card border-0 shadow-sm mb-4 rounded-4">
        <div class="card-body p-4">
            <form id="filter-form-docs" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small fw-bold text-muted">Buscar Participante o Diplomado</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="search-text" placeholder="Ej: Terapia Respiratoria, Cédula, Nombre...">
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-bold me-2" id="btn-clear-filters">
                        <i class="bi bi-eraser me-1"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-dark btn-sm px-4 fw-bold">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="rejected-table">
                <thead class="bg-light">
                    <tr style="font-size: 0.75rem;" class="text-uppercase text-muted border-bottom">
                        <th class="ps-4 py-3">Fecha Acción</th>
                        <th>Estudiante / Cédula</th>
                        <th>Diplomado</th>
                        <th class="text-center">Método Pago</th>
                        <th class="text-end pe-4">Detalle</th>
                    </tr>
                </thead>
                <tbody style="font-size: 0.85rem;">
                    <?php if (!empty($data['rejected'])): ?>
                        <?php foreach ($data['rejected'] as $r): ?>
                            <tr class="item-row clickable-row" 
                                data-id="<?= $r['enrollment_id'] ?>" 
                                data-name="<?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>"
                                data-cedula="<?= htmlspecialchars($r['cedula']) ?>"
                                data-diplomado="<?= htmlspecialchars($r['diplomado_name']) ?>"
                                data-payment="<?= htmlspecialchars($r['payment_method']) ?>"
                                data-obs="<?= htmlspecialchars($r['observations'] ?? 'No se registró motivo.') ?>">
                                
                                <td class="ps-4 text-muted small"><?= date('d/m/Y', strtotime($r['fecha_accion'])) ?></td>
                                <td>
                                    <div class="fw-bold text-dark search-field-name"><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></div>
                                    <div class="small text-muted font-monospace search-field-id"><?= htmlspecialchars($r['cedula']) ?></div>
                                </td>
                                <td class="small fw-bold text-secondary search-field-diploma"><?= htmlspecialchars($r['diplomado_name']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-3"><?= htmlspecialchars($r['payment_method']) ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <span class="btn btn-sm btn-light rounded-circle shadow-sm"><i class="bi bi-chevron-right"></i></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted small">No hay expedientes rechazados registrados actualmente.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold">Detalle de Rechazo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="modal-body-content"></div>
            <div class="modal-footer bg-light border-0 d-flex justify-content-between p-3">
                <button type="button" class="btn btn-primary fw-bold px-4" id="btn-change-status">CAMBIAR ESTATUS</button>
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/administrative_rejected.js"></script>