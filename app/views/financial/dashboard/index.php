<?php
/**
 * MÓDULO: FINANCIERO / DASHBOARD
 * ARCHIVO: app/views/financial/dashboard/index.php
 * PROPÓSITO: Pantalla de resumen financiero con indicadores clave y
 *            gráfica de ingresos vs egresos de los últimos 6 meses.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$ingresos  = (float) ($indicadores['ingresos_mes']       ?? 0);
$egresos   = (float) ($indicadores['egresos_mes']        ?? 0);
$saldo     = $ingresos - $egresos;
$ordenes   = (int)   ($indicadores['ordenes_pendientes']  ?? 0);
$tesoreria = (int)   ($indicadores['tesoreria_pendientes'] ?? 0);
$tasa      = (float) ($indicadores['tasa_bcv']            ?? 0);

$graficaJson = json_encode($graficaMensual, JSON_UNESCAPED_UNICODE);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_dashboard.css">

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
      <li class="breadcrumb-item active fw-bold text-primary">Dashboard</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-0">Dashboard Financiero</h4>
      <small class="text-muted">Resumen del mes — <?= date('F Y') ?></small>
    </div>
    <a href="<?= $basePath ?>/financial" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
      <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
  </div>

  <!-- TARJETAS INDICADORES -->
  <div class="row g-3 mb-4">

    <div class="col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100 dash-card dash-card-ingreso">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="dash-label">Ingresos del Mes</div>
              <div class="dash-valor text-success">$<?= number_format($ingresos, 2) ?></div>
            </div>
            <div class="dash-icono bg-success bg-opacity-10 text-success">
              <i class="bi bi-arrow-down-circle-fill"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100 dash-card dash-card-egreso">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="dash-label">Egresos del Mes</div>
              <div class="dash-valor text-danger">$<?= number_format($egresos, 2) ?></div>
            </div>
            <div class="dash-icono bg-danger bg-opacity-10 text-danger">
              <i class="bi bi-arrow-up-circle-fill"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100 dash-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="dash-label">Saldo del Mes</div>
              <div class="dash-valor <?= $saldo >= 0 ? 'text-success' : 'text-danger' ?>">
                $<?= number_format(abs($saldo), 2) ?>
                <?php if ($saldo < 0): ?>
                  <small class="fs-6">déficit</small>
                <?php endif; ?>
              </div>
            </div>
            <div class="dash-icono bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-wallet2"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-lg-3">
      <div class="card border-0 shadow-sm h-100 dash-card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="dash-label">Tasa BCV</div>
              <div class="dash-valor text-dark">Bs. <?= number_format($tasa, 2) ?></div>
            </div>
            <div class="dash-icono bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-currency-exchange"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- ALERTAS PENDIENTES -->
  <div class="row g-3 mb-4">
    <?php if ($ordenes > 0): ?>
      <div class="col-md-6">
        <a href="<?= $basePath ?>/financial/ordenes-pago" class="text-decoration-none">
          <div class="alert alert-warning d-flex align-items-center gap-3 mb-0">
            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            <div>
              <strong><?= $ordenes ?></strong> orden<?= $ordenes !== 1 ? 'es' : '' ?> de pago pendiente<?= $ordenes !== 1 ? 's' : '' ?> de aprobación
            </div>
            <i class="bi bi-chevron-right ms-auto"></i>
          </div>
        </a>
      </div>
    <?php endif; ?>
    <?php if ($tesoreria > 0): ?>
      <div class="col-md-6">
        <a href="<?= $basePath ?>/financial/tesoreria" class="text-decoration-none">
          <div class="alert alert-info d-flex align-items-center gap-3 mb-0">
            <i class="bi bi-cash-stack fs-4"></i>
            <div>
              <strong><?= $tesoreria ?></strong> pago<?= $tesoreria !== 1 ? 's' : '' ?> pendiente<?= $tesoreria !== 1 ? 's' : '' ?> en tesorería
            </div>
            <i class="bi bi-chevron-right ms-auto"></i>
          </div>
        </a>
      </div>
    <?php endif; ?>
  </div>

  <!-- GRÁFICA + ÚLTIMOS EGRESOS -->
  <div class="row g-3">

    <!-- GRÁFICA -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
          <h6 class="fw-bold mb-0">Ingresos vs Egresos — Últimos 6 meses</h6>
        </div>
        <div class="card-body">
          <canvas id="graficaMensual" height="100"></canvas>
        </div>
      </div>
    </div>

    <!-- ÚLTIMOS EGRESOS -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0">Últimos Egresos</h6>
          <a href="<?= $basePath ?>/financial/libro-egresos" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Ver todos</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($ultimosEgresos)): ?>
            <div class="text-center text-muted py-4 small">Sin movimientos.</div>
          <?php else: ?>
            <?php foreach ($ultimosEgresos as $e): ?>
              <div class="dash-egreso-item">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="small fw-semibold"><?= htmlspecialchars($e['concepto'] ?? $e['numero_orden'] ?? '—') ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($e['destinatario'] ?? '—') ?></div>
                  </div>
                  <div class="text-end">
                    <div class="small fw-bold <?= $e['tipo_movimiento'] === 'PAGO' ? 'text-danger' : 'text-success' ?>">
                      <?= $e['tipo_movimiento'] === 'PAGO' ? '-' : '+' ?>$<?= number_format(abs((float)$e['monto_usd']), 2) ?>
                    </div>
                    <div class="small text-muted"><?= date('d/m/Y', strtotime($e['fecha'])) ?></div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  window.GRAFICA_DATA = <?= $graficaJson ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= $basePath ?>/assets/js/financial_dashboard.js?v=<?= time() ?>"></script>