<?php
/**
 * MÓDULO: RECURSOS HUMANOS / SESIONES
 * ARCHIVO: app/views/resources/sesiones/manage.php
 * PROPÓSITO: Gestión de sesiones de una oferta. Selección de personal docente,
 *            visualizador de sesiones ya asignadas, y dos pestañas para asignar
 *            horarios teóricos y prácticos al personal seleccionado.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_sesiones.css">

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
                <a href="<?= $basePath ?>/resources/sesiones" class="text-decoration-none text-muted">Programar Sesiones</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary">
                <?= htmlspecialchars($oferta['diplomado_nombre']) ?>
            </li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark"><?= htmlspecialchars($oferta['diplomado_nombre']) ?></h2>
            <p class="text-muted small mb-0">
                <?= htmlspecialchars($oferta['cohorte_nombre']) ?>
                &nbsp;·&nbsp; <?= htmlspecialchars($oferta['general_modality']) ?>
            </p>
        </div>
        <a href="<?= $basePath ?>/resources/sesiones" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-3">

        <!-- COLUMNA IZQUIERDA: SELECTOR DE PERSONAL + VISUALIZADOR -->
        <div class="col-lg-4">

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3">
                    <div class="ses-section-title mb-3">
                        <i class="bi bi-person-badge me-1"></i> Seleccionar Personal
                    </div>
                    <select id="selectPersonal" class="form-select form-select-sm">
                        <option value="">Seleccione un profesor/instructor...</option>
                        <?php foreach ($personal as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= htmlspecialchars($p['last_name'] . ', ' . $p['first_name']) ?>
                                <span class="text-muted"> — <?= $p['document_id'] ?></span>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="card border-0 shadow-sm" id="cardVisualizador" style="display:none">
                <div class="card-body p-3">
                    <div class="ses-section-title mb-3">
                        <i class="bi bi-calendar3 me-1"></i> Sesiones Asignadas
                        <span class="badge rounded-pill ms-1" id="badgeSesiones"
                              style="background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC">0</span>
                    </div>
                    <div id="visualizadorWrap"></div>
                </div>
            </div>

        </div>

        <!-- COLUMNA DERECHA: PESTAÑAS DE HORARIOS -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">

                    <ul class="nav nav-tabs mb-3" id="tabsSesiones">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold" data-tab="teoricos">
                                <i class="bi bi-book me-1"></i> Teóricos
                                <span class="badge rounded-pill ms-1 bg-light text-muted border">
                                    <?= count($horariosTeoricos) ?>
                                </span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" data-tab="practicos">
                                <i class="bi bi-hospital me-1"></i> Prácticos
                                <span class="badge rounded-pill ms-1 bg-light text-muted border">
                                    <?= count($horariosPracticos) ?>
                                </span>
                            </button>
                        </li>
                    </ul>

                    <!-- PESTAÑA TEÓRICOS -->
                    <div id="tabTeoricos">
                        <?php if (empty($horariosTeoricos)): ?>
                            <div class="ses-empty">
                                <i class="bi bi-book fs-2 d-block mb-2 opacity-25"></i>
                                No hay horarios teóricos configurados para esta oferta.
                            </div>
                        <?php else: ?>
                            <div class="ses-aviso mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Selecciona un horario, elige las fechas y asigna el personal.
                            </div>
                            <?php foreach ($horariosTeoricos as $h): ?>
                                <div class="ses-horario-card" data-hid="<?= $h['id'] ?>" data-tipo="TEORICO">
                                    <div class="ses-horario-head">
                                        <div>
                                            <div class="ses-horario-titulo">
                                                <i class="bi bi-clock me-1"></i>
                                                <?= htmlspecialchars($h['dia_semana']) ?>
                                                &nbsp;·&nbsp;
                                                <?= date('h:i A', strtotime($h['hora_inicio'])) ?>
                                                –
                                                <?= date('h:i A', strtotime($h['hora_fin'])) ?>
                                            </div>
                                            <?php if ((int)$h['total_sesiones'] > 0): ?>
                                                <span class="badge rounded-pill mt-1"
                                                      style="background:#E1F5EE;border:1px solid #1D9E75;color:#085041;font-size:11px">
                                                    <?= $h['total_sesiones'] ?> sesión<?= $h['total_sesiones'] > 1 ? 'es' : '' ?> programada<?= $h['total_sesiones'] > 1 ? 's' : '' ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-asignar"
                                                data-hid="<?= $h['id'] ?>" data-tipo="TEORICO"
                                                data-label="<?= htmlspecialchars($h['dia_semana'] . ' ' . $h['hora_inicio'] . '-' . $h['hora_fin']) ?>">
                                            <i class="bi bi-plus-circle me-1"></i> Asignar
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- PESTAÑA PRÁCTICOS -->
                    <div id="tabPracticos" style="display:none">
                        <?php if (empty($horariosPracticos)): ?>
                            <div class="ses-empty">
                                <i class="bi bi-hospital fs-2 d-block mb-2 opacity-25"></i>
                                No hay horarios prácticos con fechas configuradas.
                            </div>
                        <?php else: ?>
                            <div class="ses-aviso mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Selecciona las fechas que dictará el personal en cada grupo/centro.
                            </div>
                            <?php
                                // Agrupar por horario_practica_id
                                $grupos = [];
                                foreach ($horariosPracticos as $hp) {
                                    $key = $hp['id'];
                                    if (!isset($grupos[$key])) {
                                        $grupos[$key] = [
                                            'id'           => $hp['id'],
                                            'grupo_nombre' => $hp['grupo_nombre'],
                                            'centro_nombre'=> $hp['centro_nombre'],
                                            'fechas'       => []
                                        ];
                                    }
                                    $grupos[$key]['fechas'][] = [
                                        'fecha_id'    => $hp['fecha_id'],
                                        'fecha'       => $hp['fecha'],
                                        'tiene_sesion'=> (int)$hp['tiene_sesion'],
                                    ];
                                }
                            ?>
                            <?php foreach ($grupos as $g): ?>
                                <div class="ses-horario-card mb-2">
                                    <div class="ses-horario-head mb-2">
                                        <div class="ses-horario-titulo">
                                            <i class="bi bi-people me-1"></i>
                                            <?= htmlspecialchars($g['grupo_nombre']) ?>
                                            &nbsp;·&nbsp;
                                            <i class="bi bi-hospital me-1"></i>
                                            <?= htmlspecialchars($g['centro_nombre']) ?>
                                        </div>
                                    </div>
                                    <div class="ses-fechas-wrap">
                                        <?php foreach ($g['fechas'] as $f): ?>
                                            <div class="ses-fecha-item <?= $f['tiene_sesion'] ? 'ses-fecha-asignada' : '' ?>">
                                                <span class="ses-fecha-label">
                                                    <?= date('d/m/Y', strtotime($f['fecha'])) ?>
                                                </span>
                                                <?php if ($f['tiene_sesion']): ?>
                                                    <span class="badge rounded-pill"
                                                          style="background:#E1F5EE;color:#085041;font-size:10px">
                                                        <i class="bi bi-check2"></i> Asignada
                                                    </span>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0 btn-asignar"
                                                            data-hid="<?= $g['id'] ?>" data-tipo="PRACTICA"
                                                            data-fecha="<?= $f['fecha'] ?>"
                                                            data-label="<?= htmlspecialchars($g['grupo_nombre'] . ' / ' . $g['centro_nombre'] . ' — ' . date('d/m/Y', strtotime($f['fecha']))) ?>">
                                                        <i class="bi bi-plus"></i> Asignar
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: SELECCIONAR FECHAS (para teóricos) -->
<div class="modal fade" id="modalFechas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background:#EEEDFE;border-bottom:1px solid #AFA9EC">
                <h5 class="modal-title fw-bold" style="color:#3C3489">
                    <i class="bi bi-calendar-plus me-2"></i>
                    <span id="modalFechasLabel">Asignar Sesiones</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="ses-aviso mb-3" id="modalPersonalNombre"></div>
                <label class="form-label fw-bold small text-uppercase">Fechas de las sesiones</label>
                <div class="d-flex gap-2 mb-2">
                    <input type="date" id="modalFechaInput" class="form-control form-control-sm">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" id="btnAddFecha">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
                <div id="modalFechasPills" class="mt-1"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-4"
                        id="btnConfirmFechas">
                    <i class="bi bi-check-circle me-1"></i> Programar Sesiones
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.APP_BASE_PATH  = '<?= $basePath ?>';
    window.OFFERING_ID    = <?= (int) $offeringId ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_sesiones.js?v=<?= time() ?>"></script>