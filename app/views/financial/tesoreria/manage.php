<?php
/**
 * MÓDULO: FINANCIERO / TESORERÍA
 * ARCHIVO: app/views/financial/tesoreria/manage.php
 * PROPÓSITO: Muestra los datos de la orden de pago + formulario de "Pagar"
 *            con campos que cambian según el medio de pago elegido, o el
 *            detalle ya pagado si estado=PAGADO. Botón Reversar si PENDIENTE.
 *            Visor de comprobante flotante, draggable, con zoom y descarga.
 * VERSIÓN: 1.1.0 - Visor de comprobante flotante draggable con zoom +-
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$p = $pago;

$estadoBadges = [
    'PENDIENTE' => ['bg' => '#FAEEDA', 'borde' => '#BA7517', 'txt' => '#633806'],
    'PAGADO'    => ['bg' => '#E1F5EE', 'borde' => '#1D9E75', 'txt' => '#085041'],
    'ANULADO'   => ['bg' => '#f1f3f5', 'borde' => '#ced4da', 'txt' => '#495057'],
];
$badge = $estadoBadges[$p['estado']] ?? $estadoBadges['ANULADO'];
$tipoLabel = ['NOMINA' => 'Nómina', 'PROVEEDOR' => 'Proveedor', 'DIRECTA' => 'Directa'][$p['tipo']] ?? $p['tipo'];
$medioLabel = ['EFECTIVO' => 'Efectivo', 'TRANSFERENCIA' => 'Transferencia', 'PAGO_MOVIL' => 'Pago Móvil'];

$denominacionesUsd = [100, 50, 20, 10, 5, 1];
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_tesoreria.css">
<style>
/* ── VISOR FLOTANTE ─────────────────────────────── */
#visorComprobante {
    display: none;
    position: fixed;
    top: 80px;
    right: 24px;
    width: 420px;
    min-width: 280px;
    max-width: 90vw;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    z-index: 9999;
    user-select: none;
}
#visorComprobante.visible { display: flex; flex-direction: column; }
#visorHeader {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: #533AB7;
    color: #fff;
    border-radius: 12px 12px 0 0;
    cursor: move;
    font-size: 13px;
    font-weight: 600;
    gap: 8px;
}
#visorHeader .visor-titulo { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
#visorControls {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-bottom: 1px solid #f0f0f0;
    background: #fafafa;
}
#visorControls button {
    border: 1px solid #dee2e6;
    background: #fff;
    border-radius: 6px;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 15px;
    transition: background 0.15s;
}
#visorControls button:hover { background: #f0f0f0; }
#visorZoomLabel {
    font-size: 12px;
    color: #555;
    min-width: 40px;
    text-align: center;
}
#visorControls a.btn-descargar {
    margin-left: auto;
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid #533AB7;
    color: #533AB7;
    text-decoration: none;
    white-space: nowrap;
}
#visorControls a.btn-descargar:hover { background: #533AB7; color: #fff; }
#visorImgWrap {
    overflow: auto;
    max-height: 60vh;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 10px;
    background: #f8f8f8;
    border-radius: 0 0 12px 12px;
}
#visorImg {
    transform-origin: top center;
    transition: transform 0.2s;
    max-width: 100%;
    border-radius: 4px;
    cursor: grab;
}
#visorImg:active { cursor: grabbing; }
#visorResizer {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 16px;
    height: 16px;
    cursor: se-resize;
    background: linear-gradient(135deg, transparent 50%, #aaa 50%);
    border-radius: 0 0 12px 0;
}
</style>

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
                <a href="<?= $basePath ?>/financial/tesoreria" class="text-decoration-none text-muted">Tesorería</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary"><?= htmlspecialchars($p['numero_orden']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?= $basePath ?>/financial/tesoreria" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
        <?php if ($p['estado'] === 'PENDIENTE'): ?>
            <button class="btn btn-outline-warning rounded-pill px-4 shadow-sm" id="btnReversar">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reversar a Orden de Pago
            </button>
        <?php elseif ($p['estado'] === 'PAGADO'): ?>
            <button class="btn btn-outline-danger rounded-pill px-4 shadow-sm" id="btnReversarPago">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reversar Pago
            </button>
        <?php endif; ?>
    </div>

    <div class="tes-detalle">

        <div class="tes-header">
            <div>
                <div class="tes-label">Orden de Pago</div>
                <div class="tes-numero"><?= htmlspecialchars($p['numero_orden']) ?></div>
                <span class="badge rounded-pill mt-1" style="font-size:11px;background:<?= $badge['bg'] ?>;border:1px solid <?= $badge['borde'] ?>;color:<?= $badge['txt'] ?>">
                    <?= $p['estado'] ?>
                </span>
            </div>
            <div class="text-end">
                <div class="tes-label">Tipo</div>
                <div class="tes-valor"><?= $tipoLabel ?></div>
                <div class="tes-label mt-2">Destinatario</div>
                <div class="small text-muted"><?= htmlspecialchars($p['destinatario'] ?? '—') ?></div>
            </div>
        </div>

        <div class="tes-body">
            <div class="tes-totales mb-4">
                <div class="tes-total-linea">
                    <span>Concepto</span>
                    <span><?= htmlspecialchars($p['concepto'] ?: '—') ?></span>
                </div>
                <div class="tes-total-final">
                    <span>MONTO A PAGAR</span>
                    <span>$<?= number_format((float)$p['monto_usd'], 2) ?></span>
                </div>
                <div class="tes-total-secundario">
                    <span>Tasa BCV: <?= number_format((float)$p['tasa_bcv'], 4) ?></span>
                    <span>Bs. <?= number_format((float)$p['monto_bs'], 2) ?></span>
                </div>
            </div>

            <?php if ($p['estado'] === 'PENDIENTE'): ?>
                <div class="tes-form-pago">
                    <div class="tes-label mb-3">Registrar Pago</div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Medio de Pago</label>
                        <select id="medioPago" class="form-select">
                            <option value="">Selecciona...</option>
                            <option value="EFECTIVO">Efectivo</option>
                            <option value="TRANSFERENCIA">Transferencia</option>
                            <option value="PAGO_MOVIL">Pago Móvil</option>
                        </select>
                    </div>

                    <div id="bloqueEfectivo" style="display:none">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Moneda</label>
                            <select id="monedaEfectivo" class="form-select">
                                <option value="">Selecciona...</option>
                                <option value="USD">Dólares (USD)</option>
                                <option value="BS">Bolívares (Bs)</option>
                            </select>
                        </div>
                        <div id="arqueoUsd" style="display:none">
                            <label class="form-label fw-bold small mb-2">Arqueo de Billetes (USD)</label>
                            <div class="tes-arqueo-grid">
                                <?php foreach ($denominacionesUsd as $den): ?>
                                    <div class="tes-arqueo-item">
                                        <span class="tes-arqueo-den">$<?= $den ?></span>
                                        <input type="number" class="form-control form-control-sm arqueo-input"
                                               data-den="<?= $den ?>" min="0" value="0">
                                        <span class="tes-arqueo-sub" id="subDen<?= $den ?>">$0</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="tes-arqueo-total">
                                <span>Total contado:</span>
                                <span id="totalArqueoUsd">$0.00</span>
                                <span class="small text-muted" id="diffArqueoUsd"></span>
                            </div>
                        </div>
                        <div id="arqueoBs" style="display:none">
                            <label class="form-label fw-bold small">Descripción del Arqueo (Bs)</label>
                            <textarea id="arqueoTextoBs" class="form-control" rows="3"
                                      placeholder="Describe el conteo de billetes/monedas en bolívares..."></textarea>
                        </div>
                    </div>

                    <div id="bloqueBancario" style="display:none">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Banco</label>
                                <input type="text" id="bancoInput" class="form-control" value="<?= htmlspecialchars($p['banco_origen'] ?? $p['banco_movil_origen'] ?? '') ?>">
                            </div>
                            <div class="col-md-6" id="campoCuenta">
                                <label class="form-label fw-bold small">N° de Cuenta</label>
                                <input type="text" id="cuentaInput" class="form-control" value="<?= htmlspecialchars($p['cuenta_origen'] ?? '') ?>">
                            </div>
                            <div class="col-md-6" id="campoTelefono" style="display:none">
                                <label class="form-label fw-bold small">Teléfono</label>
                                <input type="text" id="telefonoInput" class="form-control" value="<?= htmlspecialchars($p['telefono_origen'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Nombre del Destinatario</label>
                                <input type="text" id="destinatarioInput" class="form-control" value="<?= htmlspecialchars($p['titular_origen'] ?? $p['destinatario'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">N° de Referencia</label>
                                <input type="text" id="referenciaInput" class="form-control" placeholder="Número de referencia de la operación">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Captura del Comprobante</label>
                                <input type="file" id="comprobanteInput" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-success rounded-pill px-4 fw-bold mt-4" id="btnPagar">
                        <i class="bi bi-check2-circle me-1"></i> Confirmar Pago
                    </button>
                </div>

            <?php elseif ($p['estado'] === 'PAGADO'): ?>
                <div class="tes-pagado-detalle">
                    <div class="tes-label mb-2">Datos del Pago</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="small text-muted">Medio de Pago</div>
                            <div class="fw-bold"><?= $medioLabel[$p['medio_pago']] ?? $p['medio_pago'] ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Fecha de Pago</div>
                            <div class="fw-bold"><?= $p['paid_at'] ? date('d/m/Y h:i A', strtotime($p['paid_at'])) : '—' ?></div>
                        </div>

                        <?php if ($p['medio_pago'] === 'EFECTIVO'): ?>
                            <div class="col-md-6">
                                <div class="small text-muted">Moneda</div>
                                <div class="fw-bold"><?= $p['moneda_efectivo'] === 'USD' ? 'Dólares' : 'Bolívares' ?></div>
                            </div>
                            <div class="col-12">
                                <div class="small text-muted">Arqueo</div>
                                <div class="tes-arqueo-resultado"><?= nl2br(htmlspecialchars($p['arqueo_detalle'] ?? '—')) ?></div>
                            </div>
                        <?php else: ?>
                            <div class="col-md-6">
                                <div class="small text-muted">Banco</div>
                                <div class="fw-bold"><?= htmlspecialchars($p['banco'] ?? '—') ?></div>
                            </div>
                            <?php if ($p['medio_pago'] === 'TRANSFERENCIA'): ?>
                                <div class="col-md-6">
                                    <div class="small text-muted">Cuenta</div>
                                    <div class="fw-bold"><?= htmlspecialchars($p['cuenta'] ?? '—') ?></div>
                                </div>
                            <?php else: ?>
                                <div class="col-md-6">
                                    <div class="small text-muted">Teléfono</div>
                                    <div class="fw-bold"><?= htmlspecialchars($p['telefono'] ?? '—') ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-6">
                                <div class="small text-muted">Destinatario</div>
                                <div class="fw-bold"><?= htmlspecialchars($p['nombre_destinatario'] ?? '—') ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted">Referencia</div>
                                <div class="fw-bold"><?= htmlspecialchars($p['referencia'] ?? '—') ?></div>
                            </div>
                            <?php if ($p['comprobante_path']): ?>
                                <div class="col-12">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary rounded-pill"
                                            id="btnVerComprobante"
                                            data-path="<?= htmlspecialchars($basePath . $p['comprobante_path']) ?>">
                                        <i class="bi bi-eye me-1"></i> Ver Comprobante
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- VISOR FLOTANTE DRAGGABLE -->
<div id="visorComprobante">
    <div id="visorHeader">
        <i class="bi bi-arrows-move me-1"></i>
        <span class="visor-titulo">Comprobante de Pago</span>
        <button type="button" id="btnCerrarVisor"
                style="background:rgba(255,255,255,0.2);border:none;color:#fff;border-radius:6px;width:24px;height:24px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;padding:0">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div id="visorControls">
        <button type="button" id="btnZoomOut" title="Alejar"><i class="bi bi-dash-lg"></i></button>
        <span id="visorZoomLabel">100%</span>
        <button type="button" id="btnZoomIn" title="Acercar"><i class="bi bi-plus-lg"></i></button>
        <button type="button" id="btnZoomReset" title="Restablecer" style="font-size:12px">1:1</button>
        <a id="linkDescargar" href="#" download target="_blank" class="btn-descargar">
            <i class="bi bi-download me-1"></i>Descargar
        </a>
    </div>
    <div id="visorImgWrap">
        <img id="visorImg" src="" alt="Comprobante">
    </div>
    <div id="visorResizer"></div>
</div>

<script>
    window.APP_BASE_PATH  = '<?= $basePath ?>';
    window.TESORERIA_ID   = <?= (int) $p['id'] ?>;
    window.ORDEN_PAGO_ID  = <?= (int) $p['orden_id'] ?>;
    window.MONTO_USD      = <?= (float) $p['monto_usd'] ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/financial_tesoreria.js?v=<?= time() ?>"></script>