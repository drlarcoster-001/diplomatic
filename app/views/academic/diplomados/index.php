<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/diplomados/index.php
 * Propósito: Listado maestro con breadcrumbs y modal de vista previa técnica.
 * Version: 1.3.6 - Integración de Navegación Jerárquica y Sincronización con JS v1.5.3.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_diplomados.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size: 0.85rem;">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i> Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/academic" class="text-decoration-none text-muted">Panel Académico</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Catálogo de Diplomados</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Catálogo de Diplomados</h2>
            <p class="text-muted small">Administración de programas académicos y fichas técnicas.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="<?= $basePath ?>/academic/diplomados/create" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Diplomado
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small fw-bold text-secondary text-uppercase">
                    <tr>
                        <th class="ps-4">Código</th>
                        <th>Nombre del Diplomado</th>
                        <th class="text-center">Estatus</th>
                        <th class="text-center">Horas</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data['diplomados'])): ?>
                        <?php foreach ($data['diplomados'] as $row): ?>
                            <?php 
                                $status = strtoupper($row['status'] ?? 'BORRADOR');
                                $badgeClass = match($status) {
                                    'ACTIVO'    => 'bg-success',
                                    'INACTIVO'  => 'bg-danger',
                                    'BORRADOR'  => 'bg-warning text-dark',
                                    default     => 'bg-secondary'
                                };
                            ?>
                            <tr class="row-preview" data-id="<?= $row['id'] ?>" style="cursor:pointer;">
                                <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($row['code']) ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeClass ?> rounded-pill px-3 shadow-sm" style="font-size: 0.72rem;">
                                        <?= $status ?>
                                    </span>
                                </td>
                                <td class="text-center text-muted small"><?= $row['total_hours'] ?>h</td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="<?= $basePath ?>/academic/diplomados/edit?id=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-white border text-primary btn-action" 
                                           title="Editar Información">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-sm btn-white border text-danger btn-delete btn-action" 
                                                data-id="<?= $row['id'] ?>" 
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                title="Eliminar registro">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron diplomados registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-light py-2">
                <h6 class="modal-title small text-muted">Ficha Técnica Institucional</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body doc-container" id="printArea">
                <div class="doc-header-title mb-4">
                    <h2 id="pv_name" class="fw-bold text-dark"></h2>
                    <p class="small mb-0">CÓDIGO DE PROGRAMA: <span id="pv_code" class="fw-bold text-primary"></span></p>
                </div>

                <span class="doc-label">DIRIGIDO A:</span>
                <div class="doc-content mb-3" id="pv_directed"></div>

                <span class="doc-label">DESCRIPCIÓN Y OBJETIVOS:</span>
                <div class="doc-content mb-3" id="pv_description"></div>

                <span class="doc-label">REQUISITOS DE INGRESO:</span>
                <ul class="doc-content mb-3" id="pv_requirements"></ul>

                <span class="doc-label">CONDICIONES GENERALES:</span>
                <ul class="doc-content mb-3" id="pv_conditions"></ul>

                <div class="mt-5 pt-4 text-center" style="border-top: 2px solid #eee;">
                    <p class="mb-0 fs-6 text-secondary">
                        <strong>CARGA HORARIA TOTAL: <span id="pv_hours" class="text-dark"></span> HORAS ACADÉMICAS.</strong>
                    </p>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="btnDownloadPDF">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Descargar Ficha
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/academic_diplomados.js?v=<?= time() ?>"></script>