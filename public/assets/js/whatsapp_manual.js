/**
 * MÓDULO - public/assets/js/whatsapp_manual.js
 * Lógica para la generación de enlaces wa.me y trazabilidad.
 * Implementa el reemplazo de variables dinámicas y codificación URL.
 */

async function saveWaTemplate(btn) {
    const form = btn.closest('form');
    const path = form.getAttribute('data-basepath');
    Swal.fire({ title: 'Procesando...', didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`${path}/settings/whatsapp/save-template`, {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await res.json();
        Swal.fire(data.ok ? 'Éxito' : 'Error', data.msg, data.ok ? 'success' : 'error');
    } catch (e) { Swal.fire('Error', 'Fallo de conexión', 'error'); }
}

async function testManualLink(evento, btn) {
    const form = btn.closest('form');
    const path = form.getAttribute('data-basepath');
    const template = form.querySelector('textarea').value;

    const { value: vals } = await Swal.fire({
        title: 'Prueba de Enlace',
        html:
            '<input id="t-nom" class="swal2-input" placeholder="Nombre Estudiante" value="Juan Pérez">' +
            '<input id="t-tel" class="swal2-input" placeholder="Teléfono (Ej: 584120000000)">' +
            '<input id="t-dip" class="swal2-input" placeholder="Diplomado" value="Diplomado de Prueba">',
        preConfirm: () => ({
            n: document.getElementById('t-nom').value,
            t: document.getElementById('t-tel').value,
            d: document.getElementById('t-dip').value
        })
    });

    if (vals && vals.t) {
        // Paso 4: Generar mensaje dinámico
        let msj = template.replace('{NOMBRE}', vals.n)
                          .replace('{DIPLOMADO}', vals.d)
                          .replace('{URL_PORTAL}', 'http://diplomatic.com/portal');

        // Paso 5 y 6: Enlace wa.me codificado
        const waLink = `https://wa.me/${vals.t}?text=${encodeURIComponent(msj)}`;
        window.open(waLink, '_blank');

        // Paso 7: Trazabilidad
        const fd = new FormData();
        fd.append('estudiante', vals.n);
        fd.append('evento', evento);
        fd.append('telefono', vals.t);
        await fetch(`${path}/settings/whatsapp/log`, { method: 'POST', body: fd });
        
        setTimeout(() => { location.reload(); }, 1500);
    }
}