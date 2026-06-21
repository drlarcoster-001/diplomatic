/**
 * MÓDULO: RECURSOS HUMANOS / APROBAR NÓMINAS
 * ARCHIVO: public/assets/js/resources_aprobar_nomina_index.js
 * PROPÓSITO: Botón "Reversar" en la pestaña de nóminas Aprobadas — elimina las
 *            órdenes de pago generadas y regresa la nómina a PROCESADA.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE = (window.APP_BASE_PATH || '') + '/resources/aprobar-nomina';

    document.querySelectorAll('.btn-reversar-aprobacion').forEach(btn => {
        btn.addEventListener('click', async () => {
            const confirmed = await Swal.fire({
                title: '¿Reversar esta aprobación?',
                html: `Se eliminarán las órdenes de pago generadas para <strong>${btn.dataset.nombre}</strong> y la nómina volverá a estado PROCESADA.`,
                icon: 'warning',
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
                Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 2000, showConfirmButton: false })
                    .then(() => window.location.reload());
            } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
        });
    });
});