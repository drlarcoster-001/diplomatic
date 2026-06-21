/**
 * MÓDULO: FINANCIERO / ÓRDENES DE PAGO
 * ARCHIVO: public/assets/js/financial_ordenes_pago_manage.js
 * PROPÓSITO: Acciones Aprobar / Rechazar (con nota) / Anular (con contraseña)
 *            / Reversar.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE     = (window.APP_BASE_PATH || '') + '/financial/ordenes-pago';
    const ORDEN_ID = window.ORDEN_ID;

    document.getElementById('btnAprobar')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Aprobar esta orden de pago?',
            text: 'Pasará a Tesorería para su ejecución.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('id', ORDEN_ID);
        try {
            const resp = await fetch(`${BASE}/aprobar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            Swal.fire({ icon: 'success', title: '¡Aprobada!', text: resp.message, timer: 1800, showConfirmButton: false })
                .then(() => window.location.reload());
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    document.getElementById('btnRechazar')?.addEventListener('click', async () => {
        const { value: motivo, isConfirmed } = await Swal.fire({
            title: 'Rechazar orden de pago',
            input: 'textarea',
            inputLabel: 'Motivo del rechazo',
            inputPlaceholder: 'Explica por qué se rechaza esta orden...',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Rechazar',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => !value.trim() ? 'El motivo es obligatorio.' : undefined
        });
        if (!isConfirmed || !motivo) return;

        const fd = new FormData();
        fd.append('id', ORDEN_ID);
        fd.append('motivo', motivo.trim());
        try {
            const resp = await fetch(`${BASE}/rechazar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            Swal.fire({ icon: 'success', title: 'Rechazada', text: resp.message, timer: 1800, showConfirmButton: false })
                .then(() => window.location.reload());
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    document.getElementById('btnAnular')?.addEventListener('click', async () => {
        const { value: password, isConfirmed } = await Swal.fire({
            title: 'Anular orden de pago',
            html: `<div style="text-align:left;font-size:13px;color:#A32D2D;background:#FCEBEB;border:1px solid #E24B4A;border-radius:8px;padding:10px;margin-bottom:14px">
                     <i class="bi bi-exclamation-triangle me-1"></i>
                     Esta acción es <strong>definitiva</strong>. Para rehacer este pago tendrás que
                     regenerarlo desde el módulo original (Nómina o Pagos a Proveedores).
                   </div>`,
            input: 'password',
            inputLabel: 'Ingresa tu contraseña para confirmar',
            inputPlaceholder: 'Contraseña',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Anular',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => !value ? 'Debes ingresar tu contraseña.' : undefined
        });
        if (!isConfirmed || !password) return;

        const fd = new FormData();
        fd.append('id', ORDEN_ID);
        fd.append('password', password);
        try {
            const resp = await fetch(`${BASE}/anular`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            Swal.fire({ icon: 'success', title: 'Anulada', text: resp.message, timer: 3000, showConfirmButton: false })
                .then(() => window.location.reload());
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    document.getElementById('btnReversar')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Reversar esta orden?',
            text: 'Volverá al estado PENDIENTE.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Sí, reversar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('id', ORDEN_ID);
        try {
            const resp = await fetch(`${BASE}/reversar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1800, showConfirmButton: false })
                .then(() => window.location.reload());
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });
});