/**
 * MÓDULO: RECURSOS HUMANOS / APROBAR NÓMINAS
 * ARCHIVO: public/assets/js/resources_aprobar_nomina.js
 * PROPÓSITO: Toggle de detalle por persona y acción de aprobar nómina
 *            (genera órdenes de pago).
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE      = (window.APP_BASE_PATH || '') + '/resources/aprobar-nomina';
    const NOMINA_ID = window.NOMINA_ID;

    // =========================================================================
    // TOGGLE DETALLE POR PERSONA
    // =========================================================================
    document.querySelectorAll('.btn-toggle-detalle').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = document.getElementById(btn.dataset.target);
            if (!row) return;
            row.style.display = row.style.display === 'none' ? '' : 'none';
            const icon = btn.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    });

    // =========================================================================
    // APROBAR NÓMINA
    // =========================================================================
    document.getElementById('btnAprobar')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Aprobar esta nómina?',
            text: 'Se generará una orden de pago individual por cada persona incluida.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('nomina_id', NOMINA_ID);

        try {
            const resp = await fetch(`${BASE}/aprobar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }

            Swal.fire({ icon: 'success', title: '¡Nómina aprobada!', text: resp.message, timer: 2500, showConfirmButton: false })
                .then(() => window.location.href = `${BASE}`);
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });
});