<?php
/**
 * MÓDULO: PORTAL DOCENTE / REGISTRAR ASISTENCIA
 * ARCHIVO: app/views/professor/registrar_asistencia/sesion.php
 * PROPÓSITO: Formulario para marcar asistencia. Al guardar genera PDF imprimible.
 *            Muestra nombre del profesor, grupo y tipo de sesión en el encabezado.
 * VERSIÓN: 1.2.0 - Agrega profesor y grupo en encabezado. PDF al guardar.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$fecha    = isset($sesion['fecha']) ? date('d/m/Y', strtotime($sesion['fecha'])) : '—';
$tipo     = $sesion['tipo_horario'] === 'TEORICO' ? 'Teórica' : 'Práctica';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/professor_registrar_asistencia.css">

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
      <li class="breadcrumb-item">
        <a href="<?= $basePath ?>/professor/registrar-asistencia?offering_id=<?= $sesion['offering_id'] ?>"
           class="text-decoration-none text-muted">Registrar Asistencia</a>
      </li>
      <li class="breadcrumb-item active fw-bold text-primary">Sesión <?= $fecha ?></li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle d-flex align-items-center justify-content-center"
           style="width:48px;height:48px;background:linear-gradient(135deg,#198754,#28a745);flex-shrink:0">
        <i class="bi bi-people text-white fs-5"></i>
      </div>
      <div>
        <h4 class="mb-0 fw-bold">Registro de Asistencia — <?= $fecha ?></h4>
        <small class="text-muted">
          <?= htmlspecialchars($sesion['diplomado_nombre'] ?? '') ?>
          &nbsp;·&nbsp; Prof. <?= htmlspecialchars($sesion['profesor_nombre'] ?? '') ?>
          &nbsp;·&nbsp; <?= $tipo ?>
          &nbsp;·&nbsp; <?= htmlspecialchars($sesion['grupos_nombre'] ?? '') ?>
          &nbsp;·&nbsp; <?= htmlspecialchars($sesion['horario_desc'] ?? '') ?>
        </small>
      </div>
    </div>
    <a href="<?= $basePath ?>/professor/registrar-asistencia?offering_id=<?= $sesion['offering_id'] ?>"
       class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
      <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
  </div>

  <!-- FORMULARIO DE ASISTENCIA -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
      <h6 class="mb-0 fw-bold">
        <i class="bi bi-people-fill me-2 text-success"></i>
        <?= count($estudiantes) ?> estudiantes
      </h6>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" id="btnTodos">
          <i class="bi bi-check-all me-1"></i>Todos presentes
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" id="btnNinguno">
          <i class="bi bi-x-circle me-1"></i>Todos ausentes
        </button>
      </div>
    </div>
    <div class="card-body p-0">
      <?php if (empty($estudiantes)): ?>
        <div class="text-center text-muted py-5">
          <i class="bi bi-people fs-2 d-block mb-2"></i>
          No hay estudiantes matriculados en esta oferta.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="tablaAsistencia">
            <thead class="table-light">
              <tr>
                <th class="ps-3" style="width:50px">#</th>
                <th>Apellidos y Nombres</th>
                <th style="width:130px">Cédula</th>
                <th class="text-center" style="width:160px">Asistencia</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($estudiantes as $idx => $e): ?>
                <tr>
                  <td class="ps-3 text-muted small"><?= $idx + 1 ?></td>
                  <td class="fw-semibold">
                    <?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) ?>
                  </td>
                  <td class="small text-muted"><?= htmlspecialchars($e['document_id']) ?></td>
                  <td class="text-center">
                    <div class="btn-group" role="group">
                      <input type="radio" class="btn-check radio-asistencia"
                             name="asistencia[<?= $e['enrollment_id'] ?>]"
                             id="p_<?= $e['enrollment_id'] ?>"
                             value="1"
                             <?= (int)$e['asistio'] === 1 ? 'checked' : '' ?>>
                      <label class="btn btn-sm btn-outline-success" for="p_<?= $e['enrollment_id'] ?>">
                        <i class="bi bi-check-lg"></i> P
                      </label>

                      <input type="radio" class="btn-check radio-asistencia"
                             name="asistencia[<?= $e['enrollment_id'] ?>]"
                             id="a_<?= $e['enrollment_id'] ?>"
                             value="0"
                             <?= (int)$e['asistio'] === 0 ? 'checked' : '' ?>>
                      <label class="btn btn-sm btn-outline-danger" for="a_<?= $e['enrollment_id'] ?>">
                        <i class="bi bi-x-lg"></i> A
                      </label>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- CONTADOR Y BOTÓN GUARDAR -->
        <div class="p-3 border-top d-flex justify-content-between align-items-center">
          <small class="text-muted">
            <span id="contPresentes">0</span> presentes ·
            <span id="contAusentes">0</span> ausentes
          </small>
          <button type="button" class="btn btn-success rounded-pill px-5" id="btnGuardar">
            <i class="bi bi-save me-2"></i>Guardar Asistencia
          </button>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<script>
  window.BASE_PATH   = '<?= $basePath ?>';
  window.SESION_ID   = <?= (int)$sesion['id'] ?>;
  window.OFFERING_ID = <?= (int)$sesion['offering_id'] ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/professor_registrar_asistencia.js?v=<?= time() ?>"></script>