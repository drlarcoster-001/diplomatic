/**
 * MÓDULO: GESTIÓN DE ACCESO
 * Archivo: public/assets/js/register.js
 * Propósito: Manejo de peticiones AJAX para Registro, Olvido y Cambio de Contraseña.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. CAPTURA DE FORMULARIOS
    const formRegister = document.getElementById('formRegister');
    const formForgot   = document.getElementById('formForgot');   // Agregado para recuperación
    const formPassword = document.getElementById('formPassword');

    /**
     * FUNCIÓN GENÉRICA PARA ENVÍO DE SOLICITUDES (Registro y Olvido)
     * Maneja el feedback del botón y la respuesta del servidor.
     */
    const handleAuthSubmit = (form) => {
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Evita la página blanca con JSON

            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            // Feedback visual
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                if (!response.ok) throw new Error('Error en la respuesta del servidor');
                return response.json();
            })
            .then(data => {
                if (data.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Excelente!',
                        text: data.msg,
                        confirmButtonColor: '#1a73e8'
                    });
                    this.reset(); // Limpiamos campos tras el éxito
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Atención',
                        text: data.msg,
                        confirmButtonColor: '#d33'
                    });
                }
            })
            .catch(error => {
                console.error('Error AJAX:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo comunicar con el servidor. Verifique su conexión.'
                });
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    };

    // Inicializamos los formularios de solicitud
    handleAuthSubmit(formRegister);
    handleAuthSubmit(formForgot);

    /**
     * RUTINA PARA ESTABLECER/CAMBIAR CONTRASEÑA
     * Incluye validación de coincidencia y longitud de caracteres.
     */
    if (formPassword) {
        formPassword.addEventListener('submit', function(e) {
            e.preventDefault();

            const pass = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const basePath = this.getAttribute('data-basepath') || '';

            // Validación local previa al envío
            if (pass !== confirm) {
                Swal.fire('Error', 'Las contraseñas no coinciden', 'warning');
                return;
            }

            if (pass.length < 8) {
                Swal.fire('Error', 'La contraseña debe tener al menos 8 caracteres', 'warning');
                return;
            }

            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Actualizando...';

            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Hecho!',
                        text: data.msg,
                        confirmButtonColor: '#1a73e8'
                    }).then(() => {
                        // Redirección al Login tras éxito
                        window.location.href = basePath + '/';
                    });
                } else {
                    Swal.fire('Error', data.msg, 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error crítico al procesar la solicitud', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }
});