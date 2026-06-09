<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * Archivo: app/views/resources/personal/edit.php
 * Propósito: Expediente completo del personal operativo con navegación por tabs.
 * Versión: 1.5.0 - Tab de Contratos agregado.
 *
 * @var array  $persona
 * @var array  $tipos
 * @var array  $contratos
 */
$p         = $persona;
$basePath  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$tabActivo = $_GET['tab'] ?? 'datos';

$avatar = !empty($p['foto'])
    ? '/diplomatic/public/' . ltrim($p['foto'], '/')
    : (!empty($p['profesor_foto'])
        ? $p['profesor_foto']
        : 'https://ui-avatars.com/api/?name=' . urlencode($p['first_name'] . '+' . $p['last_name']) . '&background=a855f7&color=fff&size=150&bold=true');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_personal.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">

<div class="container-fluid py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources" class="text-decoration-none text-muted">Panel de Recursos</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources/personal" class="text-decoration-none text-muted">Personal</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#a855f7;">Expediente</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Expediente de Personal</h2>
            <p class="text-muted small">
                ID: #<?= $p['id'] ?> |
                <?php if (!empty($p['expediente'])): ?>
                    <span style="color:#a855f7; font-weight:600;"><?= htmlspecialchars($p['expediente']) ?></span> |
                <?php endif; ?>
                Registro: <?= date('d/m/Y', strtotime($p['created_at'])) ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/resources/personal" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm btn-carnet"
                    data-id="<?= $p['id'] ?>">
                <i class="bi bi-person-badge me-1"></i> Carnet
            </button>
            <a href="<?= $basePath ?>/resources/personal/expediente?id=<?= $p['id'] ?>"
               target="_blank"
               class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-file-person me-1"></i> Expediente
            </a>
            <button class="btn rounded-pill px-4 shadow-sm text-white" style="background:#a855f7;"
                    onclick="guardarConTab();">
                <i class="bi bi-check-circle me-1"></i> Guardar
            </button>
        </div>
    </div>

    <div class="row g-4">

        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm text-center p-4" style="border-top: 3px solid #a855f7 !important;">
                <div class="position-relative mx-auto mb-3" style="width:130px; height:130px;">
                    <img src="<?= htmlspecialchars($avatar) ?>"
                         id="profile-img-preview"
                         class="rounded-circle object-fit-cover shadow-sm w-100 h-100 border border-3 border-white"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($p['first_name'] . '+' . $p['last_name']) ?>&background=a855f7&color=fff&size=150'">
                    <label for="inputFotoUpload"
                           class="btn btn-sm rounded-circle position-absolute bottom-0 end-0 shadow text-white"
                           style="background:#a855f7; width:35px; height:35px; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-camera"></i>
                    </label>
                </div>
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></h6>
                <p class="text-muted small mb-1"><?= htmlspecialchars($p['document_id']) ?></p>
                <?php if (!empty($p['expediente'])): ?>
                    <p class="mb-2" style="font-size:0.7rem; color:#a855f7; font-weight:600;"><?= htmlspecialchars($p['expediente']) ?></p>
                <?php endif; ?>
                <span class="badge rounded-pill px-3 py-2 w-100 mb-3 text-white" style="background:#a855f7; font-size:0.8rem;">
                    <?= htmlspecialchars($p['tipo_nombre']) ?>
                </span>
                <?php if (!empty($p['fecha_inicio'])): ?>
                    <div class="small text-muted"><i class="bi bi-calendar3 me-1"></i> Desde <?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($p['fecha_fin'])): ?>
                    <div class="small text-muted"><i class="bi bi-calendar-x me-1"></i> Hasta <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?></div>
                <?php endif; ?>

                <!-- Resumen contratos -->
                <?php if (!empty($contratos)): ?>
                    <div class="mt-3 pt-3 border-top">
                        <div class="small text-muted mb-1">Contratos</div>
                        <span class="badge rounded-pill px-3 py-1 text-white" style="background:#198754;">
                            <?= count($contratos) ?> generado(s)
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabs -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-3 px-4">
                    <ul class="nav nav-tabs fw-bold" id="expedienteTabs">
                        <li class="nav-item">
                            <button class="nav-link <?= $tabActivo === 'datos' ? 'active' : '' ?>"
                                    data-bs-toggle="tab" data-bs-target="#tab-datos" type="button">
                                <i class="bi bi-person-vcard me-2"></i> Datos Personales
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link <?= $tabActivo === 'academico' ? 'active' : '' ?>"
                                    data-bs-toggle="tab" data-bs-target="#tab-academico" type="button">
                                <i class="bi bi-mortarboard me-2"></i> Académico
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link <?= $tabActivo === 'operativo' ? 'active' : '' ?>"
                                    data-bs-toggle="tab" data-bs-target="#tab-operativo" type="button">
                                <i class="bi bi-briefcase me-2"></i> Operativo
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link <?= $tabActivo === 'contratos' ? 'active' : '' ?>"
                                    data-bs-toggle="tab" data-bs-target="#tab-contratos" type="button">
                                <i class="bi bi-file-earmark-check me-2"></i> Contratos
                                <?php if (!empty($contratos)): ?>
                                    <span class="badge rounded-pill ms-1 text-white" style="background:#198754; font-size:0.7rem;">
                                        <?= count($contratos) ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4 bg-light bg-opacity-50">
                    <form id="formPersonal" action="<?= $basePath ?>/resources/personal/update" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="tab" value="<?= $tabActivo ?>">
                        <input type="file" name="foto" id="inputFotoUpload" accept="image/*" style="display:none;" onchange="previewFoto(this)">

                        <div class="tab-content">

                            <!-- TAB: Datos Personales -->
                            <div class="tab-pane fade <?= $tabActivo === 'datos' ? 'show active' : '' ?>" id="tab-datos">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">CÉDULA</label>
                                        <input type="text" name="document_id" class="form-control" value="<?= htmlspecialchars($p['document_id']) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">NOMBRES</label>
                                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($p['first_name']) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">APELLIDOS</label>
                                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($p['last_name']) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">FECHA DE NACIMIENTO</label>
                                        <input type="date" name="fecha_nacimiento" class="form-control" value="<?= $p['fecha_nacimiento'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">ESTADO CIVIL</label>
                                        <select name="estado_civil" class="form-select">
                                            <option value="">Seleccione...</option>
                                            <?php foreach (['Soltero','Casado','Divorciado','Viudo','Unión estable'] as $ec): ?>
                                                <option value="<?= $ec ?>" <?= ($p['estado_civil'] ?? '') === $ec ? 'selected' : '' ?>><?= $ec ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">EMAIL</label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($p['email'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">TELÉFONO LOCAL</label>
                                        <input type="text" name="telefono_local" class="form-control" value="<?= htmlspecialchars($p['telefono_local'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">TELÉFONO CELULAR</label>
                                        <input type="text" name="telefono_celular" class="form-control" value="<?= htmlspecialchars($p['telefono_celular'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">DIRECCIÓN</label>
                                        <textarea name="direccion" class="form-control" rows="2"><?= htmlspecialchars($p['direccion'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">HOJA DE RESUMEN CURRICULAR</label>
                                        <?php if (!empty($p['cv_path'])): ?>
                                            <div class="mb-2">
                                                <a href="/diplomatic/public/<?= htmlspecialchars($p['cv_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                                    <i class="bi bi-file-earmark-person me-1"></i> Ver CV actual
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" name="cv" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                        <div class="form-text">PDF o imagen. Se adjuntará en el expediente.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: Académico -->
                            <div class="tab-pane fade <?= $tabActivo === 'academico' ? 'show active' : '' ?>" id="tab-academico">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">GRADO DE INSTRUCCIÓN</label>
                                        <select name="grado_instruccion" class="form-select">
                                            <option value="">Seleccione...</option>
                                            <?php foreach (['Primaria','Secundaria','TSU','Pregrado','Especialista','Magister','Doctorado','Postdoctorado','No aplica'] as $g): ?>
                                                <option value="<?= $g ?>" <?= ($p['grado_instruccion'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">ESTUDIOS ADICIONALES</label>
                                        <textarea name="estudios_adicionales" class="form-control" rows="4"
                                                  placeholder="Cursos, certificaciones, diplomados adicionales..."><?= htmlspecialchars($p['estudios_adicionales'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB: Operativo -->
                            <div class="tab-pane fade <?= $tabActivo === 'operativo' ? 'show active' : '' ?>" id="tab-operativo">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold" style="color:#a855f7;">TIPO DE PERSONAL</label>
                                        <select name="tipo_personal_id" class="form-select" required>
                                            <?php foreach ($tipos as $t): ?>
                                                <option value="<?= $t['id'] ?>" <?= $p['tipo_personal_id'] == $t['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($t['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">FECHA DE INICIO</label>
                                        <input type="date" name="fecha_inicio" class="form-control" value="<?= $p['fecha_inicio'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">FECHA DE FIN</label>
                                        <input type="date" name="fecha_fin" class="form-control" value="<?= $p['fecha_fin'] ?? '' ?>">
                                    </div>
                                    <?php if (!empty($p['expediente'])): ?>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">CÓDIGO DE EXPEDIENTE</label>
                                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($p['expediente']) ?>" readonly>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-12 mt-2">
                                        <hr>
                                        <label class="form-label small fw-bold" style="color:#a855f7;">DATOS BANCARIOS</label>
                                    </div>
                                    <div class="col-md-6">
    <label class="form-label small fw-bold">BANCO</label>
    <select name="banco" class="form-select">
        <option value="">-- Seleccionar Banco --</option>
        <option value="0102 - BANCO DE VENEZUELA" <?= ($p['banco'] ?? '') === '0102 - BANCO DE VENEZUELA' ? 'selected' : '' ?>>0102 - BANCO DE VENEZUELA</option>
        <option value="0104 - VENEZOLANO DE CRÉDITO" <?= ($p['banco'] ?? '') === '0104 - VENEZOLANO DE CRÉDITO' ? 'selected' : '' ?>>0104 - VENEZOLANO DE CRÉDITO</option>
        <option value="0105 - BANCO MERCANTIL" <?= ($p['banco'] ?? '') === '0105 - BANCO MERCANTIL' ? 'selected' : '' ?>>0105 - BANCO MERCANTIL</option>
        <option value="0108 - BBVA PROVINCIAL" <?= ($p['banco'] ?? '') === '0108 - BBVA PROVINCIAL' ? 'selected' : '' ?>>0108 - BBVA PROVINCIAL</option>
        <option value="0114 - BANCARIBE" <?= ($p['banco'] ?? '') === '0114 - BANCARIBE' ? 'selected' : '' ?>>0114 - BANCARIBE</option>
        <option value="0115 - BANCO EXTERIOR" <?= ($p['banco'] ?? '') === '0115 - BANCO EXTERIOR' ? 'selected' : '' ?>>0115 - BANCO EXTERIOR</option>
        <option value="0128 - BANCO CARONÍ" <?= ($p['banco'] ?? '') === '0128 - BANCO CARONÍ' ? 'selected' : '' ?>>0128 - BANCO CARONÍ</option>
        <option value="0134 - BANCO BANESCO" <?= ($p['banco'] ?? '') === '0134 - BANCO BANESCO' ? 'selected' : '' ?>>0134 - BANCO BANESCO</option>
        <option value="0137 - BANCO SOFITASA" <?= ($p['banco'] ?? '') === '0137 - BANCO SOFITASA' ? 'selected' : '' ?>>0137 - BANCO SOFITASA</option>
        <option value="0138 - BANCO PLAZA" <?= ($p['banco'] ?? '') === '0138 - BANCO PLAZA' ? 'selected' : '' ?>>0138 - BANCO PLAZA</option>
        <option value="0146 - BANGENTE" <?= ($p['banco'] ?? '') === '0146 - BANGENTE' ? 'selected' : '' ?>>0146 - BANGENTE</option>
        <option value="0151 - BFC BANCO FONDO COMÚN" <?= ($p['banco'] ?? '') === '0151 - BFC BANCO FONDO COMÚN' ? 'selected' : '' ?>>0151 - BFC BANCO FONDO COMÚN</option>
        <option value="0156 - 100% BANCO" <?= ($p['banco'] ?? '') === '0156 - 100% BANCO' ? 'selected' : '' ?>>0156 - 100% BANCO</option>
        <option value="0157 - DELSUR BANCO UNIVERSAL" <?= ($p['banco'] ?? '') === '0157 - DELSUR BANCO UNIVERSAL' ? 'selected' : '' ?>>0157 - DELSUR BANCO UNIVERSAL</option>
        <option value="0163 - BANCO DEL TESORO" <?= ($p['banco'] ?? '') === '0163 - BANCO DEL TESORO' ? 'selected' : '' ?>>0163 - BANCO DEL TESORO</option>
        <option value="0166 - BANCO AGRÍCOLA DE VENEZUELA" <?= ($p['banco'] ?? '') === '0166 - BANCO AGRÍCOLA DE VENEZUELA' ? 'selected' : '' ?>>0166 - BANCO AGRÍCOLA DE VENEZUELA</option>
        <option value="0168 - BANCRECER" <?= ($p['banco'] ?? '') === '0168 - BANCRECER' ? 'selected' : '' ?>>0168 - BANCRECER</option>
        <option value="0169 - MI BANCO" <?= ($p['banco'] ?? '') === '0169 - MI BANCO' ? 'selected' : '' ?>>0169 - MI BANCO</option>
        <option value="0171 - BANCO ACTIVO" <?= ($p['banco'] ?? '') === '0171 - BANCO ACTIVO' ? 'selected' : '' ?>>0171 - BANCO ACTIVO</option>
        <option value="0172 - BANCAMIGA" <?= ($p['banco'] ?? '') === '0172 - BANCAMIGA' ? 'selected' : '' ?>>0172 - BANCAMIGA</option>
        <option value="0173 - BANCO INTERNACIONAL DE DESARROLLO" <?= ($p['banco'] ?? '') === '0173 - BANCO INTERNACIONAL DE DESARROLLO' ? 'selected' : '' ?>>0173 - BANCO INTERNACIONAL DE DESARROLLO</option>
        <option value="0174 - BANPLUS" <?= ($p['banco'] ?? '') === '0174 - BANPLUS' ? 'selected' : '' ?>>0174 - BANPLUS</option>
        <option value="0175 - BANCO DIGITAL DE LOS TRABAJADORES" <?= ($p['banco'] ?? '') === '0175 - BANCO DIGITAL DE LOS TRABAJADORES' ? 'selected' : '' ?>>0175 - BANCO DIGITAL DE LOS TRABAJADORES</option>
        <option value="0177 - BANFANB" <?= ($p['banco'] ?? '') === '0177 - BANFANB' ? 'selected' : '' ?>>0177 - BANFANB</option>
        <option value="0178 - N58 BANCO DIGITAL" <?= ($p['banco'] ?? '') === '0178 - N58 BANCO DIGITAL' ? 'selected' : '' ?>>0178 - N58 BANCO DIGITAL</option>
        <option value="0191 - BNC BANCO NACIONAL DE CRÉDITO" <?= ($p['banco'] ?? '') === '0191 - BNC BANCO NACIONAL DE CRÉDITO' ? 'selected' : '' ?>>0191 - BNC BANCO NACIONAL DE CRÉDITO</option>
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
                                            placeholder="Ej: 0102-0000-00-0000000000">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">TITULAR DE LA CUENTA</label>
                                        <input type="text" name="titular_cuenta" class="form-control"
                                            value="<?= htmlspecialchars($p['titular_cuenta'] ?? '') ?>"
                                            placeholder="Nombre del titular">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">TELÉFONO PAGO MÓVIL</label>
                                        <input type="text" name="telefono_pago_movil" class="form-control"
                                            value="<?= htmlspecialchars($p['telefono_pago_movil'] ?? '') ?>"
                                            placeholder="Ej: 0414-1234567">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">CÉDULA PAGO MÓVIL</label>
                                        <input type="text" name="cedula_pago_movil" class="form-control"
                                            value="<?= htmlspecialchars($p['cedula_pago_movil'] ?? '') ?>"
                                            placeholder="Ej: V-12345678">
                                    </div>

                                    <div class="col-md-6">
    <label class="form-label small fw-bold">BANCO</label>
    <select name="banco" class="form-select">
        <option value="">-- Seleccionar Banco --</option>
        <option value="0102 - BANCO DE VENEZUELA" <?= ($p['banco'] ?? '') === '0102 - BANCO DE VENEZUELA' ? 'selected' : '' ?>>0102 - BANCO DE VENEZUELA</option>
        <option value="0104 - VENEZOLANO DE CRÉDITO" <?= ($p['banco'] ?? '') === '0104 - VENEZOLANO DE CRÉDITO' ? 'selected' : '' ?>>0104 - VENEZOLANO DE CRÉDITO</option>
        <option value="0105 - BANCO MERCANTIL" <?= ($p['banco'] ?? '') === '0105 - BANCO MERCANTIL' ? 'selected' : '' ?>>0105 - BANCO MERCANTIL</option>
        <option value="0108 - BBVA PROVINCIAL" <?= ($p['banco'] ?? '') === '0108 - BBVA PROVINCIAL' ? 'selected' : '' ?>>0108 - BBVA PROVINCIAL</option>
        <option value="0114 - BANCARIBE" <?= ($p['banco'] ?? '') === '0114 - BANCARIBE' ? 'selected' : '' ?>>0114 - BANCARIBE</option>
        <option value="0115 - BANCO EXTERIOR" <?= ($p['banco'] ?? '') === '0115 - BANCO EXTERIOR' ? 'selected' : '' ?>>0115 - BANCO EXTERIOR</option>
        <option value="0128 - BANCO CARONÍ" <?= ($p['banco'] ?? '') === '0128 - BANCO CARONÍ' ? 'selected' : '' ?>>0128 - BANCO CARONÍ</option>
        <option value="0134 - BANCO BANESCO" <?= ($p['banco'] ?? '') === '0134 - BANCO BANESCO' ? 'selected' : '' ?>>0134 - BANCO BANESCO</option>
        <option value="0137 - BANCO SOFITASA" <?= ($p['banco'] ?? '') === '0137 - BANCO SOFITASA' ? 'selected' : '' ?>>0137 - BANCO SOFITASA</option>
        <option value="0138 - BANCO PLAZA" <?= ($p['banco'] ?? '') === '0138 - BANCO PLAZA' ? 'selected' : '' ?>>0138 - BANCO PLAZA</option>
        <option value="0146 - BANGENTE" <?= ($p['banco'] ?? '') === '0146 - BANGENTE' ? 'selected' : '' ?>>0146 - BANGENTE</option>
        <option value="0151 - BFC BANCO FONDO COMÚN" <?= ($p['banco'] ?? '') === '0151 - BFC BANCO FONDO COMÚN' ? 'selected' : '' ?>>0151 - BFC BANCO FONDO COMÚN</option>
        <option value="0156 - 100% BANCO" <?= ($p['banco'] ?? '') === '0156 - 100% BANCO' ? 'selected' : '' ?>>0156 - 100% BANCO</option>
        <option value="0157 - DELSUR BANCO UNIVERSAL" <?= ($p['banco'] ?? '') === '0157 - DELSUR BANCO UNIVERSAL' ? 'selected' : '' ?>>0157 - DELSUR BANCO UNIVERSAL</option>
        <option value="0163 - BANCO DEL TESORO" <?= ($p['banco'] ?? '') === '0163 - BANCO DEL TESORO' ? 'selected' : '' ?>>0163 - BANCO DEL TESORO</option>
        <option value="0166 - BANCO AGRÍCOLA DE VENEZUELA" <?= ($p['banco'] ?? '') === '0166 - BANCO AGRÍCOLA DE VENEZUELA' ? 'selected' : '' ?>>0166 - BANCO AGRÍCOLA DE VENEZUELA</option>
        <option value="0168 - BANCRECER" <?= ($p['banco'] ?? '') === '0168 - BANCRECER' ? 'selected' : '' ?>>0168 - BANCRECER</option>
        <option value="0169 - MI BANCO" <?= ($p['banco'] ?? '') === '0169 - MI BANCO' ? 'selected' : '' ?>>0169 - MI BANCO</option>
        <option value="0171 - BANCO ACTIVO" <?= ($p['banco'] ?? '') === '0171 - BANCO ACTIVO' ? 'selected' : '' ?>>0171 - BANCO ACTIVO</option>
        <option value="0172 - BANCAMIGA" <?= ($p['banco'] ?? '') === '0172 - BANCAMIGA' ? 'selected' : '' ?>>0172 - BANCAMIGA</option>
        <option value="0173 - BANCO INTERNACIONAL DE DESARROLLO" <?= ($p['banco'] ?? '') === '0173 - BANCO INTERNACIONAL DE DESARROLLO' ? 'selected' : '' ?>>0173 - BANCO INTERNACIONAL DE DESARROLLO</option>
        <option value="0174 - BANPLUS" <?= ($p['banco'] ?? '') === '0174 - BANPLUS' ? 'selected' : '' ?>>0174 - BANPLUS</option>
        <option value="0175 - BANCO DIGITAL DE LOS TRABAJADORES" <?= ($p['banco'] ?? '') === '0175 - BANCO DIGITAL DE LOS TRABAJADORES' ? 'selected' : '' ?>>0175 - BANCO DIGITAL DE LOS TRABAJADORES</option>
        <option value="0177 - BANFANB" <?= ($p['banco'] ?? '') === '0177 - BANFANB' ? 'selected' : '' ?>>0177 - BANFANB</option>
        <option value="0178 - N58 BANCO DIGITAL" <?= ($p['banco'] ?? '') === '0178 - N58 BANCO DIGITAL' ? 'selected' : '' ?>>0178 - N58 BANCO DIGITAL</option>
        <option value="0191 - BNC BANCO NACIONAL DE CRÉDITO" <?= ($p['banco'] ?? '') === '0191 - BNC BANCO NACIONAL DE CRÉDITO' ? 'selected' : '' ?>>0191 - BNC BANCO NACIONAL DE CRÉDITO</option>
    </select>
</div>


                                </div>
                            </div>

                            <!-- TAB: Contratos -->
                            <div class="tab-pane fade <?= $tabActivo === 'contratos' ? 'show active' : '' ?>" id="tab-contratos">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="small text-muted">Historial de contratos generados para este personal.</span>
                                    <a href="<?= $basePath ?>/resources/contratos/create"
                                       class="btn btn-sm rounded-pill px-3 text-white" style="background:#198754;">
                                        <i class="bi bi-plus-lg me-1"></i> Nuevo Contrato
                                    </a>
                                </div>
                                <?php if (empty($contratos)): ?>
                                    <div class="text-center py-4 text-muted">
                                        <i class="bi bi-file-earmark-x fs-2 d-block mb-2 opacity-25"></i>
                                        No hay contratos generados para este personal.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle small mb-0">
                                            <thead class="bg-light text-secondary text-uppercase" style="font-size:0.75rem;">
                                                <tr>
                                                    <th>N° Contrato</th>
                                                    <th>Plantilla</th>
                                                    <th>Fecha</th>
                                                    <th class="text-center">Estado</th>
                                                    <th class="text-end">PDF</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($contratos as $c):
                                                    $estadoColor = match($c['estado']) {
                                                        'Activo'     => 'success',
                                                        'Borrador'   => 'secondary',
                                                        'Finalizado' => 'primary',
                                                        'Rescindido' => 'danger',
                                                        default      => 'secondary'
                                                    };
                                                ?>
                                                <tr>
                                                    <td class="fw-bold" style="color:#198754;"><?= htmlspecialchars($c['numero_contrato']) ?></td>
                                                    <td class="text-muted"><?= htmlspecialchars($c['plantilla_nombre']) ?></td>
                                                    <td class="text-muted"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-<?= $estadoColor ?> rounded-pill px-3"><?= $c['estado'] ?></span>
                                                    </td>
                                                    <td class="text-end">
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-success rounded-pill px-3 btn-ver-contrato"
                                                                data-id="<?= $c['id'] ?>"
                                                                data-numero="<?= htmlspecialchars($c['numero_contrato']) ?>"
                                                                data-persona="<?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>">
                                                            <i class="bi bi-eye me-1"></i> Ver
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Contrato -->
<div class="modal fade" id="modalVerContrato" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 py-3 px-4" style="background:#198754;">
                <div>
                    <h5 class="modal-title fw-bold text-white mb-0" id="modalContratoNumero"></h5>
                    <small class="text-white opacity-75" id="modalContratoPersona"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div style="background:#e9ecef; padding:32px; min-height:500px;">
                    <div id="modal-contrato-contenido"
                         style="background:white; max-width:800px; margin:0 auto; padding:60px 70px; box-shadow:0 4px 24px rgba(0,0,0,0.10); min-height:400px; font-family:'Segoe UI', serif; font-size:14px; line-height:1.8; color:#222;">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 px-4">
                <a id="btn-modal-pdf" href="#" target="_blank" class="btn btn-success rounded-pill px-4">
                    <i class="bi bi-file-pdf me-1"></i> Descargar PDF
                </a>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Carnet -->
<div class="modal fade" id="modalCarnet" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:360px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow:hidden; position:relative;">
            <button type="button" class="btn-close bg-white rounded-circle shadow-sm position-absolute"
                    data-bs-dismiss="modal" style="top:12px; right:12px; z-index:10; opacity:1;"></button>
            <div id="carnet-visor" style="overflow:hidden; cursor:grab; user-select:none; height:480px; background:#f0e8ff; position:relative;">
                <div id="carnet-inner" style="transform-origin:top center; transition:transform 0.1s; position:relative;">
                    <div style="background:linear-gradient(135deg,#a855f7,#7c3aed); padding:28px 20px 65px; text-align:center; color:white; position:relative;">
                        <div style="font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; opacity:.9;">Decanato de Ciencias de la Salud</div>
                        <div style="font-size:11px; opacity:.7; margin-top:4px;">UCLA — Programa de Diplomados</div>
                        <img id="carnet-foto" src="" alt="Foto"
                             style="position:absolute; bottom:-45px; left:50%; transform:translateX(-50%); width:90px; height:90px; border-radius:50%; border:4px solid white; object-fit:cover; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                    </div>
                    <div style="padding:55px 24px 20px; text-align:center; background:#fff;">
                        <div id="carnet-nombre" style="font-size:17px; font-weight:700; color:#1a1a2e; margin-bottom:4px;"></div>
                        <div id="carnet-cedula" style="font-size:13px; color:#888; margin-bottom:6px;"></div>
                        <div id="carnet-exp" style="font-size:10px; color:#a855f7; font-weight:600; margin-bottom:12px;"></div>
                        <div id="carnet-tipo" style="display:inline-block; background:linear-gradient(135deg,#a855f7,#7c3aed); color:white; padding:5px 16px; border-radius:20px; font-size:11px; font-weight:600; margin-bottom:20px;"></div>
                        <div style="background:#f8f5ff; border-radius:10px; padding:14px; text-align:left;">
                            <div style="display:flex; justify-content:space-between; font-size:12px; padding:5px 0; border-bottom:1px solid #ede9fe;">
                                <span style="color:#888;">Email</span><span id="carnet-email" style="font-weight:600; max-width:200px; text-align:right; word-break:break-all;"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:12px; padding:5px 0; border-bottom:1px solid #ede9fe;">
                                <span style="color:#888;">Teléfono</span><span id="carnet-tel" style="font-weight:600;"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:12px; padding:5px 0; border-bottom:1px solid #ede9fe;">
                                <span style="color:#888;">Desde</span><span id="carnet-desde" style="font-weight:600;"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:12px; padding:5px 0;">
                                <span style="color:#888;">Instrucción</span><span id="carnet-instruccion" style="font-weight:600;"></span>
                            </div>
                        </div>
                    </div>
                    <div id="carnet-footer" style="background:#f8f5ff; padding:10px; text-align:center; font-size:10px; color:#aaa; border-top:1px solid #ede9fe;">
                        Generado: — Sistema DIPLOMATIC
                    </div>
                </div>
            </div>
            <div style="background:#1a1a2e; padding:10px 16px; display:flex; justify-content:center; align-items:center; gap:8px;">
                <button onclick="zoomCarnet(-0.15)" class="btn btn-sm btn-outline-light rounded-circle" style="width:32px;height:32px;padding:0;font-size:16px;">−</button>
                <button onclick="resetCarnet()" class="btn btn-sm btn-outline-light rounded-pill px-3" style="font-size:12px;">↺ Reset</button>
                <button onclick="zoomCarnet(0.15)" class="btn btn-sm btn-outline-light rounded-circle" style="width:32px;height:32px;padding:0;font-size:16px;">+</button>
                <button onclick="imprimirCarnet()" class="btn btn-sm rounded-pill px-4 text-white ms-2" style="background:#a855f7;font-size:12px;">
                    <i class="bi bi-printer me-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_personal.js?v=<?= time() ?>"></script>
<script src="<?= $basePath ?>/assets/js/resources_contratos.js?v=<?= time() ?>"></script>