/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: public/assets/js/academic_horarios_practicas.js
 * PROPÓSITO: Lógica completa del módulo de Horarios de Práctica v2. Maneja pestañas,
 *            CRUD de grupos, asignación de estudiantes (modal 2 columnas), formulario
 *            de horario con Flatpickr multi-fecha, y calendario mensual de fechas
 *            asignadas coloreado por grupo.
 * VERSIÓN: 2.0.0 - UX reescrita. Flatpickr multi-date. Calendario mensual propio.
 *           Vanilla JS puro, sin jQuery.
 */

document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.OFFERING_ID === 'undefined') return;

    const BASE        = (window.APP_BASE_PATH || '') + '/academic/horarios-practicas';
    const OFFERING_ID = window.OFFERING_ID;

    let grupos   = window.GRUPOS_INIT   || [];
    let horarios = window.HORARIOS_INIT || [];
    let fechas   = window.FECHAS_INIT   || [];

    let pendingConfirm = null;
    let activeGrupoId  = null;
    let editHorarioId  = null;
    let calYear, calMonth;

    const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                   'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const DIAS_SEMANA = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

    const COLS = [
        { bg:'#EEEDFE', borde:'#7F77DD', txt:'#3C3489' },
        { bg:'#E1F5EE', borde:'#1D9E75', txt:'#085041' },
        { bg:'#FAECE7', borde:'#D85A30', txt:'#712B13' },
        { bg:'#E6F1FB', borde:'#378ADD', txt:'#0C447C' },
        { bg:'#FAEEDA', borde:'#BA7517', txt:'#633806' },
        { bg:'#FBEAF0', borde:'#D4537E', txt:'#4B1528' },
    ];
    const colMap = {};
    let colIdx = 0;
    function getCol(grupoId) {
        if (!colMap[grupoId]) { colMap[grupoId] = COLS[colIdx % COLS.length]; colIdx++; }
        return colMap[grupoId];
    }
    grupos.forEach(g => getCol(g.id));
    fechas.forEach(f => getCol(f.grupo_id));

    // =========================================================================
    // PESTAÑAS
    // =========================================================================
    document.querySelectorAll('#tabsPractica .nav-link').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#tabsPractica .nav-link').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const tab = btn.dataset.tab;
            document.getElementById('tabGrupos').style.display   = tab === 'grupos'   ? '' : 'none';
            document.getElementById('tabHorarios').style.display = tab === 'horarios' ? '' : 'none';
        });
    });

    // =========================================================================
    // FLATPICKR MULTI-FECHA
    // =========================================================================
    let fp = null;
    if (document.getElementById('h_fechas')) {
        fp = flatpickr('#h_fechas', {
            mode: 'multiple',
            dateFormat: 'Y-m-d',
            locale: 'es',
            inline: false,
            onChange: function(selectedDates, dateStr) {
                renderFechasSeleccionadas(selectedDates);
            }
        });
    }

    function renderFechasSeleccionadas(dates) {
        const wrap = document.getElementById('fechasSeleccionadas');
        if (!wrap) return;
        if (!dates || !dates.length) { wrap.innerHTML = ''; return; }
        const pills = dates.map(d => {
            const f = d.toISOString().split('T')[0];
            const label = d.toLocaleDateString('es-VE', { day:'2-digit', month:'short', year:'numeric' });
            return `<span class="badge rounded-pill me-1 mb-1"
                         style="background:#EEEDFE;border:1px solid #AFA9EC;color:#3C3489;font-size:11px">
                        ${label}
                    </span>`;
        }).join('');
        wrap.innerHTML = `<div class="d-flex flex-wrap">${pills}</div>`;
    }

    // =========================================================================
    // RENDER GRUPOS
    // =========================================================================
    function renderGrupos() {
        const wrap = document.getElementById('gruposWrap');
        if (!wrap) return;
        document.getElementById('badgeGrupos').textContent = grupos.length;

        // Actualizar select de grupos en pestaña horarios
        const sel = document.getElementById('h_grupo');
        if (sel) {
            const val = sel.value;
            sel.innerHTML = '<option value="">Seleccione un grupo...</option>';
            grupos.forEach(g => {
                const opt = document.createElement('option');
                opt.value = g.id; opt.textContent = g.nombre;
                if (g.id == val) opt.selected = true;
                sel.appendChild(opt);
            });
        }

        if (!grupos.length) {
            wrap.innerHTML = `<div class="ht-empty">
                <i class="bi bi-people fs-1 opacity-25 d-block mb-2"></i>
                No hay grupos creados.<br><small>Crea el primero desde el panel izquierdo.</small>
            </div>`;
            return;
        }

        let html = '<div class="hp-grupos-grid">';
        grupos.forEach(g => {
            const col = getCol(g.id);
            html += `
            <div class="hp-grupo-card" style="border-left:4px solid ${col.borde}">
                <div class="hp-grupo-head">
                    <div>
                        <div class="hp-grupo-nombre" style="color:${col.txt}">${g.nombre || '(sin nombre)'}</div>
                        <div class="hp-grupo-count">
                            <i class="bi bi-person me-1"></i>${g.total_estudiantes || 0} estudiante${(g.total_estudiantes || 0) != 1 ? 's' : ''}
                        </div>
                    </div>
                    <button class="hp-card-x" data-gid="${g.id}" data-gnombre="${g.nombre || ''}" aria-label="Eliminar grupo">✕</button>
                </div>
                <button class="btn btn-sm w-100 rounded-pill mt-2 fw-bold"
                        style="background:${col.bg};border:1px solid ${col.borde};color:${col.txt};font-size:12px"
                        data-ver-grupo="${g.id}" data-ver-nombre="${g.nombre || ''}">
                    <i class="bi bi-people me-1"></i> Ver / Asignar estudiantes
                </button>
            </div>`;
        });
        html += '</div>';
        wrap.innerHTML = html;

        wrap.querySelectorAll('.hp-card-x').forEach(x => {
            x.addEventListener('click', () => pedirConfirm('eliminarGrupo', { id: x.dataset.gid },
                '¿Eliminar grupo?',
                `Se eliminará el grupo <strong>${x.dataset.gnombre}</strong>.<br>
                 <small class="text-muted">Si tiene estudiantes asignados, se inactivará.</small>`));
        });
        wrap.querySelectorAll('[data-ver-grupo]').forEach(btn => {
            btn.addEventListener('click', () => abrirModalEstudiantes(parseInt(btn.dataset.verGrupo), btn.dataset.verNombre));
        });
    }

    // =========================================================================
    // CREAR GRUPO
    // =========================================================================
    document.getElementById('btnSaveGrupo')?.addEventListener('click', async () => {
        const nombre = document.getElementById('g_nombre').value.trim();
        if (!nombre) {
            Swal.fire({ icon: 'warning', title: 'Campo vacío', text: 'Escribe el nombre del grupo.', confirmButtonColor: '#533AB7' });
            return;
        }
        const fd = new FormData();
        fd.append('offering_id', OFFERING_ID);
        fd.append('nombre', nombre);
        try {
            const resp = await fetch(`${BASE}/saveGrupo`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            grupos = resp.grupos;
            grupos.forEach(g => getCol(g.id));
            document.getElementById('g_nombre').value = '';
            renderGrupos();
            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false });
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    // =========================================================================
    // MODAL ESTUDIANTES
    // =========================================================================
    async function abrirModalEstudiantes(grupoId, grupoNombre) {
        activeGrupoId = grupoId;
        document.getElementById('modalGrupoNombre').textContent = `Grupo ${grupoNombre}`;
        new bootstrap.Modal(document.getElementById('modalEstudiantes')).show();
        await recargarEstudiantes(grupoId);
    }

    async function recargarEstudiantes(grupoId) {
        const [rAsig, rSin] = await Promise.all([
            fetch(`${BASE}/getEstudiantes?grupo_id=${grupoId}&tipo=asignados`).then(r => r.json()),
            fetch(`${BASE}/getEstudiantes?offering_id=${OFFERING_ID}&tipo=sin_grupo`).then(r => r.json()),
        ]);
        const asignados = rAsig.data || [], sinGrupo = rSin.data || [];
        document.getElementById('badgeAsignados').textContent = asignados.length;
        document.getElementById('badgeSinGrupo').textContent  = sinGrupo.length;

        const listaA = document.getElementById('listaAsignados');
        listaA.innerHTML = !asignados.length
            ? `<div class="p-3 text-center text-muted small">Sin estudiantes asignados.</div>`
            : asignados.map(e => `
                <div class="hp-est-row d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <div>
                        <div class="small fw-bold">${e.last_name}, ${e.first_name}</div>
                        <div class="small text-muted">${e.document_id}</div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0"
                            data-quitar="${e.asignacion_id}">
                        <i class="bi bi-person-dash"></i>
                    </button>
                </div>`).join('');

        listaA.querySelectorAll('[data-quitar]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const fd = new FormData();
                fd.append('asignacion_id', btn.dataset.quitar);
                fd.append('grupo_id', grupoId);
                fd.append('offering_id', OFFERING_ID);
                const resp = await fetch(`${BASE}/deleteEstudiante`, { method:'POST', body:fd }).then(r => r.json());
                if (resp.success) { grupos = resp.grupos; renderGrupos(); await recargarEstudiantes(grupoId); }
            });
        });

        const listaS = document.getElementById('listaSinGrupo');
        listaS.innerHTML = !sinGrupo.length
            ? `<div class="p-3 text-center text-muted small">Todos los estudiantes tienen grupo.</div>`
            : sinGrupo.map(e => `
                <div class="hp-est-row d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <div>
                        <div class="small fw-bold">${e.last_name}, ${e.first_name}</div>
                        <div class="small text-muted">${e.document_id}</div>
                    </div>
                    <button class="btn btn-sm btn-outline-success rounded-pill px-2 py-0"
                            data-asignar="${e.enrollment_id}">
                        <i class="bi bi-person-plus"></i>
                    </button>
                </div>`).join('');

        listaS.querySelectorAll('[data-asignar]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const fd = new FormData();
                fd.append('grupo_id', grupoId);
                fd.append('enrollment_id', btn.dataset.asignar);
                fd.append('offering_id', OFFERING_ID);
                const resp = await fetch(`${BASE}/saveEstudiante`, { method:'POST', body:fd }).then(r => r.json());
                if (resp.success) { grupos = resp.grupos; renderGrupos(); await recargarEstudiantes(grupoId); }
                else Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
            });
        });
    }

    // =========================================================================
    // GUARDAR HORARIO (GRUPO → CENTRO + FECHAS)
    // =========================================================================
    document.getElementById('btnSaveHorario')?.addEventListener('click', async () => {
        const grupoId  = document.getElementById('h_grupo').value;
        const centroId = document.getElementById('h_centro').value;
        const fechasSel = fp ? fp.selectedDates.map(d => d.toISOString().split('T')[0]) : [];

        if (!grupoId || !centroId) {
            Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Selecciona grupo y centro médico.', confirmButtonColor: '#533AB7' });
            return;
        }

        const fd = new FormData();
        fd.append('offering_id',      OFFERING_ID);
        fd.append('grupo_id',         grupoId);
        fd.append('centro_medico_id', centroId);

        try {
            let resp;
            if (editHorarioId) {
                // Solo guardar fechas si estamos editando
                const fd2 = new FormData();
                fd2.append('horario_practica_id', editHorarioId);
                fd2.append('offering_id', OFFERING_ID);
                fd2.append('fechas', fechasSel.join(','));
                resp = await fetch(`${BASE}/saveFechas`, { method:'POST', body:fd2 }).then(r => r.json());
            } else {
                resp = await fetch(`${BASE}/saveHorario`, { method:'POST', body:fd }).then(r => r.json());
                // Si se creó bien y hay fechas, guardarlas
                if (resp.success && fechasSel.length && resp.horario_id) {
                    const fd2 = new FormData();
                    fd2.append('horario_practica_id', resp.horario_id);
                    fd2.append('offering_id', OFFERING_ID);
                    fd2.append('fechas', fechasSel.join(','));
                    const resp2 = await fetch(`${BASE}/saveFechas`, { method:'POST', body:fd2 }).then(r => r.json());
                    if (resp2.success) { resp.horarios = resp2.horarios; resp.fechas = resp2.fechas; }
                }
            }

            if (!resp.success) { Swal.fire({ icon:'error', title:'Error', text:resp.message }); return; }
            horarios = resp.horarios || horarios;
            fechas   = resp.fechas   || fechas;
            renderHorariosList();
            renderCalendario();
            resetHorForm();
            Swal.fire({ icon:'success', title:'¡Listo!', text:resp.message, timer:1500, showConfirmButton:false });
        } catch (e) { Swal.fire({ icon:'error', title:'Error de conexión' }); }
    });

    function resetHorForm() {
        editHorarioId = null;
        if (document.getElementById('h_grupo'))  document.getElementById('h_grupo').value  = '';
        if (document.getElementById('h_centro')) document.getElementById('h_centro').value = '';
        if (fp) { fp.clear(); }
        document.getElementById('fechasSeleccionadas').innerHTML = '';
        document.getElementById('horFormTitle').innerHTML = '<i class="bi bi-hospital me-1"></i> Nueva Asignación';
        document.getElementById('btnSaveHorarioTxt').textContent = 'Guardar Asignación';
        const btnC = document.getElementById('btnCancelHorario');
        if (btnC) btnC.style.display = 'none';
    }

    document.getElementById('btnCancelHorario')?.addEventListener('click', resetHorForm);

    // =========================================================================
    // LISTA DE HORARIOS ASIGNADOS (debajo del calendario)
    // =========================================================================
    function renderHorariosList() {
        const wrap = document.getElementById('horariosListaWrap');
        if (!wrap) return;
        document.getElementById('badgeHorarios').textContent = horarios.length;

        if (!horarios.length) {
            wrap.innerHTML = `<div class="ht-empty" style="padding:1rem">
                <i class="bi bi-hospital fs-2 opacity-25 d-block mb-1"></i>
                <small>No hay asignaciones. Selecciona grupo y centro médico.</small>
            </div>`;
            return;
        }

        // Agrupar por grupo
        const porGrupo = {};
        horarios.forEach(h => {
            if (!porGrupo[h.grupo_id]) porGrupo[h.grupo_id] = { nombre: h.grupo_nombre, items: [] };
            porGrupo[h.grupo_id].items.push(h);
        });

        let html = '<div class="hp-horarios-grid">';
        Object.entries(porGrupo).forEach(([grupoId, info]) => {
            const col = getCol(parseInt(grupoId));
            html += `<div class="hp-horario-grupo-card" style="border-left:4px solid ${col.borde}">
                <div class="hp-horario-grupo-nombre" style="color:${col.txt}">
                    <i class="bi bi-people me-1"></i>${info.nombre}
                </div>`;
            info.items.forEach(h => {
                html += `<div class="hp-centro-item">
                    <i class="bi bi-hospital-fill me-2" style="color:${col.borde}"></i>
                    <div>
                        <div style="font-size:12px">${h.centro_nombre}</div>
                        <div style="font-size:11px;color:#6c757d">
                            ${h.total_fechas} fecha${h.total_fechas != 1 ? 's' : ''}
                        </div>
                    </div>
                    <div class="ms-auto d-flex gap-1">
                        <button class="hp-btn-edit" data-hid="${h.id}" data-grupo="${h.grupo_id}"
                                data-centro="${h.centro_medico_id}"
                                title="Editar fechas">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="hp-card-x" data-hid="${h.id}"
                                data-hnombre="${h.grupo_nombre} → ${h.centro_nombre}"
                                title="Eliminar">✕</button>
                    </div>
                </div>`;
            });
            html += `</div>`;
        });
        html += '</div>';
        wrap.innerHTML = html;

        wrap.querySelectorAll('.hp-btn-edit').forEach(btn => {
            btn.addEventListener('click', async () => {
                editHorarioId = parseInt(btn.dataset.hid);
                document.getElementById('h_grupo').value  = btn.dataset.grupo;
                document.getElementById('h_centro').value = btn.dataset.centro;
                document.getElementById('horFormTitle').innerHTML = '<i class="bi bi-pencil me-1"></i> Editando fechas';
                document.getElementById('btnSaveHorarioTxt').textContent = 'Guardar Fechas';
                document.getElementById('btnCancelHorario').style.display = '';
                // Cargar fechas existentes
                const resp = await fetch(`${BASE}/getFechas?horario_practica_id=${editHorarioId}`).then(r => r.json());
                if (resp.success && fp) {
                    fp.setDate(resp.data.map(f => f.fecha));
                    renderFechasSeleccionadas(fp.selectedDates);
                }
                // Scroll al formulario
                document.getElementById('h_grupo').scrollIntoView({ behavior:'smooth', block:'center' });
            });
        });

        wrap.querySelectorAll('.hp-card-x[data-hid]').forEach(x => {
            x.addEventListener('click', () => pedirConfirm('eliminarHorario',
                { id: x.dataset.hid },
                '¿Eliminar asignación?',
                `Se eliminará: <strong>${x.dataset.hnombre}</strong>`));
        });
    }

    // =========================================================================
    // CALENDARIO MENSUAL
    // =========================================================================
    const hoy = new Date();
    calYear  = hoy.getFullYear();
    calMonth = hoy.getMonth();

    // Si hay fechas, ir al mes de la primera fecha
    if (fechas.length) {
        const primera = new Date(fechas[0].fecha + 'T12:00:00');
        calYear  = primera.getFullYear();
        calMonth = primera.getMonth();
    }

    document.getElementById('btnCalPrev')?.addEventListener('click', () => {
        calMonth--; if (calMonth < 0) { calMonth = 11; calYear--; }
        renderCalendario();
    });
    document.getElementById('btnCalNext')?.addEventListener('click', () => {
        calMonth++; if (calMonth > 11) { calMonth = 0; calYear++; }
        renderCalendario();
    });

    function renderCalendario() {
        const wrap = document.getElementById('calendarioWrap');
        if (!wrap) return;
        document.getElementById('calMesLabel').textContent = `${MESES[calMonth]} ${calYear}`;

        // Indexar fechas por día
        const fechasPorDia = {};
        fechas.forEach(f => {
            const d = new Date(f.fecha + 'T12:00:00');
            if (d.getFullYear() === calYear && d.getMonth() === calMonth) {
                const key = d.getDate();
                if (!fechasPorDia[key]) fechasPorDia[key] = [];
                fechasPorDia[key].push(f);
            }
        });

        const primerDia = new Date(calYear, calMonth, 1).getDay();
        const diasEnMes = new Date(calYear, calMonth + 1, 0).getDate();

        let html = `<table class="hp-cal-table w-100">
            <thead><tr>`;
        DIAS_SEMANA.forEach(d => { html += `<th class="hp-cal-th">${d}</th>`; });
        html += `</tr></thead><tbody><tr>`;

        for (let i = 0; i < primerDia; i++) html += `<td class="hp-cal-td hp-cal-vacio"></td>`;

        for (let dia = 1; dia <= diasEnMes; dia++) {
            if ((primerDia + dia - 1) % 7 === 0 && dia > 1) html += `</tr><tr>`;
            const esHoy = dia === hoy.getDate() && calMonth === hoy.getMonth() && calYear === hoy.getFullYear();
            const eventos = fechasPorDia[dia] || [];
            html += `<td class="hp-cal-td${esHoy ? ' hp-cal-hoy' : ''}">
                <div class="hp-cal-dia">${dia}</div>`;
            eventos.forEach(ev => {
                const col = getCol(ev.grupo_id);
                html += `<div class="hp-cal-evento" style="background:${col.bg};border-left:3px solid ${col.borde};color:${col.txt}">
                    ${ev.grupo_nombre} · ${ev.centro_nombre}
                </div>`;
            });
            html += `</td>`;
        }

        const totalCeldas = primerDia + diasEnMes;
        const celdasRestantes = (7 - (totalCeldas % 7)) % 7;
        for (let i = 0; i < celdasRestantes; i++) html += `<td class="hp-cal-td hp-cal-vacio"></td>`;
        html += `</tr></tbody></table>`;
        wrap.innerHTML = html;

        // Leyenda
        const leyendaWrap = document.getElementById('leyendaGrupos');
        if (leyendaWrap) {
            const gruposConFechas = [...new Map(fechas.map(f => [f.grupo_id, { id: f.grupo_id, nombre: f.grupo_nombre }])).values()];
            leyendaWrap.innerHTML = gruposConFechas.map(g => {
                const col = getCol(g.id);
                return `<span style="background:${col.bg};border:1px solid ${col.borde};color:${col.txt};border-radius:20px;padding:2px 10px;font-size:11px;font-weight:600">
                    ${g.nombre}
                </span>`;
            }).join('');
        }
    }

    // =========================================================================
    // CONFIRMACIÓN GENÉRICA
    // =========================================================================
    function pedirConfirm(tipo, data, title, msg) {
        pendingConfirm = { tipo, data };
        document.getElementById('confirmTitle').innerHTML = title;
        document.getElementById('confirmMsg').innerHTML   = msg;
        new bootstrap.Modal(document.getElementById('modalConfirm')).show();
    }

    document.getElementById('btnConfirm')?.addEventListener('click', async () => {
        if (!pendingConfirm) return;
        const { tipo, data } = pendingConfirm;
        pendingConfirm = null;
        bootstrap.Modal.getInstance(document.getElementById('modalConfirm')).hide();

        const fd = new FormData();
        let resp;
        try {
            if (tipo === 'eliminarGrupo') {
                fd.append('id', data.id);
                resp = await fetch(`${BASE}/deleteGrupo`, { method:'POST', body:fd }).then(r => r.json());
                if (resp.success) { grupos = resp.grupos; grupos.forEach(g => getCol(g.id)); renderGrupos(); }
            } else if (tipo === 'eliminarHorario') {
                fd.append('id', data.id);
                fd.append('offering_id', OFFERING_ID);
                resp = await fetch(`${BASE}/deleteHorario`, { method:'POST', body:fd }).then(r => r.json());
                if (resp.success) { horarios = resp.horarios; fechas = resp.fechas; renderHorariosList(); renderCalendario(); }
            }
            if (resp?.success) Swal.fire({ icon:'success', title:'¡Listo!', text:resp.message, timer:1500, showConfirmButton:false });
            else if (resp) Swal.fire({ icon:'error', title:'Error', text:resp.message });
        } catch (e) { Swal.fire({ icon:'error', title:'Error de conexión' }); }
    });

    // =========================================================================
    // INIT
    // =========================================================================
    renderGrupos();
    renderHorariosList();
    renderCalendario();
});