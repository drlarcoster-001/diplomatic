<?php
/**
 * MÓDULO: GESTIÓN FINANCIERA / PROVEEDORES
 * Archivo: app/views/financial/proveedores/edit.php
 * Versión: 1.0.0
 *
 * @var array $proveedor
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$p = $proveedor;

$bancos = [
    '0102 - BANCO DE VENEZUELA','0104 - VENEZOLANO DE CRÉDITO','0105 - BANCO MERCANTIL',
    '0108 - BBVA PROVINCIAL','0114 - BANCARIBE','0115 - BANCO EXTERIOR',
    '0128 - BANCO CARONÍ','0134 - BANCO BANESCO','0137 - BANCO SOFITASA',
    '0138 - BANCO PLAZA','0146 - BANGENTE','0151 - BFC BANCO FONDO COMÚN',
    '0156 - 100% BANCO','0157 - DELSUR BANCO UNIVERSAL','0163 - BANCO DEL TESORO',
    '0166 - BANCO AGRÍCOLA DE VENEZUELA','0168 - BANCRECER','0169 - MI BANCO',
    '0171 - BANCO ACTIVO','0172 - BANCAMIGA','0173 - BANCO INTERNACIONAL DE DESARROLLO',
    '0174 - BANPLUS','0175 - BANCO DIGITAL DE LOS TRABAJADORES',
    '0177 - BANFANB','0178 - N58 BANCO DIGITAL','0191 - BNC BANCO NACIONAL DE CRÉDITO'
];
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/financial_proveedores.css">

<div class="container-fluid py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/financial" class="text-decoration-none text-muted">Panel Financiero</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/financial/proveedores" class="text-decoration-none text-muted">Proveedores</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#fd7e14;">Editar</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Editar Proveedor</h2>
            <p class="text-muted small">ID: #<?= $p['id'] ?> · Registro: <?= date('d/m/Y', strtotime($p['created_at'])) ?></p>
        </div>
        <a href="<?= $basePath ?>/financial/proveedores" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <form id="formProveedor" action="<?= $basePath ?>/financial/proveedores/update" method="POST">
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <input type="hidden" name="redirect" id="redirect_input" value="stay">

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4" style="border-top: 3px solid #fd7e14 !important;">
                    <div class="card-header bg-white fw-bold py-3 px-4 border-bottom">
                        <i class="bi bi-building me-2" style="color:#fd7e14;"></i> Datos Generales
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">NOMBRE / RAZÓN SOCIAL</label>
                                <input type="text" name="nombre" id="input_nombre" class="form-control"
                                       value="<?= htmlspecialchars($p['nombre']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">TIPO</label>
                                <select name="tipo" class="form-select">
                                    <option value="Persona Natural" <?= $p['tipo'] === 'Persona Natural' ? 'selected' : '' ?>>Persona Natural</option>
                                    <option value="Empresa" <?= $p['tipo'] === 'Empresa' ? 'selected' : '' ?>>Empresa</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">RIF / CÉDULA</label>
                                <input type="text" name="rif_cedula" class="form-control"
                                       value="<?= htmlspecialchars($p['rif_cedula']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">EMAIL</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($p['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">TELÉFONO</label>
                                <input type="text" name="telefono" class="form-control"
                                       value="<?= htmlspecialchars($p['telefono'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">DIRECCIÓN</label>
                                <textarea name="direccion" class="form-control" rows="2"><?= htmlspecialchars($p['direccion'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold py-3 px-4 border-bottom">
                        <i class="bi bi-bank me-2" style="color:#fd7e14;"></i> Datos Bancarios
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">BANCO</label>
                                <select name="banco" class="form-select">
                                    <option value="">-- Seleccionar Banco --</option>
                                    <?php foreach ($bancos as $b): ?>
                                        <option value="<?= $b ?>" <?= ($p['banco'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">TIPO DE CUENTA</label>
                                <select name="tipo_cuenta" class="form-select">
                                    <option value="">Seleccione...</option>
                                    <option value="Corriente" <?= ($p['tipo_cuenta'] ?? '') === 'Corriente' ? 'selected' : '' ?>>Corriente</option>
                                    <option value="Ahorro"    <?= ($p['tipo_cuenta'] ?? '') === 'Ahorro'    ? 'selected' : '' ?>>Ahorro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">NÚMERO DE CUENTA</label>
                                <input type="text" name="numero_cuenta" class="form-control"
                                       value="<?= htmlspecialchars($p['numero_cuenta'] ?? '') ?>"
                                       placeholder="0102-0000-00-0000000000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">TITULAR DE LA CUENTA</label>
                                <input type="text" name="titular_cuenta" class="form-control"
                                       value="<?= htmlspecialchars($p['titular_cuenta'] ?? '') ?>">
                            </div>
                            <div class="col-12 mt-2"><hr>
                                <label class="form-label small fw-bold" style="color:#fd7e14;">PAGO MÓVIL</label>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">CÉDULA PAGO MÓVIL</label>
                                <input type="text" name="cedula_pago_movil" class="form-control"
                                       value="<?= htmlspecialchars($p['cedula_pago_movil'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">TELÉFONO PAGO MÓVIL</label>
                                <input type="text" name="telefono_pago_movil" class="form-control"
                                       value="<?= htmlspecialchars($p['telefono_pago_movil'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">BANCO PAGO MÓVIL</label>
                                <select name="banco_pago_movil" class="form-select">
                                    <option value="">-- Seleccionar Banco --</option>
                                    <?php foreach ($bancos as $b): ?>
                                        <option value="<?= $b ?>" <?= ($p['banco_pago_movil'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm" style="border-top: 3px solid #fd7e14 !important;">
                    <div class="card-body p-4 text-center">
                        <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                             style="width:80px; height:80px; background:rgba(253,126,20,0.1);">
                            <i class="bi bi-building fs-1" style="color:#fd7e14;"></i>
                        </div>
                        <h6 class="fw-bold" id="preview-nombre"><?= htmlspecialchars($p['nombre']) ?></h6>
                        <span class="badge rounded-pill px-3 py-1 text-white mb-3" style="background:#fd7e14;">
                            <?= htmlspecialchars($p['tipo']) ?>
                        </span>
                        <div class="d-grid gap-2 mt-3">
                            <button type="button" class="btn rounded-pill text-white w-100"
                                    style="background:#fd7e14;" onclick="submitForm('stay')">
                                <i class="bi bi-check-circle me-1"></i> Guardar y quedarme
                            </button>
                            <button type="button" class="btn btn-outline-secondary rounded-pill w-100"
                                    onclick="submitForm('index')">
                                <i class="bi bi-list-ul me-1"></i> Guardar e ir al directorio
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/financial_proveedores.js?v=<?= time() ?>"></script>
