<?php
/**
 * MÓDULO: PORTAL DOCENTE / MI HORARIO
 * ARCHIVO: app/views/professor/horario/index.php
 * PROPÓSITO: Selector de oferta con grupo. Grilla semanal para horarios
 *            teóricos y calendario mensual para prácticos.
 * VERSIÓN: 1.1.0 - Grilla semanal + calendario mensual de prácticas.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

// ── Días en orden ──────────────────────────────────────────────────────────
$diasOrden = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];

// ── Construir estructura de grilla teórica ─────────────────────────────────
$franjas = [];   // ['08:00' => ['inicio'=>'08:00','fin'=>'12:00'], ...]
$diasUsados = [];

foreach ($teoricos as $t) {
    $key = $t['hora_inicio'];
    if (!isset($franjas[$key])) {
        $franjas[$key] = ['inicio' => $t['hora_inicio'], 'fin' => $t['hora_fin']];
    }
    if (!in_array($t['dia_semana'], $diasUsados, true)) {
        $diasUsados[] = $t['dia_semana'];
    }
}
ksort($franjas);
usort($diasUsados, fn($a,$b) => array_search($a,$diasOrden) <=> array_search($b,$diasOrden));

// Mapa [dia][franja] = clase
$gridTeorico = [];
foreach ($teoricos as $t) {
    $gridTeorico[$t['dia_semana']][$t['hora_inicio']] = $t;
}

// ── Construir calendario de prácticas ──────────────────────────────────────
$calendarios = [];  // [año-mes => [dia => [practicas]]]
$mesesUsados = [];
foreach ($practicos as $p) {
    $ts  = strtotime($p['fecha']);
    $ym  = date('Y-m', $ts);
    $dia = (int) date('j', $ts);
    if (!isset($calendarios[$ym])) {
        $calendarios[$ym] = [];
        $mesesUsados[$ym] = date('Y', $ts) . '-' . date('m', $ts);
    }
    $calendarios[$ym][$dia][] = $p;
}
ksort($calendarios);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/professor_horario.css">

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
        <a href="<?= $basePath ?>/professor" class="text-decoration-none text-muted">Portal Docente</a>
      </li>
      <li class="breadcrumb-item active fw-bold text-primary">Mi Horario</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle d-flex align-items-center justify-content-center"
           style="width:48px;height:48px;background:linear-gradient(135deg,#0dcaf0,#0d6efd);flex-shrink:0">
        <i class="bi bi-calendar3 text-white fs-5"></i>
      </div>
      <div>
        <h4 class="mb-0 fw-bold">Mi Horario</h4>
        <small class="text-muted">Horarios teóricos y prácticos asignados</small>
      </div>
    </div>
    <a href="<?= $basePath ?>/professor" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
      <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
  </div>

  <!-- SELECTOR DE OFERTA -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="" class="row g-3 align-items-end">
        <div class="col-md-9">
          <label class="form-label fw-semibold">Selecciona una oferta académica</label>
          <select name="offering_id" class="form-select" onchange="this.form.submit()">
            <option value="">— Elige un diplomado / cohorte —</option>
            <?php foreach ($ofertas as $o): ?>
              <option value="<?= $o['offering_id'] ?>"
                <?= (int)$offeringId === (int)$o['offering_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($o['diplomado_nombre'] . ' — ' . $o['cohorte_nombre'] .
                    (!empty($o['grupos_nombre']) ? ' (' . $o['grupos_nombre'] . ')' : '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search me-1"></i> Ver
          </button>
        </div>
      </form>
    </div>
  </div>

  <?php if ($offeringId && $ofertaActiva): ?>

    <!-- TÍTULO + BOTÓN PDF -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h6 class="fw-bold mb-0">
          <?= htmlspecialchars($ofertaActiva['diplomado_nombre'] . ' — ' . $ofertaActiva['cohorte_nombre']) ?>
          <?php if (!empty($ofertaActiva['grupos_nombre'])): ?>
            <span class="text-muted fw-normal small">(<?= htmlspecialchars($ofertaActiva['grupos_nombre']) ?>)</span>
          <?php endif; ?>
        </h6>
        <?php if (!empty($ofertaActiva['modalidades'])): ?>
          <small class="text-muted">Modalidades: <strong><?= htmlspecialchars($ofertaActiva['modalidades']) ?></strong></small>
        <?php endif; ?>
      </div>
      <a href="<?= $basePath ?>/professor/horario/pdf?offering_id=<?= $offeringId ?>"
         target="_blank"
         class="btn btn-outline-dark rounded-pill px-4 shadow-sm">
        <i class="bi bi-printer me-1"></i> Imprimir PDF
      </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- SECCIÓN 1: GRILLA SEMANAL TEÓRICA                              -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header border-0 py-3 horario-header-teorico">
        <h6 class="mb-0 fw-bold">
          <i class="bi bi-book me-2"></i>Horario Teórico
        </h6>
      </div>
      <div class="card-body p-0">
        <?php if (empty($teoricos)): ?>
          <div class="text-center text-muted py-4">
            <i class="bi bi-calendar-x fs-2 d-block mb-2 opacity-25"></i>
            No tienes horario teórico asignado en esta oferta.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0 horario-grid">
              <thead>
                <tr>
                  <th class="horario-th-hora">Horario</th>
                  <?php foreach ($diasUsados as $dia): ?>
                    <th class="text-center horario-th-dia"><?= htmlspecialchars($dia) ?></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($franjas as $franja): ?>
                  <tr>
                    <td class="horario-td-hora fw-semibold">
                      <?= $franja['inicio'] ?> – <?= $franja['fin'] ?>
                    </td>
                    <?php foreach ($diasUsados as $dia): ?>
                      <?php $celda = $gridTeorico[$dia][$franja['inicio']] ?? null; ?>
                      <td class="text-center <?= $celda ? 'horario-celda-activa' : 'horario-celda-vacia' ?>">
                        <?php if ($celda): ?>
                          <div class="horario-clase-badge">
                            <i class="bi bi-book-fill me-1"></i>Teórica
                            <?php if (!empty($celda['grupo_nombre'])): ?>
                              <br><small><?= htmlspecialchars($celda['grupo_nombre']) ?></small>
                            <?php endif; ?>
                          </div>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- SECCIÓN 2: CALENDARIOS MENSUALES DE PRÁCTICAS                  -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="card border-0 shadow-sm">
      <div class="card-header border-0 py-3 horario-header-practica">
        <h6 class="mb-0 fw-bold">
          <i class="bi bi-hospital me-2"></i>Horario Práctico
        </h6>
      </div>
      <div class="card-body">
        <?php if (empty($practicos)): ?>
          <div class="text-center text-muted py-4">
            <i class="bi bi-calendar-x fs-2 d-block mb-2 opacity-25"></i>
            No tienes horario práctico asignado en esta oferta.
          </div>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach ($calendarios as $ym => $diasPractica):
              [$anio, $mes] = explode('-', $ym);
              $nombreMes = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                            'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][(int)$mes];
              $primerDia  = date('N', strtotime("{$anio}-{$mes}-01")); // 1=Lun, 7=Dom
              $diasEnMes  = (int) date('t', strtotime("{$anio}-{$mes}-01"));
            ?>
              <div class="col-md-6 col-lg-4">
                <div class="calendario-mes">
                  <div class="calendario-titulo"><?= $nombreMes ?> <?= $anio ?></div>
                  <table class="calendario-tabla">
                    <thead>
                      <tr>
                        <th>Lu</th><th>Ma</th><th>Mi</th>
                        <th>Ju</th><th>Vi</th><th>Sá</th><th>Do</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $celdasTotal = $primerDia - 1 + $diasEnMes;
                      $filas = ceil($celdasTotal / 7);
                      $diaActual = 1 - ($primerDia - 1);
                      for ($fila = 0; $fila < $filas; $fila++):
                      ?>
                        <tr>
                          <?php for ($col = 0; $col < 7; $col++):
                            $tienePractica = isset($diasPractica[$diaActual]) && $diaActual >= 1 && $diaActual <= $diasEnMes;
                          ?>
                            <td class="<?= $diaActual < 1 || $diaActual > $diasEnMes ? 'calendario-vacio' : ($tienePractica ? 'calendario-practica' : '') ?>">
                              <?php if ($diaActual >= 1 && $diaActual <= $diasEnMes): ?>
                                <span class="calendario-numero"><?= $diaActual ?></span>
                                <?php if ($tienePractica): ?>
                                  <?php foreach ($diasPractica[$diaActual] as $pr): ?>
                                    <div class="calendario-info">
                                      <i class="bi bi-building"></i>
                                      <?= htmlspecialchars($pr['centro_medico']) ?>
                                    </div>
                                  <?php endforeach; ?>
                                <?php endif; ?>
                              <?php endif; ?>
                            </td>
                          <?php $diaActual++; endfor; ?>
                        </tr>
                      <?php endfor; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  <?php elseif ($offeringId && !$ofertaActiva): ?>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle me-2"></i>
      No tienes acceso a esa oferta o no existe.
    </div>
  <?php endif; ?>

</div>