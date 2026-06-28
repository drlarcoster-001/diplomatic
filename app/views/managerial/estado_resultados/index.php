<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / ESTADO DE RESULTADOS
 * ARCHIVO: app/views/managerial/estado_resultados/index.php
 * PROPÓSITO: Reporte contable de ingresos vs egresos con filtro por fechas.
 *            Exportable a PDF.
 * VERSIÓN: 1.1.0 - IDs agregados para JS externo.
 */
$basePath   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$colorSaldo = $saldo >= 0 ? 'text-success' : 'text-danger';
$signoSaldo = $saldo >= 0 ? '+' : '-';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/managerial_estado_resultados.css">

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
      <li class="breadcrumb-item active fw-bold text-primary">Estado de Resultados</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-0">Estado de Resultados</h4>
      <small class="text-muted">
        Período: <strong><?= date('d/m/Y', strtotime($desde)) ?></strong>
        al <strong><?= date('d/m/Y', strtotime($hasta)) ?></strong>
      </small>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= $basePath ?>/managerial/estado-resultados/pdf?desde=<?= $desde ?>&hasta=<?= $hasta ?>"
         target="_blank"
         class="btn btn-outline-dark rounded-pill px-4 shadow-sm">
        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
      </a>
      <a href="<?= $basePath ?>/managerial" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
      </a>
    </div>
  </div>

  <!-- FILTRO DE FECHAS -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" id="formFiltro" class="row g-2 align-items-end">
        <div class="col-md-12 mb-2">
          <label class="form-label small fw-bold mb-1">Período</label>
          <select name="periodo_id" id="selPeriodo" class="form-select">
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
        <div class="col-md-4">
          <label class="form-label small fw-bold mb-1">Desde</label>
          <input type="date" name="desde" id="inputDesde" class="form-control"
                 value="<?= htmlspecialchars($desde) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-bold mb-1">Hasta</label>
          <input type="date" name="hasta" id="inputHasta" class="form-control"
                 value="<?= htmlspecialchars($hasta) ?>">
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-dark w-100 rounded-pill">
            <i class="bi bi-funnel me-1"></i> Aplicar
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- ESTADO DE RESULTADOS -->
  <div class="row g-4 justify-content-center">
    <div class="col-lg-7">

      <!-- INGRESOS -->
      <div class="er-seccion mb-3">
        <div class="er-seccion-titulo er-ingreso">
          <i class="bi bi-arrow-down-circle-fill me-2"></i>INGRESOS
        </div>
        <div class="er-tabla">
          <div class="er-fila">
            <span>Pagos de Estudiantes</span>
            <span class="text-success fw-semibold">+$<?= number_format($totalIngreso, 2) ?></span>
          </div>
          <div class="er-fila er-fila-small text-muted">
            <span><i class="bi bi-receipt me-1"></i><?= $ingresos['cantidad'] ?? 0 ?> transacciones</span>
            <span></span>
          </div>
          <div class="er-fila er-total">
            <span>TOTAL INGRESOS</span>
            <span class="text-success">+$<?= number_format($totalIngreso, 2) ?></span>
          </div>
        </div>
      </div>

      <!-- EGRESOS -->
      <div class="er-seccion mb-3">
        <div class="er-seccion-titulo er-egreso">
          <i class="bi bi-arrow-up-circle-fill me-2"></i>EGRESOS
        </div>
        <div class="er-tabla">
          <div class="er-fila">
            <span>Nómina</span>
            <span class="text-danger fw-semibold">-$<?= number_format($totalNomina, 2) ?></span>
          </div>
          <div class="er-fila">
            <span>Proveedores</span>
            <span class="text-danger fw-semibold">-$<?= number_format($totalProveedor, 2) ?></span>
          </div>
          <div class="er-fila">
            <span>Directa</span>
            <span class="text-danger fw-semibold">-$<?= number_format($totalDirecta, 2) ?></span>
          </div>
          <?php if ((float)($egresos['reversas'] ?? 0) > 0): ?>
          <div class="er-fila">
            <span class="text-success"><i class="bi bi-arrow-counterclockwise me-1"></i>Reversas</span>
            <span class="text-success fw-semibold">+$<?= number_format((float)$egresos['reversas'], 2) ?></span>
          </div>
          <?php endif; ?>
          <div class="er-fila er-total">
            <span>TOTAL EGRESOS</span>
            <span class="text-danger">-$<?= number_format($totalEgreso, 2) ?></span>
          </div>
        </div>
      </div>

      <!-- SALDO -->
      <div class="er-saldo <?= $saldo >= 0 ? 'er-saldo-positivo' : 'er-saldo-negativo' ?>">
        <span>SALDO DEL PERÍODO</span>
        <span class="er-saldo-valor">
          <?= $signoSaldo ?>$<?= number_format(abs($saldo), 2) ?>
        </span>
      </div>

    </div>
  </div>

</div>

<script src="<?= $basePath ?>/assets/js/managerial_estado_resultados.js?v=<?= time() ?>"></script>