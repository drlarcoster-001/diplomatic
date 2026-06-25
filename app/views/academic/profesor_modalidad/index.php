<?php
/**
 * MÓDULO: ACADÉMICO / ASIGNACIÓN DE MODALIDAD A PROFESORES
 * ARCHIVO: app/views/academic/profesor_modalidad/index.php
 * PROPÓSITO: Listado con buscador + modal crear/editar con buscadores
 *            inteligentes (Profesor, Oferta, Grupo), selector de modalidad
 *            por checkboxes y botones de limpiar por campo.
 * VERSIÓN: 2.2.0 - Bloque GRUPO reubicado debajo de MODALIDAD (flujo natural).
 */
$basePath  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$errorType = $_GET['error'] ?? '';

$labelsModalidad  = ['TEORICA' => 'Teórica', 'PRACTICA' => 'Práctica', 'VIRTUAL' => 'Virtual'];
$coloresModalidad = ['TEORICA' => 'primary', 'PRACTICA' => 'success', 'VIRTUAL' => 'info'];

// Catálogos en JSON para los buscadores inteligentes
$profesoresJson = json_encode(array_map(fn($p) => ['id' => $p['id'], 'label' => $p['full_name']], $profesores), JSON_UNESCAPED_UNICODE);
$ofertasJson    = json_encode(array_map(fn($o) => [
    'id' => $o['id'],
    'label' => $o['diplomado_nombre'] . ' — ' . $o['cohorte_nombre'] . ($o['grupos_nombre'] ? ' (' . $o['grupos_nombre'] . ')' : ' (sin grupo configurado)'),
    'grupos' => $o['grupos_nombre'] ?? ''
], $ofertas), JSON_UNESCAPED_UNICODE);
$mapaJson       = json_encode($mapaProfesorOfertas, JSON_UNESCAPED_UNICODE);
$mapaGruposJson = json_encode($mapaOfertaGrupos, JSON_UNESCAPED_UNICODE);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_profesor_modalidad.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size:0.85rem">
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i> Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= $basePath ?>/academic" class="text-decoration-none text-muted">Académico</a></li>
            <li class="breadcrumb-item active fw-bold text-primary">Asignación de Modalidad</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Asignación de Modalidad Virtual</h2>
            <p class="text-muted small mb-0">Define qué profesor dicta la parte Virtual de cada oferta.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <button type="button" class="btn btn-dark rounded-pill px-4 shadow-sm" id="btnNuevo">
                <i class="bi bi-plus-circle me-1"></i> Nuevo
            </button>
        </div>
    </div>

    <?php if ($errorType === 'incompleto'): ?>
        <div class="alert alert-danger">Completa profesor, oferta y modalidad.</div>
    <?php elseif ($errorType === 'duplicado'): ?>
        <div class="alert alert-warning">Esa oferta ya tiene un profesor asignado para esa modalidad.</div>
    <?php elseif ($errorType === 'sinvinculo'): ?>
        <div class="alert alert-danger">Ese profesor no está vinculado a esa oferta en Ofertas Académicas. Primero debe asignarse ahí.</div>
    <?php elseif ($errorType === 'falta_grupo'): ?>
        <div class="alert alert-danger">La modalidad Teórica requiere elegir un grupo (Viernes, Sábado, etc.) de esa oferta.</div>
    <?php elseif ($errorType === 'oferta_online'): ?>
        <div class="alert alert-danger">Esa oferta es 100% Online — solo se puede asignar la modalidad Virtual.</div>
    <?php elseif ($errorType === 'db'): ?>
        <div class="alert alert-danger">Ocurrió un error al guardar.</div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Buscar por profesor o diplomado..." value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-outline-dark w-100 rounded-pill">Buscar</button></div>
            </form>

            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small fw-bold text-secondary text-uppercase">
                    <tr>
                        <th class="ps-3">Profesor</th>
                        <th>Diplomado</th>
                        <th style="width:140px">Modalidad</th>
                        <th style="width:120px">Asignado</th>
                        <th style="width:120px" class="text-end pe-3">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($asignaciones)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No hay asignaciones registradas.</td></tr>
                    <?php else: foreach ($asignaciones as $a): ?>
                        <tr class="fila-asignacion" style="cursor:pointer"
                            data-id="<?= $a['id'] ?>"
                            data-professor-id="<?= $a['professor_id'] ?>"
                            data-professor-nombre="<?= htmlspecialchars($a['profesor_nombre']) ?>"
                            data-offering-id="<?= $a['offering_id'] ?>"
                            data-offering-nombre="<?= htmlspecialchars($a['diplomado_nombre']) ?> — <?= htmlspecialchars($a['cohorte_nombre']) ?>"
                            data-modalidad="<?= $a['modalidad'] ?>"
                            data-group-id="<?= $a['offering_group_id'] ?? '' ?>"
                            data-group-nombre="<?= htmlspecialchars($a['grupo_nombre'] ?? '') ?>">
                            <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($a['profesor_nombre']) ?></td>
                            <td class="small">
                                <?= htmlspecialchars($a['diplomado_nombre']) ?>
                                <?php if (!empty($a['grupo_nombre'])): ?>
                                    <span class="text-muted">— <?= htmlspecialchars($a['grupo_nombre']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge rounded-pill bg-<?= $coloresModalidad[$a['modalidad']] ?>"><?= $labelsModalidad[$a['modalidad']] ?></span></td>
                            <td class="small text-muted"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 btn-editar" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 btn-delete" data-id="<?= $a['id'] ?>" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CREAR/EDITAR -->
<div class="modal fade" id="modalAsignacion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="formAsignacion" method="POST">
                <input type="hidden" name="id" id="f_id">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="modalTitulo"><i class="bi bi-person-video2 me-2 text-primary"></i> Nueva Asignación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <!-- PROFESOR CON BOTÓN LIMPIAR -->
                    <div class="mb-3 buscador-inteligente" data-target="professor_id">
                        <label class="form-label small fw-bold">PROFESOR</label>
                        <div class="input-group">
                            <input type="text" class="form-control buscador-input" placeholder="Escribe para buscar un profesor..." autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary btn-limpiar d-none" title="Limpiar selección">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <input type="hidden" name="professor_id" class="buscador-hidden">
                        <div class="buscador-dropdown"></div>
                    </div>

                    <!-- OFERTA CON BOTÓN LIMPIAR -->
                    <div class="mb-3 buscador-inteligente" data-target="offering_id">
                        <label class="form-label small fw-bold">OFERTA / COHORTE</label>
                        <div class="input-group">
                            <input type="text" class="form-control buscador-input" placeholder="Escribe para buscar una oferta..." autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary btn-limpiar d-none" title="Limpiar selección">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <input type="hidden" name="offering_id" class="buscador-hidden">
                        <div class="buscador-dropdown"></div>
                    </div>

                    <!-- MODALIDAD FIJA: VIRTUAL -->
                    <input type="hidden" name="modalidad[]" value="VIRTUAL">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">MODALIDAD</label>
                        <div class="alert alert-info py-2 px-3 small mb-0">
                            <i class="bi bi-camera-video me-1"></i> Esta asignación es exclusiva para la modalidad <strong>Virtual</strong>.
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.APP_BASE_PATH = '<?= $basePath ?>';
    window.CATALOGO_PROFESORES = <?= $profesoresJson ?>;
    window.CATALOGO_OFERTAS    = <?= $ofertasJson ?>;
    window.MAPA_PROFESOR_OFERTAS = <?= $mapaJson ?>;
    window.MAPA_OFERTA_GRUPOS = <?= $mapaGruposJson ?>;
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/academic_profesor_modalidad.js?v=<?= time() ?>"></script>