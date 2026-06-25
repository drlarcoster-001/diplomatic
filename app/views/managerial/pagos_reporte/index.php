<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / REPORTE DE PAGOS
 * ARCHIVO: app/views/managerial/pagos_reporte/index.php
 * PROPÓSITO: Filtros en cascada Período → Oferta(Diplomado+Grupo) → Usuario.
 *            Lista + buscador de usuario. Botón Limpiar. Tabla de pagos. PDF.
 * VERSIÓN: 1.2.0 - Cascada simplificada. Cohorte implícita en oferta.
 */
$basePath    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$labelMethod = ['CASH' => 'Efectivo', 'ZELLE' => 'Zelle', 'BINANCE' => 'Binance', 'PAGOMOVIL' => 'Pago Móvil'];
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/managerial_pagos_reporte.css">

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
        <a href="<?= $basePath ?>/managerial" class="text-decoration-none text-muted">Panel Gerencial</a>
      </li>
      <li class="breadcrumb-item active fw-bold text-primary">Reporte de Pagos</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-0">Reporte de Pagos</h4>
      <small class="text-muted">Pagos validados por período, diplomado y estudiante.</small>
    </div>
    <div class="d-flex gap-2">
      <?php if ($periodoId && !empty($pagos)): ?>
        <a href="<?= $basePath ?>/managerial/pagos-reporte/pdf?periodo_id=<?= $periodoId ?>&offering_id=<?= $offeringId ?>&user_id=<?= $userId ?>"
           target="_blank"
           class="btn btn-outline-dark rounded-pill px-4 shadow-sm">
          <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
      <?php endif; ?>
      <a href="<?= $basePath ?>/managerial" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
      </a>
    </div>
  </div>

  <!-- FILTROS EN CASCADA -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <div class="row g-3">

        <!-- PERÍODO -->
        <div class="col-md-3">
          <label class="form-label small fw-bold mb-1">Período</label>
          <select id="selPeriodo" class="form-select">
            <option value="">— Selecciona —</option>
            <?php foreach ($periodos as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $periodoId === (int)$p['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- DIPLOMADO + GRUPO -->
        <div class="col-md-4">
          <label class="form-label small fw-bold mb-1">Diplomado / Grupo</label>
          <select id="selDiplomado" class="form-select" disabled>
            <option value="">— Primero elige período —</option>
          </select>
        </div>

        <!-- USUARIO -->
        <div class="col-md-4 position-relative">
          <label class="form-label small fw-bold mb-1">Usuario</label>
          <div class="input-group">
            <input type="text" id="inputUsuario" class="form-control"
                   placeholder="Buscar o seleccionar..."
                   value="<?= htmlspecialchars($userSearch ?? '') ?>"
                   disabled autocomplete="off">
            <button type="button" id="btnLimpiarUsuario"
                    class="btn btn-outline-secondary d-none" title="Limpiar usuario">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <input type="hidden" id="hidUsuario" value="<?= (int)$userId ?>">
          <div id="usuarioResultados" class="list-group mt-1 position-absolute shadow-sm"
               style="max-height:220px;overflow-y:auto;z-index:200;left:0;right:0"></div>
        </div>

        <!-- COL VACÍO PARA ALINEAR BOTONES -->
        <div class="col-md-1"></div>

      </div>

      <!-- BOTONES -->
      <div class="mt-3 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="btnLimpiar">
          <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar
        </button>
        <button type="button" class="btn btn-dark rounded-pill px-5" id="btnBuscar" disabled>
          <i class="bi bi-search me-1"></i> Buscar
        </button>
      </div>

    </div>
  </div>

  <!-- TOTALES -->
  <?php if ($periodoId && !empty($pagos)): ?>
  <div class="row g-3 mb-4">
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-secondary"><?= $totales['cantidad'] ?></div>
        <div class="text-muted small">Total pagos</div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-warning">Bs. <?= number_format($totales['total_bs'], 2) ?></div>
        <div class="text-muted small">Total en Bolívares</div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-success">$<?= number_format($totales['total_usd'], 2) ?></div>
        <div class="text-muted small">Total en USD</div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- TABLA -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <?php if (!$periodoId): ?>
        <div class="text-center text-muted py-5">
          <i class="bi bi-funnel fs-2 d-block mb-2 opacity-25"></i>
          Selecciona un período para ver los pagos.
        </div>
      <?php elseif (empty($pagos)): ?>
        <div class="text-center text-muted py-5">
          <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
          No hay pagos registrados con los filtros seleccionados.
        </div>
      <?php else: ?>
        <div class="table-responsive" style="max-height:520px;overflow-y:auto">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-dark" style="position:sticky;top:0;z-index:1">
              <tr>
                <th class="ps-3">Estudiante</th>
                <th>Diplomado</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Método</th>
                <th>Referencia</th>
                <th class="text-end">Monto Bs.</th>
                <th class="text-center">Tasa</th>
                <th class="text-end pe-3">Monto USD</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pagos as $p): ?>
                <tr>
                  <td class="ps-3">
                    <div class="fw-semibold small"><?= htmlspecialchars($p['last_name'] . ', ' . $p['first_name']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($p['document_id']) ?></small>
                  </td>
                  <td class="small text-muted"><?= htmlspecialchars($p['diplomado']) ?></td>
                  <td class="text-center small"><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
                  <td class="text-center">
                    <span class="badge rounded-pill bg-light text-dark border">
                      <?= $labelMethod[$p['method']] ?? $p['method'] ?>
                    </span>
                  </td>
                  <td class="small text-muted"><?= htmlspecialchars($p['reference_id'] ?? '—') ?></td>
                  <td class="text-end fw-bold">Bs. <?= number_format((float)$p['amount'], 2) ?></td>
                  <td class="text-center small text-muted">
                    <?= $p['tasa_bcv'] ? number_format((float)$p['tasa_bcv'], 2) : '—' ?>
                  </td>
                  <td class="text-end fw-bold pe-3 text-success">
                    <?= $p['monto_usd'] ? '$' . number_format((float)$p['monto_usd'], 2) : '—' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<script>
  window.APP_BASE_PATH = '<?= $basePath ?>';
  window.PERIODO_ID    = <?= (int)$periodoId ?>;
  window.OFFERING_ID   = <?= (int)$offeringId ?>;
  window.USER_ID       = <?= (int)$userId ?>;
  window.USER_SEARCH   = '<?= htmlspecialchars($userSearch ?? '') ?>';
</script>
<script src="<?= $basePath ?>/assets/js/managerial_pagos_reporte.js?v=<?= time() ?>"></script>