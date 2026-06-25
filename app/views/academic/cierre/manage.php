<?php
/**
 * MÓDULO: ACADÉMICO / CIERRE ACADÉMICO
 * ARCHIVO: app/views/academic/cierre/manage.php
 * PROPÓSITO: Grid con 3 columnas fijas (Teórica/Práctica/Virtual). Nota junto
 *            a cada modalidad con ícono clickeable que abre modal con datos
 *            del profesor. Nota Final = promedio de las 3 sin decimales.
 *            WA en columna Solvente e columna Expediente según corresponda.
 * VERSIÓN: 1.2.0 - 3 notas obligatorias, modal profesor, WA por columna.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$totalEst = count($estudiantes);
$aptos    = count(array_filter($estudiantes, fn($e) => $e['apto']));
$noAptos  = $totalEst - $aptos;

// Datos de profesores en JSON para el modal
$profesoresJson = json_encode([
    'TEORICA'  => $profesores['TEORICA']  ?? null,
    'PRACTICA' => $profesores['PRACTICA'] ?? null,
    'VIRTUAL'  => $profesores['VIRTUAL']  ?? null,
], JSON_UNESCAPED_UNICODE);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_cierre.css">

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
        <a href="<?= $basePath ?>/academic/cierre" class="text-decoration-none text-muted">Cierre Académico</a>
      </li>
      <li class="breadcrumb-item active fw-bold text-primary">
        <?= htmlspecialchars($oferta['diplomado_nombre']) ?>
      </li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-0"><?= htmlspecialchars($oferta['diplomado_nombre']) ?></h4>
      <small class="text-muted">
        <?= htmlspecialchars($oferta['cohorte_nombre']) ?>
        <?php if (!empty($oferta['grupos_nombre'])): ?>
          · <?= htmlspecialchars($oferta['grupos_nombre']) ?>
        <?php endif; ?>
        · Costo: <strong>$<?= number_format($totalCosto, 2) ?></strong>
        · Nota mínima: <strong><?= number_format($notaMinima, 0) ?></strong>
      </small>
    </div>
    <div class="d-flex gap-2">
          
    <?php if ($oferta['status'] === 'CERRADA'): ?>
        <button type="button" class="btn btn-warning rounded-pill px-4 shadow-sm" id="btnReversar">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reversar Cierre
        </button>
    <?php else: ?>
        <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm" id="btnCerrar">
            <i class="bi bi-lock-fill me-1"></i> Cerrar Oferta
        </button>
    <?php endif; ?>
      <a href="<?= $basePath ?>/academic/cierre" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
      </a>
    </div>
  </div>

  <!-- RESUMEN -->
  <div class="row g-3 mb-4">
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-secondary"><?= $totalEst ?></div>
        <div class="text-muted small">Total estudiantes</div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-success"><?= $aptos ?></div>
        <div class="text-muted small">Aptos</div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-danger"><?= $noAptos ?></div>
        <div class="text-muted small">No aptos</div>
      </div>
    </div>
  </div>

  <?php if (!$todosAptos): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <span>Hay estudiantes que no cumplen todas las condiciones. Resuélvelas antes de cerrar la oferta.</span>
    </div>
  <?php endif; ?>

  <!-- GRID -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive" style="max-height:560px;overflow-y:auto">
        <table class="table table-hover align-middle mb-0 cierre-tabla">
          <thead class="table-dark" style="position:sticky;top:0;z-index:1">
            <tr>
              <th class="ps-3" style="width:40px">#</th>
              <th>Estudiante</th>
              <th class="text-center" style="width:120px">Solvente</th>
              <th class="text-center" style="width:100px">
                  <span class="d-inline-flex align-items-center gap-1">
                      Teórica
                      <button class="btn-profesor-info" data-modalidad="TEORICA" title="Ver profesor"><i class="bi bi-person-circle"></i></button>
                  </span>
              </th>
              <th class="text-center" style="width:100px">
                  <span class="d-inline-flex align-items-center gap-1">
                      Práctica
                      <button class="btn-profesor-info" data-modalidad="PRACTICA" title="Ver profesor"><i class="bi bi-person-circle"></i></button>
                  </span>
              </th>
              <th class="text-center" style="width:100px">
                  <span class="d-inline-flex align-items-center gap-1">
                      Virtual
                      <button class="btn-profesor-info" data-modalidad="VIRTUAL" title="Ver profesor"><i class="bi bi-person-circle"></i></button>
                  </span>
              </th>
              <th class="text-center" style="width:100px">Nota Final</th>
              <th class="text-center" style="width:160px">Expediente</th>
              <th class="text-center" style="width:90px">Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($estudiantes)): ?>
              <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                  No hay estudiantes matriculados en esta oferta.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($estudiantes as $idx => $e): ?>
                <tr class="<?= $e['apto'] ? 'fila-apta' : 'fila-no-apta' ?>">
                  <td class="ps-3 text-muted small"><?= $idx + 1 ?></td>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($e['document_id']) ?></small>
                  </td>

                  <!-- SOLVENTE -->
                <td class="text-center">
                    <div class="d-flex align-items-center justify-content-center gap-1">
                        <?php if ($e['solvente']): ?>
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span class="text-success fw-bold small">$0.00</span>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger"></i>
                            <span class="text-danger fw-bold small">$<?= number_format($e['saldo_falta'], 2) ?></span>
                            <?php if ($e['wa_solvencia'] && $e['phone_clean']): ?>
                                <a href="https://wa.me/<?= $e['phone_clean'] ?>?text=<?= $e['wa_solvencia'] ?>"
                                  target="_blank"
                                  class="btn btn-sm btn-success rounded-circle wa-btn"
                                  title="Recordar pago">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </td>


                  <!-- TEÓRICA -->
                  <td class="text-center">
                    <?php if ($e['nota_teorica'] !== null): ?>
                      <span class="fw-bold <?= (float)$e['nota_teorica'] >= $notaMinima ? 'text-success' : 'text-danger' ?>">
                        <?= (int)round((float)$e['nota_teorica']) ?>
                      </span>
                    <?php else: ?>
                      <span class="badge bg-light text-muted border">No cargada</span>
                    <?php endif; ?>
                  </td>

                  <!-- PRÁCTICA -->
                  <td class="text-center">
                    <?php if ($e['nota_practica'] !== null): ?>
                      <span class="fw-bold <?= (float)$e['nota_practica'] >= $notaMinima ? 'text-success' : 'text-danger' ?>">
                        <?= (int)round((float)$e['nota_practica']) ?>
                      </span>
                    <?php else: ?>
                      <span class="badge bg-light text-muted border">No cargada</span>
                    <?php endif; ?>
                  </td>

                  <!-- VIRTUAL -->
                  <td class="text-center">
                    <?php if ($e['nota_virtual'] !== null): ?>
                      <span class="fw-bold <?= (float)$e['nota_virtual'] >= $notaMinima ? 'text-success' : 'text-danger' ?>">
                        <?= (int)round((float)$e['nota_virtual']) ?>
                      </span>
                    <?php else: ?>
                      <span class="badge bg-light text-muted border">No cargada</span>
                    <?php endif; ?>
                  </td>

                  <!-- NOTA FINAL -->
                  <td class="text-center">
                    <?php if ($e['nota_final'] !== null): ?>
                      <span class="badge rounded-pill px-3 <?= $e['aprobado'] ? 'bg-success' : 'bg-danger' ?>"
                            style="font-size:13px">
                        <?= $e['nota_final'] ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted small">Pendiente</span>
                    <?php endif; ?>
                  </td>

                  <!-- EXPEDIENTE -->
                  <td class="text-center">
                    <div class="d-flex align-items-center justify-content-center gap-1">
                      <span class="led-badge <?= $e['ced_ok'] ? 'led-ok' : 'led-falta' ?>" title="Cédula">Ced</span>
                      <span class="led-badge <?= $e['tit_ok'] ? 'led-ok' : 'led-falta' ?>" title="Título">Tit</span>
                      <span class="led-badge <?= $e['cv_ok']  ? 'led-ok' : 'led-falta' ?>" title="CV">CV</span>
                      <?php if ($e['wa_expediente'] && $e['phone_clean']): ?>
                        <a href="https://wa.me/<?= $e['phone_clean'] ?>?text=<?= $e['wa_expediente'] ?>"
                           target="_blank"
                           class="btn btn-sm btn-success rounded-circle wa-btn ms-1"
                           title="Solicitar documentos">
                          <i class="bi bi-whatsapp"></i>
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>

                  <!-- ESTADO -->
                  <td class="text-center">
                    <?php if ($e['apto']): ?>
                      <span class="badge bg-success">Apto</span>
                    <?php else: ?>
                      <span class="badge bg-danger">No Apto</span>
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

<!-- MODAL PROFESOR -->
<div class="modal fade" id="modalProfesor" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:380px">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0" style="background:#533AB7;color:#fff;border-radius:12px 12px 0 0">
        <h6 class="modal-title fw-bold" id="modalProfesorTitulo">
          <i class="bi bi-person-circle me-2"></i>Profesor — Modalidad
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="modalProfesorBody">
      </div>
    </div>
  </div>
</div>

<script>
  window.APP_BASE_PATH  = '<?= $basePath ?>';
  window.OFFERING_ID    = <?= (int)$offeringId ?>;
  window.PROFESORES     = <?= $profesoresJson ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/academic_cierre.js?v=<?= time() ?>"></script>