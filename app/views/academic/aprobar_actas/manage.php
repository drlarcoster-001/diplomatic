<?php
/**
 * MÓDULO: ACADÉMICO / APROBAR ACTAS
 * ARCHIVO: app/views/academic/aprobar_actas/manage.php
 * PROPÓSITO: Detalle del acta con tabla de estudiantes y notas.
 *            Botones Imprimir PDF, Aprobar y Reversar.
 *            JS y CSS en archivos externos.
 * VERSIÓN: 1.1.0 - JS y CSS extraídos a archivos externos.
 */
$basePath   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$labelMod   = ['TEORICA' => 'Teórica', 'PRACTICA' => 'Práctica', 'VIRTUAL' => 'Virtual'];
$colorMod   = ['TEORICA' => '#0d6efd', 'PRACTICA' => '#198754', 'VIRTUAL' => '#0dcaf0'];

$totalEst   = count($estudiantes);
$aprobados  = count(array_filter($estudiantes, fn($e) => $e['nota'] !== null && (float)$e['nota'] >= $notaMinima));
$reprobados = count(array_filter($estudiantes, fn($e) => $e['nota'] !== null && (float)$e['nota'] < $notaMinima));
$sinNota    = count(array_filter($estudiantes, fn($e) => $e['nota'] === null));
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_aprobar_actas.css">

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
      <li class="breadcrumb-item">
        <a href="<?= $basePath ?>/academic/aprobar-actas" class="text-decoration-none text-muted">Aprobar Actas</a>
      </li>
      <li class="breadcrumb-item active fw-bold text-primary">
        <?= htmlspecialchars($acta['diplomado_nombre']) ?>
      </li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-0"><?= htmlspecialchars($acta['diplomado_nombre']) ?></h4>
      <small class="text-muted">
        <?= htmlspecialchars($acta['cohorte_nombre']) ?>
        <?php if (!empty($acta['grupos_nombre'])): ?>
          · <?= htmlspecialchars($acta['grupos_nombre']) ?>
        <?php endif; ?>
        · Prof. <?= htmlspecialchars($acta['profesor_nombre']) ?>
      </small>
    </div>
    <a href="<?= $basePath ?>/academic/aprobar-actas" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
      <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
  </div>

  <!-- RESUMEN -->
  <div class="row g-3 mb-4">
    <div class="col-sm-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-secondary"><?= $totalEst ?></div>
        <div class="text-muted small">Total estudiantes</div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-success"><?= $aprobados ?></div>
        <div class="text-muted small">Aprobados</div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-danger"><?= $reprobados ?></div>
        <div class="text-muted small">Reprobados</div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-warning"><?= $sinNota ?></div>
        <div class="text-muted small">Sin nota</div>
      </div>
    </div>
  </div>

  <!-- TABLA DE ESTUDIANTES -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
      <div>
        <h6 class="mb-0 fw-bold">
          Acta —
          <span class="badge rounded-pill"
                style="background:<?= $colorMod[$acta['modalidad']] ?>20;color:<?= $colorMod[$acta['modalidad']] ?>">
            <?= $labelMod[$acta['modalidad']] ?? $acta['modalidad'] ?>
          </span>
          <?php if ($acta['estado'] === 'ENVIADA'): ?>
            <span class="badge bg-warning text-dark ms-1">Enviada</span>
          <?php else: ?>
            <span class="badge bg-success ms-1">Aprobada</span>
          <?php endif; ?>
        </h6>
        <small class="text-muted">Nota mínima para aprobar: <strong><?= number_format($notaMinima, 2) ?></strong></small>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= $basePath ?>/academic/aprobar-actas/pdf?id=<?= $acta['id'] ?>"
           target="_blank"
           class="btn btn-outline-dark rounded-pill px-3">
          <i class="bi bi-printer me-1"></i> Imprimir
        </a>
        <?php if (in_array($acta['estado'], ['ENVIADA','APROBADA'])): ?>
          <button type="button" class="btn btn-outline-warning rounded-pill px-3" id="btnReversar">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reversar
          </button>
        <?php endif; ?>
        <?php if ($acta['estado'] === 'ENVIADA'): ?>
          <button type="button" class="btn btn-success rounded-pill px-4" id="btnAprobar">
            <i class="bi bi-check2-circle me-1"></i> Aprobar Acta
          </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive" style="max-height:520px;overflow-y:auto">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light" style="position:sticky;top:0;z-index:1">
            <tr>
              <th class="ps-3" style="width:50px">#</th>
              <th>Apellidos y Nombres</th>
              <th style="width:130px">Cédula</th>
              <th class="text-center" style="width:100px">Nota</th>
              <th class="text-center" style="width:110px">Resultado</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($estudiantes)): ?>
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">No hay estudiantes en esta acta.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($estudiantes as $idx => $e):
                $aprobado = $e['nota'] !== null ? ((float)$e['nota'] >= $notaMinima ? 1 : 0) : -1;
              ?>
                <tr data-aprobado="<?= $aprobado ?>">
                  <td class="ps-3 text-muted small"><?= $idx + 1 ?></td>
                  <td class="fw-semibold">
                    <?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) ?>
                  </td>
                  <td class="small text-muted"><?= htmlspecialchars($e['document_id']) ?></td>
                  <td class="text-center nota-valor">
                    <?= $e['nota'] !== null ? number_format((float)$e['nota'], 2) : '—' ?>
                  </td>
                  <td class="text-center">
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
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script>
  window.APP_BASE_PATH = '<?= $basePath ?>';
  window.ACTA_ID       = <?= (int)$acta['id'] ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/academic_aprobar_actas.js?v=<?= time() ?>"></script>