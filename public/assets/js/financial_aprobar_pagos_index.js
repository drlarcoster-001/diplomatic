/**
 * MÓDULO: FINANCIERO / APROBAR PAGOS A PROVEEDORES
 * ARCHIVO: public/assets/js/financial_aprobar_pagos_index.js
 * PROPÓSITO: Botón "Reversar" en la pestaña Aprobados.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE = (window.APP_BASE_PATH || '') + '/financial/aprobar-pagos';

    document.querySelectorAll('.btn-reversar-aprobacion').forEach(btn => {
        btn.addEventListener('click', async () => {
            const confirmed = await Swal.fire({
                title: '¿Reversar esta aprobación?',
                html: `Se eliminará la orden de pago generada para <strong>${btn.dataset.numero}</strong> y volverá a estado PROCESADA.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Sí, reversar',
                cancelButtonText: 'Cancelar'
            });
            if (!confirmed.isConfirmed) return;

            const fd = new FormData();
            fd.append('pago_id', btn.dataset.id);

            try {
                const resp = await fetch(`${BASE}/reversar`, { method: 'POST', body: fd }).then(r => r.json());
                if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
                Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 2000, showConfirmButton: false })
                    .then(() => window.location.reload());
            } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
        });
    });
});