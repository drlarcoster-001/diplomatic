<?php
/**
 * MÓDULO: RECURSOS HUMANOS / NÓMINA
 * ARCHIVO: app/views/resources/nomina/index.php
 * PROPÓSITO: Listado de nóminas creadas, con búsqueda, paginación y totales.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$page       = $page       ?? 1;
$total      = $total      ?? 0;
$totalPages = $totalPages ?? 1;
$perPage    = $perPage    ?? 25;
$offset     = ($page - 1) * $perPage;

function buildUrlN(array $params): string {
    return '?' . http_build_query(array_merge($_GET ?? [], $params));
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_nomina.css">

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
            <li class="breadcrumb-item active fw-bold text-primary">Nómina</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Nómina</h2>
            <p class="text-muted small mb-0">
                Gestión de nóminas y generación de órdenes de pago.
                <?php if ($total > 0): ?>
                    &nbsp;·&nbsp; <strong><?= $total ?></strong> nómina<?= $total !== 1 ? 's' : '' ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/resources" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="<?= $basePath ?>/resources/nomina/create" class="btn btn-danger rounded-pill px-4 shadow-sm fw-bold">
                <i class="bi bi-plus-circle me-1"></i> Nueva Nómina
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="<?= $basePath ?>/resources/nomina" class="row g-2 mb-3">
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
                            <th style="width:110px">Estado</th>
                            <th style="width:100px" class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($nominas)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-cash-stack fs-2 d-block mb-2 opacity-25"></i>
                                    No hay nóminas creadas todavía.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($nominas as $n):
                                $manageUrl = "{$basePath}/resources/nomina/manage?id={$n['id']}";
                                $tipoBadge = match($n['tipo']) {
                                    'QUINCENAL'  => ['bg' => '#EEEDFE', 'borde' => '#7F77DD', 'txt' => '#3C3489'],
                                    'POR_DIA'    => ['bg' => '#E1F5EE', 'borde' => '#1D9E75', 'txt' => '#085041'],
                                    'POR_SESION' => ['bg' => '#FAEEDA', 'borde' => '#BA7517', 'txt' => '#633806'],
                                    default      => ['bg' => '#f1f3f5', 'borde' => '#ced4da', 'txt' => '#495057'],
                                };
                                $estadoBadge = match($n['estado']) {
                                    'BORRADOR'  => ['bg' => '#FCEBEB', 'borde' => '#E24B4A', 'txt' => '#A32D2D'],
                                    'PROCESADA' => ['bg' => '#E6F1FB', 'borde' => '#378ADD', 'txt' => '#0C447C'],
                                    'APROBADA'  => ['bg' => '#E1F5EE', 'borde' => '#1D9E75', 'txt' => '#085041'],
                                    default     => ['bg' => '#f1f3f5', 'borde' => '#ced4da', 'txt' => '#495057'],
                                };
                            ?>
                            <tr style="cursor:pointer" onclick="window.location='<?= $manageUrl ?>'">
                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($n['nombre']) ?></td>
                                <td>
                                    <span class="badge rounded-pill" style="background:<?= $tipoBadge['bg'] ?>;border:1px solid <?= $tipoBadge['borde'] ?>;color:<?= $tipoBadge['txt'] ?>;font-size:10px">
                                        <?= str_replace('_', ' ', $n['tipo']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($n['fecha_pago'])) ?></td>
                                <td class="text-center"><?= (int)$n['total_personal'] ?></td>
                                <td class="text-end fw-bold">$<?= number_format((float)$n['total_usd'], 2) ?></td>
                                <td>
                                    <span class="badge rounded-pill" style="background:<?= $estadoBadge['bg'] ?>;border:1px solid <?= $estadoBadge['borde'] ?>;color:<?= $estadoBadge['txt'] ?>;font-size:10px">
                                        <?= $n['estado'] ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex gap-1 justify-content-end" onclick="event.stopPropagation()">
                                        <a href="<?= $manageUrl ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-eye me-1"></i> Ver
                                        </a>
                                        <?php if ($n['estado'] === 'BORRADOR'): ?>
                                            <button class="btn btn-sm btn-outline-danger rounded-pill px-2 btn-descartar"
                                                    data-id="<?= $n['id'] ?>" data-nombre="<?= htmlspecialchars($n['nombre']) ?>"
                                                    title="Descartar nómina">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php elseif (in_array($n['estado'], ['PROCESADA', 'APROBADA'], true)): ?>
                                            <button class="btn btn-sm btn-outline-warning rounded-pill px-2 btn-reversar"
                                                    data-id="<?= $n['id'] ?>" data-nombre="<?= htmlspecialchars($n['nombre']) ?>"
                                                    title="Reversar">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
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
                                <a class="page-link" href="<?= buildUrlN(['page' => $page - 1]) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                                <li class="page-item <?= $i===$page?'active':'' ?>">
                                    <a class="page-link" href="<?= buildUrlN(['page'=>$i]) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                                <a class="page-link" href="<?= buildUrlN(['page' => $page + 1]) ?>">
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
<script src="<?= $basePath ?>/assets/js/resources_nomina_index.js?v=<?= time() ?>"></script>