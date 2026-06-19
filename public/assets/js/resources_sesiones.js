/**
 * MÓDULO: RECURSOS HUMANOS / SESIONES
 * ARCHIVO: public/assets/js/resources_sesiones.js
 * PROPÓSITO: Lógica del módulo de Programar Sesiones. Selector de fechas simple
 *            con input date + botón agregar (sin Flatpickr).
 * VERSIÓN: 1.2.0 - Eliminado Flatpickr, reemplazado por selector de fechas nativo.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE        = (window.APP_BASE_PATH || '') + '/resources/sesiones';
    const OFFERING_ID = window.OFFERING_ID;

    let personalId       = null;
    let personalNombre   = '';
    let pendingHorarioId = null;
    let pendingTipo      = null;
    let fechasSeleccionadas = [];

    // =========================================================================
    // PESTAÑAS
    // =========================================================================
    document.querySelectorAll('#tabsSesiones .nav-link').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#tabsSesiones .nav-link').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tabTeoricos').style.display  = btn.dataset.tab === 'teoricos'  ? '' : 'none';
            document.getElementById('tabPracticos').style.display = btn.dataset.tab === 'practicos' ? '' : 'none';
        });
    });

    // =========================================================================
    // SELECTOR DE PERSONAL
    // =========================================================================
    document.getElementById('selectPersonal').addEventListener('change', async function () {
        personalId     = this.value ? parseInt(this.value) : null;
        personalNombre = this.options[this.selectedIndex].text || '';

        const card = document.getElementById('cardVisualizador');
        if (!personalId) { card.style.display = 'none'; return; }
        card.style.display = '';
        await recargarSesiones();
    });

    // =========================================================================
    // VISUALIZADOR DE SESIONES DEL PERSONAL
    // =========================================================================
    async function recargarSesiones() {
        if (!personalId) return;
        try {
            const resp    = await fetch(`${BASE}/getSesiones?personal_id=${personalId}&offering_id=${OFFERING_ID}`).then(r => r.json());
            const wrap    = document.getElementById('visualizadorWrap');
            const badge   = document.getElementById('badgeSesiones');
            const sesiones = resp.data || [];
            badge.textContent = sesiones.length;

            if (!sesiones.length) {
                wrap.innerHTML = `<div class="text-center text-muted small py-3">Sin sesiones asignadas en esta oferta.</div>`;
                return;
            }

            wrap.innerHTML = sesiones.map(s => {
                const est = s.estado === 'DICTADA'
                    ? 'style="background:#E1F5EE;border:1px solid #1D9E75;color:#085041"'
                    : 'style="background:#EEEDFE;border:1px solid #AFA9EC;color:#3C3489"';
                return `<div class="ses-sesion-item">
                    <div>
                        <div class="small fw-bold">${s.descripcion || '—'}</div>
                        <div class="small text-muted">${formatFecha(s.fecha)}</div>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge rounded-pill" ${est} style="font-size:10px">${s.estado}</span>
                        ${s.estado !== 'DICTADA' ? `<button class="ses-btn-x" data-sid="${s.id}">✕</button>` : ''}
                    </div>
                </div>`;
            }).join('');

            wrap.querySelectorAll('.ses-btn-x').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const fd = new FormData();
                    fd.append('id',          btn.dataset.sid);
                    fd.append('personal_id', personalId);
                    fd.append('offering_id', OFFERING_ID);
                    const r = await fetch(`${BASE}/delete`, { method: 'POST', body: fd }).then(r => r.json());
                    if (r.success) {
                        await recargarSesiones();
                        Swal.fire({ icon: 'success', title: 'Sesión eliminada', timer: 1200, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: r.message });
                    }
                });
            });
        } catch (e) { console.error(e); }
    }

    // =========================================================================
    // SELECTOR DE FECHAS (sin Flatpickr)
    // =========================================================================
    document.getElementById('btnAddFecha')?.addEventListener('click', () => {
        const input = document.getElementById('modalFechaInput');
        const fecha = input.value;
        if (!fecha) return;
        if (fechasSeleccionadas.includes(fecha)) {
            Swal.fire({ icon: 'warning', title: 'Fecha ya agregada', timer: 1000, showConfirmButton: false });
            return;
        }
        fechasSeleccionadas.push(fecha);
        renderPills();
        input.value = '';
    });

    function renderPills() {
        const wrap = document.getElementById('modalFechasPills');
        if (!wrap) return;
        if (!fechasSeleccionadas.length) {
            wrap.innerHTML = '<span class="text-muted small">No hay fechas seleccionadas.</span>';
            return;
        }
        wrap.innerHTML = fechasSeleccionadas.map(f => {
            const label = new Date(f + 'T12:00:00').toLocaleDateString('es-VE', { day: '2-digit', month: 'short', year: 'numeric' });
            return `<span class="badge rounded-pill me-1 mb-1"
                style="background:#EEEDFE;border:1px solid #AFA9EC;color:#3C3489;font-size:11px;cursor:pointer"
                data-fecha="${f}" title="Clic para quitar">${label} ✕</span>`;
        }).join('');

        wrap.querySelectorAll('[data-fecha]').forEach(pill => {
            pill.addEventListener('click', () => {
                fechasSeleccionadas = fechasSeleccionadas.filter(f => f !== pill.dataset.fecha);
                renderPills();
            });
        });
    }

    // =========================================================================
    // BOTONES ASIGNAR — TEÓRICOS
    // =========================================================================
    document.querySelectorAll('.btn-asignar[data-tipo="TEORICO"]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!personalId) {
                Swal.fire({ icon: 'warning', title: 'Selecciona el personal primero', confirmButtonColor: '#533AB7' });
                return;
            }
            pendingHorarioId = parseInt(btn.dataset.hid);
            pendingTipo      = 'TEORICO';
            fechasSeleccionadas = [];

            document.getElementById('modalFechasLabel').textContent = `Asignar: ${btn.dataset.label}`;
            document.getElementById('modalPersonalNombre').innerHTML =
                `<i class="bi bi-person me-1"></i> <strong>${personalNombre}</strong>`;
            renderPills();

            new bootstrap.Modal(document.getElementById('modalFechas')).show();
        });
    });

    // =========================================================================
    // BOTONES ASIGNAR — PRÁCTICOS (delegación)
    // =========================================================================
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btn-asignar[data-tipo="PRACTICA"]');
        if (!btn) return;

        if (!personalId) {
            Swal.fire({ icon: 'warning', title: 'Selecciona el personal primero', confirmButtonColor: '#533AB7' });
            return;
        }

        const confirmed = await Swal.fire({
            title: 'Confirmar asignación',
            html: `Asignar a <strong>${personalNombre}</strong>:<br>
                   <span class="badge mt-2 rounded-pill px-3"
                         style="background:#EEEDFE;border:1px solid #AFA9EC;color:#3C3489">
                       ${btn.dataset.label}
                   </span>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#533AB7',
            confirmButtonText: 'Sí, asignar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('tipo_horario', 'PRACTICA');
        fd.append('horario_id',   btn.dataset.hid);
        fd.append('personal_id',  personalId);
        fd.append('offering_id',  OFFERING_ID);
        fd.append('fechas',       btn.dataset.fecha);

        try {
            const resp = await fetch(`${BASE}/save`, { method: 'POST', body: fd }).then(r => r.json());
            if (resp.success) {
                const item = btn.closest('.ses-fecha-item');
                if (item) {
                    item.classList.add('ses-fecha-asignada');
                    btn.outerHTML = `<span class="badge rounded-pill"
                        style="background:#E1F5EE;color:#085041;font-size:10px">
                        <i class="bi bi-check2"></i> Asignada</span>`;
                }
                await recargarSesiones();
                Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error de conexión' });
        }
    });

    // =========================================================================
    // CONFIRMAR FECHAS (modal teóricos)
    // =========================================================================
    document.getElementById('btnConfirmFechas')?.addEventListener('click', async () => {
        if (!fechasSeleccionadas.length) {
            Swal.fire({ icon: 'warning', title: 'Agrega al menos una fecha', confirmButtonColor: '#533AB7' });
            return;
        }

        const fd = new FormData();
        fd.append('tipo_horario', pendingTipo);
        fd.append('horario_id',   pendingHorarioId);
        fd.append('personal_id',  personalId);
        fd.append('offering_id',  OFFERING_ID);
        fd.append('fechas',       fechasSeleccionadas.join(','));

        try {
            const resp = await fetch(`${BASE}/save`, { method: 'POST', body: fd }).then(r => r.json());
            bootstrap.Modal.getInstance(document.getElementById('modalFechas')).hide();
            if (resp.success) {
                await recargarSesiones();
                Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1800, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error de conexión' });
        }
    });

    // =========================================================================
    // HELPERS
    // =========================================================================
    function formatFecha(fechaStr) {
        if (!fechaStr) return '—';
        return new Date(fechaStr + 'T12:00:00').toLocaleDateString('es-VE', { day: '2-digit', month: 'short', year: 'numeric' });
    }
});