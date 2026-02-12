/**
 * MÓDULO - public/assets/js/settings.js
 * Script de gestión para la interfaz de configuración.
 * Controla la visibilidad de protocolos y la comunicación AJAX con el servidor.
 */

document.addEventListener('change', (e) => {
    if (e.target.matches('input[type="radio"][name$="_provider"]')) {
        const pane = e.target.closest('.tab-pane');
        const provider = e.target.value;
        
        // LÓGICA DE VISIBILIDAD: Solo aparece en CUSTOM
        const pg = pane.querySelector('.protocol-group');
        if (pg) {
            if (provider === 'CUSTOM') {
                pg.classList.remove('d-none');
            } else {
                pg.classList.add('d-none');
            }
        }

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

window.saveActiveSettings = async function() {
    const pane = document.querySelector('.tab-pane.show.active');
    const form = pane.querySelector('form');
    const path = form.getAttribute('data-basepath');

    Swal.fire({ title: 'Guardando...', didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`${path}/settings/save-correo`, { method: 'POST', body: new FormData(form) });
        const data = await res.json();
        Swal.fire(data.ok ? 'Éxito' : 'Error', data.msg, data.ok ? 'success' : 'error');
    } catch (e) {
        console.error("Save Error:", e);
        Swal.fire('Error', 'Fallo técnico. Revisa la consola.', 'error');
    }
};

window.testActiveSettings = async function(formId) {
    const form = document.getElementById(formId);
    const path = form.getAttribute('data-basepath');
    
    const { value: email } = await Swal.fire({ title: 'Destinatario de prueba', input: 'email', showCancelButton: true });
    if (!email) return;

    const fd = new FormData(form);
    fd.append('email_test', email);
    Swal.fire({ title: 'Enviando...', didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`${path}/settings/test-correo`, { method: 'POST', body: fd });
        const data = await res.json();
        Swal.fire(data.ok ? 'Éxito' : 'Fallo', data.msg, data.ok ? 'success' : 'error');
    } catch (e) {
        Swal.fire('Error', 'Fallo de conexión.', 'error');
    }
};