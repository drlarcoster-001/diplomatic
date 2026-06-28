<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/cohortes_config/index.php
 * Propósito: Interfaz para forzar estatus, borrados físicos y acciones masivas con paginación.
 * Versión: 1.3.0
 *
 * @var array  $cohortes
 * @var string $search
 * @var int    $currentPage
 * @var int    $totalPages
 */
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="/diplomatic/public/assets/css/academic_cohortes_config.css">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Configuración Avanzada de Cohortes</h2>
            <p class="text-danger small fw-bold"><i class="fas fa-exclamation-triangle"></i> ATENCIÓN: Acciones de alto impacto y borrado físico.</p>
        </div>
        <div>
            <a href="/diplomatic/public/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="/diplomatic/public/academic/cohortes-config" class="row g-3">
                <div class="col-md-4">
                    <select name="periodo_id" class="form-select">
                        <option value="">— Todos los períodos —</option>
                        <?php foreach ($periodos ?? [] as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $periodoId === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombre']) ?><?= $p['estado'] === 'Finalizado' ? ' (Finalizado)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Buscar por código o nombre..." value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden border-top border-warning border-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small fw-bold text-secondary text-uppercase">
                    <tr>
                        <th class="ps-4" style="width:40px;">
                            <input type="checkbox" id="checkAll" title="Seleccionar todos">
                        </th>
                        <th style="width:60px;">#</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Estatus Actual</th>
                        <th>Archivo</th>
                        <th class="text-end pe-4">Acciones Críticas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cohortes)): ?>
                        <tr><td colspan="7" class="text-center py-4">No hay registros.</td></tr>
                    <?php else: ?>
                        <?php $loop = 0; ?>
                        <?php foreach ($cohortes as $c): ?>
                            <?php $rowNum = ($currentPage - 1) * 25 + $loop++ + 1; ?>
                            <tr>
                                <td class="ps-4">
                                    <input type="checkbox" class="check-item" value="<?= $c['id'] ?>">
                                </td>
                                <td class="text-muted small"><?= $rowNum ?></td>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($c['cohort_code']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                                <td>
                                    <?php
                                        $bg = match(trim(strtolower($c['cohort_status']))) {
                                            'planificada' => 'bg-secondary',
                                            'en curso'    => 'bg-primary',
                                            'finalizada'  => 'bg-success',
                                            'reabierta'   => 'bg-warning text-dark',
                                            'suspendida'  => 'bg-danger',
                                            'cancelada'   => 'bg-dark',
                                            default       => 'bg-light text-dark'
                                        };
                                    ?>
                                    <span class="badge rounded-pill <?= $bg ?>"><?= $c['cohort_status'] ?></span>
                                </td>
                                <td>
                                    <?php if ($c['is_active'] == 0): ?>
                                        <span class="badge bg-secondary rounded-pill">Archivada</span>
                                    <?php else: ?>
                                        <span class="badge bg-light border text-dark rounded-pill">Activo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-white border text-warning btn-status" data-id="<?= $c['id'] ?>" title="Forzar Estatus / Revivir">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-white border text-danger btn-hard-delete" data-id="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" title="Borrado Físico Definitivo">
                                            <i class="bi bi-trash-fill"></i>
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

    <!-- Paginación -->
    <?php if (($totalPages ?? 1) > 1): ?>
    <div class="d-flex justify-content-center mt-4">
        <nav>
            <ul class="pagination pagination-sm">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= htmlspecialchars($search ?? '') ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>

</div>

<!-- Panel masivo flotante -->
<div class="position-fixed bottom-0 end-0 p-4" style="z-index:999;">
    <div class="card border-0 shadow-lg p-3 d-none" id="panelMasivo">
        <p class="small fw-bold mb-2"><span id="countSelected">0</span> cohorte(s) seleccionada(s)</p>
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm rounded-pill px-3" id="btnReactivar">
                <i class="bi bi-arrow-up-circle"></i> Reactivar
            </button>
            <button class="btn btn-secondary btn-sm rounded-pill px-3" id="btnArchivar">
                <i class="bi bi-archive"></i> Archivar
            </button>
        </div>
    </div>
</div>

<form id="formMasivo" action="/diplomatic/public/academic/cohortes-config/massiveAction" method="POST">
    <input type="hidden" name="accion" id="accionMasiva">
    <div id="hiddenIds"></div>
</form>

<!-- Modal: Forzar Estatus -->
<div class="modal fade" id="modalForceStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formStatus" action="/diplomatic/public/academic/cohortes-config/updateStatus" method="POST" class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-sync-alt me-2"></i> Forzar Estatus de Cohorte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Modificar el estatus desde esta pantalla anulará las reglas de negocio regulares.
                    Si la cohorte estaba "Archivada", se <strong>reactivará</strong> automáticamente al guardar.
                </p>
                <input type="hidden" name="id" id="status_id">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Cohorte Seleccionada:</label>
                    <input type="text" class="form-control bg-light border-0" id="status_name" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Seleccione Nuevo Estatus:</label>
                    <select name="cohort_status" id="status_select" class="form-select form-select-lg" required>
                        <option value="Planificada">Planificada</option>
                        <option value="En curso">En curso</option>
                        <option value="Finalizada">Finalizada</option>
                        <option value="Reabierta">Reabierta (Extemporánea)</option>
                        <option value="Suspendida">Suspendida</option>
                        <option value="Cancelada">Cancelada</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning fw-bold rounded-pill px-4">Ejecutar Cambio</button>
            </div>
        </form>
    </div>
</div>

<div id="error-container" data-error="<?= htmlspecialchars($_GET['error'] ?? '') ?>" style="display:none;"></div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/diplomatic/public/assets/js/academic_cohortes_config.js"></script>