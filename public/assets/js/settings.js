/**
 * MÓDULO: CONFIGURACIÓN
 * Archivo: public/assets/js/settings.js
 * Propósito: Gestión de SMTP, autoconfiguración y diagnóstico de errores.
 */

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('change', (e) => {
        if (e.target.matches('input[type="radio"][name$="_provider"]')) {
            const pane = e.target.closest('.tab-pane');
            const provider = e.target.value;
            
            // Selector de protocolo SMTP/POP3
            const pg = pane.querySelector('.protocol-group');
            if (pg) provider === 'CUSTOM' ? pg.classList.remove('d-none') : pg.classList.add('d-none');

            const configs = {
                'GMAIL':   { h: 'smtp.gmail.com', p: 587, s: 'TLS' },
                'OUTLOOK': { h: 'smtp.office365.com', p: 587, s: 'TLS' },
                'YAHOO':   { h: 'smtp.mail.yahoo.com', p: 587, s: 'TLS' },
                'CUSTOM':  { h: '', p: 465, s: 'SSL' }
            };
            const c = configs[provider];
            if (c) {
                pane.querySelector('input[name="smtp_host"]').value = c.h;
                pane.querySelector('input[name="smtp_port"]').value = c.p;
                pane.querySelector('select[name="smtp_security"]').value = c.s;
            }
        }
    });
});

window.saveActiveSettings = async function() {
    const pane = document.querySelector('.tab-pane.show.active');
    const form = pane ? pane.querySelector('form') : null;
    if (!form) return;

    const path = form.getAttribute('data-basepath');
    Swal.fire({ title: 'Guardando...', didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`${path}/settings/save-correo`, { method: 'POST', body: new FormData(form) });
        const text = await res.text();
        console.log("Debug Save:", text);
        const data = JSON.parse(text);
        Swal.fire(data.ok ? 'Éxito' : 'Error', data.msg, data.ok ? 'success' : 'error');
    } catch (e) {
        console.error("Save Error:", e);
        Swal.fire('Error', 'Fallo técnico. Revisa la consola (F12).', 'error');
    }
};

window.testActiveSettings = async function(formId) {
    const form = document.querySelector('.tab-pane.show.active form');
    const path = form.getAttribute('data-basepath');
    const { value: email } = await Swal.fire({ title: 'Probar Correo', input: 'email', showCancelButton: true });
    if (!email) return;

    const fd = new FormData(form);
    fd.append('email_test', email);
    Swal.fire({ title: 'Enviando...', didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`${path}/settings/test-correo`, { method: 'POST', body: fd });
        const text = await res.text();
        console.log("Debug Test:", text);
        const data = JSON.parse(text);
        Swal.fire(data.ok ? 'Éxito' : 'Fallo', data.msg, data.ok ? 'success' : 'error');
    } catch (e) {
        console.error("Test Error:", e);
        Swal.fire('Error', 'Fallo al conectar. Revisa la consola.', 'error');
    }
};