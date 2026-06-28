<?php
/**
 * MÓDULO: PORTAL DOCENTE / CONTROL DE ASISTENCIA
 * ARCHIVO: app/views/professor/control_asistencia/index.php
 * PROPÓSITO: Selector de oferta académica + tabla de sesiones del profesor con
 *            botón de descarga de lista de asistencia en blanco (PDF) para el aula.
 *            El layout es inyectado automáticamente por View.php — sin includes propios.
 * VERSIÓN: 1.1.0 - Agrega miga de pan y botón Volver.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

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
      <li class="breadcrumb-item active fw-bold text-primary">Control de Asistencia</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle d-flex align-items-center justify-content-center"
           style="width:48px;height:48px;background:linear-gradient(135deg,#533AB7,#7B5EE8);flex-shrink:0">
        <i class="bi bi-calendar-check text-white fs-5"></i>
      </div>
      <div>
        <h4 class="mb-0 fw-bold">Control de Asistencia</h4>
        <small class="text-muted">Consulta tus sesiones y descarga la lista para el aula</small>
      </div>
    </div>
    <a href="<?= $basePath ?>/professor" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
      <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
  </div>

  <!-- FILTROS -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="" class="row g-3 align-items-end">

        <!-- PERÍODO -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">Período</label>
          <select name="periodo_id" class="form-select" onchange="this.form.submit()">
            <option value="">— Todos los períodos —</option>
            <?php foreach ($periodos as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $periodoId == $p['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['nombre']) ?><?= $p['estado'] === 'Finalizado' ? ' (Finalizado)' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- OFERTA -->
        <div class="col-md-5">
          <label class="form-label fw-semibold">Oferta Académica</label>
          <select name="offering_id" class="form-select" onchange="this.form.submit()">
            <option value="">— Todas las ofertas —</option>
            <?php foreach ($ofertas as $o): ?>
              <option value="<?= $o['offering_id'] ?>" <?= (int)$offeringId === (int)$o['offering_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($o['diplomado_nombre']) ?>
                <?= !empty($o['grupos_nombre']) ? '(' . htmlspecialchars($o['grupos_nombre']) . ')' : '' ?>
                — <?= $o['total_sesiones'] ?> sesiones
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search me-1"></i> Buscar
          </button>
        </div>

      </form>
    </div>
  </div>

  <?php if ($offeringId && $ofertaActiva): ?>

    <!-- RESUMEN -->
    <div class="row g-3 mb-4">
      <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
          <div class="fs-2 fw-bold text-primary"><?= (int)$ofertaActiva['total_sesiones'] ?></div>
          <div class="text-muted small">Total sesiones</div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
          <div class="fs-2 fw-bold text-warning"><?= (int)$ofertaActiva['programadas'] ?></div>
          <div class="text-muted small">Programadas</div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
          <div class="fs-2 fw-bold text-success"><?= (int)$ofertaActiva['dictadas'] ?></div>
          <div class="text-muted small">Dictadas</div>
        </div>
      </div>
    </div>

    <!-- TABLA DE SESIONES -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">
          <?= htmlspecialchars($ofertaActiva['diplomado_nombre'] . ' — ' . $ofertaActiva['cohorte_nombre']) ?>
        </h6>
        <span class="badge bg-secondary"><?= count($sesiones) ?> sesiones</span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($sesiones)): ?>
          <div class="text-center text-muted py-5">
            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
            No tienes sesiones programadas en esta oferta.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Fecha</th>
                  <th>Tipo</th>
                  <th>Horario / Grupo</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Lista PDF</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($sesiones as $s): ?>
                  <tr>
                    <td class="fw-semibold"><?= date('d/m/Y', strtotime($s['fecha'])) ?></td>
                    <td>
                      <?php if ($s['tipo_horario'] === 'TEORICO'): ?>
                        <span class="badge" style="background:#0d6efd20;color:#0d6efd">
                          <i class="bi bi-book me-1"></i>Teórica
                        </span>
                      <?php else: ?>
                        <span class="badge" style="background:#19875420;color:#198754">
                          <i class="bi bi-hospital me-1"></i>Práctica
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($s['horario_desc'] ?? '—') ?></td>
                    <td class="text-center">
                      <?php if ($s['estado'] === 'PROGRAMADA'): ?>
                        <span class="badge bg-warning text-dark">Programada</span>
                      <?php elseif ($s['estado'] === 'DICTADA'): ?>
                        <span class="badge bg-success">Dictada</span>
                      <?php else: ?>
                        <span class="badge bg-secondary"><?= htmlspecialchars($s['estado']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <a href="<?= $basePath ?>/professor/control-asistencia/pdf?sesion_id=<?= $s['id'] ?>"
                         target="_blank"
                         class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Lista
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
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