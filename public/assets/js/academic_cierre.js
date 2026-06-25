/**
 * MÓDULO: ACADÉMICO / CIERRE ACADÉMICO
 * ARCHIVO: public/assets/js/academic_cierre.js
 * PROPÓSITO: Modal de contacto del profesor por modalidad y confirmación
 *            de cierre de oferta vía AJAX.
 * VERSIÓN: 1.1.0 - Agrega modal profesor por modalidad.
 */

document.addEventListener('DOMContentLoaded', () => {
    const BASE        = window.APP_BASE_PATH || '';
    const OFFERING_ID = window.OFFERING_ID   || 0;
    const PROFESORES  = window.PROFESORES    || {};

    const labelMod = {
        'TEORICA':  'Teórica',
        'PRACTICA': 'Práctica',
        'VIRTUAL':  'Virtual'
    };

    // =========================================================================
    // MODAL PROFESOR POR MODALIDAD
    // =========================================================================
    document.querySelectorAll('.btn-profesor-info').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const modalidad = btn.dataset.modalidad;
            const prof      = PROFESORES[modalidad];
            const titulo    = document.getElementById('modalProfesorTitulo');
            const body      = document.getElementById('modalProfesorBody');

            titulo.innerHTML = `<i class="bi bi-person-circle me-2"></i>Profesor — ${labelMod[modalidad] || modalidad}`;

            if (!prof) {
                body.innerHTML = `
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-person-x fs-2 d-block mb-2 opacity-50"></i>
                        <p class="mb-0">Sin profesor asignado para esta modalidad.</p>
                    </div>`;
            } else {
                const phone    = (prof.telefono || '').replace(/[^0-9]/g, '');
                const waLink   = phone ? `https://wa.me/${phone}` : null;
                const emailLink = prof.email ? `mailto:${prof.email}` : null;

                body.innerHTML = `
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:48px;height:48px;background:#EEEDFE;color:#533AB7;flex-shrink:0">
                            <i class="bi bi-person-fill fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-6">${prof.full_name || '—'}</div>
                            <small class="text-muted">${labelMod[modalidad] || modalidad}</small>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex flex-column gap-2 mt-3">
                        ${prof.email ? `
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-envelope text-muted"></i>
                            ${emailLink
                                ? `<a href="${emailLink}" class="small text-decoration-none">${prof.email}</a>`
                                : `<span class="small">${prof.email}</span>`}
                        </div>` : ''}
                        ${prof.telefono ? `
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-telephone text-muted"></i>
                                <span class="small">${prof.telefono}</span>
                            </div>
                            ${waLink ? `
                            <a href="${waLink}" target="_blank"
                               class="btn btn-sm btn-success rounded-pill px-3">
                                <i class="bi bi-whatsapp me-1"></i>WhatsApp
                            </a>` : ''}
                        </div>` : ''}
                        ${!prof.email && !prof.telefono ? `
                        <p class="text-muted small mb-0">Sin datos de contacto registrados.</p>` : ''}
                    </div>`;
            }

            new bootstrap.Modal(document.getElementById('modalProfesor')).show();
        });
    });

    // =========================================================================
    // CERRAR OFERTA
    // =========================================================================
    document.getElementById('btnCerrar')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Cerrar esta oferta académica?',
            html: 'Esta acción es <strong>irreversible</strong>.<br>Todos los estudiantes aptos quedarán registrados como egresados.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, cerrar oferta',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('offering_id', OFFERING_ID);

        Swal.fire({ title: 'Cerrando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const resp = await fetch(`${BASE}/academic/cierre/cerrar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire('Error', resp.message, 'error'); return; }
            Swal.fire({ icon: 'success', title: '¡Oferta cerrada!', text: resp.message, timer: 2500, showConfirmButton: false })
                .then(() => window.location.href = `${BASE}/academic/cierre`);
        } catch (e) {
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        }
    });

   document.getElementById('btnReversar')?.addEventListener('click', async () => {
    const { value: motivo, isConfirmed } = await Swal.fire({
        title: '¿Reversar el cierre?',
        html: `La oferta volverá a estado <strong>ABIERTA</strong> y las actas aprobadas volverán a BORRADOR.<br><br>
               <textarea id="swal-motivo" class="swal2-textarea" placeholder="Explica el motivo de la reversa..." style="height:100px"></textarea>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        confirmButtonText: 'Sí, reversar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const val = document.getElementById('swal-motivo').value.trim();
            if (!val) {
                Swal.showValidationMessage('El motivo es obligatorio.');
                return false;
            }
            return val;
        }
    });
    if (!isConfirmed || !motivo) return;

    const fd = new FormData();
    fd.append('offering_id', OFFERING_ID);
    fd.append('motivo', motivo);

    try {
        const resp = await fetch(`${BASE}/academic/cierre/reversar`, { method: 'POST', body: fd }).then(r => r.json());
        if (!resp.success) { Swal.fire('Error', resp.message, 'error'); return; }
        Swal.fire({ icon: 'success', title: '¡Reversado!', text: resp.message, timer: 2000, showConfirmButton: false })
            .then(() => window.location.href = `${BASE}/academic/cierre`);
    } catch (e) {
        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
    }
}); 
});