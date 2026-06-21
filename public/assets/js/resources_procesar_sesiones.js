/**
 * MÓDULO: RECURSOS HUMANOS / PROCESAR SESIONES
 * ARCHIVO: public/assets/js/resources_procesar_sesiones.js
 * PROPÓSITO: Clic en sesión → carga lista de estudiantes con checkbox por defecto,
 *            se desmarcan los ausentes, se procesa la sesión vía AJAX.
 *            Botón imprimir abre el PDF en nueva pestaña.
 * VERSIÓN: 1.0.0 - Creación inicial. Vanilla JS puro.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE        = (window.APP_BASE_PATH || '') + '/resources/procesar-sesiones';
    const OFFERING_ID = window.OFFERING_ID;

    let sesionActiva = null;

    // =========================================================================
    // PESTAÑAS PENDIENTES / DICTADAS
    // =========================================================================
    document.querySelectorAll('#tabsPS .nav-link').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#tabsPS .nav-link').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const esDictadas = btn.dataset.pstab === 'dictadas';
            document.getElementById('tabPendientes').style.display = esDictadas ? 'none' : '';
            document.getElementById('tabDictadas').style.display   = esDictadas ? '' : 'none';

            // Limpiar selección y panel derecho al cambiar de pestaña
            document.querySelectorAll('.ps-sesion-item').forEach(i => i.classList.remove('ps-sesion-activa'));
            document.getElementById('panelAsistencia').innerHTML = `
                <div class="ps-empty">
                    <i class="bi bi-hand-index fs-2 d-block mb-2 opacity-25"></i>
                    Selecciona una sesión de la lista.
                </div>`;

            if (esDictadas) cargarDictadas();
        });
    });

    let dictadasData = [];

    async function cargarDictadas() {
        const wrap = document.getElementById('listaDictadas');
        wrap.innerHTML = `<div class="text-center py-3 text-muted small"><div class="spinner-border spinner-border-sm me-2"></div>Cargando...</div>`;

        try {
            const resp = await fetch(`${BASE}/dictadas?offering_id=${OFFERING_ID}`).then(r => r.json());
            dictadasData = resp.data || [];

            document.getElementById('badgeDictadas').textContent = dictadasData.length;

            if (!dictadasData.length) {
                wrap.innerHTML = `<div class="ps-empty">
                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                    Ninguna sesión dictada todavía.
                </div>`;
                return;
            }

            wrap.innerHTML = dictadasData.map(s => `
                <div class="ps-sesion-item" data-sid="${s.id}">
                    <div>
                        <div class="ps-sesion-profesor">
                            <i class="bi bi-person me-1"></i>
                            ${s.last_name}, ${s.first_name}
                            ${parseInt(s.en_nomina) ? '<i class="bi bi-lock-fill text-danger ms-1" title="En nómina"></i>' : ''}
                        </div>
                        <div class="ps-sesion-horario">${s.horario_desc || '—'}</div>
                        <div class="ps-sesion-fecha">
                            <i class="bi bi-calendar3 me-1"></i> ${formatFecha(s.fecha)}
                        </div>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </div>`).join('');

        } catch (e) {
            wrap.innerHTML = `<div class="ps-empty text-danger">Error al cargar.</div>`;
        }
    }

    document.getElementById('listaDictadas')?.addEventListener('click', async function (e) {
        const item = e.target.closest('.ps-sesion-item');
        if (!item) return;

        const sesionId = parseInt(item.dataset.sid);
        document.querySelectorAll('#listaDictadas .ps-sesion-item').forEach(i => i.classList.remove('ps-sesion-activa'));
        item.classList.add('ps-sesion-activa');

        const sData = dictadasData.find(d => d.id == sesionId);
        await cargarAsistencia(sesionId, true, sData ? parseInt(sData.en_nomina) : 0);
    });

    async function reversarSesion(sesionId) {
        const confirmed = await Swal.fire({
            title: '¿Reversar esta sesión?',
            text: 'Volverá a estado PROGRAMADA y se borrará la asistencia registrada.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Sí, reversar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('sesion_id', sesionId);
        fd.append('offering_id', OFFERING_ID);

        try {
            const resp = await fetch(`${BASE}/reversar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }

            await Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false });
            location.reload();
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    }

    // =========================================================================
    // CLIC EN SESIÓN
    // =========================================================================
    document.getElementById('listaSesiones')?.addEventListener('click', async function (e) {
        const item = e.target.closest('.ps-sesion-item');
        if (!item) return;

        const sesionId = parseInt(item.dataset.sid);

        // Marcar activa
        document.querySelectorAll('.ps-sesion-item').forEach(i => i.classList.remove('ps-sesion-activa'));
        item.classList.add('ps-sesion-activa');

        await cargarAsistencia(sesionId);
    });

    // =========================================================================
    // CARGAR ASISTENCIA
    // =========================================================================
    async function cargarAsistencia(sesionId, readOnly = false, enNomina = 0) {
        const panel = document.getElementById('panelAsistencia');
        panel.innerHTML = `<div class="text-center py-4 text-muted small">
            <div class="spinner-border spinner-border-sm me-2"></div> Cargando...
        </div>`;

        try {
            const resp = await fetch(
                `${BASE}/asistencia?sesion_id=${sesionId}&offering_id=${OFFERING_ID}`
            ).then(r => r.json());

            if (!resp.success) {
                panel.innerHTML = `<div class="ps-empty text-danger">${resp.message}</div>`;
                return;
            }

            sesionActiva = resp.sesion;
            const estudiantes = resp.estudiantes || [];
            const s = resp.sesion;

            const presentes = estudiantes.filter(e => e.asistio == 1).length;
            const ausentes  = estudiantes.filter(e => e.asistio == 0).length;

            const botonAccion = readOnly
                ? (enNomina
                    ? `<span class="badge rounded-pill px-3" style="background:#FCEBEB;border:1px solid #E24B4A;color:#A32D2D">
                           <i class="bi bi-lock-fill me-1"></i> En nómina
                       </span>`
                    : `<button class="btn btn-sm btn-outline-warning rounded-pill px-3" id="btnReversar">
                           <i class="bi bi-arrow-counterclockwise me-1"></i> Reversar
                       </button>`)
                : `<button class="btn btn-sm btn-primary rounded-pill px-3" id="btnProcesar">
                       <i class="bi bi-check2-circle me-1"></i> Marcar como Dictada
                   </button>`;

            panel.innerHTML = `
            <div class="ps-asistencia-header mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-bold">${s.last_name}, ${s.first_name}</div>
                        <div class="small text-muted">${s.horario_desc || '—'} · ${formatFecha(s.fecha)}</div>
                    </div>
                    <div class="d-flex gap-2">
                        ${readOnly ? `
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btnImprimir">
                            <i class="bi bi-printer me-1"></i> Imprimir
                        </button>` : ''}
                        ${botonAccion}
                    </div>
                </div>
                <div class="d-flex gap-3 mt-2">
                    <span class="badge rounded-pill px-3" style="background:#E1F5EE;border:1px solid #1D9E75;color:#085041">
                        ✓ ${presentes} presente${presentes !== 1 ? 's' : ''}
                    </span>
                    <span class="badge rounded-pill px-3" id="badgeAusentes"
                          style="background:#FCEBEB;border:1px solid #E24B4A;color:#A32D2D">
                        ✗ ${ausentes} ausente${ausentes !== 1 ? 's' : ''}
                    </span>
                    <span class="small text-muted ms-auto">${estudiantes.length} estudiantes</span>
                </div>
            </div>

            ${readOnly ? '' : `
            <div class="ps-aviso mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Todos vienen marcados como <strong>presentes</strong>.
                Desmarca los que <strong>faltaron</strong>.
            </div>`}

            <div id="listaEstudiantes">
                ${estudiantes.map(e => readOnly ? `
                <div class="ps-estudiante-item">
                    <i class="bi ${e.asistio == 1 ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'} me-2"></i>
                    <div>
                        <div class="small fw-bold">${e.last_name}, ${e.first_name}</div>
                        <div class="small text-muted">${e.document_id}</div>
                    </div>
                </div>` : `
                <div class="ps-estudiante-item">
                    <label class="d-flex align-items-center gap-2 w-100" style="cursor:pointer">
                        <input type="checkbox" class="form-check-input chk-asistencia"
                               data-eid="${e.enrollment_id}"
                               ${e.asistio == 1 ? 'checked' : ''}
                               style="width:18px;height:18px;cursor:pointer">
                        <div>
                            <div class="small fw-bold">${e.last_name}, ${e.first_name}</div>
                            <div class="small text-muted">${e.document_id}</div>
                        </div>
                    </label>
                </div>`).join('')}
            </div>`;

            if (readOnly) {
                document.getElementById('btnImprimir')?.addEventListener('click', () => {
                    window.open(`${BASE}/pdf?sesion_id=${sesionId}`, '_blank');
                });
                document.getElementById('btnReversar')?.addEventListener('click', () => reversarSesion(sesionId));
            } else {
                // Actualizar contador de ausentes al cambiar checks
                document.querySelectorAll('.chk-asistencia').forEach(chk => {
                    chk.addEventListener('change', actualizarContador);
                });
                document.getElementById('btnProcesar').addEventListener('click', () => procesarSesion(sesionId));
            }

        } catch (e) {
            panel.innerHTML = `<div class="ps-empty text-danger">Error al cargar la asistencia.</div>`;
            console.error(e);
        }
    }

    // =========================================================================
    // ACTUALIZAR CONTADOR DE AUSENTES
    // =========================================================================
    function actualizarContador() {
        const total    = document.querySelectorAll('.chk-asistencia').length;
        const presentes = document.querySelectorAll('.chk-asistencia:checked').length;
        const ausentes  = total - presentes;
        const badge = document.getElementById('badgeAusentes');
        if (badge) badge.textContent = `✗ ${ausentes} ausente${ausentes !== 1 ? 's' : ''}`;
    }

    // =========================================================================
    // PROCESAR SESIÓN
    // =========================================================================
    async function procesarSesion(sesionId) {
        const confirmed = await Swal.fire({
            title: '¿Marcar sesión como dictada?',
            text: 'Se registrará la asistencia y la sesión pasará a estado DICTADA.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#533AB7',
            confirmButtonText: 'Sí, procesar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('sesion_id',   sesionId);
        fd.append('offering_id', OFFERING_ID);

        document.querySelectorAll('.chk-asistencia').forEach(chk => {
            fd.append(`asistencia[${chk.dataset.eid}]`, chk.checked ? '1' : '0');
        });

        try {
            const resp = await fetch(`${BASE}/procesar`, { method: 'POST', body: fd }).then(r => r.json());

            if (!resp.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
                return;
            }

            // Remover sesión de la lista
            const item = document.querySelector(`.ps-sesion-item[data-sid="${sesionId}"]`);
            if (item) item.remove();

            // Actualizar badge
            const restantes = document.querySelectorAll('.ps-sesion-item').length;
            const badge = document.getElementById('badgeSesiones');
            if (badge) badge.textContent = restantes;

            // Limpiar panel
            document.getElementById('panelAsistencia').innerHTML = `
                <div class="ps-empty" style="color:#085041">
                    <i class="bi bi-check2-circle fs-2 d-block mb-2"></i>
                    Sesión procesada correctamente.
                </div>`;

            // Abrir el PDF con la asistencia ya guardada
            window.open(`${BASE}/pdf?sesion_id=${sesionId}`, '_blank');

            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 2000, showConfirmButton: false });

        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error de conexión' });
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================
    function formatFecha(fechaStr) {
        if (!fechaStr) return '—';
        return new Date(fechaStr + 'T12:00:00').toLocaleDateString('es-VE', {
            day: '2-digit', month: 'short', year: 'numeric'
        });
    }
});