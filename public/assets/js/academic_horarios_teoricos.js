/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: public/assets/js/academic_horarios_teoricos.js
 * PROPÓSITO: Lógica del módulo de Horarios Teóricos. index.php solo usa estilos de
 *            la tabla (sin lógica JS especial). manage.php usa toda la lógica de
 *            grilla interactiva: crear/editar/eliminar horarios via AJAX, scoped a
 *            window.OFFERING_ID (una sola oferta a la vez, sin solapamiento posible).
 * VERSIÓN: 3.0.0 - Rediseño de flujo. JS solo activo en manage.php.
 *           Vanilla JS puro, sin jQuery.
 */

document.addEventListener('DOMContentLoaded', function () {

    // Solo ejecutar la lógica de grilla si estamos en manage.php
    if (typeof window.OFFERING_ID === 'undefined') return;

    const BASE       = (window.APP_BASE_PATH || '') + '/academic/horarios-teoricos';
    const OFFERING_ID = window.OFFERING_ID;
    const SLOT        = 32;
    const DIAS        = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    const COL         = { bg: '#EEEDFE', borde: '#7F77DD', txt: '#3C3489', xbg: '#AFA9EC' };

    let horarios     = window.HORARIOS_INIT || [];
    let editId       = null;
    let pendingDelId = null;

    // === HELPERS ===
    function toMin(t) { if (!t) return 0; const [h, m] = t.split(':').map(Number); return h * 60 + m; }
    function fmt(t)   { if (!t) return '--'; const [h, m] = t.split(':').map(Number); const ap = h >= 12 ? 'PM' : 'AM'; return `${h % 12 || 12}:${String(m).padStart(2, '0')} ${ap}`; }
    function padH(n)  { return String(n).padStart(2, '0') + ':00'; }
    function durStr(ini, fin) {
        const d = toMin(fin) - toMin(ini);
        if (d <= 0) return 'Inválido';
        const h = Math.floor(d / 60), m = d % 60;
        return h && m ? `${h}h ${m}min` : h ? `${h}h` : `${m}min`;
    }

    // === DURACIÓN EN TIEMPO REAL ===
    const fDia     = document.getElementById('f_dia');
    const fIni     = document.getElementById('f_ini');
    const fFin     = document.getElementById('f_fin');
    const durBadge = document.getElementById('durBadge');
    const durTxt   = document.getElementById('durTxt');

    function calcDur() {
        if (!fIni || !fFin) return;
        const ini = fIni.value, fin = fFin.value;
        if (!ini || !fin) { durTxt.textContent = '--'; durBadge.className = 'ht-dur-badge'; return; }
        const ds = durStr(ini, fin);
        durTxt.textContent = ds;
        durBadge.className = ds === 'Inválido' ? 'ht-dur-badge ht-dur-error' : 'ht-dur-badge ht-dur-ok';
    }
    if (fIni) fIni.addEventListener('input', calcDur);
    if (fFin) fFin.addEventListener('input', calcDur);

    // === MODO FORMULARIO ===
    function setModo(modo, horario = null) {
        const bar   = document.getElementById('modeBar');
        const btnC  = document.getElementById('btnCancelar');
        const btnEl = document.getElementById('wrapBtnEliminar');

        if (modo === 'edit' && horario) {
            editId = horario.id;
            if (fDia) fDia.value = horario.dia_semana;
            if (fIni) fIni.value = horario.hora_inicio.substring(0, 5);
            if (fFin) fFin.value = horario.hora_fin.substring(0, 5);
            calcDur();
            bar.className = 'ht-mode-bar ht-mode-edit mb-3';
            bar.innerHTML = '<i class="bi bi-pencil me-1"></i><span>Editando horario</span>';
            if (btnC)  btnC.style.display  = '';
            if (btnEl) btnEl.style.display = '';
        } else {
            editId = null;
            if (fDia) fDia.value = '';
            if (fIni) fIni.value = '';
            if (fFin) fFin.value = '';
            calcDur();
            bar.className = 'ht-mode-bar ht-mode-new mb-3';
            bar.innerHTML = '<i class="bi bi-plus-circle me-1"></i><span>Nuevo horario</span>';
            if (btnC)  btnC.style.display  = 'none';
            if (btnEl) btnEl.style.display = 'none';
        }
        renderGrilla();
    }

    const btnCancelar = document.getElementById('btnCancelar');
    if (btnCancelar) btnCancelar.addEventListener('click', () => setModo('new'));

    // === GUARDAR / ACTUALIZAR ===
    const btnGuardar = document.getElementById('btnGuardar');
    if (btnGuardar) {
        btnGuardar.addEventListener('click', async () => {
            const diaSemana  = fDia ? fDia.value : '';
            const horaInicio = fIni ? fIni.value : '';
            const horaFin    = fFin ? fFin.value : '';

            if (!diaSemana || !horaInicio || !horaFin) {
                Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Completa todos los campos.', confirmButtonColor: '#533AB7' });
                return;
            }
            if (durStr(horaInicio, horaFin) === 'Inválido') {
                Swal.fire({ icon: 'error', title: 'Horas inválidas', text: 'La hora de fin debe ser posterior a la de inicio.', confirmButtonColor: '#e74a3b' });
                return;
            }

            const fd = new FormData();
            fd.append('offering_id', OFFERING_ID);
            fd.append('dia_semana',  diaSemana);
            fd.append('hora_inicio', horaInicio);
            fd.append('hora_fin',    horaFin);

            let url = `${BASE}/save`;
            if (editId !== null) { fd.append('id', editId); url = `${BASE}/update`; }

            try {
                const resp = await fetch(url, { method: 'POST', body: fd }).then(r => r.json());
                if (!resp.success) {
                    Swal.fire({ icon: 'error', title: 'Error', text: resp.message, confirmButtonColor: '#e74a3b' });
                    return;
                }
                if (resp.horario) {
                    if (editId !== null) {
                        horarios = horarios.map(h => h.id === editId ? resp.horario : h);
                    } else {
                        horarios.push(resp.horario);
                    }
                }
                setModo('new');
                Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1800, showConfirmButton: false });
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo contactar al servidor.' });
            }
        });
    }

    // === ELIMINAR DESDE BOTÓN DEL FORMULARIO ===
    const btnEliminarForm = document.getElementById('btnEliminarForm');
    if (btnEliminarForm) {
        btnEliminarForm.addEventListener('click', () => {
            if (editId === null) return;
            const h = horarios.find(x => x.id === editId);
            if (h) pedirEliminar(h);
        });
    }

    // === CONFIRMACIÓN ELIMINAR ===
    function pedirEliminar(h) {
        pendingDelId = h.id;
        document.getElementById('confirmMsg').innerHTML =
            `Estás por eliminar el bloque del <strong>${window.DIPLOMADO_NOMBRE || 'diplomado'}</strong>:<br><br>
             <span class="badge rounded-pill px-3 py-2" style="background:${COL.bg};border:1px solid ${COL.borde};color:${COL.txt}">
                 ${h.dia_semana} &nbsp;·&nbsp; ${fmt(h.hora_inicio)} – ${fmt(h.hora_fin)}
             </span>`;

        const modal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
        modal.show();
    }

    const btnConfirmDelete = document.getElementById('btnConfirmDelete');
    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener('click', async () => {
            if (pendingDelId === null) return;
            const fd = new FormData();
            fd.append('id', pendingDelId);

            try {
                const resp = await fetch(`${BASE}/delete`, { method: 'POST', body: fd }).then(r => r.json());
                bootstrap.Modal.getInstance(document.getElementById('modalConfirmDelete')).hide();

                if (!resp.success) {
                    Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
                    return;
                }

                if (editId === pendingDelId) setModo('new');
                horarios = horarios.filter(x => x.id !== pendingDelId);
                pendingDelId = null;
                renderGrilla();
                Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1800, showConfirmButton: false });
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error de conexión' });
            }
        });
    }

    // === RENDERIZAR GRILLA ===
    function renderGrilla() {
        const wrap = document.getElementById('grillaWrap');
        if (!wrap) return;

        if (!horarios.length) {
            wrap.innerHTML = `<div class="ht-empty">
                <i class="bi bi-calendar-x fs-1 opacity-25 d-block mb-2"></i>
                No hay horarios configurados para esta oferta.<br>
                <small>Agrega el primero desde el panel izquierdo.</small>
            </div>`;
            return;
        }

        const diasUsados = [...new Set(horarios.map(h => h.dia_semana))]
            .sort((a, b) => DIAS.indexOf(a) - DIAS.indexOf(b));

        const minH = Math.min(...horarios.map(h => Math.floor(toMin(h.hora_inicio) / 60)));
        const maxH = Math.max(...horarios.map(h => Math.ceil(toMin(h.hora_fin) / 60)));
        const HS   = Math.max(6, minH - 1);
        const HE   = Math.min(22, maxH + 1);
        const horas = [];
        for (let h = HS; h <= HE; h++) horas.push(padH(h));

        let g = `<div class="ht-grilla" style="grid-template-columns:44px repeat(${diasUsados.length},1fr)">`;
        g += `<div class="ht-ghead"></div>`;
        diasUsados.forEach(d => { g += `<div class="ht-ghead">${d}</div>`; });

        horas.forEach(hora => {
            g += `<div class="ht-gtime">${hora}</div>`;
            diasUsados.forEach(dia => {
                g += `<div class="ht-gcell">`;
                horarios.forEach(hor => {
                    if (hor.dia_semana !== dia) return;
                    const cellS = toMin(hora), cellE = cellS + 60;
                    if (toMin(hor.hora_inicio) >= cellE || toMin(hor.hora_fin) <= cellS) return;
                    const top  = Math.max(0, (toMin(hor.hora_inicio) - cellS) / 60 * SLOT);
                    const h2   = Math.min(SLOT - top, (toMin(hor.hora_fin) - Math.max(toMin(hor.hora_inicio), cellS)) / 60 * SLOT) - 1;
                    if (h2 <= 0) return;
                    const sel = editId === hor.id ? 'ht-bloque-sel' : '';
                    g += `<div class="ht-bloque ${sel}" data-id="${hor.id}" style="top:${top}px;height:${h2}px;background:${COL.bg};border:1px solid ${COL.borde}">
                        <div class="ht-btitle" style="color:${COL.txt}">${fmt(hor.hora_inicio)} – ${fmt(hor.hora_fin)}</div>
                        ${h2 > 18 ? `<div class="ht-bhora" style="color:${COL.txt}">${hor.dia_semana}</div>` : ''}
                        <button class="ht-bx" data-xid="${hor.id}" aria-label="Eliminar horario" style="background:${COL.xbg};color:${COL.txt}">✕</button>
                    </div>`;
                });
                g += `</div>`;
            });
        });
        g += `</div>`;
        wrap.innerHTML = g;

        wrap.querySelectorAll('.ht-bloque').forEach(b => {
            b.addEventListener('click', e => {
                if (e.target.closest('.ht-bx')) return;
                const h = horarios.find(x => x.id === Number(b.dataset.id));
                if (h) setModo('edit', h);
            });
        });
        wrap.querySelectorAll('.ht-bx').forEach(x => {
            x.addEventListener('click', e => {
                e.stopPropagation();
                const h = horarios.find(hor => hor.id === Number(x.dataset.xid));
                if (h) pedirEliminar(h);
            });
        });
    }

    renderGrilla();
});