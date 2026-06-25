<?php
/**
 * MÓDULO: ACADÉMICO / HISTÓRICO
 * ARCHIVO: app/views/academic/historico/index.php
 * PROPÓSITO: Listado de ofertas cerradas con buscador. Clic en fila
 *            abre el detalle con notas finales de los estudiantes.
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
      <li class="breadcrumb-item active fw-bold text-primary">Histórico</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="h4 fw-bold mb-0 text-dark">Histórico Académico</h2>
      <p class="text-muted small mb-0">Consulta de diplomados cerrados con notas y resultados finales.</p>
    </div>
    <a href="<?= $basePath ?>/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
      <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
  </div>

  <!-- BUSCADOR -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" class="row g-2">
        <div class="col-md-10">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" name="search" class="form-control border-start-0"
                   placeholder="Buscar por diplomado o cohorte..."
                   value="<?= htmlspecialchars($search ?? '') ?>">
            <?php if (!empty($search)): ?>
              <a href="<?= $basePath ?>/academic/historico" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg"></i>
              </a>
            <?php endif; ?>
          </div>
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
              <th>Cohorte</th>
              <th>Grupo</th>
              <th class="text-center">Estudiantes</th>
              <th class="text-center">Fecha Cierre</th>
              <th class="text-end pe-3">Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($ofertas)): ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-archive fs-2 d-block mb-2 opacity-25"></i>
                  No hay diplomados cerrados en el histórico.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($ofertas as $idx => $o): ?>
                <tr style="cursor:pointer"
                    onclick="window.location='<?= $basePath ?>/academic/historico/manage?offering_id=<?= $o['offering_id'] ?>'">
                  <td class="ps-3 text-muted small"><?= $idx + 1 ?></td>
                  <td class="fw-bold text-dark"><?= htmlspecialchars($o['diplomado_nombre']) ?></td>
                  <td class="small text-muted"><?= htmlspecialchars($o['cohorte_nombre']) ?></td>
                  <td>
                    <?php if (!empty($o['grupos_nombre'])): ?>
                      <span class="badge bg-light text-dark border">
                        <?= htmlspecialchars($o['grupos_nombre']) ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted small">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <span class="badge rounded-pill px-3"
                          style="background:#EEEDFE;border:1px solid #AFA9EC;color:#3C3489">
                      <?= $o['enrolled_count'] ?>
                    </span>
                  </td>
                  <td class="text-center small text-muted">
                    <?= !empty($o['fecha_cierre']) ? date('d/m/Y', strtotime($o['fecha_cierre'])) : '—' ?>
                  </td>
                  <td class="text-end pe-3">
                    <a href="<?= $basePath ?>/academic/historico/manage?offering_id=<?= $o['offering_id'] ?>"
                       class="btn btn-sm btn-outline-primary rounded-pill px-3"
                       onclick="event.stopPropagation()">
                      <i class="bi bi-eye me-1"></i> Ver
                    </a>
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