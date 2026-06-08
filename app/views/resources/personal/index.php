<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * Archivo: app/views/resources/personal/index.php
 * Propósito: Directorio maestro del personal operativo del programa de diplomados.
 * Versión: 1.3.0
 *
 * @var array  $personal
 * @var string $search
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/resources_personal.css">

<div class="container-fluid py-4">

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/resources" class="text-decoration-none text-muted">Panel de Recursos</a></li>
            <li class="breadcrumb-item active fw-bold" style="color:#a855f7;">Personal</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Directorio de Personal</h2>
            <p class="text-muted small">Catálogo operativo del personal vinculado al programa de diplomados.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/resources" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="<?= $basePath ?>/resources/personal/create" class="btn rounded-pill px-4 shadow-sm text-white" style="background:#a855f7;">
                <i class="bi bi-plus-lg me-1"></i> Nuevo
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= $basePath ?>/resources/personal" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Buscar por nombre, cédula o tipo..." value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($personal)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-people fs-1 d-block mb-3 opacity-25"></i>
            No hay personal registrado.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($personal as $p): ?>
                <?php
                    $avatar = !empty($p['foto'])
                        ? '/diplomatic/public/' . ltrim($p['foto'], '/')
                        : 'https://ui-avatars.com/api/?name=' . urlencode($p['first_name'] . '+' . $p['last_name']) . '&background=a855f7&color=fff&size=150&bold=true';
                    $badgeColor = match($p['tipo_nombre']) {
                        'Profesor teórico'        => '#0d6efd',
                        'Docente de práctica'     => '#198754',
                        'Coordinador de práctica' => '#fd7e14',
                        'Coordinador virtual'     => '#0dcaf0',
                        'Administrativo'          => '#6c757d',
                        'Mantenimiento'           => '#795548',
                        'Vigilancia'              => '#dc3545',
                        'Audiovisual'             => '#9c27b0',
                        default                   => '#a855f7'
                    };
                ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card border-0 shadow-sm h-100 personal-card" style="border-top: 3px solid <?= $badgeColor ?> !important;">
                        <div class="card-body p-4 text-center">
                            <img src="<?= htmlspecialchars($avatar) ?>" alt="Foto"
                                 class="rounded-circle object-fit-cover shadow-sm mb-3 border border-3 border-white"
                                 width="90" height="90"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($p['first_name'] . '+' . $p['last_name']) ?>&background=a855f7&color=fff&size=150'">
                            <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></h6>
                            <p class="text-muted small mb-2"><?= htmlspecialchars($p['document_id']) ?></p>
                            <span class="badge rounded-pill px-3 py-1 mb-3 text-white" style="background:<?= $badgeColor ?>; font-size:0.75rem;">
                                <?= htmlspecialchars($p['tipo_nombre']) ?>
                            </span>
                            <div class="text-muted small mb-3">
                                <?php if (!empty($p['email'])): ?>
                                    <div><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($p['email']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($p['telefono_celular'])): ?>
                                    <div><i class="bi bi-phone me-1"></i><?= htmlspecialchars($p['telefono_celular']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($p['fecha_inicio'])): ?>
                                    <div class="mt-1"><i class="bi bi-calendar3 me-1"></i>Desde <?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="<?= $basePath ?>/resources/personal/edit?id=<?= $p['id'] ?>"
                                   class="btn btn-sm rounded-pill px-3 text-white" style="background:#a855f7;">
                                    <i class="bi bi-pencil me-1"></i> Editar
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 btn-carnet"
                                        data-id="<?= $p['id'] ?>" title="Ver Carnet">
                                    <i class="bi bi-person-badge"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-delete"
                                        data-id="<?= $p['id'] ?>"
                                        data-name="<?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>"
                                        title="Inactivar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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