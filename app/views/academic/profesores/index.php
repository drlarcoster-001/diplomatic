<?php
/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: app/views/academic/profesores/index.php
 * Propósito: Directorio maestro de profesores con navegación jerárquica.
 * Version: 1.4.0 - Reemplaza "Crear Acceso" por "Vincular Usuario" existente con rol PROFESOR.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="<?= $basePath ?>/assets/css/academic_profesores.css">

<div class="container-fluid py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-2" style="font-size: 0.85rem;">
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/dashboard" class="text-decoration-none text-muted">
                    <i class="bi bi-house-door me-1"></i> Inicio
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $basePath ?>/academic" class="text-decoration-none text-muted">Panel Académico</a>
            </li>
            <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Profesores</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Directorio de Profesores</h2>
            <p class="text-muted small">Gestión de personal docente, invitados y coordinadores.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $basePath ?>/academic" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="<?= $basePath ?>/academic/profesores/create" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Nuevo
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?= $basePath ?>/academic/profesores" class="row g-3">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Buscar por identificación o nombre..." value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-pill">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light small fw-bold text-secondary text-uppercase">
                    <tr>
                        <th class="ps-4">Perfil</th>
                        <th>Identificación</th>
                        <th>Nombre Completo</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($profesores)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No hay profesores registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($profesores as $p): ?>
                            <tr class="profesor-row" data-id="<?= $p['id'] ?>" style="cursor:pointer;">
                                <td class="ps-4">
                                    <?php
                                        $avatar = !empty($p['photo_path']) ? $p['photo_path'] : 'https://ui-avatars.com/api/?name=' . urlencode($p['first_name'] . ' ' . $p['last_name']) . '&background=4e73df&color=fff&size=150';
                                    ?>
                                    <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="rounded-circle object-fit-cover shadow-sm" width="45" height="45">
                                </td>
                                <td class="fw-bold text-secondary"><?= htmlspecialchars($p['identification']) ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($p['full_name']) ?></td>
                                <td>
                                    <?php
                                        $typeBadge = match($p['professor_type']) {
                                            'Docente'     => 'bg-primary',
                                            'Coordinador' => 'bg-info text-dark',
                                            'Invitado'    => 'bg-warning text-dark',
                                            'Tutor'       => 'bg-success',
                                            default       => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $typeBadge ?> rounded-pill px-3"><?= htmlspecialchars($p['professor_type']) ?></span>
                                </td>
                                <td><span class="badge bg-light text-success border border-success rounded-pill px-3">Activo</span></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="<?= $basePath ?>/academic/profesores/edit?id=<?= $p['id'] ?>" class="btn btn-sm btn-white border text-primary" title="Editar Expediente">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if (empty($p['user_id'])): ?>
                                            <button type="button" class="btn btn-sm btn-white border text-success btn-vincular"
                                                    data-id="<?= $p['id'] ?>" data-nombre="<?= htmlspecialchars($p['full_name']) ?>"
                                                    title="Vincular Usuario al Portal" onclick="event.stopPropagation()">
                                                <i class="bi bi-link-45deg"></i>
                                            </button>

                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-white border text-warning btn-desvincular"
                                                    data-id="<?= $p['id'] ?>" data-nombre="<?= htmlspecialchars($p['full_name']) ?>"
                                                    title="Desvincular Usuario" onclick="event.stopPropagation()">
                                                <i class="bi bi-shield-x"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-white border text-danger btn-delete" data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['full_name']) ?>" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL FICHA RESUMEN -->
<div class="modal fade" id="modalProfesorPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0 py-3">
                <h5 class="modal-title fw-bold text-secondary"><i class="bi bi-person-badge me-2"></i> Ficha Resumen del Docente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-md-4 bg-primary bg-opacity-10 p-4 text-center border-end">
                        <img id="prev_photo" src="" class="rounded-circle object-fit-cover shadow mb-3 border border-white border-3" width="120" height="120" alt="Foto">
                        <h5 class="fw-bold text-dark mb-1" id="prev_name">--</h5>
                        <p class="text-primary fw-bold small mb-3" id="prev_type">--</p>
                        <div class="text-start mt-4">
                            <p class="small text-muted mb-1 text-uppercase fw-bold">Identificación</p>
                            <p class="mb-3 fw-medium" id="prev_id">--</p>
                            <p class="small text-muted mb-1 text-uppercase fw-bold">Contacto</p>
                            <p class="mb-1 small"><i class="bi bi-envelope-fill text-secondary me-2"></i> <span id="prev_email">No registrado</span></p>
                            <p class="mb-0 small"><i class="bi bi-telephone-fill text-secondary me-2"></i> <span id="prev_phone">No registrado</span></p>
                        </div>
                    </div>
                    <div class="col-md-8 p-4">
                        <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-secondary">Perfil Profesional</h6>
                        <p class="small text-justify text-muted mb-4" id="prev_bio">Sin biografía registrada.</p>
                        <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-secondary">Especialidades Principales</h6>
                        <div id="prev_specialties" class="mb-4"><span class="text-muted small">Cargando...</span></div>
                        <h6 class="fw-bold text-uppercase border-bottom pb-2 mb-3 text-secondary">Última Formación Destacada</h6>
                        <div id="prev_formation" class="small"><span class="text-muted">Cargando...</span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <a href="#" id="btn_full_profile" class="btn btn-outline-primary rounded-pill px-4">Ver Expediente Completo</a>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VINCULAR USUARIO -->
<div class="modal fade" id="modalVincularUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-link-45deg me-2 text-primary"></i> Vincular Usuario al Portal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Profesor: <strong id="vincular_nombre">--</strong>
                </p>
                <input type="hidden" id="vincular_profesor_id">
                <label class="form-label small fw-bold">BUSCAR USUARIO CON ROL PROFESOR</label>
                <input type="text" id="vincularBuscador" class="form-control mb-2"
                       placeholder="Escribe nombre o email...">
                <div id="vincularResultados" class="list-group mt-1" style="max-height:250px;overflow-y:auto"></div>
                <div id="vincularSeleccionado" class="alert alert-primary d-none mt-3 py-2">
                    <i class="bi bi-person-check me-1"></i>
                    <span id="vincularSeleccionadoNombre"></span>
                    <input type="hidden" id="vincular_user_id">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                        data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-pill px-4"
                        id="btnConfirmarVincular" disabled>
                    <i class="bi bi-link me-1"></i> Vincular
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/academic_profesores.js?v=<?= time() ?>"></script>
<script>
const BASE_PATH = '<?= $basePath ?>';

// ── ABRIR MODAL VINCULAR ──────────────────────────────────────────
document.querySelectorAll('.btn-vincular').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('vincular_profesor_id').value       = this.dataset.id;
        document.getElementById('vincular_nombre').innerText        = this.dataset.nombre;
        document.getElementById('vincularBuscador').value           = '';
        document.getElementById('vincularResultados').innerHTML     = '';
        document.getElementById('vincularSeleccionado').classList.add('d-none');
        document.getElementById('vincular_user_id').value           = '';
        document.getElementById('btnConfirmarVincular').disabled    = true;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVincularUsuario')).show();
    });
});

