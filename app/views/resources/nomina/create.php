<?php
/**
 * MÓDULO: RECURSOS HUMANOS / NÓMINA
 * ARCHIVO: app/views/resources/nomina/create.php
 * PROPÓSITO: Formulario inicial para crear una nómina: tipo, fecha de pago.
 *            Al guardar redirige a manage.php para agregar personal.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_nomina.css">

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
                <a href="<?= $basePath ?>/resources/nomina" class="text-decoration-none text-muted">Nómina</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary">Nueva Nómina</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Nueva Nómina</h2>
            <p class="text-muted small mb-0">Selecciona el tipo y la fecha de pago para comenzar.</p>
        </div>
        <a href="<?= $basePath ?>/resources/nomina" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase">Tipo de Nómina</label>
                        <div class="n-tipo-grid">
                            <label class="n-tipo-card">
                                <input type="radio" name="tipo" value="QUINCENAL" class="n-tipo-radio">
                                <div class="n-tipo-content">
                                    <i class="bi bi-calendar2-week fs-3"></i>
                                    <div class="fw-bold mt-2">Quincenal</div>
                                    <div class="small text-muted">Administrativos, Coord. Práctica</div>
                                </div>
                            </label>
                            <label class="n-tipo-card">
                                <input type="radio" name="tipo" value="POR_DIA" class="n-tipo-radio">
                                <div class="n-tipo-content">
                                    <i class="bi bi-calendar2-day fs-3"></i>
                                    <div class="fw-bold mt-2">Por Día</div>
                                    <div class="small text-muted">Vigilancia, Mantenimiento, Audio Visual</div>
                                </div>
                            </label>
                            <label class="n-tipo-card">
                                <input type="radio" name="tipo" value="POR_SESION" class="n-tipo-radio">
                                <div class="n-tipo-content">
                                    <div class="position-relative d-inline-block">
                                        <i class="bi bi-easel fs-3"></i>
                                        <?php if (($sesionesPendientesCount ?? 0) > 0): ?>
                                            <span class="n-notif-badge">
                                                <?= $sesionesPendientesCount > 99 ? '99+' : $sesionesPendientesCount ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fw-bold mt-2">Por Sesión</div>
                                    <div class="small text-muted">Profesores, Coord. Entornos Virtuales</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase">Fecha de Pago</label>
                        <input type="date" id="fecha_pago" class="form-control">
                    </div>

                    <button class="btn btn-danger w-100 rounded-pill fw-bold py-2" id="btnCrearNomina">
                        <i class="bi bi-check-circle me-1"></i> Crear Nómina
                    </button>

                </div>
            </div>
        </div>
    </div>
</div>

<script>window.APP_BASE_PATH = '<?= $basePath ?>';</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_nomina_create.js?v=<?= time() ?>"></script>