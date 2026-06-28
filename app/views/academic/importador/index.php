<?php
/**
 * MÓDULO: ACADÉMICO / IMPORTADOR
 * ARCHIVO: app/views/academic/importador/index.php
 * PROPÓSITO: Formulario para crear nuevo período y clonar configuración
 *            de un período origen. Muestra log del proceso al terminar.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_importador.css">

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
        <a href="<?= $basePath ?>/academic" class="text-decoration-none text-muted">Académico</a>
      </li>
      <li class="breadcrumb-item active fw-bold text-primary">Importador de Período</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-0">Importador de Período</h4>
      <small class="text-muted">Crea un nuevo período y clona la configuración de uno existente.</small>
    </div>
    <a href="<?= $basePath ?>/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
      <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
  </div>

  <?php if ($result !== null): ?>
    <!-- RESULTADO -->
    <div class="card border-0 shadow-sm mb-4 <?= $result['success'] ? 'border-success' : 'border-danger' ?>"
         style="border-left: 4px solid <?= $result['success'] ? '#198754' : '#dc3545' ?> !important">
      <div class="card-body">
        <?php if ($result['success']): ?>
          <h5 class="fw-bold text-success mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>
            Importación completada — Período: <?= htmlspecialchars($result['periodo_destino']['nombre'] ?? '') ?>
          </h5>
        <?php else: ?>
          <h5 class="fw-bold text-danger mb-3">
            <i class="bi bi-x-circle-fill me-2"></i>
            Error: <?= htmlspecialchars($result['error'] ?? 'Error desconocido') ?>
          </h5>
        <?php endif; ?>

        <?php if (!empty($result['log'])): ?>
          <div class="imp-log">
            <?php foreach ($result['log'] as $linea): ?>
              <div class="imp-log-line"><?= htmlspecialchars($linea) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- FORMULARIO -->
    <div class="col-lg-7">
      <form method="POST" action="<?= $basePath ?>/academic/importador/importar" id="formImportador">

        <!-- PERÍODO ORIGEN -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-0 py-3">
            <h6 class="fw-bold mb-0">
              <i class="bi bi-arrow-right-circle-fill text-primary me-2"></i>
              Paso 1 — Período Origen
            </h6>
          </div>
          <div class="card-body pt-0">
            <label class="form-label small fw-bold">Selecciona el período a clonar</label>
            <select name="periodo_origen_id" id="selOrigen" class="form-select" required>
              <option value="">— Selecciona —</option>
              <?php foreach ($periodos as $p): ?>
                <option value="<?= $p['id'] ?>">
                  <?= htmlspecialchars($p['nombre']) ?>
                  (<?= $p['estado'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div id="resumenOrigen" class="mt-3 d-none"></div>
          </div>
        </div>

        <!-- NUEVO PERÍODO -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white border-0 py-3">
            <h6 class="fw-bold mb-0">
              <i class="bi bi-plus-circle-fill text-success me-2"></i>
              Paso 2 — Nuevo Período Destino
            </h6>
          </div>
          <div class="card-body pt-0">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-bold">Código</label>
                <input type="text" name="periodo_code" class="form-control"
                       placeholder="Ej: 2026-COHORTE-16" required>
              </div>
              <div class="col-md-8">
                <label class="form-label small fw-bold">Nombre</label>
                <input type="text" name="nombre" class="form-control"
                       placeholder="Ej: Año 2026 - Cohorte 16" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Inicio del Período</label>
                <input type="date" name="fecha_inicio" id="inputInicio" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Fin del Período</label>
                <input type="date" name="fecha_fin" id="inputFin" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Apertura Inscripción</label>
                <input type="date" name="apertura_inscripcion" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Cierre Inscripción</label>
                <input type="date" name="cierre_inscripcion" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label small fw-bold">Descripción / Observaciones</label>
                <textarea name="descripcion" class="form-control" rows="2"
                          placeholder="Notas adicionales..."></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- BOTÓN -->
        <div class="d-grid">
          <button type="button" class="btn btn-success btn-lg rounded-pill shadow" id="btnImportar">
            <i class="bi bi-arrow-repeat me-2"></i> Importar y Crear Nuevo Período
          </button>
        </div>

      </form>
    </div>

    <!-- INFO LATERAL -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
          <h6 class="fw-bold mb-0">
            <i class="bi bi-info-circle-fill text-info me-2"></i>
            ¿Qué se clona?
          </h6>
        </div>
        <div class="card-body">
          <div class="imp-item imp-ok">
            <i class="bi bi-check-circle-fill"></i>
            <div>
              <div class="fw-semibold small">Cohortes</div>
              <div class="text-muted" style="font-size:12px">Con nuevos códigos y fechas del período destino</div>
            </div>
          </div>
          <div class="imp-item imp-ok">
            <i class="bi bi-check-circle-fill"></i>
            <div>
              <div class="fw-semibold small">Ofertas Académicas</div>
              <div class="text-muted" style="font-size:12px">Mismos diplomados, costos y modalidades. Estado: BORRADOR</div>
            </div>
          </div>
          <div class="imp-item imp-ok">
            <i class="bi bi-check-circle-fill"></i>
            <div>
              <div class="fw-semibold small">Grupos</div>
              <div class="text-muted" style="font-size:12px">Mismos grupos asignados a cada oferta</div>
            </div>
          </div>
          <div class="imp-item imp-ok">
            <i class="bi bi-check-circle-fill"></i>
            <div>
              <div class="fw-semibold small">Profesores</div>
              <div class="text-muted" style="font-size:12px">Mismas asignaciones de profesores y modalidades</div>
            </div>
          </div>
          <div class="imp-item imp-ok">
            <i class="bi bi-check-circle-fill"></i>
            <div>
              <div class="fw-semibold small">Horarios Teóricos</div>
              <div class="text-muted" style="font-size:12px">Mismo día de semana y horario</div>
            </div>
          </div>
          <hr>
          <div class="imp-item imp-no">
            <i class="bi bi-x-circle-fill"></i>
            <div>
              <div class="fw-semibold small">Estudiantes / Matrículas</div>
              <div class="text-muted" style="font-size:12px">No se clonan — son nuevas inscripciones</div>
            </div>
          </div>
          <div class="imp-item imp-no">
            <i class="bi bi-x-circle-fill"></i>
            <div>
              <div class="fw-semibold small">Pagos / Notas / Sesiones</div>
              <div class="text-muted" style="font-size:12px">No se clonan — son datos del período anterior</div>
            </div>
          </div>
          <div class="imp-item imp-no">
            <i class="bi bi-x-circle-fill"></i>
            <div>
              <div class="fw-semibold small">Fechas de Prácticas</div>
              <div class="text-muted" style="font-size:12px">No se clonan — deben configurarse manualmente</div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- HISTORIAL DE IMPORTACIONES -->
<?php if (!empty($importaciones)): ?>
<div class="card border-0 shadow-sm mt-4">
  <div class="card-header bg-white border-0 py-3">
    <h6 class="fw-bold mb-0">
      <i class="bi bi-clock-history text-warning me-2"></i>
      Historial de Importaciones
    </h6>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover align-middle mb-0">
      <thead class="bg-light small fw-bold text-secondary text-uppercase">
        <tr>
          <th class="ps-3">Origen</th>
          <th>Destino</th>
          <th>Importado por</th>
          <th>Fecha</th>
          <th class="text-end pe-3">Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($importaciones as $imp): ?>
          <tr>
            <td class="ps-3 small"><?= htmlspecialchars($imp['origen_nombre']) ?></td>
            <td class="small fw-semibold"><?= htmlspecialchars($imp['destino_nombre']) ?>
              <span class="badge bg-<?= $imp['destino_estado'] === 'Planificado' ? 'secondary' : 'success' ?> ms-1">
                <?= $imp['destino_estado'] ?>
              </span>
            </td>
            <td class="small text-muted"><?= htmlspecialchars($imp['last_name'] . ', ' . $imp['first_name']) ?></td>
            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($imp['created_at'])) ?></td>
            <td class="text-end pe-3">
              <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-reversar"
                      data-id="<?= $imp['periodo_destino_id'] ?>"
                      data-nombre="<?= htmlspecialchars($imp['destino_nombre']) ?>">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reversar
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>


<script>
  window.APP_BASE_PATH = '<?= $basePath ?>';
  window.PERIODOS_DATA = <?= json_encode($periodos, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/academic_importador.js?v=<?= time() ?>"></script>