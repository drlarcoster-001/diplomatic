<?php
/**
 * MÓDULO: GESTIÓN DE SEGURIDAD
 * Archivo: app/views/users/security.php
 * Propósito: Interfaz para la actualización de credenciales de acceso con validación de identidad previa y auditoría de eventos.
 */

declare(strict_types=1);

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                
                <div class="card-header bg-white border-0 py-4 text-center">
                    <div class="bg-light-primary text-primary mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 65px; height: 65px; background-color: #eef2ff;">
                        <i class="bi bi-shield-lock-fill" style="font-size: 1.8rem; color: #0d6efd;"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Seguridad de la Cuenta</h5>
                    <p class="text-muted small">Actualice sus credenciales para mantener su acceso protegido.</p>
                </div>

                <div class="card-body p-4 pt-0">
                    <form id="form-security-update" data-basepath="<?= $basePath ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Contraseña Anterior</label>
                            <input type="password" name="old_password" class="form-control bg-light border-0 py-2" required placeholder="Su clave actual">
                            <div class="text-end mt-1">
                                <a href="javascript:void(0)" onclick="handleForgotOldPass()" class="text-decoration-none small text-primary fw-semibold">
                                    No recuerdo mi contraseña anterior
                                </a>
                            </div>
                        </div>

                        <hr class="my-4 opacity-10">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Contraseña Nueva</label>
                            <input type="password" name="new_password" id="new_password" class="form-control bg-light border-0 py-2" required placeholder="Mínimo 8 caracteres">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-dark">Confirma Contraseña Nueva</label>
                            <input type="password" id="confirm_password" class="form-control bg-light border-0 py-2" required placeholder="Repita la nueva clave">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" id="btnUpdatePass" class="btn btn-dark fw-bold py-2 shadow-sm">
                                <i class="bi bi-key me-2"></i> Actualizar Contraseña
                            </button>
                            <a href="<?= $basePath ?>/dashboard" class="btn btn-link btn-sm text-muted text-decoration-none">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Lógica para cuando el usuario olvida su clave actual.
 */
function handleForgotOldPass() {
    Swal.fire({
        title: '¿Olvidó su contraseña?',
        text: "Por seguridad, si no conoce su clave actual debe cerrar sesión y usar el flujo de recuperación de contraseña desde el Login.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Ir al Login',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#000'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= $basePath ?>/logout';
        }
    });
}

/**
 * Procesamiento del formulario vía AJAX
 */
document.getElementById('form-security-update').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const newPass = document.getElementById('new_password').value;
    const confirm = document.getElementById('confirm_password').value;
    const bPath = this.getAttribute('data-basepath');

    // Validación básica antes de enviar al servidor
    if (newPass !== confirm) {
        return Swal.fire('Error', 'La nueva contraseña y su confirmación no coinciden.', 'error');
    }

    if (newPass.length < 8) {
        return Swal.fire('Seguridad', 'La nueva contraseña debe tener al menos 8 caracteres.', 'warning');
    }

    Swal.fire({
        title: '¿Confirmar cambio?',
        text: "¡Todo listo! Al confirmar, actualizaremos tu contraseña para que tu cuenta esté siempre segura.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#000',
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData(this);
            const btn = document.getElementById('btnUpdatePass');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';

            fetch(`${bPath}/profile/change-password`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    Swal.fire({
                        title: '¡Contraseña Actualizada!',
                        text: data.msg,
                        icon: 'success',
                        confirmButtonColor: '#0d6efd'
                    }).then(() => {
                        /**
                         * REGLA DE ORO: Redirección al Dashboard tras éxito
                         * Se corrige la ruta de destino para evitar el retorno al perfil.
                         */
                        window.location.href = `${bPath}/dashboard`;
                    });
                } else {
                    Swal.fire('Error', data.msg, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error de Conexión', 'No se pudo contactar con el servidor.', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-key me-2"></i> Actualizar Contraseña';
            });
        }
    });
});
</script>