// ── BUSCADOR DE USUARIOS ──────────────────────────────────────────
let vincularTimer = null;
document.getElementById('vincularBuscador')?.addEventListener('input', function() {
    clearTimeout(vincularTimer);
    const term = this.value.trim();
    const box  = document.getElementById('vincularResultados');
    if (term.length < 2) { box.innerHTML = ''; return; }
    vincularTimer = setTimeout(async () => {
        try {
            const resp = await fetch(`${BASE_PATH}/academic/profesores/searchUsuarios?term=${encodeURIComponent(term)}`).then(r => r.json());
            if (!resp.success || !resp.data.length) {
                box.innerHTML = '<div class="list-group-item text-muted small">Sin resultados.</div>';
                return;
            }
            box.innerHTML = resp.data.map(u => `
                <button type="button" class="list-group-item list-group-item-action small vincular-item"
                        data-uid="${u.id}"
                        data-nombre="${u.last_name}, ${u.first_name} — ${u.email}">
                    <i class="bi bi-person me-1"></i>
                    <strong>${u.last_name}, ${u.first_name}</strong>
                    <span class="text-muted"> — ${u.email}</span>
                </button>`).join('');

            box.querySelectorAll('.vincular-item').forEach(item => {
                item.addEventListener('click', () => {
                    document.getElementById('vincular_user_id').value          = item.dataset.uid;
                    document.getElementById('vincularSeleccionadoNombre').textContent = item.dataset.nombre;
                    document.getElementById('vincularSeleccionado').classList.remove('d-none');
                    document.getElementById('btnConfirmarVincular').disabled   = false;
                    box.innerHTML = '';
                    document.getElementById('vincularBuscador').value = '';
                });
            });
        } catch(e) { console.error(e); }
    }, 300);
});

