<?php
/**
 * MÓDULO: RECURSOS HUMANOS / PROCESAR SESIONES
 * ARCHIVO: app/views/resources/procesar_sesiones/manage.php
 * PROPÓSITO: Lista de sesiones PROGRAMADAS de una oferta. Al hacer clic en una sesión
 *            se abre el panel de asistencia: lista de estudiantes con check por defecto,
 *            se desmarcan los ausentes y se procesa. Botón imprimir genera el PDF.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_procesar_sesiones.css">

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
                <a href="<?= $basePath ?>/resources/procesar-sesiones" class="text-decoration-none text-muted">Procesar Sesiones</a>
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
        <a href="<?= $basePath ?>/resources/procesar-sesiones"
           class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="ps-layout">

        <!-- PANEL IZQUIERDO: LISTA DE SESIONES -->
        <div class="ps-panel-left">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">

                    <ul class="nav nav-tabs mb-3" id="tabsPS" style="font-size:13px">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold" data-pstab="pendientes">
                                <i class="bi bi-hourglass-split me-1"></i> Pendientes
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" data-pstab="dictadas">
                                <i class="bi bi-check2-circle me-1"></i> Dictadas
                            </button>
                        </li>
                    </ul>

                    <div id="tabPendientes">
                        <div class="ps-section-title mb-3">
                            <i class="bi bi-calendar-check me-1"></i> Sesiones Pendientes
                            <span class="badge rounded-pill ms-1" id="badgeSesiones"
                                  style="background:#FAEEDA;border:1px solid #BA7517;color:#633806">
                                <?= count($sesiones) ?>
                            </span>
                        </div>

                        <?php if (empty($sesiones)): ?>
                            <div class="ps-empty">
                                <i class="bi bi-check2-all fs-2 d-block mb-2 opacity-25"></i>
                                Todas las sesiones han sido procesadas.
                            </div>
                        <?php else: ?>
                            <div id="listaSesiones">
                                <?php foreach ($sesiones as $s): ?>
                                    <div class="ps-sesion-item" data-sid="<?= $s['id'] ?>">
                                        <div>
                                            <div class="ps-sesion-profesor">
                                                <i class="bi bi-person me-1"></i>
                                                <?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?>
                                            </div>
                                            <div class="ps-sesion-horario">
                                                <?= htmlspecialchars($s['horario_desc']) ?>
                                            </div>
                                            <div class="ps-sesion-fecha">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?= date('d/m/Y', strtotime($s['fecha'])) ?>
                                            </div>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="tabDictadas" style="display:none">
                        <div class="ps-section-title mb-3" style="background:#E1F5EE;border-color:#1D9E75;color:#085041">
                            <i class="bi bi-check2-circle me-1"></i> Sesiones Dictadas
                            <span class="badge rounded-pill ms-1" id="badgeDictadas"
                                  style="background:#E1F5EE;border:1px solid #1D9E75;color:#085041">
                                0
                            </span>
                        </div>
                        <div id="listaDictadas">
                            <div class="ps-empty">
                                <i class="bi bi-hourglass fs-2 d-block mb-2 opacity-25"></i>
                                Cargando...
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- PANEL DERECHO: FORMATO DE ASISTENCIA (compartido, editable o solo lectura) -->
        <div class="ps-panel-right">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3" id="panelAsistencia">
                    <div class="ps-empty">
                        <i class="bi bi-hand-index fs-2 d-block mb-2 opacity-25"></i>
                        Selecciona una sesión de la lista.
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    window.APP_BASE_PATH = '<?= $basePath ?>';
    window.OFFERING_ID   = <?= (int) $offeringId ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_procesar_sesiones.js?v=<?= time() ?>"></script>