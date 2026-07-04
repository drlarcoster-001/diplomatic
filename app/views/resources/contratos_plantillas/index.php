<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * Archivo: app/views/resources/contratos_plantillas/index.php
 * Propósito: Grid principal de plantillas de contratos institucionales.
 * Versión: 1.1.0
 *
 * @var array  $plantillas
 * @var string $search
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_contratos_plantillas.css">

<div class="container-fluid py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources" class="text-decoration-none text-muted">Panel de Recursos</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#0d6efd;">Plantillas de Contratos</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Plantillas de Contratos</h2>
            <p class="text-muted small">Diseño y gestión de plantillas reutilizables para contratos institucionales.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/resources" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="<?= $basePath ?>/resources/contratos/plantillas/create" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Nueva Plantilla
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= $basePath ?>/resources/contratos/plantillas" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Buscar por nombre o tipo de contrato..."
                               value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($plantillas)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-file-earmark-text fs-1 d-block mb-3 opacity-25"></i>
            No hay plantillas de contratos registradas.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($plantillas as $p): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 plantilla-card" style="border-top: 3px solid #0d6efd !important;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($p['nombre']) ?></h6>
                                    <span class="badge rounded-pill px-3 py-1 text-white" style="background:#0d6efd; font-size:0.75rem;">
                                        <?= htmlspecialchars($p['tipo_siglas'] ?? '—') ?>
                                        · <?= htmlspecialchars($p['tipo_nombre'] ?? '—') ?>
                                    </span>
                                </div>
                                <i class="bi bi-file-earmark-text fs-2 text-primary opacity-25"></i>
                            </div>

                            <div class="d-flex gap-3 small text-muted mb-3">
                                <span><i class="bi bi-braces me-1"></i><?= (int)$p['total_campos'] ?> campo(s)</span>
                                <span><i class="bi bi-file-earmark-check me-1"></i><?= (int)$p['total_contratos'] ?> contrato(s)</span>
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-ver"
                                        data-id="<?= $p['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                        data-tipo="<?= htmlspecialchars(($p['tipo_siglas'] ?? '') . ' · ' . ($p['tipo_nombre'] ?? '')) ?>">
                                    <i class="bi bi-eye me-1"></i> Ver
                                </button>
                                <a href="<?= $basePath ?>/resources/contratos/plantillas/edit?id=<?= $p['id'] ?>"
                                   class="btn btn-sm btn-primary rounded-pill px-3">
                                    <i class="bi bi-pencil me-1"></i> Editar
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-delete"
                                        data-id="<?= $p['id'] ?>"
                                        data-name="<?= htmlspecialchars($p['nombre']) ?>"
                                        data-count="<?= (int)$p['total_contratos'] ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Modal Vista Previa -->
<div class="modal fade" id="modalVerPlantilla" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 py-3 px-4" style="background:#0d6efd;">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="modalVerNombre"></h5>
                    <small class="text-white opacity-75" id="modalVerTipo"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Hoja de papel simulada -->
                <div style="background:#e9ecef; padding:32px; min-height:600px;">
                    <div id="modal-contenido-plantilla" class="ql-editor"
                        style="background:white; max-width:800px; margin:0 auto; padding:60px 70px; box-shadow:0 4px 24px rgba(0,0,0,0.10); min-height:500px; font-family:'Segoe UI', serif; font-size:14px; line-height:1.8; color:#222;">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 px-4">
                <a id="btn-modal-editar" href="#" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-pencil me-1"></i> Editar Plantilla
                </a>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_contratos_plantillas.js?v=<?= time() ?>"></script>