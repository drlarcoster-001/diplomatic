<?php
/**
 * MÓDULO: FINANCIERO / APROBAR PAGOS A PROVEEDORES
 * ARCHIVO: app/views/financial/aprobar_pagos/manage.php
 * PROPÓSITO: Vista de solo lectura de la factura completa (mismo layout tipo
 *            factura que el módulo de Pagos a Proveedores), con botón Aprobar.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$p = $pago;
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_pagos_proveedores.css">

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
                <a href="<?= $basePath ?>/financial/aprobar-pagos" class="text-decoration-none text-muted">Aprobar Pagos a Proveedores</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary"><?= htmlspecialchars($p['numero_pago']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?= $basePath ?>/financial/aprobar-pagos" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="alert alert-warning d-flex justify-content-between align-items-center mb-3">
        <span>
            <i class="bi bi-exclamation-triangle me-2"></i>
            Revisa cuidadosamente antes de aprobar. Esta acción generará una orden de pago y no se puede deshacer fácilmente.
        </span>
        <button class="btn btn-success rounded-pill px-4 fw-bold flex-shrink-0 ms-3" id="btnAprobar">
            <i class="bi bi-check2-circle me-1"></i> Aprobar Pago
        </button>
    </div>

    <!-- ===================== FACTURA (SOLO LECTURA) ===================== -->
    <div class="pp-factura">

        <div class="pp-factura-header">
            <div class="pp-factura-proveedor">
                <div class="pp-factura-label">Proveedor</div>
                <div class="pp-factura-nombre"><?= htmlspecialchars($p['proveedor_nombre']) ?></div>
                <div class="pp-factura-sub"><?= htmlspecialchars($p['rif_cedula']) ?></div>
                <div class="pp-factura-bancario">
                    <i class="bi bi-bank me-1"></i>
                    <?php if ($p['banco']): ?>
                        <?= htmlspecialchars($p['banco']) ?> · <?= htmlspecialchars($p['tipo_cuenta'] ?? '') ?> · <?= htmlspecialchars($p['numero_cuenta'] ?? '') ?>
                        <br><span class="text-muted">Titular: <?= htmlspecialchars($p['titular_cuenta'] ?? '') ?></span>
                    <?php elseif ($p['banco_pago_movil']): ?>
                        Pago móvil: <?= htmlspecialchars($p['banco_pago_movil']) ?> · <?= htmlspecialchars($p['telefono_pago_movil'] ?? '') ?>
                        <br><span class="text-muted">CI/RIF: <?= htmlspecialchars($p['cedula_pago_movil'] ?? '') ?></span>
                    <?php else: ?>
                        <span class="text-muted">Sin datos bancarios registrados.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pp-factura-doc">
                <div class="pp-factura-numero"><?= htmlspecialchars($p['numero_pago']) ?></div>
                <span class="badge rounded-pill mb-2" style="font-size:11px;background:#E6F1FB;border:1px solid #378ADD;color:#0C447C">
                    PROCESADA
                </span>
                <div class="pp-factura-label mt-2">Fecha de Pago</div>
                <div class="pp-factura-sub"><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></div>
            </div>
        </div>

        <div class="pp-factura-body">
            <table class="pp-tabla-items">
                <thead>
                    <tr>
                        <th style="width:36px">#</th>
                        <th>Descripción</th>
                        <th style="width:90px" class="text-end">Cant.</th>
                        <th style="width:120px" class="text-end">Precio Unit.</th>
                        <th style="width:120px" class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $idx => $it): ?>
                        <tr>
                            <td class="text-muted small"><?= $idx + 1 ?></td>
                            <td><?= htmlspecialchars($it['descripcion']) ?></td>
                            <td class="text-end"><?= rtrim(rtrim(number_format((float)$it['cantidad'], 2), '0'), '.') ?></td>
                            <td class="text-end">$<?= number_format((float)$it['precio_unitario'], 2) ?></td>
                            <td class="text-end fw-bold">$<?= number_format((float)$it['subtotal'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!empty($ajustes)): ?>
                <div class="pp-ajustes-zona">
                    <div class="pp-factura-label mb-2">Impuestos / Deducciones</div>
                    <?php foreach ($ajustes as $a): ?>
                        <div class="pp-ajuste-chip">
                            <span><?= htmlspecialchars($a['nombre']) ?>
                                <span class="text-muted">
                                    (<?= $a['tipo'] === 'PORCENTAJE' ? number_format((float)$a['valor'],2).'%' : '$'.number_format((float)$a['valor'],2) ?>)
                                </span>
                            </span>
                            <span style="color:<?= $a['direccion']==='SUMA' ? '#085041' : '#A32D2D' ?>" class="fw-bold">
                                <?= $a['direccion']==='SUMA' ? '+' : '-' ?>$<?= number_format((float)$a['monto_calculado'], 2) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="pp-factura-footer">
            <div class="pp-totales-box">
                <div class="pp-total-linea">
                    <span>Subtotal</span>
                    <span>$<?= number_format((float)$p['subtotal'], 2) ?></span>
                </div>
                <div class="pp-total-final">
                    <span>TOTAL A PAGAR</span>
                    <span>$<?= number_format((float)$p['total_usd'], 2) ?></span>
                </div>
                <div class="pp-total-secundario">
                    <span>Tasa BCV: <?= number_format((float)$p['tasa_bcv'], 4) ?></span>
                    <span>Bs. <?= number_format((float)$p['total_bs'], 2) ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    window.APP_BASE_PATH = '<?= $basePath ?>';
    window.PAGO_ID       = <?= (int) $pagoId ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/financial_aprobar_pagos.js?v=<?= time() ?>"></script>