<?php
/**
 * MÓDULO: ACADÉMICO / HISTÓRICO
 * ARCHIVO: app/views/academic/historico/manage.php
 * PROPÓSITO: Detalle de un diplomado cerrado con tabla de estudiantes,
 *            notas por modalidad, nota final y resultado. Botón PDF.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

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
        <a href="<?= $basePath ?>/academic/historico" class="text-decoration-none text-muted">Histórico</a>
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
        · Cerrado el <?= !empty($oferta['fecha_cierre']) ? date('d/m/Y', strtotime($oferta['fecha_cierre'])) : '—' ?>
        · Nota mínima: <strong><?= number_format((float)$oferta['nota_minima'], 0) ?></strong>
      </small>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= $basePath ?>/academic/historico/pdf?offering_id=<?= $offeringId ?>"
         target="_blank"
         class="btn btn-outline-dark rounded-pill px-4 shadow-sm">
        <i class="bi bi-printer me-1"></i> Imprimir PDF
      </a>
      <a href="<?= $basePath ?>/academic/historico" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver
      </a>
    </div>
  </div>

  <!-- RESUMEN -->
  <div class="row g-3 mb-4">
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-secondary"><?= count($estudiantes) ?></div>
        <div class="text-muted small">Total estudiantes</div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-success"><?= $aprobados ?></div>
        <div class="text-muted small">Aprobados</div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm text-center py-3">
        <div class="fs-2 fw-bold text-danger"><?= $reprobados ?></div>
        <div class="text-muted small">Reprobados</div>
      </div>
    </div>
  </div>

  <!-- TABLA -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive" style="max-height:520px;overflow-y:auto">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-dark" style="position:sticky;top:0;z-index:1">
            <tr>
              <th class="ps-3" style="width:40px">#</th>
              <th>Apellidos y Nombres</th>
              <th style="width:120px">Cédula</th>
              <th class="text-center" style="width:80px">Teórica</th>
              <th class="text-center" style="width:80px">Práctica</th>
              <th class="text-center" style="width:80px">Virtual</th>
              <th class="text-center" style="width:90px">Nota Final</th>
              <th class="text-center" style="width:100px">Resultado</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($estudiantes)): ?>
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  No hay estudiantes en esta oferta.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($estudiantes as $idx => $e): ?>
                <tr style="background: <?= $e['aprobado'] ? '#f0fff4' : '#fff5f5' ?>">
                  <td class="ps-3 text-muted small"><?= $idx + 1 ?></td>
                  <td class="fw-semibold"><?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) ?></td>
                  <td class="small text-muted"><?= htmlspecialchars($e['document_id']) ?></td>
                  <td class="text-center fw-bold <?= $e['nota_teorica'] !== null && (float)$e['nota_teorica'] >= (float)$e['nota_minima'] ? 'text-success' : 'text-danger' ?>">
                    <?= $e['nota_teorica'] !== null ? (int)round((float)$e['nota_teorica']) : '—' ?>
                  </td>
                  <td class="text-center fw-bold <?= $e['nota_practica'] !== null && (float)$e['nota_practica'] >= (float)$e['nota_minima'] ? 'text-success' : 'text-danger' ?>">
                    <?= $e['nota_practica'] !== null ? (int)round((float)$e['nota_practica']) : '—' ?>
                  </td>
                  <td class="text-center fw-bold <?= $e['nota_virtual'] !== null && (float)$e['nota_virtual'] >= (float)$e['nota_minima'] ? 'text-success' : 'text-danger' ?>">
                    <?= $e['nota_virtual'] !== null ? (int)round((float)$e['nota_virtual']) : '—' ?>
                  </td>
                  <td class="text-center">
                    <?php if ($e['nota_final'] !== null): ?>
                      <span class="badge rounded-pill px-3 <?= $e['aprobado'] ? 'bg-success' : 'bg-danger' ?>"
                            style="font-size:13px">
                        <?= $e['nota_final'] ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php if ($e['aprobado']): ?>
                      <span class="badge bg-success">Aprobado</span>
                    <?php else: ?>
                      <span class="badge bg-danger">Reprobado</span>
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