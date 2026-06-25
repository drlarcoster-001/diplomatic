/**
 * MÓDULO: ACADÉMICO / APROBAR ACTAS
 * ARCHIVO: public/assets/js/academic_aprobar_actas.js
 * PROPÓSITO: Lógica de aprobación y reversión de actas vía AJAX con
 *            confirmación SweetAlert. Resaltado de filas aprobadas/reprobadas.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', () => {
    const BASE    = window.APP_BASE_PATH || '';
    const ACTA_ID = window.ACTA_ID || 0;

    // =========================================================================
    // RESALTAR FILAS
    // =========================================================================
    document.querySelectorAll('tbody tr[data-aprobado]').forEach(fila => {
        if (fila.dataset.aprobado === '1') {
            fila.classList.add('fila-aprobado');
        } else if (fila.dataset.aprobado === '0') {
            fila.classList.add('fila-reprobado');
        }
    });

    // =========================================================================
    // APROBAR ACTA
    // =========================================================================
    document.getElementById('btnAprobar')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Aprobar esta acta?',
            text: 'Las notas quedarán definitivas y el profesor no podrá modificarlas.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('id', ACTA_ID);

        try {
            const resp = await fetch(`${BASE}/academic/aprobar-actas/aprobar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire('Error', resp.message, 'error'); return; }
            Swal.fire({ icon: 'success', title: '¡Acta aprobada!', text: resp.message, timer: 2000, showConfirmButton: false })
                .then(() => window.location.reload());
        } catch (e) {
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        }
    });

    // =========================================================================
    // REVERSAR ACTA
    // =========================================================================
    document.getElementById('btnReversar')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Reversar esta acta?',
            text: 'El acta volverá a estado BORRADOR y el profesor podrá corregir las notas.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Sí, reversar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('id', ACTA_ID);

        try {
            const resp = await fetch(`${BASE}/academic/aprobar-actas/reversar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire('Error', resp.message, 'error'); return; }
            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 2000, showConfirmButton: false })
                .then(() => window.location.href = `${BASE}/academic/aprobar-actas`);
        } catch (e) {
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        }
    });
});