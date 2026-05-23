/**
 * MÓDULO: GESTIÓN DE ACCESO
 * Archivo: public/assets/js/register.js
 * Propósito: Gestión de la lógica de autenticación, validación de identidad y redirección.
 * VERSIÓN: 1.2.2 - Fix: Nota de SPAM en popup y redirección post-registro.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. CONFIGURACIÓN DE ELEMENTOS ---
    const phoneMask    = document.getElementById('phone_mask');
    const fullPhone    = document.getElementById('full_phone');
    const formRegister = document.getElementById('formRegister');
    const formPassword = document.getElementById('formPassword');
    const formForgot   = document.getElementById('formForgot');

    // Determinamos el basePath dinámicamente para recursos
    const basePath = window.location.pathname.split('/public')[0] + '/public';

    // --- 2. LÓGICA DE TELÉFONO INTERNACIONAL ---
    let iti;
    if (phoneMask) {
        iti = window.intlTelInput(phoneMask, {
            initialCountry: "ve",
            separateDialCode: true,
            dropdownContainer: document.body,
            utilsScript: basePath + "/assets/js/utils.js"
        });

        /**
         * FIX DE USABILIDAD: 
         * Se permite el borrado nativo. intlTelInput gestiona el formato automáticamente
         * sin necesidad de máscaras de regex que bloqueen el teclado.
         */
        phoneMask.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' || e.key === 'Delete') {
                return; 
            }
        });
    }

    // --- 3. LÓGICA AJAX PARA REGISTRO Y RECUPERACIÓN ---
    const handleAuthSubmit = (form) => {
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault(); 

            // Validación de teléfono para el formulario de registro
            if (form.id === 'formRegister' && phoneMask) {
                if (!iti.isValidNumber()) {
                    Swal.fire({ 
                        icon: 'warning', 
                        title: 'Teléfono inválido', 
                        text: 'Por favor, ingrese un número válido para el país seleccionado.', 
                        confirmButtonColor: '#0d6efd' 
                    });
                    return;
                }
                fullPhone.value = iti.getNumber();
            }

            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';

            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    // CAMBIO: Mensaje detallado con instrucción de SPAM y redirección
                    Swal.fire({ 
                        icon: 'success', 
                        title: '¡Excelente!', 
                        html: `
                            <p style="margin-bottom:10px;">Registro exitoso.</p>
                            <p style="font-size:0.95rem; color:#545454;">
                                Se le ha enviado a su correo la información de registro.<br>
                                <strong>(Revise su bandeja de spam o correo no deseado)</strong>
                            </p>
                        `, 
                        confirmButtonColor: '#0d6efd',
                        confirmButtonText: 'Ir al Inicio',
                        allowOutsideClick: false
                    }).then(() => {
                        window.location.href = basePath + '/';
                    });

                    this.reset(); 
                    if(phoneMask) phoneMask.value = ''; 
                } else {
                    Swal.fire({ icon: 'error', title: 'Atención', text: data.msg, confirmButtonColor: '#d33' });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor.' }))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    };

    handleAuthSubmit(formRegister);
    handleAuthSubmit(formForgot);

    // --- 4. LÓGICA PARA ASIGNACIÓN DE CONTRASEÑA ---
    if (formPassword) {
        formPassword.addEventListener('submit', function(e) {
            e.preventDefault();

            const pass    = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;

            if (pass !== confirm) {
                Swal.fire('Atención', 'Las contraseñas no coinciden.', 'warning');
                return;
            }

            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = 'Validando acceso...';

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
                        title: '¡Acceso Concedido!',
                        text: data.msg,
                        confirmButtonColor: '#0d6efd',
                        allowOutsideClick: false
                    }).then(() => {
                        window.location.href = basePath + '/';
                    });
                } else {
                    Swal.fire('Error', data.msg, 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'Validar y Finalizar';
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Error crítico en el servidor.', 'error');
                btn.disabled = false;
            });
        });
    }
});