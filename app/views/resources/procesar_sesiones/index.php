<?php
/**
 * MÓDULO: RECURSOS HUMANOS / PROCESAR SESIONES
 * ARCHIVO: app/views/resources/procesar_sesiones/index.php
 * PROPÓSITO: Listado de ofertas que tienen sesiones PROGRAMADAS pendientes de procesar.
 * VERSIÓN: 2.0.0 - Agrega filtro por período, columna grupos y reordena columnas.
 */
$basePath   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$page       = $page       ?? 1;
$total      = $total      ?? 0;
$totalPages = $totalPages ?? 1;
$perPage    = $perPage    ?? 25;
$offset     = ($page - 1) * $perPage;

function buildUrlPS(array $params): string {
    return '?' . http_build_query(array_merge($_GET ?? [], $params));
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_procesar_sesiones.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i>Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/resources" class="text-decoration-none text-muted">Recursos</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary">Procesar Sesiones</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Procesar Sesiones</h2>
            <p class="text-muted small mb-0">
                Ofertas con sesiones pendientes de procesar.
                <?php if ($total > 0): ?>
                    &nbsp;·&nbsp; <strong><?= $total ?></strong> oferta<?= $total !== 1 ? 's' : '' ?>
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= $basePath ?>/resources" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
                    <form method="GET" action="<?= $basePath ?>/resources/procesar-sesiones" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="periodo_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- Todos los períodos --</option>
            <?php foreach($periodos as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($filters['periodo_id'] == $p['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nombre']) ?><?= $p['estado'] === 'Finalizado' ? ' (Finalizado)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-7">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" name="search" class="form-control border-start-0"
                   placeholder="Buscar por diplomado o cohorte..."
                   value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            <?php if (!empty($filters['search']) || !empty($filters['periodo_id'])): ?>
                <a href="<?= $basePath ?>/resources/procesar-sesiones" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-dark w-100 rounded-pill">Buscar</button>
    </div>
</form>

            <div style="max-height:520px;overflow-y:auto">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small fw-bold text-secondary text-uppercase"
                        style="position:sticky;top:0;z-index:1">
                        <tr>
                            <th class="ps-3" style="width:46px">#</th>
                            <th>Diplomado</th>
                            <th style="width:160px">Grupos</th>
                            <th style="width:140px">Cohorte</th>
                            <th style="width:110px">Modalidad</th>
                            <th style="width:120px" class="text-center">Pendientes</th>
                            <th style="width:120px" class="text-center">Dictadas</th>
                            <th style="width:100px" class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ofertas)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-check2-all fs-2 d-block mb-2 opacity-25"></i>
                                    No hay ofertas con sesiones programadas o dictadas.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ofertas as $idx => $o):
                                $num      = $offset + $idx + 1;
                                $manageUrl = "{$basePath}/resources/procesar-sesiones/manage?offering_id={$o['offering_id']}";
                                $modBadge  = match($o['general_modality']) {
                                    'Presencial' => 'bg-info bg-opacity-10 text-info',
                                    'Virtual'    => 'bg-primary bg-opacity-10 text-primary',
                                    'Mixta'      => 'bg-warning bg-opacity-10 text-warning',
                                    default      => 'bg-secondary bg-opacity-10 text-secondary'
                                };
                            ?>
                            <tr style="cursor:pointer" onclick="window.location='<?= $manageUrl ?>'">
                                <td class="ps-3 text-muted small"><?= $num ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($o['diplomado_nombre']) ?></td>
                                <td>
                                    <?php if (!empty($o['grupos_nombre'])): ?>
                                        <?php foreach (explode(', ', $o['grupos_nombre']) as $g): ?>
                                            <span class="badge rounded-pill bg-light text-dark border me-1 mb-1">
                                                <?= htmlspecialchars(trim($g)) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($o['cohorte_nombre']) ?></td>
                                <td>
                                    <span class="badge rounded-pill <?= $modBadge ?>">
                                        <?= htmlspecialchars($o['general_modality']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$o['sesiones_pendientes'] > 0): ?>
                                        <span class="badge rounded-pill px-3 fw-bold"
                                              style="background:#FAEEDA;border:1px solid #BA7517;color:#633806">
                                            <i class="bi bi-hourglass-split me-1"></i>
                                            <?= $o['sesiones_pendientes'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$o['sesiones_dictadas'] > 0): ?>
                                        <span class="badge rounded-pill px-3 fw-bold"
                                              style="background:#E1F5EE;border:1px solid #1D9E75;color:#085041">
                                            <i class="bi bi-check2-circle me-1"></i>
                                            <?= $o['sesiones_dictadas'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?= $manageUrl ?>"
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                       onclick="event.stopPropagation()">
                                        <i class="bi bi-eye me-1"></i> Ver
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-3 px-1">
                    <small class="text-muted">Página <?= $page ?> de <?= $totalPages ?> · <?= $total ?> registros</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= buildUrlPS(['page' => $page - 1]) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                                <li class="page-item <?= $i===$page?'active':'' ?>">
                                    <a class="page-link" href="<?= buildUrlPS(['page'=>$i]) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                                <a class="page-link" href="<?= buildUrlPS(['page' => $page + 1]) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>