<?php
/**
 * MÓDULO: PERFIL DE USUARIO
 * Archivo: app/views/users/profile.php
 * Propósito: Interfaz simplificada para la gestión de datos personales y profesionales.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$avatarPath = $basePath . '/assets/img/avatars/' . ($u['avatar'] ?? 'default_avatar.png');
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid py-4">
    <form id="form-profile-full" enctype="multipart/form-data" data-basepath="<?= $basePath ?>">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <img src="<?= $avatarPath ?>" id="imgPreview" class="rounded-circle border shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                            <label for="avatarInput" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 32px; height: 32px; cursor: pointer;">
                                <i class="bi bi-camera-fill" style="font-size: 0.9rem;"></i>
                            </label>
                            <input type="file" id="avatarInput" name="avatar" class="d-none" accept="image/*">
                            <input type="hidden" name="current_avatar" value="<?= htmlspecialchars($u['avatar'] ?? 'default_avatar.png') ?>">
                        </div>
                        <h5 class="mt-3 fw-bold mb-0"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></h5>
                        <p class="text-muted small"><?= htmlspecialchars($u['email']) ?></p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nombres</label>
                            <input type="text" class="form-control bg-light border-0" value="<?= $u['first_name'] ?>" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Apellidos</label>
                            <input type="text" class="form-control bg-light border-0" value="<?= $u['last_name'] ?>" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Cédula / ID</label>
                            <input type="text" class="form-control bg-light border-0" value="<?= $u['document_id'] ?>" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Teléfono</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($u['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Procedencia</label>
                            <input type="text" name="provenance" class="form-control" value="<?= htmlspecialchars($u['provenance'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Carrera de Pregrado</label>
                            <input type="text" name="undergraduate_degree" class="form-control" value="<?= htmlspecialchars($u['undergraduate_degree'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Dirección Detallada</label>
                            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($u['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="text-end mt-4 pt-3 border-top">
                        <a href="<?= $basePath ?>/dashboard" class="btn btn-light px-4 fw-bold me-2 rounded-3 border">
                            Volver
                        </a>
                        <button type="submit" id="btnGuardar" class="btn btn-primary px-5 fw-bold shadow rounded-3">
                            Guardar
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Previsualización de imagen
document.getElementById('avatarInput').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('imgPreview').src = e.target.result;
        reader.readAsDataURL(this.files[0]);
    }
});

// Procesamiento AJAX del Perfil
document.getElementById('form-profile-full').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardar');
    const bPath = this.getAttribute('data-basepath');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Guardando...';

    const formData = new FormData(this);
    fetch(`${bPath}/profile/update`, { 
        method: 'POST', 
        body: formData 
    })
    .then(r => r.json()) 
    .then(data => {
        if(data.ok) {
            Swal.fire({
                title: '¡Hecho!',
                text: 'Tu información ha sido actualizada correctamente.',
                icon: 'success',
                confirmButtonColor: '#0d6efd'
            }).then(() => {
                window.location.href = `${bPath}/dashboard`;
            });
        } else {
            Swal.fire('Atención', data.msg, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error de Red', 'No se pudo conectar con el servidor.', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Guardar';
    });
});
</script>