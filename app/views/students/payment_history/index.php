<?php
/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / VISTAS
 * ARCHIVO: app/views/students/payment_history/index.php
 * PROPÓSITO: Vista del historial de pagos del estudiante (inscripciones y cuotas).
 * VERSIÓN: 1.0.0 - Creación inicial del módulo de historial de pagos estudiantil.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$urlBase = (strpos($basePath, 'public') === false) ? $basePath . '/public' : $basePath;
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb shadow-sm py-2 px-4 bg-white rounded-pill border-0">
            <li class="breadcrumb-item small"><a href="<?= $urlBase ?>/dashboard" class="text-decoration-none text-muted">Inicio</a></li>
            <li class="breadcrumb-item small"><a href="<?= $urlBase ?>/students" class="text-decoration-none text-muted">Panel Estudiantil</a></li>
            <li class="breadcrumb-item small active fw-bold text-primary">Mis Pagos</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Mis Pagos</h2>
            <p class="text-muted small mb-0">Historial completo de todos tus pagos reportados.</p>
        </div>
        <a href="<?= $urlBase ?>/students" class="btn btn-light btn-sm rounded-pill px-3 shadow-sm border fw-bold text-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-3">
        <?php if (!empty($pagos)): ?>
            <?php foreach ($pagos as $p): 
                $statusColor = match($p['status']) {
                    'APPROVED' => 'success',
                    'REJECTED' => 'danger',
                    default    => 'warning'
                };
                $statusLabel = match($p['status']) {
                    'APPROVED' => 'Aprobado',
                    'REJECTED' => 'Rechazado',
                    default    => 'En Revisión'
                };
                $tipoColor = $p['tipo'] === 'INSCRIPCION' ? 'primary' : 'info';
            ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <span class="badge bg-<?= $tipoColor ?> bg-opacity-10 text-<?= $tipoColor ?> rounded-pill px-3 py-1 mb-2">
                                    <?= $p['tipo'] === 'INSCRIPCION' ? 'Inscripción' : 'Cuota' ?>
                                </span>
                                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($p['diplomado_name']) ?></h6>
                                <div class="text-muted small">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
                                    &nbsp;|&nbsp;
                                    <i class="fas fa-credit-card me-1"></i>
                                    <?= htmlspecialchars($p['method']) ?>
                                    <?php if ($p['reference_id']): ?>
                                        &nbsp;|&nbsp; Ref: <strong><?= htmlspecialchars($p['reference_id']) ?></strong>
                                    <?php endif; ?>
                                </div>
                                <?php if ($p['status'] === 'REJECTED' && $p['observation']): ?>
                                    <div class="mt-2 text-danger small">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        <?= htmlspecialchars($p['observation']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <div class="fs-5 fw-bold text-dark mb-1">
                                    <?= number_format((float)$p['amount'], 2) ?> <?= htmlspecialchars($p['currency']) ?>
                                </div>
                                <span class="badge bg-<?= $statusColor ?> rounded-pill px-3 py-1">
                                    <?= $statusLabel ?>
                                </span>
                                <?php if ($p['screenshot_path']): ?>
                                    <div class="mt-2">
                                        <button onclick="verComprobante('<?= $urlBase ?>/<?= htmlspecialchars($p['screenshot_path']) ?>')"
                                                class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                            <i class="fas fa-image me-1"></i> Ver comprobante
                                        </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="fas fa-receipt" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:16px"></i>
                <p class="fw-bold">No tienes pagos registrados aún.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Comprobante -->
<div class="modal fade" id="modalComprobante" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Comprobante de Pago</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <button onclick="zoomIn()" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-search-plus me-1"></i> Acercar
                    </button>
                    <button onclick="zoomOut()" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-search-minus me-1"></i> Alejar
                    </button>
                    <button onclick="resetZoom()" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fas fa-expand me-1"></i> Restablecer
                    </button>
                </div>
                <div style="overflow:auto;max-height:75vh">
                    <img id="imgComprobante" src="" class="img-fluid rounded-3" style="transition:transform 0.2s;transform-origin:top center">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let zoomLevel = 1;

function verComprobante(url) {
    document.getElementById('imgComprobante').src = url;
    resetZoom();
    new bootstrap.Modal(document.getElementById('modalComprobante')).show();
}

function zoomIn() {
    zoomLevel = Math.min(zoomLevel + 0.25, 3);
    document.getElementById('imgComprobante').style.transform = `scale(${zoomLevel})`;
}

function zoomOut() {
    zoomLevel = Math.max(zoomLevel - 0.25, 0.5);
    document.getElementById('imgComprobante').style.transform = `scale(${zoomLevel})`;
}

function resetZoom() {
    zoomLevel = 1;
    document.getElementById('imgComprobante').style.transform = 'scale(1)';
}
</script>