// ── CONFIRMAR VINCULAR ────────────────────────────────────────────
document.getElementById('btnConfirmarVincular')?.addEventListener('click', async () => {
    const profesorId = document.getElementById('vincular_profesor_id').value;
    const userId     = document.getElementById('vincular_user_id').value;
    if (!profesorId || !userId) return;

    const fd = new FormData();
    fd.append('profesor_id', profesorId);
    fd.append('user_id',     userId);

    try {
        const resp = await fetch(`${BASE_PATH}/academic/profesores/vincular`, {
            method: 'POST', body: fd
        }).then(r => r.json());

        bootstrap.Modal.getInstance(document.getElementById('modalVincularUsuario')).hide();

        if (resp.success) {
            Swal.fire({ icon: 'success', title: '¡Vinculado!', text: resp.message, timer: 2000, showConfirmButton: false })
                .then(() => window.location.reload());
        } else {
            Swal.fire('Error', resp.message, 'error');
        }
    } catch(e) {
        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
    }
});

// ── ALERTAS URL ───────────────────────────────────────────────────
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('acceso_creado')) {
    Swal.fire({ icon: 'success', title: 'Acceso creado.', text: 'El profesor ya puede iniciar sesión.', confirmButtonColor: '#198754' });
}
if (urlParams.get('error') === 'email_duplicado') {
    Swal.fire({ icon: 'error', title: 'Correo ya registrado', text: 'Ese correo ya pertenece a otro usuario.', confirmButtonColor: '#e74a3b' });
}
if (urlParams.get('error') === 'incompleto') {
    Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Completa el correo y contraseña.', confirmButtonColor: '#0d6efd' });
}
if (urlParams.get('error') === 'ya_tiene_acceso') {
    Swal.fire({ icon: 'info', title: 'Ya tiene acceso', text: 'Este profesor ya tiene una cuenta vinculada.', confirmButtonColor: '#0d6efd' });
}

// ── DESVINCULAR ───────────────────────────────────────────────────
document.querySelectorAll('.btn-desvincular').forEach(btn => {
    btn.addEventListener('click', async function(e) {
        e.stopPropagation();
        const confirmed = await Swal.fire({
            title: '¿Desvincular usuario?',
            html: `El profesor <strong>${this.dataset.nombre}</strong> perderá acceso al portal docente.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Sí, desvincular',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('profesor_id', this.dataset.id);

        try {
            const resp = await fetch(`${BASE_PATH}/academic/profesores/desvincular`, {
                method: 'POST', body: fd
            }).then(r => r.json());

            if (resp.success) {
                Swal.fire({ icon: 'success', title: '¡Desvinculado!', text: resp.message, timer: 2000, showConfirmButton: false })
                    .then(() => window.location.reload());
            } else {
                Swal.fire('Error', resp.message, 'error');
            }
        } catch(e) {
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        }
    });
});
</script>

