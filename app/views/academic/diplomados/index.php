<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/diplomados/index.php
 * Propósito: Listado maestro con vista previa tipo documento serio.
 */
// Forzamos la base path para evitar errores de ruteo en XAMPP
$basePath = '/diplomatic/public';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_diplomados.css">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Catálogo de Diplomados</h2>
            <p class="text-muted small">Administración de programas académicos. Haga clic en la fila para vista previa.</p>
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
                        <th class="text-center">Horas</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data['diplomados'])): ?>
                        <?php foreach ($data['diplomados'] as $row): ?>
                            <tr class="row-preview" data-id="<?= $row['id'] ?>" style="cursor:pointer;">
                                <td class="ps-4 fw-bold text-primary"><?= $row['code'] ?></td>
                                <td class="fw-bold"><?= $row['name'] ?></td>
                                <td class="text-center"><?= $row['total_hours'] ?>h</td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="<?= $basePath ?>/academic/diplomados/edit?id=<?= $row['id'] ?>" class="btn btn-sm btn-white border text-primary btn-action">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-sm btn-white border text-danger btn-delete btn-action" data-id="<?= $row['id'] ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-4">No hay diplomados registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header border-0 bg-light py-2">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body doc-container" id="printArea" style="font-family:'Times New Roman', serif;">
                <div class="doc-header-title text-center mb-4">
                    <h2 class="fw-bold mb-1 text-uppercase" id="pv_name"></h2>
                    <p class="small mb-0">CÓDIGO: <span id="pv_code"></span></p>
                </div>

                <span class="doc-label" style="font-weight:bold; text-decoration:underline; display:block; margin-top:20px;">DIRIGIDO A:</span>
                <div class="doc-content mt-2" id="pv_directed"></div>

                <span class="doc-label" style="font-weight:bold; text-decoration:underline; display:block; margin-top:20px;">DESCRIPCIÓN Y OBJETIVOS:</span>
                <div class="doc-content mt-2" id="pv_description"></div>

                <span class="doc-label" style="font-weight:bold; text-decoration:underline; display:block; margin-top:20px;">REQUISITOS:</span>
                <ul class="mt-2" id="pv_requirements"></ul>

                <span class="doc-label" style="font-weight:bold; text-decoration:underline; display:block; margin-top:20px;">ALGUNAS CONDICIONES GENERALES:</span>
                <ul class="mt-2" id="pv_conditions"></ul>

                <div class="mt-5 pt-4 text-center" style="border-top: 2px solid #000;">
                    <p class="mb-0 fs-5"><strong>CARGA HORARIA TOTAL: <span id="pv_hours"></span> HORAS ACADÉMICAS.</strong></p>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="btnDownloadPDF">Descargar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/diplomatic/public/assets/js/academic_diplomados.js"></script>