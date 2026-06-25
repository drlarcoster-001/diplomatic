<?php
/**
 * MÓDULO: ACADÉMICO / APROBAR ACTAS
 * ARCHIVO: app/views/academic/aprobar_actas/index.php
 * PROPÓSITO: Listado de actas generadas por profesores con filtro por estado
 *            (ENVIADA / APROBADA). Clic en fila abre el detalle.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$page       = $page       ?? 1;
$total      = $total      ?? 0;
$totalPages = $totalPages ?? 1;
$perPage    = $perPage    ?? 25;
$offset     = ($page - 1) * $perPage;

$labelMod = ['TEORICA' => 'Teórica', 'PRACTICA' => 'Práctica', 'VIRTUAL' => 'Virtual'];
$colorMod = ['TEORICA' => 'primary', 'PRACTICA' => 'success', 'VIRTUAL' => 'info'];

function buildUrlAA(array $params): string {
    return '?' . http_build_query(array_merge($_GET ?? [], $params));
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

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
      <li class="breadcrumb-item active fw-bold text-primary">Aprobar Actas</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="h4 fw-bold mb-0 text-dark">Aprobar Actas</h2>
      <p class="text-muted small mb-0">
        Revisa y aprueba las actas de notas enviadas por los profesores.
        <?php if ($total > 0): ?>
          &nbsp;·&nbsp; <strong><?= $total ?></strong> acta<?= $total !== 1 ? 's' : '' ?>
        <?php endif; ?>
      </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $basePath ?>/academic/aprobar-actas/reporte?<?= http_build_query(['search' => $search, 'estado' => $estado]) ?>"
          target="_blank"
          class="btn btn-outline-dark rounded-pill px-4 shadow-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Reporte PDF
        </a>
        <a href="<?= $basePath ?>/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
  </div>

  <!-- FILTROS -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" class="row g-2">
        <div class="col-md-7">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" name="search" class="form-control border-start-0"
                   placeholder="Buscar por diplomado, cohorte o profesor..."
                   value="<?= htmlspecialchars($search ?? '') ?>">
          </div>
        </div>
        <div class="col-md-3">
          <select name="estado" class="form-select" onchange="this.form.submit()">
            <option value="" <?= $estado === '' ? 'selected' : '' ?>>Todos los estados</option>
            <option value="ENVIADA"  <?= $estado === 'ENVIADA'  ? 'selected' : '' ?>>Enviadas</option>
            <option value="APROBADA" <?= $estado === 'APROBADA' ? 'selected' : '' ?>>Aprobadas</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-dark w-100 rounded-pill">Buscar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- TABLA -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div style="max-height:560px;overflow-y:auto">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light small fw-bold text-secondary text-uppercase"
                 style="position:sticky;top:0;z-index:1">
            <tr>
              <th class="ps-3">#</th>
              <th>Diplomado</th>
              <th>Cohorte / Grupo</th>
              <th style="width:100px">Modalidad</th>
              <th>Profesor</th>
              <th style="width:110px">Enviada</th>
              <th style="width:100px" class="text-center">Estado</th>
              <th style="width:80px" class="text-end pe-3">Ver</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($actas)): ?>
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                  No hay actas <?= $estado === 'ENVIADA' ? 'enviadas' : ($estado === 'APROBADA' ? 'aprobadas' : '') ?> todavía.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($actas as $idx => $a): ?>
                <tr style="cursor:pointer"
                    onclick="window.location='<?= $basePath ?>/academic/aprobar-actas/manage?id=<?= $a['id'] ?>'">
                  <td class="ps-3 text-muted small"><?= $offset + $idx + 1 ?></td>
                  <td class="fw-bold text-dark small"><?= htmlspecialchars($a['diplomado_nombre']) ?></td>
                  <td class="small text-muted">
                    <?= htmlspecialchars($a['cohorte_nombre']) ?>
                    <?php if (!empty($a['grupos_nombre'])): ?>
                      <br><span class="badge bg-light text-dark border"><?= htmlspecialchars($a['grupos_nombre']) ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge rounded-pill bg-<?= $colorMod[$a['modalidad']] ?>">
                      <?= $labelMod[$a['modalidad']] ?? $a['modalidad'] ?>
                    </span>
                  </td>
                  <td class="small"><?= htmlspecialchars($a['profesor_nombre']) ?></td>
                  <td class="small text-muted"><?= date('d/m/Y', strtotime($a['updated_at'])) ?></td>
                  <td class="text-center">
                    <?php if ($a['estado'] === 'ENVIADA'): ?>
                      <span class="badge bg-warning text-dark">Enviada</span>
                    <?php else: ?>
                      <span class="badge bg-success">Aprobada</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end pe-3">
                    <a href="<?= $basePath ?>/academic/aprobar-actas/manage?id=<?= $a['id'] ?>"
                       class="btn btn-sm btn-outline-primary rounded-pill px-2"
                       onclick="event.stopPropagation()">
                      <i class="bi bi-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center p-3 border-top">
          <small class="text-muted">Página <?= $page ?> de <?= $totalPages ?> · <?= $total ?> registros</small>
          <nav><ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= buildUrlAA(['page' => $page - 1]) ?>">
                <i class="bi bi-chevron-left"></i>
              </a>
            </li>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= buildUrlAA(['page' => $i]) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= buildUrlAA(['page' => $page + 1]) ?>">
                <i class="bi bi-chevron-right"></i>
              </a>
            </li>
          </ul></nav>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>