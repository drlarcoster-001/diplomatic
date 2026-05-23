/**
 * MODULE: SETTINGS & CONFIGURATION
 * File: public/assets/js/settings.js
 * Propósito: Lógica de autocompletado SMTP y gestión de guardado AJAX.
 */

"use strict";

window.saveActiveSettings = async function() {
    const activePane = document.querySelector('.tab-pane.active');
    const form = activePane.querySelector('form');
    const path = form.getAttribute('data-basepath');
    
    Swal.fire({ title: 'Guardando...', didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`${path}/settings/save-correo`, {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await res.json();
        Swal.fire(data.ok ? 'Éxito' : 'Error', data.msg, data.ok ? 'success' : 'error');
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
    }
};

window.detectProvider = function(input, prefix) {
    const email = input.value.trim();
    const domain = email.split('@')[1];
    if (!domain) return;
    const presets = {
        'gmail.com': { h: 'smtp.gmail.com', p: 587, s: 'TLS' },
        'hotmail.com': { h: 'smtp-mail.outlook.com', p: 587, s: 'TLS' },
        'outlook.com': { h: 'smtp-mail.outlook.com', p: 587, s: 'TLS' }
    };
    if (presets[domain]) {
        const form = document.getElementById(`form-${prefix}`);
        form.querySelector('[name="smtp_host"]').value = presets[domain].h;
        form.querySelector('[name="smtp_port"]').value = presets[domain].p;
        form.querySelector('[name="smtp_security"]').value = presets[domain].s;
    }
};

window.testProfessionalConnection = async function(formId) {
    const form = document.getElementById(formId);
    const path = form.getAttribute('data-basepath');
    const { value: email } = await Swal.fire({ title: 'Email de destino', input: 'email', showCancelButton: true });
    
    if (!email) return;

    const fd = new FormData(form);
    fd.append('email_test', email);
    fd.append('mode', 'connection');

    Swal.fire({ title: 'Enviando prueba...', didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`${path}/settings/test-correo`, { method: 'POST', body: fd });
        const data = await res.json();
        Swal.fire(data.ok ? 'Enviado' : 'Error', data.msg, data.ok ? 'success' : 'error');
    } catch (e) {
        Swal.fire('Error', 'Fallo técnico de comunicación.', 'error');
    }
};

window.insertTag = function(btn, tag) {
    const textarea = btn.closest('.card-body').querySelector('textarea');
    textarea.value += tag;
    textarea.focus();
};

window.togglePassword = function(btn) {
    const input = btn.closest('.input-group').querySelector('input');
    input.type = input.type === 'password' ? 'text' : 'password';
};