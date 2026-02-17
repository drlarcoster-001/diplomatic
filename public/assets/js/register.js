/**
 * MÓDULO: GESTIÓN DE ACCESO
 * Archivo: public/assets/js/register.js
 * Propósito: Manejo de peticiones AJAX para Registro y Recuperación de Contraseña.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. CAPTURA DE FORMULARIOS
    // Usamos el ID unificado 'formRegister' que definimos en la vista
    const formRegister = document.getElementById('formRegister');
    const formPassword = document.getElementById('formPassword');

    /**
     * RENTINA PARA REGISTRO Y SOLICITUD DE RECUPERACIÓN
     */
    if (formRegister) {
        formRegister.addEventListener('submit', function(e) {
            // VITAL: Detenemos el envío tradicional para evitar la página blanca con JSON
            e.preventDefault();

            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            // Feedback visual: Bloqueamos botón mientras el servidor procesa/envía el correo
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
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
                    this.reset(); // Limpiamos el formulario tras el éxito
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
                // Restauramos el botón pase lo que pase
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }

    /**
     * RUTINA PARA ESTABLECER/CAMBIAR CONTRASEÑA (password.php / password_forgot.php)
     */
    if (formPassword) {
        formPassword.addEventListener('submit', function(e) {
            e.preventDefault();

            const pass = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;

            // Validación básica de coincidencia antes de disparar al servidor
            if (pass !== confirm) {
                Swal.fire('Error', 'Las contraseñas no coinciden', 'warning');
                return;
            }

            if (pass.length < 8) {
                Swal.fire('Error', 'La contraseña debe tener al menos 8 caracteres', 'warning');
                return;
            }

            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;

            fetch(this.action, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Hecho!',
                        text: data.msg,
                    }).then(() => {
                        // Redirigimos al login tras cambiar la clave
                        window.location.href = this.getAttribute('data-basepath') + '/';
                    });
                } else {
                    Swal.fire('Error', data.msg, 'error');
                    btn.disabled = false;
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error crítico al procesar la solicitud', 'error');
                btn.disabled = false;
            });
        });
    }
});