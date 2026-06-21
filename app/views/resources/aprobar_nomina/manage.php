<?php
/**
 * MÓDULO: RECURSOS HUMANOS / APROBAR NÓMINAS
 * ARCHIVO: app/views/resources/aprobar_nomina/manage.php
 * PROPÓSITO: Detalle de solo lectura de una nómina PROCESADA. Muestra todo el
 *            personal, montos, conceptos y (si aplica) sesiones vinculadas.
 *            Botón "Aprobar Nómina" genera las órdenes de pago.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$n = $nomina;

$totalUsdGeneral = array_sum(array_column($personal, 'total_usd'));
$totalBsGeneral  = array_sum(array_column($personal, 'total_bs'));
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_aprobar_nomina.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i>Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/resources" class="text-decoration-none text-muted">Recursos</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/resources/aprobar-nomina" class="text-decoration-none text-muted">Aprobar Nóminas</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary"><?= htmlspecialchars($n['nombre']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <?= htmlspecialchars($n['nombre']) ?>
                <span class="badge rounded-pill ms-2"
                      style="font-size:11px;background:#E6F1FB;border:1px solid #378ADD;color:#0C447C">
                    PROCESADA
                </span>
            </h2>
            <p class="text-muted small mb-0">
                <?= str_replace('_', ' ', $n['tipo']) ?>
                &nbsp;·&nbsp; Fecha de pago: <?= date('d/m/Y', strtotime($n['fecha_pago'])) ?>
            </p>
        </div>
        <a href="<?= $basePath ?>/resources/aprobar-nomina" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="alert alert-warning d-flex justify-content-between align-items-center mb-3">
        <span>
            <i class="bi bi-exclamation-triangle me-2"></i>
            Revisa cuidadosamente antes de aprobar. Esta acción generará <strong><?= count($personal) ?></strong> orden(es) de pago y no se puede deshacer fácilmente.
        </span>
        <button class="btn btn-success rounded-pill px-4 fw-bold flex-shrink-0 ms-3" id="btnAprobar">
            <i class="bi bi-check2-circle me-1"></i> Aprobar Nómina
        </button>
    </div>

    <!-- GRID DE PERSONAL (SOLO LECTURA) -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="an-section-title mb-3">
                <i class="bi bi-people me-1"></i> Personal Incluido
                <span class="badge rounded-pill ms-1" style="background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC">
                    <?= count($personal) ?>
                </span>
            </div>

            <?php if (empty($personal)): ?>
                <div class="an-empty">No hay personal en esta nómina.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle an-table">
                        <thead>
                            <tr>
                                <th>Personal</th>
                                <th class="text-end">Salario Base</th>
                                <th class="text-end">+ Asig.</th>
                                <th class="text-end">- Deduc.</th>
                                <th class="text-end">Total USD</th>
                                <th class="text-end">Total Bs</th>
                                <th class="text-center">Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($personal as $p): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold small"><?= htmlspecialchars($p['last_name'] . ', ' . $p['first_name']) ?></div>
                                    <div class="text-muted" style="font-size:11px"><?= htmlspecialchars($p['tipo_nombre']) ?></div>
                                </td>
                                <td class="text-end">$<?= number_format((float)$p['salario_base'], 2) ?></td>
                                <td class="text-end text-success">+$<?= number_format((float)$p['total_asignaciones'], 2) ?></td>
                                <td class="text-end text-danger">-$<?= number_format((float)$p['total_deducciones'], 2) ?></td>
                                <td class="text-end fw-bold">$<?= number_format((float)$p['total_usd'], 2) ?></td>
                                <td class="text-end fw-bold text-muted">Bs. <?= number_format((float)$p['total_bs'], 2) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0 btn-toggle-detalle"
                                            data-target="detalle-<?= $p['id'] ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class="an-detalle-row" id="detalle-<?= $p['id'] ?>" style="display:none">
                                <td colspan="7">
                                    <div class="an-detalle-box">
                                        <?php if (!empty($p['asignaciones'])): ?>
                                            <div class="mb-2">
                                                <strong class="small" style="color:#085041">Asignaciones:</strong>
                                                <?php foreach ($p['asignaciones'] as $a): ?>
                                                    <div class="an-concepto-linea">
                                                        <?= htmlspecialchars($a['nombre_concepto']) ?>
                                                        <span class="float-end">$<?= number_format((float)$a['monto'], 2) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($p['deducciones'])): ?>
                                            <div class="mb-2">
                                                <strong class="small" style="color:#A32D2D">Deducciones:</strong>
                                                <?php foreach ($p['deducciones'] as $d): ?>
                                                    <div class="an-concepto-linea">
                                                        <?= htmlspecialchars($d['nombre_concepto']) ?>
                                                        <span class="float-end">-$<?= number_format((float)$d['monto'], 2) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($p['sesiones'])): ?>
                                            <div>
                                                <strong class="small" style="color:#633806">Sesiones pagadas:</strong>
                                                <?php foreach ($p['sesiones'] as $s): ?>
                                                    <div class="an-concepto-linea">
                                                        <i class="bi bi-calendar-event me-1 text-muted"></i>
                                                        <?= date('d/m/Y', strtotime($s['fecha'])) ?>
                                                        — <?= htmlspecialchars($s['diplomado_nombre'] ?? '—') ?>
                                                        · <?= htmlspecialchars($s['horario_desc'] ?? '—') ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="an-totales-bar mt-3">
                    <div class="d-flex justify-content-end gap-4">
                        <div class="text-end">
                            <div class="small text-muted text-uppercase fw-bold">Total USD</div>
                            <div class="fs-5 fw-bold" style="color:#085041">$<?= number_format($totalUsdGeneral, 2) ?></div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted text-uppercase fw-bold">Total Bs</div>
                            <div class="fs-5 fw-bold" style="color:#3C3489">Bs. <?= number_format($totalBsGeneral, 2) ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    window.APP_BASE_PATH = '<?= $basePath ?>';
    window.NOMINA_ID     = <?= (int) $nominaId ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_aprobar_nomina.js?v=<?= time() ?>"></script>