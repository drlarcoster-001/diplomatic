<?php
/**
 * MÓDULO - app/views/settings/whatsapp.php
 * Vista de configuración para envíos manuales de WhatsApp.
 * Implementa el algoritmo de generación de enlaces wa.me.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$t = $templates;
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/whatsapp_manual.css?v=<?= time() ?>">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">WhatsApp (Link Manual)</h2>
            <p class="text-muted small mb-0">Gestión de plantillas dinámicas y trazabilidad de envíos.</p>
        </div>
        <a href="<?= $basePath ?>/settings" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 rounded-4">
                <h5 class="fw-bold mb-4 border-bottom pb-2 text-success">Plantillas de Eventos</h5>
                
                <div class="accordion accordion-flush" id="waAccordion">
                    <?php 
                    $eventos = [
                        'INSCRIPCION' => ['Inscripción Confirmada', 'bi-person-check'],
                        'CONSTANCIA_ESTUDIO' => ['Constancia de Estudio', 'bi-file-earmark-text'],
                        'CONSTANCIA_INSCRIPCION' => ['Constancia de Inscripción', 'bi-file-earmark-person'],
                        'PAGO' => ['Recordatorio de Pago', 'bi-cash-stack']
                    ];
                    foreach($eventos as $key => $info): 
                        $data = $t[$key] ?? ['mensaje' => '', 'activo' => 1];
                    ?>
                    <div class="accordion-item border rounded-4 mb-3 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#coll-<?= $key ?>">
                                <i class="<?= $info[1] ?> me-2 text-success fs-5"></i> <?= $info[0] ?>
                            </button>
                        </h2>
                        <div id="coll-<?= $key ?>" class="accordion-collapse collapse" data-bs-parent="#waAccordion">
                            <div class="accordion-body bg-light">
                                <form class="form-wa-manual" data-basepath="<?= $basePath ?>">
                                    <input type="hidden" name="evento" value="<?= $key ?>">
                                    <div class="mb-3">
                                        <label class="small fw-bold mb-1">Cuerpo del Mensaje</label>
                                        <textarea name="mensaje" class="form-control" rows="4"><?= htmlspecialchars($data['mensaje']) ?></textarea>
                                        <div class="form-text small opacity-75">Variables: {NOMBRE}, {DIPLOMADO}, {URL_PORTAL}</div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="activo" <?= $data['activo']?'checked':'' ?>>
                                            <label class="form-check-label small fw-bold">Evento Habilitado</label>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-primary btn-sm px-4" onclick="saveWaTemplate(this)">Guardar</button>
                                            <button type="button" class="btn btn-success btn-sm px-4" onclick="testManualLink('<?= $key ?>', this)">
                                                <i class="bi bi-whatsapp me-1"></i> Probar Link
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                <h5 class="fw-bold mb-3 small text-muted text-uppercase">Trazabilidad de Envíos</h5>
                <div class="list-group list-group-flush small">
                    <?php foreach($logs as $l): ?>
                    <div class="list-group-item px-0 py-3 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($l['estudiante']) ?></span>
                            <span class="badge bg-success-subtle text-success" style="font-size: 0.65rem;">Enviado</span>
                        </div>
                        <div class="text-muted mt-1" style="font-size: 0.75rem;">
                            <i class="bi bi-clock me-1"></i> <?= date('d/m H:i', strtotime($l['fecha_envio'])) ?> • <?= $l['evento'] ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/whatsapp_manual.js?v=<?= time() ?>"></script>