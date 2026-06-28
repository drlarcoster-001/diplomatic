<?php
/**
 * MÓDULO: GESTIÓN DE RECURSOS
 * Archivo: app/views/resources/personal/create.php
 * Propósito: Formulario de registro inicial de nuevo personal operativo.
 * Versión: 1.1.0 - Búsqueda automática en tbl_professors por cédula.
 *
 * @var array $tipos
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
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
            <li class="breadcrumb-item active fw-bold" style="color:#a855f7;">Nuevo Registro</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Nuevo Registro de Personal</h2>
            <p class="text-muted small">Inicie el registro básico para habilitar el expediente completo.</p>
        </div>
        <a href="<?= $basePath ?>/resources/personal" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-x-circle me-1"></i> Cancelar
        </a>
    </div>

    <div class="card border-0 shadow-sm col-lg-6 mx-auto" style="border-top: 4px solid #a855f7 !important;">
        <div class="card-body p-5">

            <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
                <div class="alert alert-danger rounded-3 mb-4">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Ya existe un registro con esa cédula de identidad.
                </div>
            <?php endif; ?>

            <form action="<?= $basePath ?>/resources/personal/save" method="POST" autocomplete="off">
                <input type="hidden" name="profesor_id" id="profesor_id_hidden">

                <!-- CÉDULA con búsqueda automática -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase">Cédula de Identidad</label>
                    <input type="text" name="document_id" id="input_cedula"
                           class="form-control form-control-lg"
                           placeholder="Ej: V-12345678" required autofocus>
                </div>

                <!-- Ficha de profesor encontrado -->
                <div id="ficha-profesor" class="alert border-0 rounded-3 mb-3 d-flex align-items-center gap-3"
                     style="display:none !important; background:#f5f3ff; border-left: 4px solid #a855f7 !important;">
                    <img id="ficha-foto" src="" alt="Foto"
                         class="rounded-circle border border-2"
                         style="width:52px; height:52px; object-fit:cover; border-color:#a855f7 !important;">
                    <div>
                        <div class="fw-bold text-dark" id="ficha-nombre"></div>
                        <div class="small" style="color:#a855f7;">
                            <i class="bi bi-mortarboard me-1"></i> Profesor registrado en el sistema académico
                        </div>
                        <div class="small text-muted" id="ficha-tipo-profesor"></div>
                    </div>
                </div>

                <!-- Nombres y Apellidos -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-uppercase">Nombres</label>
                        <input type="text" name="first_name" id="input_nombres"
                               class="form-control form-control-lg" placeholder="Ej: María José" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-uppercase">Apellidos</label>
                        <input type="text" name="last_name" id="input_apellidos"
                               class="form-control form-control-lg" placeholder="Ej: González Pérez" required>
                    </div>
                </div>

                <!-- Tipo de Personal -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase" style="color:#a855f7;">Tipo de Personal</label>
                    <select name="tipo_personal_id" class="form-select form-select-lg" required>
                        <option value="" disabled selected>Seleccione el tipo...</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Fecha de Inicio -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase">Fecha de Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control form-control-lg" value="<?= date('Y-m-d') ?>">
                </div>

                <button type="submit" class="btn btn-lg w-100 rounded-pill shadow text-white" style="background:#a855f7;">
                    Crear Expediente <i class="bi bi-arrow-right-circle ms-2"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const basePath = '<?= $basePath ?>';
let buscarTimer = null;

document.getElementById('input_cedula').addEventListener('input', function () {
    clearTimeout(buscarTimer);
    const cedula = this.value.trim();

    if (cedula.length < 5) {
        ocultarFicha();
        return;
    }

    buscarTimer = setTimeout(() => buscarProfesor(cedula), 400);
});

function buscarProfesor(cedula) {
    fetch(`${basePath}/resources/personal/buscarProfesor?cedula=${encodeURIComponent(cedula)}`)
        .then(res => res.json())
        .then(data => {
            if (data.ok && data.profesor) {
                const p = data.profesor;

                document.getElementById('input_nombres').value   = p.first_name;
                document.getElementById('input_apellidos').value  = p.last_name;
                document.getElementById('profesor_id_hidden').value = p.id;
                if (p.email && document.getElementById('input_email')) {
                    document.getElementById('input_email').value = p.email;
                }
                if (p.phone && document.getElementById('input_phone')) {
                    document.getElementById('input_phone').value = p.phone;
                }

                document.getElementById('ficha-nombre').innerText       = p.first_name + ' ' + p.last_name;
                document.getElementById('ficha-tipo-profesor').innerText = p.professor_type ?? '';

                const foto = p.photo_path
                ? p.photo_path
                : `https://ui-avatars.com/api/?name=${encodeURIComponent(p.first_name + '+' + p.last_name)}&background=a855f7&color=fff&size=100`;
                document.getElementById('ficha-foto').src = foto;

                document.getElementById('ficha-profesor').style.display = 'flex';

            } else {
                ocultarFicha();
            }
        })
        .catch(() => ocultarFicha());
}

function ocultarFicha() {
    document.getElementById('ficha-profesor').style.display = 'none';
    document.getElementById('profesor_id_hidden').value = '';
}
</script>