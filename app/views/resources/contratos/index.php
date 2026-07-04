<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * Archivo: app/views/resources/contratos/index.php
 * Propósito: Historial de contratos generados.
 * Versión: 1.0.0
 *
 * @var array  $contratos
 * @var string $search
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_contratos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">

<div class="container-fluid py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources" class="text-decoration-none text-muted">Panel de Recursos</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#198754;">Contratos</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Contratos</h2>
            <p class="text-muted small">Historial y gestión de contratos generados para el personal.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/resources" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="<?= $basePath ?>/resources/contratos/create" class="btn rounded-pill px-4 shadow-sm text-white" style="background:#198754;">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Contrato
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= $basePath ?>/resources/contratos" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Buscar por personal, cédula, número de contrato..."
                               value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small fw-bold text-secondary text-uppercase">
                    <tr>
                        <th class="ps-4">N° Contrato</th>
                        <th>Personal</th>
                        <th>Plantilla</th>
                        <th>Fecha</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contratos)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No hay contratos generados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($contratos as $c):
                            $estadoColor = match($c['estado']) {
                                'Activo'     => 'success',
                                'Borrador'   => 'secondary',
                                'Finalizado' => 'primary',
                                'Rescindido' => 'danger',
                                default      => 'secondary'
                            };
                        ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold small" style="color:#198754;"><?= htmlspecialchars($c['numero_contrato']) ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></div>
                                <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($c['document_id']) ?> · <?= htmlspecialchars($c['tipo_siglas'] ?? '') ?></div>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($c['plantilla_nombre']) ?></td>
                            <td class="small text-muted"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?= $estadoColor ?> rounded-pill px-3"><?= $c['estado'] ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                <button type="button"
                                        class="btn btn-sm btn-white border text-success btn-ver-contrato"
                                        data-id="<?= $c['id'] ?>"
                                        data-numero="<?= htmlspecialchars($c['numero_contrato']) ?>"
                                        data-persona="<?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>"
                                        title="Ver Contrato">
                                    <i class="bi bi-eye"></i>
                                </button>
                                    <button type="button"
                                            class="btn btn-sm btn-white border text-primary btn-estado"
                                            data-id="<?= $c['id'] ?>"
                                            data-estado="<?= $c['estado'] ?>"
                                            title="Cambiar estado">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                    <a href="<?= $basePath ?>/resources/contratos/edit?id=<?= $c['id'] ?>"
                                       class="btn btn-sm btn-white border text-warning"
                                       title="Editar / Reasignar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-white border text-danger btn-delete-contrato"
                                            data-id="<?= $c['id'] ?>"
                                            data-numero="<?= htmlspecialchars($c['numero_contrato']) ?>"
                                            title="Eliminar permanentemente">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ver Contrato -->
<div class="modal fade" id="modalVerContrato" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 py-3 px-4" style="background:#198754;">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="modalContratoNumero"></h5>
                    <small class="text-white opacity-75" id="modalContratoPersona"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div style="background:#e9ecef; padding:32px; min-height:500px;">
                    <div id="modal-contrato-contenido" class="ql-editor"
                         style="background:white; max-width:800px; margin:0 auto; padding:60px 70px; box-shadow:0 4px 24px rgba(0,0,0,0.10); min-height:400px; font-family:'Segoe UI', serif; font-size:14px; line-height:1.8; color:#222;">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 px-4">
                <a id="btn-modal-pdf" href="#" target="_blank" class="btn btn-success rounded-pill px-4">
                    <i class="bi bi-file-pdf me-1"></i> Descargar PDF
                </a>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal cambio de estado -->
<div class="modal fade" id="modalEstado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form id="formEstado" action="<?= $basePath ?>/resources/contratos/changeStatus" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 py-3" style="background:#198754;">
                <h6 class="modal-title fw-bold text-white">Cambiar Estado</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="estado_id">
                <label class="form-label small fw-bold">NUEVO ESTADO</label>
                <select name="estado" id="estado_select" class="form-select">
                    <option value="Borrador">Borrador</option>
                    <option value="Activo">Activo</option>
                    <option value="Finalizado">Finalizado</option>
                    <option value="Rescindido">Rescindido</option>
                </select>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn rounded-pill px-3 text-white" style="background:#198754;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_contratos.js?v=<?= time() ?>"></script>