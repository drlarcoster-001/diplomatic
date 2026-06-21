/**
 * MÓDULO: FINANCIERO / APROBAR PAGOS A PROVEEDORES
 * ARCHIVO: public/assets/js/financial_aprobar_pagos.js
 * PROPÓSITO: Acción de aprobar el pago (genera la orden de pago).
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE    = (window.APP_BASE_PATH || '') + '/financial/aprobar-pagos';
    const PAGO_ID = window.PAGO_ID;

    document.getElementById('btnAprobar')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Aprobar este pago?',
            text: 'Se generará una orden de pago para este proveedor.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('pago_id', PAGO_ID);

        try {
            const resp = await fetch(`${BASE}/aprobar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }

            Swal.fire({ icon: 'success', title: '¡Pago aprobado!', text: resp.message, timer: 2500, showConfirmButton: false })
                .then(() => window.location.href = BASE);
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });
});