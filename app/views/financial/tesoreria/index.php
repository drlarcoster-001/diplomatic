<?php
/**
 * MÓDULO: FINANCIERO / TESORERÍA
 * ARCHIVO: app/views/financial/tesoreria/index.php
 * PROPÓSITO: Grid con buscador, filtro por estado, tabla en cuadro con
 *            scroll y paginación a 25.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$page       = $page       ?? 1;
$total      = $total      ?? 0;
$totalPages = $totalPages ?? 1;
$estado     = $estado     ?? '';

function buildUrlTes(array $params): string {
    return '?' . http_build_query(array_merge($_GET ?? [], $params));
}

$estadoBadges = [
    'PENDIENTE' => ['bg' => '#FAEEDA', 'borde' => '#BA7517', 'txt' => '#633806'],
    'PAGADO'    => ['bg' => '#E1F5EE', 'borde' => '#1D9E75', 'txt' => '#085041'],
    'ANULADO'   => ['bg' => '#f1f3f5', 'borde' => '#ced4da', 'txt' => '#495057'],
];
$medioLabel = ['EFECTIVO' => 'Efectivo', 'TRANSFERENCIA' => 'Transferencia', 'PAGO_MOVIL' => 'Pago Móvil'];
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i>Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/financial" class="text-decoration-none text-muted">Financiero</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary">Tesorería</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Tesorería</h2>
            <p class="text-muted small mb-0">Ejecución de pagos sobre órdenes ya aprobadas.</p>
        </div>
        <a href="<?= $basePath ?>/financial" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="<?= $basePath ?>/financial/tesoreria" class="row g-2 mb-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Buscar por número de orden o destinatario..."
                               value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="estado" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="PENDIENTE" <?= $estado==='PENDIENTE'?'selected':'' ?>>Pendiente</option>
                        <option value="PAGADO"    <?= $estado==='PAGADO'?'selected':'' ?>>Pagado</option>
                        <option value="ANULADO"   <?= $estado==='ANULADO'?'selected':'' ?>>Anulado</option>
                    </select>
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
                            <th class="ps-3">N° Orden</th>
                            <th>Destinatario</th>
                            <th style="width:110px">Fecha Pago</th>
                            <th style="width:130px" class="text-end">Monto USD</th>
                            <th style="width:130px">Medio de Pago</th>
                            <th style="width:110px">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pagos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-cash-stack fs-2 d-block mb-2 opacity-25"></i>
                                    No hay pagos por procesar.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pagos as $p):
                                $badge = $estadoBadges[$p['estado']] ?? $estadoBadges['ANULADO'];
                                $manageUrl = "{$basePath}/financial/tesoreria/manage?id={$p['id']}";
                            ?>
                            <tr style="cursor:pointer" onclick="window.location='<?= $manageUrl ?>'">
                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($p['numero_orden']) ?></td>
                                <td class="small"><?= htmlspecialchars($p['destinatario'] ?? '—') ?></td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
                                <td class="text-end fw-bold">$<?= number_format((float)$p['monto_usd'], 2) ?></td>
                                <td class="small text-muted"><?= $p['medio_pago'] ? ($medioLabel[$p['medio_pago']] ?? $p['medio_pago']) : '—' ?></td>
                                <td>
                                    <span class="badge rounded-pill" style="background:<?= $badge['bg'] ?>;border:1px solid <?= $badge['borde'] ?>;color:<?= $badge['txt'] ?>;font-size:10px">
                                        <?= $p['estado'] ?>
                                    </span>
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
                                <a class="page-link" href="<?= buildUrlTes(['page' => $page - 1]) ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                                <li class="page-item <?= $i===$page?'active':'' ?>">
                                    <a class="page-link" href="<?= buildUrlTes(['page'=>$i]) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                                <a class="page-link" href="<?= buildUrlTes(['page' => $page + 1]) ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>