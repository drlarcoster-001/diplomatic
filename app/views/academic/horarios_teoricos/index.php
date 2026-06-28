<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: app/views/academic/horarios_teoricos/index.php
 * PROPÓSITO: Listado paginado de ofertas ABIERTA/EN CURSO para configurar horarios
 *            teóricos. Los GRUPOS son el diferenciador visual principal. Búsqueda,
 *            paginación a 25 y tabla con scroll interno.
 * VERSIÓN: 3.2.0 - Grupos como diferenciador, solo ofertas activas, sin duplicados.
 */
$basePath   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$perPage    = $perPage    ?? 25;
$page       = $page       ?? 1;
$total      = $total      ?? 0;
$totalPages = $totalPages ?? 1;
$offset     = ($page - 1) * $perPage;

function buildUrl(array $params): string {
    $merged = array_merge($_GET ?? [], $params);
    return '?' . http_build_query($merged);
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_horarios_teoricos.css">

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
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Horarios Teóricos</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Horarios Teóricos</h2>
            <p class="text-muted small mb-0">
                Ofertas abiertas o en curso. Selecciona una para configurar sus horarios.
                <?php if ($total > 0): ?>
                    &nbsp;·&nbsp; <strong><?= $total ?></strong> oferta<?= $total !== 1 ? 's' : '' ?>
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= $basePath ?>/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">

            <form method="GET" action="<?= $basePath ?>/academic/horarios-teoricos" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select name="periodo_id" class="form-select" onchange="this.form.submit()">
                        <option value="">— Todos los períodos —</option>
                        <?php foreach ($periodos ?? [] as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $periodoId === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombre']) ?><?= $p['estado'] === 'Finalizado' ? ' (Finalizado)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <select name="diploma_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Todos los diplomados --</option>
                        <?php foreach ($diplomados ?? [] as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($diplomaId ?? 0) == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <?php if (!empty($periodoId) || !empty($diplomaId)): ?>
                        <a href="<?= $basePath ?>/academic/horarios-teoricos" class="btn btn-outline-secondary w-100 rounded-pill">
                            <i class="bi bi-x-lg me-1"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <div style="max-height: 520px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small fw-bold text-secondary text-uppercase"
                           style="position: sticky; top: 0; z-index: 1;">
                        <tr>
                            <th class="ps-3" style="width: 46px;">#</th>
                            <th>Diplomado</th>
                            <th style="width: 130px;">Cohorte</th>
                            <th>Grupos</th>
                            <th style="width: 120px;" class="text-center">Horarios</th>
                            <th style="width: 130px;" class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ofertas)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-2 d-block mb-2 opacity-25"></i>
                                    <?= !empty($search)
                                        ? 'No se encontraron ofertas para "' . htmlspecialchars($search) . '".'
                                        : 'No hay ofertas académicas abiertas o en curso.' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ofertas as $idx => $o):
                                $num       = $offset + $idx + 1;
                                $grupos    = $o['grupos'] ?? null;
                                $manageUrl = "{$basePath}/academic/horarios-teoricos/manage?offering_id={$o['offering_id']}";

                                $statusBadge = $o['status'] === 'EN CURSO'
                                    ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25'
                                    : 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25';
                            ?>
                                <tr style="cursor: pointer;" onclick="window.location='<?= $manageUrl ?>'">

                                    <td class="ps-3 text-muted small"><?= $num ?></td>

                                    <td>
                                        <div class="fw-bold text-dark lh-sm">
                                            <?= htmlspecialchars($o['diplomado_nombre']) ?>
                                        </div>
                                        <div class="small mt-1">
                                            <span class="badge rounded-pill <?= $statusBadge ?>" style="font-size:10px">
                                                <?= $o['status'] ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="small text-muted">
                                        <?= htmlspecialchars($o['cohorte_nombre']) ?>
                                    </td>

                                    <td>
                                        <?php if ($grupos): ?>
                                            <?php foreach (explode(', ', $grupos) as $g): ?>
                                                <span class="badge me-1 mb-1 rounded-pill"
                                                      style="background:#EEEDFE;border:1px solid #AFA9EC;color:#3C3489;font-size:11px;font-weight:500">
                                                    <i class="bi bi-people me-1"></i><?= htmlspecialchars(trim($g)) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin grupos asignados</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <?php if ((int)$o['total_horarios'] > 0): ?>
                                            <span class="badge rounded-pill px-3"
                                                  style="background:#E1F5EE;border:1px solid #1D9E75;color:#085041;font-weight:600">
                                                <i class="bi bi-clock me-1"></i>
                                                <?= $o['total_horarios'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-light text-muted border" style="font-size:11px">
                                                Sin horarios
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end pe-3">
                                        <a href="<?= $manageUrl ?>"
                                           class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                           onclick="event.stopPropagation()">
                                            <i class="bi bi-calendar-week me-1"></i>
                                            <?= (int)$o['total_horarios'] > 0 ? 'Ver / Editar' : 'Configurar' ?>
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
                    <small class="text-muted">
                        Página <?= $page ?> de <?= $totalPages ?>
                        &nbsp;·&nbsp; <?= $total ?> registros
                    </small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= buildUrl(['page' => $page - 1]) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php
                                $rangeStart = max(1, $page - 2);
                                $rangeEnd   = min($totalPages, $page + 2);
                            ?>
                            <?php if ($rangeStart > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= buildUrl(['page' => 1]) ?>">1</a>
                                </li>
                                <?php if ($rangeStart > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php for ($i = $rangeStart; $i <= $rangeEnd; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= buildUrl(['page' => $i]) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($rangeEnd < $totalPages): ?>
                                <?php if ($rangeEnd < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= buildUrl(['page' => $totalPages]) ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= buildUrl(['page' => $page + 1]) ?>">
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