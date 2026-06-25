<?php
/**
 * MÓDULO: ACADÉMICO / CIERRE ACADÉMICO
 * ARCHIVO: app/views/academic/cierre/index.php
 * PROPÓSITO: Lista de ofertas con tres pestañas: Abiertas, Cerradas e
 *            Historial de Reversas.
 * VERSIÓN: 1.2.0 - Agrega pestaña Historial de Reversas.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$tab = $_GET['tab'] ?? 'abiertas';

$ofertasAbiertas = array_filter($ofertas, fn($o) => $o['status'] === 'ABIERTA');
$ofertasCerradas = array_filter($ofertas, fn($o) => $o['status'] === 'CERRADA');
$historial       = $historial ?? [];
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
      <li class="breadcrumb-item active fw-bold text-primary">Cierre Académico</li>
    </ol>
  </nav>

  <!-- ENCABEZADO -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="h4 fw-bold mb-0 text-dark">Cierre Académico</h2>
      <p class="text-muted small mb-0">Gestión de cierres y reversas de ofertas académicas.</p>
    </div>
    <a href="<?= $basePath ?>/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
      <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
  </div>

  <!-- BUSCADOR -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" class="row g-2">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <div class="col-md-10">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" name="search" class="form-control border-start-0"
                   placeholder="Buscar por diplomado o cohorte..."
                   value="<?= htmlspecialchars($search ?? '') ?>">
            <?php if (!empty($search)): ?>
              <a href="<?= $basePath ?>/academic/cierre?tab=<?= $tab ?>" class="btn btn-outline-secondary">
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

  <!-- PESTAÑAS -->
  <ul class="nav nav-tabs mb-0" id="tabsCierre">
    <li class="nav-item">
      <a class="nav-link fw-bold <?= $tab === 'abiertas' ? 'active' : '' ?>"
         href="?tab=abiertas&search=<?= urlencode($search ?? '') ?>">
        <i class="bi bi-unlock me-1"></i> Abiertas
        <span class="badge rounded-pill ms-1 <?= $tab === 'abiertas' ? 'bg-primary' : 'bg-light text-muted border' ?>">
          <?= count($ofertasAbiertas) ?>
        </span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link fw-bold <?= $tab === 'cerradas' ? 'active' : '' ?>"
         href="?tab=cerradas&search=<?= urlencode($search ?? '') ?>">
        <i class="bi bi-lock me-1"></i> Cerradas
        <span class="badge rounded-pill ms-1 <?= $tab === 'cerradas' ? 'bg-danger' : 'bg-light text-muted border' ?>">
          <?= count($ofertasCerradas) ?>
        </span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link fw-bold <?= $tab === 'historial' ? 'active' : '' ?>"
         href="?tab=historial&search=<?= urlencode($search ?? '') ?>">
        <i class="bi bi-clock-history me-1"></i> Historial de Reversas
        <span class="badge rounded-pill ms-1 <?= $tab === 'historial' ? 'bg-warning text-dark' : 'bg-light text-muted border' ?>">
          <?= count($historial) ?>
        </span>
      </a>
    </li>
  </ul>

  <!-- CONTENIDO -->
  <div class="card border-0 shadow-sm" style="border-radius: 0 0.375rem 0.375rem 0.375rem">
    <div class="card-body p-0">
      <div style="max-height:560px;overflow-y:auto">

        <?php if ($tab === 'historial'): ?>
          <!-- HISTORIAL DE REVERSAS -->
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light small fw-bold text-secondary text-uppercase"
                   style="position:sticky;top:0;z-index:1">
              <tr>
                <th class="ps-3">#</th>
                <th>Diplomado</th>
                <th>Cohorte / Grupo</th>
                <th>Reversado por</th>
                <th>Fecha</th>
                <th>Motivo</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($historial)): ?>
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-clock-history fs-2 d-block mb-2 opacity-25"></i>
                    No hay reversas registradas.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($historial as $idx => $h): ?>
                  <tr>
                    <td class="ps-3 text-muted small"><?= $idx + 1 ?></td>
                    <td class="fw-bold text-dark small"><?= htmlspecialchars($h['diplomado_nombre']) ?></td>
                    <td class="small text-muted">
                      <?= htmlspecialchars($h['cohorte_nombre']) ?>
                      <?php if (!empty($h['grupos_nombre'])): ?>
                        <br><span class="badge bg-light text-dark border"><?= htmlspecialchars($h['grupos_nombre']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="small"><?= htmlspecialchars($h['last_name'] . ', ' . $h['first_name']) ?></td>
                    <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
                    <td class="small">
                      <span class="d-inline-block text-truncate" style="max-width:250px" title="<?= htmlspecialchars($h['motivo']) ?>">
                        <?= htmlspecialchars($h['motivo']) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>

        <?php else: ?>
          <!-- ABIERTAS / CERRADAS -->
          <?php
            $listaActiva = $tab === 'cerradas' ? $ofertasCerradas : $ofertasAbiertas;
            $esCerradas  = $tab === 'cerradas';
          ?>
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light small fw-bold text-secondary text-uppercase"
                   style="position:sticky;top:0;z-index:1">
              <tr>
                <th class="ps-3">#</th>
                <th>Diplomado</th>
                <th>Cohorte</th>
                <th>Grupos</th>
                <th class="text-center">Estudiantes</th>
                <th class="text-end pe-3">Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($listaActiva)): ?>
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                    No hay ofertas <?= $esCerradas ? 'cerradas' : 'abiertas' ?>.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($listaActiva as $idx => $o): ?>
                  <tr style="cursor:pointer"
                      onclick="window.location='<?= $basePath ?>/academic/cierre/manage?offering_id=<?= $o['offering_id'] ?>'">
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
                    <td class="text-end pe-3">
                      <?php if ($esCerradas): ?>
                        <a href="<?= $basePath ?>/academic/cierre/manage?offering_id=<?= $o['offering_id'] ?>"
                           class="btn btn-sm btn-outline-warning rounded-pill px-3"
                           onclick="event.stopPropagation()">
                          <i class="bi bi-arrow-counterclockwise me-1"></i> Ver / Reversar
                        </a>
                      <?php else: ?>
                        <a href="<?= $basePath ?>/academic/cierre/manage?offering_id=<?= $o['offering_id'] ?>"
                           class="btn btn-sm btn-outline-primary rounded-pill px-3"
                           onclick="event.stopPropagation()">
                          <i class="bi bi-clipboard-check me-1"></i> Iniciar Cierre
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        <?php endif; ?>

      </div>
    </div>
  </div>

</div>