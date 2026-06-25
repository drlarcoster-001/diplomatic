<?php
/**
 * MÓDULO: PORTAL DOCENTE / REGISTRAR ASISTENCIA
 * ARCHIVO: app/views/professor/registrar_asistencia/index.php
 * PROPÓSITO: Selector de oferta + lista de sesiones PROGRAMADAS con indicador
 *            de si ya tienen asistencia cargada. Botón Registrar por sesión.
 * VERSIÓN: 1.0.0 - Creación inicial.
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
      <li class="breadcrumb-item active fw-bold text-primary">Registrar Asistencia</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle d-flex align-items-center justify-content-center"
           style="width:48px;height:48px;background:linear-gradient(135deg,#198754,#28a745);flex-shrink:0">
        <i class="bi bi-pencil-square text-white fs-5"></i>
      </div>
      <div>
        <h4 class="mb-0 fw-bold">Registrar Asistencia</h4>
        <small class="text-muted">Selecciona una sesión y marca quién asistió</small>
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
                <?= htmlspecialchars($o['diplomado_nombre'] . ' — ' . $o['cohorte_nombre'] . (!empty($o['grupos_nombre']) ? ' (' . $o['grupos_nombre'] . ')' : '')) ?>
                (<?= $o['total_sesiones'] ?> sesiones)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search me-1"></i> Ver sesiones
          </button>
        </div>
      </form>
    </div>
  </div>

  <?php if ($offeringId && $ofertaActiva): ?>

    <!-- TABLA DE SESIONES -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">
          <?= htmlspecialchars($ofertaActiva['diplomado_nombre'] . ' — ' . $ofertaActiva['cohorte_nombre']) ?>
        </h6>
        <span class="badge bg-secondary"><?= count($sesiones) ?> sesiones pendientes</span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($sesiones)): ?>
          <div class="text-center text-muted py-5">
            <i class="bi bi-calendar-check fs-2 d-block mb-2"></i>
            No tienes sesiones pendientes en esta oferta.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Fecha</th>
                  <th>Tipo</th>
                  <th>Horario / Grupo</th>
                  <th class="text-center">Asistencia</th>
                  <th class="text-center">Acción</th>
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
                      <?php if ((int)$s['tiene_asistencia'] > 0): ?>
                        <span class="badge bg-success">
                          <i class="bi bi-check-circle me-1"></i>Cargada
                        </span>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark">
                          <i class="bi bi-clock me-1"></i>Pendiente
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <a href="<?= $basePath ?>/professor/registrar-asistencia/sesion?sesion_id=<?= $s['id'] ?>"
                         class="btn btn-sm btn-outline-success rounded-pill px-3">
                        <i class="bi bi-pencil-square me-1"></i>
                        <?= (int)$s['tiene_asistencia'] > 0 ? 'Editar' : 'Registrar' ?>
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