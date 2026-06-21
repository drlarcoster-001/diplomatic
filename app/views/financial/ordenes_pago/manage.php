<?php
/**
 * MÓDULO: FINANCIERO / ÓRDENES DE PAGO
 * ARCHIVO: app/views/financial/ordenes_pago/manage.php
 * PROPÓSITO: Detalle completo de la orden + botones Aprobar/Rechazar/Anular
 *            (según PENDIENTE) o Reversar (según APROBADA/RECHAZADA).
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$o = $orden;

$estadoBadges = [
    'PENDIENTE' => ['bg' => '#FAEEDA', 'borde' => '#BA7517', 'txt' => '#633806'],
    'APROBADA'  => ['bg' => '#E1F5EE', 'borde' => '#1D9E75', 'txt' => '#085041'],
    'RECHAZADA' => ['bg' => '#FCEBEB', 'borde' => '#E24B4A', 'txt' => '#A32D2D'],
    'ANULADA'   => ['bg' => '#f1f3f5', 'borde' => '#ced4da', 'txt' => '#495057'],
    'PAGADA'    => ['bg' => '#E6F1FB', 'borde' => '#378ADD', 'txt' => '#0C447C'],
];
$badge = $estadoBadges[$o['estado']] ?? $estadoBadges['ANULADA'];
$tipoLabel = ['NOMINA' => 'Nómina', 'PROVEEDOR' => 'Proveedor', 'DIRECTA' => 'Directa'][$o['tipo']] ?? $o['tipo'];
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_ordenes_pago.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i>Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/financial" class="text-decoration-none text-muted">Financiero</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/financial/ordenes-pago" class="text-decoration-none text-muted">Órdenes de Pago</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary"><?= htmlspecialchars($o['numero_orden']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-3">
        <a href="<?= $basePath ?>/financial/ordenes-pago" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
        <div class="d-flex gap-2">
            <?php if ($o['estado'] === 'PENDIENTE'): ?>
                <button class="btn btn-outline-danger rounded-pill px-4 shadow-sm" id="btnAnular">
                    <i class="bi bi-x-octagon me-1"></i> Anular
                </button>
                <button class="btn btn-outline-warning rounded-pill px-4 shadow-sm" id="btnRechazar">
                    <i class="bi bi-hand-thumbs-down me-1"></i> Rechazar
                </button>
                <button class="btn btn-success rounded-pill px-4 shadow-sm fw-bold" id="btnAprobar">
                    <i class="bi bi-check2-circle me-1"></i> Aprobar
                </button>
            <?php elseif ($o['estado'] === 'APROBADA'): ?>
                <button class="btn btn-outline-danger rounded-pill px-4 shadow-sm" id="btnAnular">
                    <i class="bi bi-x-octagon me-1"></i> Anular
                </button>
                <button class="btn btn-outline-warning rounded-pill px-4 shadow-sm" id="btnReversar">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reversar
                </button>
            <?php elseif ($o['estado'] === 'RECHAZADA' && $o['tipo'] === 'DIRECTA'): ?>
                <button class="btn btn-outline-warning rounded-pill px-4 shadow-sm" id="btnReversar">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reversar
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="op-detalle">

        <div class="op-header">
            <div>
                <div class="op-label">Orden de Pago</div>
                <div class="op-numero"><?= htmlspecialchars($o['numero_orden']) ?></div>
                <span class="badge rounded-pill mt-1" style="font-size:11px;background:<?= $badge['bg'] ?>;border:1px solid <?= $badge['borde'] ?>;color:<?= $badge['txt'] ?>">
                    <?= $o['estado'] ?>
                </span>
            </div>
            <div class="text-end">
                <div class="op-label">Tipo</div>
                <div class="op-tipo-valor"><?= $tipoLabel ?></div>
                <div class="op-label mt-2">Documento Origen</div>
                <div class="small text-muted"><?= htmlspecialchars($o['documento_origen'] ?? '—') ?></div>
            </div>
        </div>

        <?php if ($o['estado'] === 'RECHAZADA' && $o['motivo_rechazo']): ?>
            <div class="op-alerta-rechazo">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Motivo del rechazo:</strong> <?= htmlspecialchars($o['motivo_rechazo']) ?>
                <?php if ($o['tipo'] !== 'DIRECTA'): ?>
                    <br><span class="small">El registro de origen ya volvió a "Pendientes de Aprobar" en su módulo correspondiente.</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="op-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="op-label mb-1">Destinatario</div>
                    <div class="op-valor"><?= htmlspecialchars($o['destinatario'] ?? '—') ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($o['destinatario_doc'] ?? '') ?></div>

                    <?php if (in_array($o['tipo'], ['PROVEEDOR', 'DIRECTA'], true)): ?>
                        <div class="op-bancario mt-3">
                            <i class="bi bi-bank me-1"></i>
                            <?php if ($o['banco']): ?>
                                <?= htmlspecialchars($o['banco']) ?> · <?= htmlspecialchars($o['tipo_cuenta'] ?? '') ?> · <?= htmlspecialchars($o['numero_cuenta'] ?? '') ?>
                                <br><span class="text-muted">Titular: <?= htmlspecialchars($o['titular_cuenta'] ?? '') ?></span>
                            <?php elseif ($o['banco_pago_movil']): ?>
                                Pago móvil: <?= htmlspecialchars($o['banco_pago_movil']) ?> · <?= htmlspecialchars($o['telefono_pago_movil'] ?? '') ?>
                            <?php else: ?>
                                <span class="text-muted">Sin datos bancarios registrados.</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <div class="op-label mb-1">Concepto</div>
                    <div class="op-valor"><?= htmlspecialchars($o['concepto'] ?: ('Nómina: ' . ($o['documento_origen'] ?? ''))) ?></div>

                    <div class="op-label mb-1 mt-3">Fecha de Pago</div>
                    <div class="op-valor"><?= date('d/m/Y', strtotime($o['fecha_pago'])) ?></div>
                </div>
            </div>

            <div class="op-totales">
                <div class="op-total-linea">
                    <span>Tasa BCV</span>
                    <span><?= number_format((float)$o['tasa_bcv'], 4) ?></span>
                </div>
                <div class="op-total-final">
                    <span>MONTO A PAGAR</span>
                    <span>$<?= number_format((float)$o['monto_usd'], 2) ?></span>
                </div>
                <div class="op-total-secundario">
                    <span>Equivalente en Bs.</span>
                    <span>Bs. <?= number_format((float)$o['monto_bs'], 2) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.APP_BASE_PATH = '<?= $basePath ?>';
    window.ORDEN_ID       = <?= (int) $o['id'] ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/financial_ordenes_pago_manage.js?v=<?= time() ?>"></script>