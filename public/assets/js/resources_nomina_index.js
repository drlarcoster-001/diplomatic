/**
 * MÓDULO: RECURSOS HUMANOS / NÓMINA
 * ARCHIVO: public/assets/js/resources_nomina_index.js
 * PROPÓSITO: Acciones del listado de nóminas — descartar (BORRADOR) y
 *            reversar a borrador (PROCESADA).
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE = (window.APP_BASE_PATH || '') + '/resources/nomina';

    document.querySelectorAll('.btn-descartar').forEach(btn => {
        btn.addEventListener('click', async () => {
            const confirmed = await Swal.fire({
                title: '¿Descartar esta nómina?',
                html: `Se eliminará <strong>${btn.dataset.nombre}</strong> junto con todo su personal y conceptos. Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Sí, descartar',
                cancelButtonText: 'Cancelar'
            });
            if (!confirmed.isConfirmed) return;

            const fd = new FormData();
            fd.append('nomina_id', btn.dataset.id);

            try {
                const resp = await fetch(`${BASE}/descartar`, { method: 'POST', body: fd }).then(r => r.json());
                if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
                Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false })
                    .then(() => window.location.reload());
            } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
        });
    });

    document.querySelectorAll('.btn-reversar').forEach(btn => {
        btn.addEventListener('click', async () => {
            const confirmed = await Swal.fire({
                title: '¿Reversar esta nómina?',
                html: `<strong>${btn.dataset.nombre}</strong> retrocederá un paso en el flujo. Si estaba APROBADA, se eliminarán sus órdenes de pago (si ninguna fue pagada).`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Sí, reversar',
                cancelButtonText: 'Cancelar'
            });
            if (!confirmed.isConfirmed) return;

            const fd = new FormData();
            fd.append('nomina_id', btn.dataset.id);

            try {
                const resp = await fetch(`${BASE}/reversar`, { method: 'POST', body: fd }).then(r => r.json());
                if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
                Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false })
                    .then(() => window.location.reload());
            } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
        });
    });
});