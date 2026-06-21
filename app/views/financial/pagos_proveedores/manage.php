<?php
/**
 * MÓDULO: FINANCIERO / PAGOS A PROVEEDORES
 * ARCHIVO: app/views/financial/pagos_proveedores/manage.php
 * PROPÓSITO: Layout tipo factura real en tres zonas: encabezado (proveedor +
 *            documento), cuerpo (tabla de ítems tipo hoja de cálculo, captura
 *            rápida con Tab/Enter), y pie (totales alineados a la derecha).
 * VERSIÓN: 2.0.0 - Rediseño completo tipo factura.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$p = $pago;
$editable = $p['estado'] === 'BORRADOR';

$estadoBadge = match($p['estado']) {
    'BORRADOR'  => ['bg' => '#f1f3f5', 'borde' => '#ced4da', 'txt' => '#495057'],
    'PROCESADA' => ['bg' => '#E6F1FB', 'borde' => '#378ADD', 'txt' => '#0C447C'],
    'APROBADA'  => ['bg' => '#E1F5EE', 'borde' => '#1D9E75', 'txt' => '#085041'],
    default     => ['bg' => '#f1f3f5', 'borde' => '#ced4da', 'txt' => '#495057'],
};
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
                <a href="<?= $basePath ?>/financial/pagos-proveedores" class="text-decoration-none text-muted">Pagos a Proveedores</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary"><?= htmlspecialchars($p['numero_pago']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?= $basePath ?>/financial/pagos-proveedores" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
        <div class="d-flex gap-2">
            <?php if ($p['estado'] === 'BORRADOR'): ?>
                <button class="btn btn-outline-danger rounded-pill px-4 shadow-sm" id="btnDescartar">
                    <i class="bi bi-trash me-1"></i> Descartar
                </button>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnProcesar" style="background:#533AB7;border-color:#533AB7">
                    <i class="bi bi-check2-circle me-1"></i> Procesar Pago
                </button>
            <?php elseif (in_array($p['estado'], ['PROCESADA', 'APROBADA'], true)): ?>
                <button class="btn btn-outline-warning rounded-pill px-4 shadow-sm" id="btnReversar">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reversar
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===================== FACTURA ===================== -->
    <div class="pp-factura">

        <!-- ZONA 1: ENCABEZADO -->
        <div class="pp-factura-header">
            <div class="pp-factura-proveedor">
                <div class="pp-factura-label d-flex align-items-center gap-2">
                    Proveedor
                    <?php if ($editable): ?>
                        <button class="pp-cambiar-proveedor-btn" id="btnCambiarProveedor">
                            <i class="bi bi-pencil-square"></i> Cambiar
                        </button>
                    <?php endif; ?>
                </div>
                <div class="pp-factura-nombre" id="proveedorNombreDisplay"><?= htmlspecialchars($p['proveedor_nombre']) ?></div>
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
                <span class="badge rounded-pill mb-2" style="font-size:11px;background:<?= $estadoBadge['bg'] ?>;border:1px solid <?= $estadoBadge['borde'] ?>;color:<?= $estadoBadge['txt'] ?>">
                    <?= $p['estado'] ?>
                </span>
                <div class="pp-factura-label mt-2">Fecha de Pago</div>
                <div class="pp-factura-sub"><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></div>
            </div>
        </div>

        <!-- ZONA 2: TABLA DE ÍTEMS (captura rápida tipo hoja de cálculo) -->
        <div class="pp-factura-body">
            <table class="pp-tabla-items">
                <thead>
                    <tr>
                        <th style="width:36px">#</th>
                        <th>Descripción</th>
                        <th style="width:90px" class="text-end">Cant.</th>
                        <th style="width:120px" class="text-end">Precio Unit.</th>
                        <th style="width:120px" class="text-end">Subtotal</th>
                        <?php if ($editable): ?><th style="width:36px"></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="cuerpoItems">
                    <?php if (empty($items) && !$editable): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Sin ítems.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $idx => $it): ?>
                        <tr class="pp-fila-item" data-id="<?= $it['id'] ?>">
                            <td class="text-muted small"><?= $idx + 1 ?></td>
                            <td><?= htmlspecialchars($it['descripcion']) ?></td>
                            <td class="text-end"><?= rtrim(rtrim(number_format((float)$it['cantidad'], 2), '0'), '.') ?></td>
                            <td class="text-end">$<?= number_format((float)$it['precio_unitario'], 2) ?></td>
                            <td class="text-end fw-bold">$<?= number_format((float)$it['subtotal'], 2) ?></td>
                            <?php if ($editable): ?>
                                <td class="text-center">
                                    <button class="pp-fila-del btn-del-item" data-id="<?= $it['id'] ?>" title="Eliminar">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>

                    <?php if ($editable): ?>
                        <tr class="pp-fila-nueva" id="filaNuevaItem">
                            <td class="text-muted small" id="numFilaNueva"><?= count($items) + 1 ?></td>
                            <td>
                                <input type="text" id="inpDesc" class="pp-input" placeholder="Descripción del ítem...">
                            </td>
                            <td>
                                <input type="number" id="inpCant" class="pp-input text-end" value="1" step="0.01" min="0.01">
                            </td>
                            <td>
                                <input type="number" id="inpPrecio" class="pp-input text-end" placeholder="0.00" step="0.01" min="0">
                            </td>
                            <td class="text-end fw-bold text-muted" id="previewSubtotal">$0.00</td>
                            <td class="text-center">
                                <button class="pp-fila-add" id="btnAddItem" title="Agregar (Enter)">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($editable): ?>
                <div class="pp-hint">
                    <i class="bi bi-keyboard me-1"></i>
                    Escribe y presiona <kbd>Tab</kbd> para avanzar, <kbd>Enter</kbd> para agregar la fila.
                </div>
            <?php endif; ?>

            <!-- AJUSTES -->
            <div class="pp-ajustes-zona">
                <div class="pp-factura-label mb-2">Impuestos / Deducciones</div>

                <div id="listaAjustes">
                    <?php foreach ($ajustes as $a): ?>
                        <div class="pp-ajuste-chip" data-id="<?= $a['id'] ?>">
                            <span><?= htmlspecialchars($a['nombre']) ?>
                                <span class="text-muted">
                                    (<?= $a['tipo'] === 'PORCENTAJE' ? number_format((float)$a['valor'],2).'%' : '$'.number_format((float)$a['valor'],2) ?>)
                                </span>
                            </span>
                            <span style="color:<?= $a['direccion']==='SUMA' ? '#085041' : '#A32D2D' ?>" class="fw-bold">
                                <?= $a['direccion']==='SUMA' ? '+' : '-' ?>$<?= number_format((float)$a['monto_calculado'], 2) ?>
                            </span>
                            <?php if ($editable): ?>
                                <button class="pp-chip-del btn-del-ajuste" data-id="<?= $a['id'] ?>"><i class="bi bi-x"></i></button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($editable): ?>
                    <div class="pp-ajuste-presets">
                        <button class="pp-preset-btn" data-nombre="IVA" data-tipo="PORCENTAJE" data-dir="SUMA" data-valor="16">
                            + IVA 16%
                        </button>
                        <button class="pp-preset-btn" data-nombre="Retención de IVA" data-tipo="PORCENTAJE" data-dir="RESTA" data-valor="75">
                            − Retención IVA 75%
                        </button>
                        <button class="pp-preset-btn" data-nombre="Retención ISLR" data-tipo="PORCENTAJE" data-dir="RESTA" data-valor="2">
                            − Retención ISLR 2%
                        </button>
                        <button class="pp-preset-btn pp-preset-custom" id="btnAjusteCustom">
                            <i class="bi bi-plus-lg"></i> Otro ajuste
                        </button>
                    </div>

                    <div class="pp-ajuste-form" id="formAjusteCustom" style="display:none">
                        <input type="text" id="ajusteNombre" class="pp-input-sm" placeholder="Nombre">
                        <select id="ajusteTipo" class="pp-input-sm">
                            <option value="PORCENTAJE">%</option>
                            <option value="MONTO_FIJO">$ fijo</option>
                        </select>
                        <select id="ajusteDireccion" class="pp-input-sm">
                            <option value="SUMA">Suma (+)</option>
                            <option value="RESTA">Resta (-)</option>
                        </select>
                        <input type="number" id="ajusteValor" class="pp-input-sm" placeholder="Valor" step="0.01" min="0">
                        <button class="btn btn-sm btn-dark" id="btnAddAjusteCustom">Agregar</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ZONA 3: PIE (totales) -->
        <div class="pp-factura-footer">
            <div class="pp-totales-box">
                <div class="pp-total-linea">
                    <span>Subtotal</span>
                    <span id="totSubtotal">$<?= number_format((float)$p['subtotal'], 2) ?></span>
                </div>
                <div id="totalesAjustes">
                    <?php foreach ($ajustes as $a): ?>
                        <div class="pp-total-linea text-muted small">
                            <span><?= htmlspecialchars($a['nombre']) ?></span>
                            <span style="color:<?= $a['direccion']==='SUMA' ? '#085041' : '#A32D2D' ?>">
                                <?= $a['direccion']==='SUMA' ? '+' : '-' ?>$<?= number_format((float)$a['monto_calculado'], 2) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="pp-total-final">
                    <span>TOTAL A PAGAR</span>
                    <span id="totUsd">$<?= number_format((float)$p['total_usd'], 2) ?></span>
                </div>
                <div class="pp-total-secundario">
                    <span>Tasa BCV: <span id="totTasa"><?= number_format((float)$p['tasa_bcv'], 4) ?></span></span>
                    <span id="totBs">Bs. <?= number_format((float)$p['total_bs'], 2) ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    window.APP_BASE_PATH = '<?= $basePath ?>';
    window.PAGO_ID       = <?= (int) $pagoId ?>;
    window.PROVEEDORES   = <?= json_encode(array_map(fn($pr) => ['id' => $pr['id'], 'nombre' => $pr['nombre'] . ' — ' . $pr['rif_cedula']], $proveedores)) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/financial_pagos_proveedores_manage.js?v=<?= time() ?>"></script>