<?php
/**
 * MÓDULO: FINANCIERO / ÓRDENES DE PAGO
 * ARCHIVO: app/views/financial/ordenes_pago/index.php
 * PROPÓSITO: Grid con buscador, filtro por tipo, filtro por estado, tabla
 *            en cuadro con scroll y paginación a 25.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$page       = $page       ?? 1;
$total      = $total      ?? 0;
$totalPages = $totalPages ?? 1;
$tipo       = $tipo       ?? '';
$estado     = $estado     ?? '';

function buildUrlOP(array $params): string {
    return '?' . http_build_query(array_merge($_GET ?? [], $params));
}

$estadoBadges = [
    'PENDIENTE' => ['bg' => '#FAEEDA', 'borde' => '#BA7517', 'txt' => '#633806'],
    'APROBADA'  => ['bg' => '#E1F5EE', 'borde' => '#1D9E75', 'txt' => '#085041'],
    'RECHAZADA' => ['bg' => '#FCEBEB', 'borde' => '#E24B4A', 'txt' => '#A32D2D'],
    'ANULADA'   => ['bg' => '#f1f3f5', 'borde' => '#ced4da', 'txt' => '#495057'],
    'PAGADA'    => ['bg' => '#E6F1FB', 'borde' => '#378ADD', 'txt' => '#0C447C'],
];
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
            <li class="breadcrumb-item active fw-bold text-primary">Órdenes de Pago</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Órdenes de Pago</h2>
            <p class="text-muted small mb-0">Revisión y aprobación de todas las órdenes generadas (nómina y proveedores).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/financial" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="<?= $basePath ?>/financial/ordenes-pago/create" class="btn btn-dark rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Nueva Orden Directa
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="<?= $basePath ?>/financial/ordenes-pago" class="row g-2 mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Buscar por número, destinatario o concepto..."
                               value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="tipo" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los tipos</option>
                        <option value="NOMINA"    <?= $tipo==='NOMINA'?'selected':'' ?>>Nómina</option>
                        <option value="PROVEEDOR" <?= $tipo==='PROVEEDOR'?'selected':'' ?>>Proveedor</option>
                        <option value="DIRECTA"   <?= $tipo==='DIRECTA'?'selected':'' ?>>Directa</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="estado" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="PENDIENTE" <?= $estado==='PENDIENTE'?'selected':'' ?>>Pendiente</option>
                        <option value="APROBADA"  <?= $estado==='APROBADA'?'selected':'' ?>>Aprobada</option>
                        <option value="RECHAZADA" <?= $estado==='RECHAZADA'?'selected':'' ?>>Rechazada</option>
                        <option value="ANULADA"   <?= $estado==='ANULADA'?'selected':'' ?>>Anulada</option>
                        <option value="PAGADA"    <?= $estado==='PAGADA'?'selected':'' ?>>Pagada</option>
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
                            <th style="width:100px">Tipo</th>
                            <th>Destinatario</th>
                            <th>Documento Origen</th>
                            <th style="width:110px">Fecha</th>
                            <th style="width:120px" class="text-end">Monto USD</th>
                            <th style="width:110px">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordenes)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-receipt-cutoff fs-2 d-block mb-2 opacity-25"></i>
                                    No hay órdenes de pago registradas.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ordenes as $o):
                                $badge = $estadoBadges[$o['estado']] ?? $estadoBadges['ANULADA'];
                                $manageUrl = "{$basePath}/financial/ordenes-pago/manage?id={$o['id']}";
                                $tipoLabel = ['NOMINA' => 'Nómina', 'PROVEEDOR' => 'Proveedor', 'DIRECTA' => 'Directa'][$o['tipo']] ?? $o['tipo'];
                                $tipoBadgeColor = ['NOMINA' => '#0C447C', 'PROVEEDOR' => '#533AB7', 'DIRECTA' => '#BA7517'][$o['tipo']] ?? '#495057';
                            ?>
                            <tr style="cursor:pointer" onclick="window.location='<?= $manageUrl ?>'">
                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($o['numero_orden']) ?></td>
                                <td>
                                    <span class="badge rounded-pill" style="background:<?= $tipoBadgeColor ?>1A;color:<?= $tipoBadgeColor ?>;font-size:10px">
                                        <?= $tipoLabel ?>
                                    </span>
                                </td>
                                <td class="small"><?= htmlspecialchars($o['destinatario'] ?? '—') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($o['documento_origen'] ?? '—') ?></td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($o['fecha_pago'])) ?></td>
                                <td class="text-end fw-bold">$<?= number_format((float)$o['monto_usd'], 2) ?></td>
                                <td>
                                    <span class="badge rounded-pill" style="background:<?= $badge['bg'] ?>;border:1px solid <?= $badge['borde'] ?>;color:<?= $badge['txt'] ?>;font-size:10px">
                                        <?= $o['estado'] ?>
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
                                <a class="page-link" href="<?= buildUrlOP(['page' => $page - 1]) ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                                <li class="page-item <?= $i===$page?'active':'' ?>">
                                    <a class="page-link" href="<?= buildUrlOP(['page'=>$i]) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                                <a class="page-link" href="<?= buildUrlOP(['page' => $page + 1]) ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>