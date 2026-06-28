<?php
/**
 * MÓDULO: PORTAL DOCENTE / CARGAR NOTAS
 * ARCHIVO: app/views/professor/notas/index.php
 * PROPÓSITO: Selector de oferta y modalidad. Tabla de estudiantes con campo
 *            de nota editable. Botones Guardar y Generar Acta.
 *            El profesor solo edita su modalidad asignada.
 * VERSIÓN: 1.1.0 - JS y CSS extraídos a archivos externos.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$labelModalidad = ['TEORICA' => 'Teórica', 'PRACTICA' => 'Práctica', 'VIRTUAL' => 'Virtual'];
$colorModalidad = ['TEORICA' => '#0d6efd', 'PRACTICA' => '#198754', 'VIRTUAL' => '#0dcaf0'];
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/professor_notas.css">

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
      <li class="breadcrumb-item active fw-bold text-primary">Cargar Notas</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle d-flex align-items-center justify-content-center"
           style="width:48px;height:48px;background:linear-gradient(135deg,#0d6efd,#6610f2);flex-shrink:0">
        <i class="bi bi-file-earmark-text text-white fs-5"></i>
      </div>
      <div>
        <h4 class="mb-0 fw-bold">Cargar Notas</h4>
        <small class="text-muted">Registra las notas de tus estudiantes por modalidad</small>
      </div>
    </div>
    <a href="<?= $basePath ?>/professor" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
      <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
  </div>

  <!-- SELECTOR DE OFERTA -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="" class="row g-3 align-items-end" id="formOferta">

        <!-- PERÍODO -->
        <div class="col-md-3">
          <label class="form-label fw-semibold">Período</label>
          <select name="periodo_id" class="form-select" onchange="this.form.submit()">
            <option value="">— Todos —</option>
            <?php foreach ($periodos as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($periodoId ?? 0) == $p['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['nombre']) ?><?= $p['estado'] === 'Finalizado' ? ' (Finalizado)' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- OFERTA -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Oferta Académica</label>
          <select name="offering_id" class="form-select" onchange="this.form.submit()">
            <option value="">— Todas las ofertas —</option>
            <?php foreach ($ofertas as $o): ?>
              <option value="<?= $o['offering_id'] ?>"
                <?= (int)$offeringId === (int)$o['offering_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($o['diplomado_nombre']) ?>
                <?= !empty($o['grupos_nombre']) ? '(' . htmlspecialchars($o['grupos_nombre']) . ')' : '' ?>
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

    <?php if (count($modalidades) > 1 && $modalidad === ''): ?>
      <!-- SELECTOR DE MODALIDAD (solo si tiene más de una y no viene en URL) -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
          <label class="form-label fw-semibold mb-3">Selecciona una modalidad</label>
          <div class="d-flex gap-3 flex-wrap">
            <?php foreach ($modalidades as $m): ?>
              <a href="?offering_id=<?= $offeringId ?>&modalidad=<?= $m ?>"
                 class="btn btn-outline-primary rounded-pill px-4">
                <i class="bi bi-book me-1"></i>
                <?= $labelModalidad[$m] ?? $m ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    <?php elseif ($modalidad): ?>

      <!-- ESTADO DEL ACTA -->
      <?php if ($acta): ?>
        <div class="alert <?= $acta['estado'] === 'APROBADA' ? 'alert-success' : ($acta['estado'] === 'ENVIADA' ? 'alert-warning' : 'alert-info') ?> d-flex align-items-center gap-2 mb-4">
          <i class="bi <?= $acta['estado'] === 'APROBADA' ? 'bi-check-circle-fill' : 'bi-clock-fill' ?>"></i>
        <?php if ($acta['estado'] === 'APROBADA'): ?>
            <span class="flex-grow-1">Acta <strong>aprobada</strong> por el administrador. Las notas son definitivas.</span>
        <?php elseif ($acta['estado'] === 'ENVIADA'): ?>
            <span class="flex-grow-1">Acta <strong>enviada</strong> al administrador, pendiente de aprobación. Puedes corregir notas si es necesario.</span>
        <?php else: ?>
            <span class="flex-grow-1">Acta en <strong>borrador</strong>. El administrador la devolvió para corrección.</span>
        <?php endif; ?>
        <a href="<?= $basePath ?>/professor/notas/pdf?offering_id=<?= $offeringId ?>&modalidad=<?= $modalidad ?>"
        target="_blank"
        class="btn btn-sm btn-outline-dark rounded-pill px-3 ms-2">
            <i class="bi bi-printer me-1"></i> Imprimir Acta
        </a>

        </div>
      <?php endif; ?>

      <!-- TABS DE MODALIDAD (cuando tiene más de una, para cambiar) -->
      <?php if (count($modalidades) > 1): ?>
        <div class="d-flex gap-2 mb-3 flex-wrap">
          <?php foreach ($modalidades as $m): ?>
            <a href="?offering_id=<?= $offeringId ?>&modalidad=<?= $m ?>"
               class="btn btn-sm <?= $m === $modalidad ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">
              <?= $labelModalidad[$m] ?? $m ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- TABLA DE NOTAS -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
          <div>
            <h6 class="mb-0 fw-bold">
              <?= htmlspecialchars($ofertaActiva['diplomado_nombre'] . ' — ' . $ofertaActiva['cohorte_nombre']) ?>
              <?php if (!empty($ofertaActiva['grupos_nombre'])): ?>
                <span class="text-muted fw-normal small">(<?= htmlspecialchars($ofertaActiva['grupos_nombre']) ?>)</span>
              <?php endif; ?>
            </h6>
            <small class="text-muted">
              Modalidad:
              <span class="badge rounded-pill"
                    style="background:<?= $colorModalidad[$modalidad] ?>20;color:<?= $colorModalidad[$modalidad] ?>">
                <?= $labelModalidad[$modalidad] ?? $modalidad ?>
              </span>
              &nbsp;·&nbsp; Nota mínima: <strong><?= number_format($notaMinima, 2) ?></strong>
            </small>
          </div>
          <span class="badge bg-secondary"><?= count($estudiantes) ?> estudiantes</span>
        </div>

        <div class="card-body p-0">
          <?php if (empty($estudiantes)): ?>
            <div class="text-center text-muted py-5">
              <i class="bi bi-people fs-2 d-block mb-2"></i>
              No hay estudiantes matriculados en esta oferta.
            </div>
          <?php else: ?>
            <div class="table-responsive" style="max-height:520px;overflow-y:auto">
              <table class="table table-hover align-middle mb-0" id="tablaNotas">
                <thead class="table-light">
                  <tr>
                    <th class="ps-3" style="width:50px">#</th>
                    <th>Apellidos y Nombres</th>
                    <th style="width:130px">Cédula</th>
                    <th class="text-center" style="width:140px">
                      Nota <?= $labelModalidad[$modalidad] ?? $modalidad ?>
                    </th>
                    <th class="text-center" style="width:110px">Estado</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($estudiantes as $idx => $e): ?>
                    <tr id="fila-<?= $e['enrollment_id'] ?>">
                      <td class="ps-3 text-muted small"><?= $idx + 1 ?></td>
                      <td class="fw-semibold">
                        <?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) ?>
                      </td>
                      <td class="small text-muted"><?= htmlspecialchars($e['document_id']) ?></td>
                      <td class="text-center">
                        <?php if ($acta && $acta['estado'] === 'APROBADA'): ?>
                          <span class="fw-bold">
                            <?= $e['nota'] !== null ? number_format((float)$e['nota'], 2) : '—' ?>
                          </span>
                        <?php else: ?>
                          <input type="number"
                                 class="form-control form-control-sm text-center input-nota"
                                 data-eid="<?= $e['enrollment_id'] ?>"
                                 value="<?= $e['nota'] !== null ? number_format((float)$e['nota'], 2) : '' ?>"
                                 min="0" max="20" step="0.01"
                                 placeholder="0-20">
                        <?php endif; ?>
                      </td>
                      <td class="text-center" id="estado-<?= $e['enrollment_id'] ?>">
                        <?php if ($e['nota'] !== null): ?>
                          <?php if ((float)$e['nota'] >= $notaMinima): ?>
                            <span class="badge bg-success">Aprobado</span>
                          <?php else: ?>
                            <span class="badge bg-danger">Reprobado</span>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="badge bg-light text-muted border">Sin nota</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <?php if (!$acta || $acta['estado'] !== 'APROBADA'): ?>
              <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">
                  <span id="contNotas">
                    <?= count(array_filter($estudiantes, fn($e) => $e['nota'] !== null)) ?>
                  </span> / <?= count($estudiantes) ?> notas cargadas
                </small>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-primary rounded-pill px-4" id="btnGuardar">
                    <i class="bi bi-save me-1"></i> Guardar Notas
                  </button>
                  <button type="button"
                          class="btn btn-success rounded-pill px-4 <?= $todasCargadas ? '' : 'd-none' ?>"
                          id="btnGenerarActa">
                    <i class="bi bi-send me-1"></i> Generar Acta
                  </button>
                </div>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

    <?php endif; ?>

  <?php elseif ($offeringId && !$ofertaActiva): ?>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle me-2"></i>
      No tienes acceso a esa oferta o no existe.
    </div>
  <?php endif; ?>

</div>

<script>
  window.BASE_PATH   = '<?= $basePath ?>';
  window.OFFERING_ID = <?= (int)$offeringId ?>;
  window.MODALIDAD   = '<?= htmlspecialchars($modalidad) ?>';
  window.NOTA_MINIMA = <?= (float)$notaMinima ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/professor_notas.js?v=<?= time() ?>"></script>