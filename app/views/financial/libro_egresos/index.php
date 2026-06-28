<?php
/**
 * MÓDULO: FINANCIERO / LIBRO DE EGRESOS
 * ARCHIVO: app/views/financial/libro_egresos/index.php
 * PROPÓSITO: Listado paginado de egresos con filtros por fecha, tipo y
 *            movimiento. Tarjetas de totales. Botón reporte PDF.
 *            Diseño responsive y amigable para móvil.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$offset = ($page - 1) * $perPage;

function buildUrlLE(array $params): string {
    return '?' . http_build_query(array_merge($_GET ?? [], $params));
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_libro_egresos.css">

<div class="container-fluid py-4">

  <!-- MIGA DE PAN -->
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
      <li class="breadcrumb-item">
        <a href="<?= $basePath ?>/financial/egresos" class="text-decoration-none text-muted">Operaciones de Egreso</a>
      </li>
      <li class="breadcrumb-item active fw-bold text-primary">Libro de Egresos</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-0">Libro de Egresos</h4>
      <small class="text-muted">
        <?= $total ?> registro<?= $total !== 1 ? 's' : '' ?>
        <?php if (!empty($filtros['desde']) || !empty($filtros['hasta'])): ?>
          <?= !empty($filtros['desde']) ? ' · Desde ' . $filtros['desde'] : '' ?>
          <?= !empty($filtros['hasta']) ? ' · Hasta ' . $filtros['hasta'] : '' ?>
        <?php endif; ?>
      </small>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= $basePath ?>/financial/libro-egresos/pdf?<?= http_build_query(array_merge($filtros, ['periodo_id' => $periodoId])) ?>"
         target="_blank"
         class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
      </a>
      <a href="<?= $basePath ?>/financial/egresos" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
      </a>
    </div>
  </div>

  <!-- TARJETAS TOTALES -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="le-total-card le-total-pago">
        <div class="le-total-label">Total Egresos</div>
        <div class="le-total-valor">-$<?= number_format((float)($totales['total_pagos_usd'] ?? 0), 2) ?></div>
        <div class="le-total-sub"><?= $totales['cant_pagos'] ?? 0 ?> pagos</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="le-total-card le-total-reversa">
        <div class="le-total-label">Total Reversas</div>
        <div class="le-total-valor">+$<?= number_format((float)($totales['total_reversas_usd'] ?? 0), 2) ?></div>
        <div class="le-total-sub"><?= $totales['cant_reversas'] ?? 0 ?> reversas</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <?php $neto = (float)($totales['total_pagos_usd'] ?? 0) - (float)($totales['total_reversas_usd'] ?? 0); ?>
      <div class="le-total-card le-total-neto">
        <div class="le-total-label">Neto Egresos</div>
        <div class="le-total-valor">-$<?= number_format($neto, 2) ?></div>
        <div class="le-total-sub">USD</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="le-total-card le-total-bs">
        <div class="le-total-label">Total en Bs.</div>
        <div class="le-total-valor">Bs. <?= number_format((float)($totales['total_pagos_bs'] ?? 0), 0, ',', '.') ?></div>
        <div class="le-total-sub">Bolívares</div>
      </div>
    </div>
  </div>

  <!-- FILTROS -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2">
        <div class="col-12 mb-2">
          <label class="form-label small fw-bold mb-1">Período</label>
          <select name="periodo_id" id="selPeriodo" class="form-select form-select-sm">
            <option value="">— Filtrar por fechas manuales —</option>
            <?php foreach ($periodos as $p): ?>
              <option value="<?= $p['id'] ?>"
                      data-inicio="<?= $p['fecha_inicio'] ?>"
                      data-fin="<?= $p['fecha_fin'] ?>"
                      <?= $periodoId === (int)$p['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['nombre']) ?><?= $p['estado'] === 'Finalizado' ? ' (Finalizado)' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold mb-1">Desde</label>
          <input type="date" name="desde" class="form-control form-control-sm"
                 value="<?= htmlspecialchars($filtros['desde']) ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold mb-1">Hasta</label>
          <input type="date" name="hasta" class="form-control form-control-sm"
                 value="<?= htmlspecialchars($filtros['hasta']) ?>">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold mb-1">Tipo</label>
          <select name="tipo" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="NOMINA"    <?= $filtros['tipo'] === 'NOMINA'    ? 'selected' : '' ?>>Nómina</option>
            <option value="PROVEEDOR" <?= $filtros['tipo'] === 'PROVEEDOR' ? 'selected' : '' ?>>Proveedor</option>
            <option value="DIRECTA"   <?= $filtros['tipo'] === 'DIRECTA'   ? 'selected' : '' ?>>Directa</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold mb-1">Movimiento</label>
          <select name="movimiento" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="PAGO"   <?= $filtros['movimiento'] === 'PAGO'   ? 'selected' : '' ?>>Pago</option>
            <option value="REVERSA"<?= $filtros['movimiento'] === 'REVERSA'? 'selected' : '' ?>>Reversa</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-bold mb-1">Buscar</label>
          <input type="text" name="search" class="form-control form-control-sm"
                 placeholder="Concepto, orden, destinatario..."
                 value="<?= htmlspecialchars($filtros['search']) ?>">
        </div>
        <div class="col-md-1 d-flex align-items-end gap-1">
          <button type="submit" class="btn btn-dark btn-sm rounded-pill w-100">
            <i class="bi bi-search"></i>
          </button>
          <a href="<?= $basePath ?>/financial/libro-egresos" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="bi bi-x"></i>
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- TABLA DESKTOP / CARDS MOBILE -->
  <?php if (empty($egresos)): ?>
    <div class="card border-0 shadow-sm">
      <div class="text-center text-muted py-5">
        <i class="bi bi-journal-x fs-2 d-block mb-2 opacity-25"></i>
        No hay registros con los filtros aplicados.
      </div>
    </div>
  <?php else: ?>

    <!-- TABLA (solo en desktop) -->
    <div class="card border-0 shadow-sm d-none d-md-block">
      <div class="table-responsive" style="max-height:520px;overflow-y:auto">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-dark" style="position:sticky;top:0;z-index:1">
            <tr>
              <th class="ps-3">Fecha</th>
              <th>Orden</th>
              <th>Tipo</th>
              <th>Concepto / Destinatario</th>
              <th class="text-end">Monto USD</th>
              <th class="text-center">Tasa</th>
              <th class="text-end pe-3">Monto Bs.</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($egresos as $e): ?>
              <?php $esPago = $e['tipo_movimiento'] === 'PAGO'; ?>
              <tr class="<?= $esPago ? 'le-fila-pago' : 'le-fila-reversa' ?>">
                <td class="ps-3 small text-muted">
                  <?= date('d/m/Y', strtotime($e['fecha'])) ?>
                </td>
                <td class="small fw-semibold"><?= htmlspecialchars($e['numero_orden'] ?? '—') ?></td>
                <td>
                  <span class="le-badge-tipo le-tipo-<?= strtolower($e['tipo']) ?>">
                    <?= $e['tipo'] === 'NOMINA' ? 'Nómina' : ($e['tipo'] === 'PROVEEDOR' ? 'Proveedor' : 'Directa') ?>
                  </span>
                </td>
                <td>
                  <div class="small fw-semibold text-truncate" style="max-width:200px">
                    <?= htmlspecialchars($e['concepto'] ?? '—') ?>
                  </div>
                  <div class="small text-muted"><?= htmlspecialchars($e['destinatario'] ?? '—') ?></div>
                </td>
                <td class="text-end fw-bold <?= $esPago ? 'text-danger' : 'text-success' ?>">
                  <?= $esPago ? '-' : '+' ?>$<?= number_format(abs((float)$e['monto_usd']), 2) ?>
                </td>
                <td class="text-center small text-muted"><?= number_format((float)$e['tasa_bcv'], 2) ?></td>
                <td class="text-end pe-3 fw-bold <?= $esPago ? 'text-danger' : 'text-success' ?>">
                  <?= $esPago ? '-' : '+' ?>Bs. <?= number_format(abs((float)$e['monto_bs']), 0, ',', '.') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CARDS (solo en móvil) -->
    <div class="d-md-none">
      <?php foreach ($egresos as $e): ?>
        <?php $esPago = $e['tipo_movimiento'] === 'PAGO'; ?>
        <div class="le-mobile-card <?= $esPago ? 'le-mobile-pago' : 'le-mobile-reversa' ?>">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <div>
              <span class="le-badge-tipo le-tipo-<?= strtolower($e['tipo']) ?>">
                <?= $e['tipo'] === 'NOMINA' ? 'Nómina' : ($e['tipo'] === 'PROVEEDOR' ? 'Proveedor' : 'Directa') ?>
              </span>
              <span class="small text-muted ms-2"><?= date('d/m/Y', strtotime($e['fecha'])) ?></span>
            </div>
            <span class="fw-bold <?= $esPago ? 'text-danger' : 'text-success' ?>">
              <?= $esPago ? '-' : '+' ?>$<?= number_format(abs((float)$e['monto_usd']), 2) ?>
            </span>
          </div>
          <div class="small fw-semibold"><?= htmlspecialchars($e['concepto'] ?? '—') ?></div>
          <div class="small text-muted"><?= htmlspecialchars($e['destinatario'] ?? '—') ?></div>
          <div class="d-flex justify-content-between mt-1">
            <span class="small text-muted"><?= htmlspecialchars($e['numero_orden'] ?? '') ?></span>
            <span class="small <?= $esPago ? 'text-danger' : 'text-success' ?>">
              Bs. <?= number_format(abs((float)$e['monto_bs']), 0, ',', '.') ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- PAGINACIÓN -->
    <?php if ($pages > 1): ?>
      <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <small class="text-muted">Página <?= $page ?> de <?= $pages ?> · <?= $total ?> registros</small>
        <nav><ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= buildUrlLE(['page' => $page - 1]) ?>">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
          <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="<?= buildUrlLE(['page' => $i]) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= buildUrlLE(['page' => $page + 1]) ?>">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
        </ul></nav>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<script src="<?= $basePath ?>/assets/js/financial_libro_egresos.js?v=<?= time() ?>"></script>