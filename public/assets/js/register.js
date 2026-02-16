/**
 * MODULE: REGISTER
 * File: public/assets/js/register.js
 */
"use strict";

document.addEventListener('DOMContentLoaded', function() {
    const formPass = document.getElementById('formPassword');
    if (formPass) {
        formPass.addEventListener('submit', async function(e) {
            e.preventDefault();
            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('confirm_password').value;
            if(p1 !== p2) { Swal.fire('Error', 'Las claves no coinciden', 'error'); return; }

            const btn = document.getElementById('btnPass');
            btn.disabled = true; btn.innerHTML = 'Procesando...';
            try {
                const res = await fetch(this.action, { method: 'POST', body: new FormData(this) });
                const data = await res.json();
                Swal.fire(data.ok ? '¡Éxito!' : 'Error', data.msg, data.ok ? 'success' : 'error').then(() => {
                    if(data.ok) window.location.href = this.getAttribute('data-basepath') + '/';
                });
            } catch(err) { Swal.fire('Error', 'Fallo técnico', 'error'); }
            btn.disabled = false; btn.innerHTML = 'Validar y Crear Cuenta';
        });
    }
});