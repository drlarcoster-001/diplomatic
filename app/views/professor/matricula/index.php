<?php
/**
 * MÓDULO: PORTAL DOCENTE / MATRÍCULA
 * ARCHIVO: app/views/professor/matricula/index.php
 * PROPÓSITO: Selector de clases + roster completo de estudiantes +
 *            botón Imprimir PDF de matrícula oficial.
 * VERSIÓN: 2.2.0 - Agrega miga de pan completa y botón Volver.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

$ofertasUnicas = [];
foreach ($ofertas as $o) {
    $id = (int) $o['offering_id'];
    if (!isset($ofertasUnicas[$id])) {
        $ofertasUnicas[$id] = $o;
    }
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/professor.css">

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
            <li class="breadcrumb-item active fw-bold text-primary">Matrícula</li>
        </ol>
    </nav>

    <!-- ENCABEZADO -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Matrícula</h2>
            <p class="text-muted small mb-0">Elige una clase para ver el listado completo de estudiantes inscritos.</p>
        </div>
        <a href="<?= $basePath ?>/professor" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if (empty($ofertasUnicas)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">No tienes clases asignadas todavía.</div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <label class="form-label small fw-bold text-secondary text-uppercase">Selecciona una clase</label>
                <select class="form-select form-select-lg" id="selectorClase" onchange="if(this.value) window.location.href=this.value;">
                    <option value="" <?= !$offeringId ? 'selected' : '' ?> disabled>Elige una clase...</option>
                    <?php foreach ($ofertasUnicas as $o):
                        $activo  = ((int) $o['offering_id'] === $offeringId);
                        $url     = $basePath . '/professor/matricula?offering_id=' . $o['offering_id'];
                        $grupos  = !empty($o['grupos_nombre']) ? ' (' . $o['grupos_nombre'] . ')' : '';
                    ?>
                        <option value="<?= $url ?>" <?= $activo ? 'selected' : '' ?>>
                            <?= htmlspecialchars($o['diplomado_nombre']) ?> — <?= htmlspecialchars($o['cohorte_nombre']) ?><?= htmlspecialchars($grupos) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($offeringId && $estudiantes === null): ?>
        <div class="alert alert-warning">No tienes acceso a esa clase.</div>
    <?php elseif ($ofertaActiva): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold"><i class="bi bi-people-fill me-2 text-primary"></i> <?= htmlspecialchars($ofertaActiva['diplomado_nombre']) ?></span>
                    <div class="small text-muted mt-1">
                        <?= htmlspecialchars($ofertaActiva['cohorte_nombre']) ?>
                        <?php if (!empty($ofertaActiva['grupos_nombre'])): ?>
                            — <?= htmlspecialchars($ofertaActiva['grupos_nombre']) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-light text-dark border"><?= count($estudiantes) ?> estudiante(s)</span>
                    <?php if (!empty($estudiantes)): ?>
                        <a href="<?= $basePath ?>/professor/matricula/pdf?offering_id=<?= $offeringId ?>"
                           target="_blank" class="btn btn-sm btn-danger rounded-pill px-3">
                            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Imprimir PDF
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small fw-bold text-secondary text-uppercase">
                        <tr>
                            <th class="ps-3">Código</th><th>Nombre</th><th>Cédula</th>
                            <th>Email</th><th>Teléfono</th><th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($estudiantes)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">
                                No hay estudiantes inscritos en esta oferta todavía.
                            </td></tr>
                        <?php else: foreach ($estudiantes as $e): ?>
                            <tr>
                                <td class="ps-3 small text-muted"><?= htmlspecialchars($e['student_code']) ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></td>
                                <td class="small"><?= htmlspecialchars($e['document_id']) ?></td>
                                <td class="small"><?= htmlspecialchars($e['email']) ?></td>
                                <td class="small"><?= htmlspecialchars($e['phone'] ?: '—') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($e['status']) ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>