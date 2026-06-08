<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * Archivo: app/views/resources/personal/edit.php
 * Propósito: Expediente completo del personal operativo con navegación por tabs.
 * Versión: 1.3.0
 *
 * @var array  $persona
 * @var array  $tipos
 */
$p         = $persona;
$basePath  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$tabActivo = $_GET['tab'] ?? 'datos';

$avatar = !empty($p['foto'])
    ? '/diplomatic/public/' . ltrim($p['foto'], '/')
    : 'https://ui-avatars.com/api/?name=' . urlencode($p['first_name'] . '+' . $p['last_name']) . '&background=a855f7&color=fff&size=150&bold=true';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_personal.css">

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
            <p class="text-muted small">ID: #<?= $p['id'] ?> | Registro: <?= date('d/m/Y', strtotime($p['created_at'])) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/resources/personal" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm btn-carnet"
                    data-id="<?= $p['id'] ?>">
                <i class="bi bi-person-badge me-1"></i> Carnet
            </button>
            <button class="btn rounded-pill px-4 shadow-sm text-white" style="background:#a855f7;"
                    onclick="guardarConTab();">
                <i class="bi bi-check-circle me-1"></i> Guardar
            </button>
        </div>
    </div>

    <div class="row g-4">

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
                <p class="text-muted small mb-2"><?= htmlspecialchars($p['document_id']) ?></p>
                <span class="badge rounded-pill px-3 py-2 w-100 mb-3 text-white" style="background:#a855f7; font-size:0.8rem;">
                    <?= htmlspecialchars($p['tipo_nombre']) ?>
                </span>
                <?php if (!empty($p['fecha_inicio'])): ?>
                    <div class="small text-muted"><i class="bi bi-calendar3 me-1"></i> Desde <?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($p['fecha_fin'])): ?>
                    <div class="small text-muted"><i class="bi bi-calendar-x me-1"></i> Hasta <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?></div>
                <?php endif; ?>
            </div>
        </div>

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
                    </ul>
                </div>
                <div class="card-body p-4 bg-light bg-opacity-50">
                    <form id="formPersonal" action="<?= $basePath ?>/resources/personal/update" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="tab" value="<?= $tabActivo ?>">
                        <input type="file" name="foto" id="inputFotoUpload" accept="image/*" style="display:none;"
                               onchange="previewFoto(this)">

                        <div class="tab-content">

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
                                </div>
                            </div>

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
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- MODAL CARNET — mismo bloque para index.php y edit.php -->
<div class="modal fade" id="modalCarnet" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow:hidden; position:relative;">

            <!-- Botón cerrar -->
            <button type="button" class="btn-close bg-white rounded-circle shadow-sm position-absolute"
                    data-bs-dismiss="modal"
                    style="top:12px; right:12px; z-index:10; opacity:1;"></button>

            <!-- Visor con zoom y drag -->
            <div id="carnet-visor"
                 style="overflow:hidden; cursor:grab; user-select:none; height:480px; background:#f0e8ff; position:relative;">
                <div id="carnet-inner" style="transform-origin:top center; transition:transform 0.1s; position:relative;">

                    <!-- Header morado -->
                    <div style="background:linear-gradient(135deg,#a855f7,#7c3aed); padding:28px 20px 65px; text-align:center; color:white; position:relative;">
                        <div style="font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; opacity:.9;">Decanato de Ciencias de la Salud</div>
                        <div style="font-size:11px; opacity:.7; margin-top:4px;">UCLA — Programa de Diplomados</div>
                        <img id="carnet-foto" src="" alt="Foto"
                             style="position:absolute; bottom:-45px; left:50%; transform:translateX(-50%);
                                    width:90px; height:90px; border-radius:50%; border:4px solid white;
                                    object-fit:cover; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                    </div>

                    <!-- Body -->
                    <div style="padding:55px 24px 20px; text-align:center; background:#fff;">
                        <div id="carnet-nombre" style="font-size:17px; font-weight:700; color:#1a1a2e; margin-bottom:4px;"></div>
                        <div id="carnet-cedula" style="font-size:13px; color:#888; margin-bottom:12px;"></div>
                        <div id="carnet-tipo"
                             style="display:inline-block; background:linear-gradient(135deg,#a855f7,#7c3aed);
                                    color:white; padding:5px 16px; border-radius:20px; font-size:11px;
                                    font-weight:600; margin-bottom:20px;"></div>
                        <div style="background:#f8f5ff; border-radius:10px; padding:14px; text-align:left;">
                            <div style="display:flex; justify-content:space-between; font-size:12px; padding:5px 0; border-bottom:1px solid #ede9fe;">
                                <span style="color:#888;">Email</span>
                                <span id="carnet-email" style="font-weight:600; max-width:200px; text-align:right; word-break:break-all;"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:12px; padding:5px 0; border-bottom:1px solid #ede9fe;">
                                <span style="color:#888;">Teléfono</span>
                                <span id="carnet-tel" style="font-weight:600;"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:12px; padding:5px 0; border-bottom:1px solid #ede9fe;">
                                <span style="color:#888;">Desde</span>
                                <span id="carnet-desde" style="font-weight:600;"></span>
                            </div>
                            <div style="display:flex; justify-content:space-between; font-size:12px; padding:5px 0;">
                                <span style="color:#888;">Instrucción</span>
                                <span id="carnet-instruccion" style="font-weight:600;"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer del carnet -->
                    <div id="carnet-footer"
                         style="background:#f8f5ff; padding:10px; text-align:center; font-size:10px; color:#aaa; border-top:1px solid #ede9fe;">
                        Generado: — Sistema DIPLOMATIC
                    </div>

                </div><!-- /carnet-inner -->
            </div><!-- /carnet-visor -->

            <!-- Barra de controles -->
            <div style="background:#1a1a2e; padding:10px 16px; display:flex; justify-content:center; align-items:center; gap:8px;">
                <button onclick="zoomCarnet(-0.15)"
                        class="btn btn-sm btn-outline-light rounded-circle"
                        style="width:32px; height:32px; padding:0; font-size:16px;" title="Alejar">−</button>
                <button onclick="resetCarnet()"
                        class="btn btn-sm btn-outline-light rounded-pill px-3"
                        style="font-size:12px;" title="Restablecer">↺ Reset</button>
                <button onclick="zoomCarnet(0.15)"
                        class="btn btn-sm btn-outline-light rounded-circle"
                        style="width:32px; height:32px; padding:0; font-size:16px;" title="Acercar">+</button>
                <button onclick="imprimirCarnet()"
                        class="btn btn-sm rounded-pill px-4 text-white ms-2"
                        style="background:#a855f7; font-size:12px;">
                    <i class="bi bi-printer me-1"></i> Imprimir
                </button>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/resources_personal.js?v=<?= time() ?>"></script>