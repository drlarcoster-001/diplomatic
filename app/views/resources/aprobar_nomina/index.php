<?php
/**
 * MÓDULO: RECURSOS HUMANOS / APROBAR NÓMINAS
 * ARCHIVO: app/views/resources/aprobar_nomina/index.php
 * PROPÓSITO: Dos pestañas: "Pendientes de Aprobar" (PROCESADA, con botón
 *            Revisar/Aprobar) y "Aprobadas" (APROBADA, con botón Reversar).
 *            Cada pestaña con buscador, grid en cuadro con scroll y paginación a 25.
 * VERSIÓN: 2.0.0 - Agregado sistema de pestañas.
 */
$basePath   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$tab        = $tab        ?? 'pendientes';
$page       = $page       ?? 1;
$total      = $total      ?? 0;
$totalPages = $totalPages ?? 1;
$perPage    = $perPage    ?? 25;
$offset     = ($page - 1) * $perPage;

function buildUrlAN(array $params): string {
    return '?' . http_build_query(array_merge($_GET ?? [], $params));
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_aprobar_nomina.css">

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
            <li class="breadcrumb-item active fw-bold text-primary">Aprobar Nóminas</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Aprobar Nóminas</h2>
            <p class="text-muted small mb-0">Aprobación de nóminas procesadas y gestión de aprobaciones existentes.</p>
        </div>
        <a href="<?= $basePath ?>/resources" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link fw-bold <?= $tab === 'pendientes' ? 'active' : '' ?>"
               href="<?= $basePath ?>/resources/aprobar-nomina?tab=pendientes">
                <i class="bi bi-hourglass-split me-1"></i> Pendientes de Aprobar
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold <?= $tab === 'aprobadas' ? 'active' : '' ?>"
               href="<?= $basePath ?>/resources/aprobar-nomina?tab=aprobadas">
                <i class="bi bi-check2-circle me-1"></i> Aprobadas
            </a>
        </li>
    </ul>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="<?= $basePath ?>/resources/aprobar-nomina" class="row g-2 mb-3">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Buscar por nombre de nómina..."
                               value="<?= htmlspecialchars($search ?? '') ?>">
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
                            <th class="ps-3">Nombre</th>
                            <th style="width:110px">Tipo</th>
                            <th style="width:110px">Fecha Pago</th>
                            <th style="width:90px" class="text-center">Personal</th>
                            <th style="width:140px" class="text-end">Total USD</th>
                            <th style="width:140px" class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($nominas)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-check2-all fs-2 d-block mb-2 opacity-25"></i>
                                    <?= $tab === 'aprobadas' ? 'No hay nóminas aprobadas.' : 'No hay nóminas pendientes de aprobación.' ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($nominas as $n):
                                $tipoBadge = match($n['tipo']) {
                                    'QUINCENAL'  => ['bg' => '#EEEDFE', 'borde' => '#7F77DD', 'txt' => '#3C3489'],
                                    'POR_DIA'    => ['bg' => '#E1F5EE', 'borde' => '#1D9E75', 'txt' => '#085041'],
                                    'POR_SESION' => ['bg' => '#FAEEDA', 'borde' => '#BA7517', 'txt' => '#633806'],
                                    default      => ['bg' => '#f1f3f5', 'borde' => '#ced4da', 'txt' => '#495057'],
                                };
                                $manageUrl = "{$basePath}/resources/aprobar-nomina/manage?id={$n['id']}";
                                $tienePagadas = $tab === 'aprobadas' && (int)($n['ordenes_pagadas'] ?? 0) > 0;
                            ?>
                            <tr <?= $tab === 'pendientes' ? "style=\"cursor:pointer\" onclick=\"window.location='{$manageUrl}'\"" : '' ?>>
                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($n['nombre']) ?></td>
                                <td>
                                    <span class="badge rounded-pill" style="background:<?= $tipoBadge['bg'] ?>;border:1px solid <?= $tipoBadge['borde'] ?>;color:<?= $tipoBadge['txt'] ?>;font-size:10px">
                                        <?= str_replace('_', ' ', $n['tipo']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($n['fecha_pago'])) ?></td>
                                <td class="text-center"><?= (int)$n['total_personal'] ?></td>
                                <td class="text-end fw-bold">$<?= number_format((float)$n['total_usd'], 2) ?></td>
                                <td class="text-end pe-3">
                                    <?php if ($tab === 'pendientes'): ?>
                                        <a href="<?= $manageUrl ?>" class="btn btn-sm btn-success rounded-pill px-3"
                                           onclick="event.stopPropagation()">
                                            <i class="bi bi-clipboard-check me-1"></i> Revisar
                                        </a>
                                    <?php else: ?>
                                        <?php if ($tienePagadas): ?>
                                            <span class="badge rounded-pill px-3" style="background:#FCEBEB;border:1px solid #E24B4A;color:#A32D2D" title="Tiene órdenes pagadas">
                                                <i class="bi bi-lock-fill me-1"></i> Con pagos
                                            </span>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-warning rounded-pill px-3 btn-reversar-aprobacion"
                                                    data-id="<?= $n['id'] ?>" data-nombre="<?= htmlspecialchars($n['nombre']) ?>">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reversar
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
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
                                <a class="page-link" href="<?= buildUrlAN(['page' => $page - 1]) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                                <li class="page-item <?= $i===$page?'active':'' ?>">
                                    <a class="page-link" href="<?= buildUrlAN(['page'=>$i]) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                                <a class="page-link" href="<?= buildUrlAN(['page' => $page + 1]) ?>">
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

<script>window.APP_BASE_PATH = '<?= $basePath ?>';</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_aprobar_nomina_index.js?v=<?= time() ?>"></script>