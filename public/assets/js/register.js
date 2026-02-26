/**
 * MÓDULO: GESTIÓN DE ACCESO
 * Archivo: public/assets/js/register.js
 * Propósito: Gestión de la lógica de autenticación, validación de identidad y procesamiento de formularios de acceso.
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
    if (phoneMask) {
        const iti = window.intlTelInput(phoneMask, {
            initialCountry: "ve",
            separateDialCode: true,
            dropdownContainer: document.body,
            utilsScript: basePath + "/assets/js/utils.js"
        });

        // Máscara Dinámica: (000)-(000-0000)
        phoneMask.addEventListener('input', function(e) {
            let num = e.target.value.replace(/\D/g, ''); 
            let x = num.match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            if (!x[1]) {
                e.target.value = '';
            } else {
                e.target.value = !x[2] ? '(' + x[1] 
                               : '(' + x[1] + ')-(' + x[2] + (x[3] ? '-' + x[3] + ')' : ')');
            }
        });

        // Sincronizar formato final antes del envío: (+58)-(414)-(536-5380)
        if (formRegister) {
            formRegister.addEventListener('submit', function() {
                const dialCode = iti.getSelectedCountryData().dialCode;
                const localNum = phoneMask.value;
                if (fullPhone) {
                    fullPhone.value = "(+" + dialCode + ")-" + localNum;
                }
            });
        }
    }

    // --- 3. LÓGICA AJAX PARA REGISTRO Y RECUPERACIÓN ---
    const handleAuthSubmit = (form) => {
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault(); 
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
                    Swal.fire({ icon: 'success', title: '¡Excelente!', text: data.msg, confirmButtonColor: '#0d6efd' });
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

    // --- 4. LÓGICA ESPECÍFICA PARA ASIGNACIÓN DE CONTRASEÑA ---
    if (formPassword) {
        formPassword.addEventListener('submit', function(e) {
            e.preventDefault();

            const pass    = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const bPath   = this.getAttribute('data-basepath') || '';

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
                        confirmButtonColor: '#0d6efd'
                    }).then(() => {
                        // REGLA DE ORO: Redirección automática al Login tras éxito
                        window.location.href = bPath + '/';
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