<?php
/**
 * MÓDULO: GESTIÓN GERENCIAL / LÍNEA DE TIEMPO
 * ARCHIVO: app/views/managerial/linea_tiempo/index.php
 * PROPÓSITO: Filtros en cascada Período → Oferta → Estudiante.
 *            Muestra la línea de tiempo completa del ciclo del estudiante
 *            junto con sus datos de contacto.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$labelStatus = ['APPROVED' => 'Aprobado', 'PENDING' => 'Pendiente', 'REJECTED' => 'Rechazado'];
$colorStatus = ['APPROVED' => 'success',  'PENDING' => 'warning',   'REJECTED' => 'danger'];
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/managerial_linea_tiempo.css">

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
      <li class="breadcrumb-item active fw-bold text-primary">Línea de Tiempo</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-0">Línea de Tiempo del Estudiante</h4>
      <small class="text-muted">Ciclo completo de vida de un estudiante dentro del diplomado.</small>
    </div>
    <a href="<?= $basePath ?>/managerial" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
      <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
  </div>

  <!-- FILTROS -->
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

        <!-- OFERTA -->
        <div class="col-md-4">
          <label class="form-label small fw-bold mb-1">Diplomado / Grupo</label>
          <select id="selOferta" class="form-select" disabled>
            <option value="">— Primero elige período —</option>
          </select>
        </div>

        <!-- ESTUDIANTE -->
        <div class="col-md-4 position-relative">
          <label class="form-label small fw-bold mb-1">Estudiante</label>
          <div class="input-group">
            <input type="text" id="inputEstudiante" class="form-control"
                   placeholder="Buscar por nombre, cédula o correo..."
                   value="<?= htmlspecialchars($userSearch ?? '') ?>"
                   disabled autocomplete="off">
            <button type="button" id="btnLimpiarEst"
                    class="btn btn-outline-secondary d-none" title="Limpiar">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <input type="hidden" id="hidEnrollment" value="<?= (int)$enrollmentId ?>">
          <div id="estudianteResultados" class="list-group mt-1 position-absolute shadow-sm"
               style="max-height:220px;overflow-y:auto;z-index:200;left:0;right:0"></div>
        </div>

      </div>
      <div class="mt-3 d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="btnLimpiar">
          <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar
        </button>
        <button type="button" class="btn btn-dark rounded-pill px-5" id="btnBuscar" disabled>
          <i class="bi bi-search me-1"></i> Ver Línea de Tiempo
        </button>
      </div>
    </div>
  </div>

  <?php if ($estudiante && !empty($eventos)): ?>

  <!-- DATOS DEL ESTUDIANTE + LÍNEA DE TIEMPO -->
  <div class="row g-4">

    <!-- DATOS DEL ESTUDIANTE -->
    <div class="col-lg-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-center mb-3">
            <div class="lt-avatar mx-auto mb-3">
              <i class="bi bi-person-fill"></i>
            </div>
            <h5 class="fw-bold mb-0"><?= htmlspecialchars($estudiante['last_name'] . ', ' . $estudiante['first_name']) ?></h5>
            <small class="text-muted">V-<?= htmlspecialchars($estudiante['document_id']) ?></small>
          </div>
          <hr>
          <div class="lt-dato">
            <i class="bi bi-mortarboard-fill text-primary me-2"></i>
            <div>
              <div class="small text-muted">Diplomado</div>
              <div class="small fw-semibold"><?= htmlspecialchars($estudiante['diplomado_nombre']) ?></div>
              <?php if (!empty($estudiante['grupos_nombre'])): ?>
                <div class="small text-muted"><?= htmlspecialchars($estudiante['grupos_nombre']) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($estudiante['student_code']): ?>
          <div class="lt-dato">
            <i class="bi bi-person-badge-fill text-purple me-2"></i>
            <div>
              <div class="small text-muted">Matrícula</div>
              <div class="small fw-bold text-primary"><?= htmlspecialchars($estudiante['student_code']) ?></div>
            </div>
          </div>
          <?php endif; ?>
          <div class="lt-dato">
            <i class="bi bi-envelope-fill text-danger me-2"></i>
            <div>
              <div class="small text-muted">Correo</div>
              <div class="small"><?= htmlspecialchars($estudiante['email']) ?></div>
            </div>
          </div>
          <?php if (!empty($estudiante['phone'])): ?>
          <div class="lt-dato">
            <i class="bi bi-telephone-fill text-success me-2"></i>
            <div>
              <div class="small text-muted">Teléfono</div>
              <div class="small"><?= htmlspecialchars($estudiante['phone']) ?></div>
            </div>
          </div>
          <?php $phone = preg_replace('/[^0-9]/', '', $estudiante['phone'] ?? ''); ?>
          <?php if ($phone): ?>
          <a href="https://wa.me/<?= $phone ?>" target="_blank"
             class="btn btn-success btn-sm w-100 rounded-pill mt-2">
            <i class="bi bi-whatsapp me-1"></i> WhatsApp
          </a>
          <?php endif; ?>
          <?php endif; ?>
          <div class="lt-dato mt-2">
            <i class="bi bi-circle-fill me-2 <?= $estudiante['academic_status'] === 'CURSANDO' ? 'text-success' : 'text-secondary' ?>"></i>
            <div>
              <div class="small text-muted">Estado Académico</div>
              <div class="small fw-semibold"><?= htmlspecialchars($estudiante['academic_status'] ?? 'Sin matrícula') ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- LÍNEA DE TIEMPO -->
    <div class="col-lg-9">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <div class="lt-timeline">
            <?php foreach ($eventos as $idx => $ev): ?>
              <div class="lt-evento">
                <div class="lt-icono" style="background:<?= $ev['color'] ?>22;color:<?= $ev['color'] ?>;border:2px solid <?= $ev['color'] ?>">
                  <i class="bi <?= $ev['icono'] ?>"></i>
                </div>
                <div class="lt-linea" style="border-color:<?= $ev['color'] ?>44"></div>
                <div class="lt-contenido">
                  <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                    <span class="fw-bold" style="color:<?= $ev['color'] ?>"><?= $ev['titulo'] ?></span>
                    <span class="lt-fecha text-muted small">
                      <?= !empty($ev['fecha']) ? date('d/m/Y H:i', strtotime($ev['fecha'])) : '—' ?>
                    </span>
                  </div>
                  <div class="small text-dark mt-1"><?= htmlspecialchars($ev['detalle'] ?? '') ?></div>
                  <?php if (!empty($ev['sub'])): ?>
                    <div class="small text-muted mt-1">
                      <i class="bi bi-arrow-return-right me-1"></i><?= htmlspecialchars($ev['sub']) ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($ev['label_est'])): ?>
                    <span class="badge rounded-pill mt-1"
                          style="background:<?= $ev['color_est'] ?? '#6c757d' ?>22;color:<?= $ev['color_est'] ?? '#6c757d' ?>;border:1px solid <?= $ev['color_est'] ?? '#6c757d' ?>44">
                      <?= $ev['label_est'] ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

  </div>

  <?php elseif ($enrollmentId && !$estudiante): ?>
    <div class="alert alert-warning">No se encontró información para este estudiante.</div>
  <?php elseif (!$enrollmentId): ?>
    <div class="text-center text-muted py-5">
      <i class="bi bi-person-lines-fill fs-2 d-block mb-2 opacity-25"></i>
      Selecciona un período, diplomado y estudiante para ver su línea de tiempo.
    </div>
  <?php endif; ?>

</div>

<script>
  window.APP_BASE_PATH  = '<?= $basePath ?>';
  window.PERIODO_ID     = <?= (int)$periodoId ?>;
  window.OFFERING_ID    = <?= (int)$offeringId ?>;
  window.ENROLLMENT_ID  = <?= (int)$enrollmentId ?>;
  window.USER_SEARCH    = '<?= htmlspecialchars($userSearch ?? '') ?>';
</script>
<script src="<?= $basePath ?>/assets/js/managerial_linea_tiempo.js?v=<?= time() ?>"></script>