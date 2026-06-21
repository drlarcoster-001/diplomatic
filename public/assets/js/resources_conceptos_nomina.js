/**
 * MÓDULO: RECURSOS HUMANOS / CONCEPTOS DE NÓMINA
 * ARCHIVO: public/assets/js/resources_conceptos_nomina.js
 * PROPÓSITO: CRUD de asignaciones y deducciones vía AJAX. Pestañas, formularios
 *            dinámicos según tipo (monto fijo / fórmula / salario base).
 * VERSIÓN: 1.0.0 - Creación inicial. Vanilla JS puro.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE = (window.APP_BASE_PATH || '') + '/resources/conceptos-nomina';

    let asignaciones    = window.ASIGNACIONES_INIT || [];
    let deducciones     = window.DEDUCCIONES_INIT  || [];
    let editAsigId      = null;
    let editDedId       = null;
    let pendingConfirm  = null;

    const TIPO_LABELS = {
        SALARIO_BASE: { label: 'Salario Base', bg: '#EEEDFE', borde: '#7F77DD', txt: '#3C3489' },
        MONTO_FIJO:   { label: 'Monto Fijo',   bg: '#E1F5EE', borde: '#1D9E75', txt: '#085041' },
        FORMULA:      { label: 'Fórmula',       bg: '#FAEEDA', borde: '#BA7517', txt: '#633806' },
    };

    // =========================================================================
    // PESTAÑAS
    // =========================================================================
    document.querySelectorAll('#tabsConceptos .nav-link').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#tabsConceptos .nav-link').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tabAsignaciones').style.display = btn.dataset.tab === 'asignaciones' ? '' : 'none';
            document.getElementById('tabDeducciones').style.display  = btn.dataset.tab === 'deducciones'  ? '' : 'none';
        });
    });

    // =========================================================================
    // TIPO → MOSTRAR/OCULTAR CAMPOS
    // =========================================================================
    function bindTipoChange(selectId, wrapValorId, wrapFormulaId) {
        document.getElementById(selectId)?.addEventListener('change', function () {
            document.getElementById(wrapValorId).style.display   = this.value === 'MONTO_FIJO'  ? '' : 'none';
            document.getElementById(wrapFormulaId).style.display = this.value === 'FORMULA'     ? '' : 'none';
        });
    }
    bindTipoChange('asig_tipo', 'asig_wrap_valor', 'asig_wrap_formula');
    bindTipoChange('ded_tipo',  'ded_wrap_valor',  'ded_wrap_formula');

    // =========================================================================
    // RENDER ASIGNACIONES
    // =========================================================================
    function renderAsignaciones() {
        const wrap = document.getElementById('listaAsignaciones');
        document.getElementById('badgeAsig').textContent = asignaciones.length;

        if (!asignaciones.length) {
            wrap.innerHTML = `<div class="cn-empty">
                <i class="bi bi-plus-circle fs-2 d-block mb-2 opacity-25"></i>
                No hay asignaciones. Crea la primera desde el formulario.
            </div>`;
            return;
        }

        wrap.innerHTML = asignaciones.map(a => {
            const t = TIPO_LABELS[a.tipo] || TIPO_LABELS.MONTO_FIJO;
            const valor = a.tipo === 'MONTO_FIJO'
                ? `<span class="cn-valor">$${parseFloat(a.valor || 0).toFixed(2)} USD</span>`
                : a.tipo === 'FORMULA'
                ? `<code class="cn-formula">${a.formula || '—'}</code>`
                : `<span class="cn-valor text-muted">Automático del contrato</span>`;

            return `<div class="cn-item">
                <div class="cn-item-left">
                    <div class="cn-item-nombre">${a.nombre}</div>
                    <div class="mt-1">${valor}</div>
                    ${a.descripcion ? `<div class="cn-item-desc">${a.descripcion}</div>` : ''}
                </div>
                <div class="cn-item-right">
                    <span class="badge rounded-pill"
                          style="background:${t.bg};border:1px solid ${t.borde};color:${t.txt};font-size:10px">
                        ${t.label}
                    </span>
                    <div class="d-flex gap-1 mt-2">
                        <button class="cn-btn-edit" data-id="${a.id}" data-tipo="asig" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="cn-btn-del" data-id="${a.id}" data-nombre="${a.nombre}" data-tipo="asig" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>`;
        }).join('');

        wrap.querySelectorAll('.cn-btn-edit[data-tipo="asig"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const a = asignaciones.find(x => x.id == btn.dataset.id);
                if (!a) return;
                editAsigId = a.id;
                document.getElementById('asig_nombre').value  = a.nombre;
                document.getElementById('asig_tipo').value    = a.tipo;
                document.getElementById('asig_tipo').dispatchEvent(new Event('change'));
                document.getElementById('asig_valor').value   = a.valor || '';
                document.getElementById('asig_formula').value = a.formula || '';
                document.getElementById('asig_desc').value    = a.descripcion || '';
                document.getElementById('asigModeBar').innerHTML = '<i class="bi bi-pencil me-1"></i> Editando Asignación';
                document.getElementById('btnSaveAsigTxt').textContent = 'Actualizar';
                document.getElementById('btnCancelAsig').style.display = '';
            });
        });

        wrap.querySelectorAll('.cn-btn-del[data-tipo="asig"]').forEach(btn => {
            btn.addEventListener('click', () => {
                pendingConfirm = { tipo: 'deleteAsig', id: btn.dataset.id };
                document.getElementById('confirmTitle').textContent = '¿Eliminar asignación?';
                document.getElementById('confirmMsg').innerHTML = `Se eliminará <strong>${btn.dataset.nombre}</strong>.`;
                new bootstrap.Modal(document.getElementById('modalConfirm')).show();
            });
        });
    }

    // =========================================================================
    // RENDER DEDUCCIONES
    // =========================================================================
    function renderDeducciones() {
        const wrap = document.getElementById('listaDeducciones');
        document.getElementById('badgeDed').textContent = deducciones.length;

        if (!deducciones.length) {
            wrap.innerHTML = `<div class="cn-empty">
                <i class="bi bi-dash-circle fs-2 d-block mb-2 opacity-25"></i>
                No hay deducciones. Crea la primera desde el formulario.
            </div>`;
            return;
        }

        wrap.innerHTML = deducciones.map(d => {
            const t = d.tipo === 'FORMULA' ? TIPO_LABELS.FORMULA : TIPO_LABELS.MONTO_FIJO;
            const valor = d.tipo === 'MONTO_FIJO'
                ? `<span class="cn-valor">$${parseFloat(d.valor || 0).toFixed(2)} USD</span>`
                : `<code class="cn-formula">${d.formula || '—'}</code>`;

            return `<div class="cn-item">
                <div class="cn-item-left">
                    <div class="cn-item-nombre">${d.nombre}</div>
                    <div class="mt-1">${valor}</div>
                    ${d.descripcion ? `<div class="cn-item-desc">${d.descripcion}</div>` : ''}
                </div>
                <div class="cn-item-right">
                    <span class="badge rounded-pill"
                          style="background:${t.bg};border:1px solid ${t.borde};color:${t.txt};font-size:10px">
                        ${t.label}
                    </span>
                    <div class="d-flex gap-1 mt-2">
                        <button class="cn-btn-edit" data-id="${d.id}" data-tipo="ded" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="cn-btn-del" data-id="${d.id}" data-nombre="${d.nombre}" data-tipo="ded" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>`;
        }).join('');

        wrap.querySelectorAll('.cn-btn-edit[data-tipo="ded"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const d = deducciones.find(x => x.id == btn.dataset.id);
                if (!d) return;
                editDedId = d.id;
                document.getElementById('ded_nombre').value  = d.nombre;
                document.getElementById('ded_tipo').value    = d.tipo;
                document.getElementById('ded_tipo').dispatchEvent(new Event('change'));
                document.getElementById('ded_valor').value   = d.valor || '';
                document.getElementById('ded_formula').value = d.formula || '';
                document.getElementById('ded_desc').value    = d.descripcion || '';
                document.getElementById('dedModeBar').innerHTML = '<i class="bi bi-pencil me-1"></i> Editando Deducción';
                document.getElementById('btnSaveDedTxt').textContent = 'Actualizar';
                document.getElementById('btnCancelDed').style.display = '';
            });
        });

        wrap.querySelectorAll('.cn-btn-del[data-tipo="ded"]').forEach(btn => {
            btn.addEventListener('click', () => {
                pendingConfirm = { tipo: 'deleteDed', id: btn.dataset.id };
                document.getElementById('confirmTitle').textContent = '¿Eliminar deducción?';
                document.getElementById('confirmMsg').innerHTML = `Se eliminará <strong>${btn.dataset.nombre}</strong>.`;
                new bootstrap.Modal(document.getElementById('modalConfirm')).show();
            });
        });
    }

    // =========================================================================
    // CANCELAR EDICIÓN
    // =========================================================================
    document.getElementById('btnCancelAsig')?.addEventListener('click', () => {
        editAsigId = null;
        ['asig_nombre','asig_valor','asig_formula','asig_desc'].forEach(id => { document.getElementById(id).value = ''; });
        document.getElementById('asig_tipo').value = '';
        document.getElementById('asig_tipo').dispatchEvent(new Event('change'));
        document.getElementById('asigModeBar').innerHTML = '<i class="bi bi-plus-circle me-1"></i> Nueva Asignación';
        document.getElementById('btnSaveAsigTxt').textContent = 'Guardar Asignación';
        document.getElementById('btnCancelAsig').style.display = 'none';
    });

    document.getElementById('btnCancelDed')?.addEventListener('click', () => {
        editDedId = null;
        ['ded_nombre','ded_valor','ded_formula','ded_desc'].forEach(id => { document.getElementById(id).value = ''; });
        document.getElementById('ded_tipo').value = '';
        document.getElementById('ded_tipo').dispatchEvent(new Event('change'));
        document.getElementById('dedModeBar').innerHTML = '<i class="bi bi-dash-circle me-1"></i> Nueva Deducción';
        document.getElementById('btnSaveDedTxt').textContent = 'Guardar Deducción';
        document.getElementById('btnCancelDed').style.display = 'none';
    });

    // =========================================================================
    // GUARDAR ASIGNACIÓN
    // =========================================================================
    document.getElementById('btnSaveAsig')?.addEventListener('click', async () => {
        const fd = new FormData();
        if (editAsigId) fd.append('id', editAsigId);
        fd.append('nombre',      document.getElementById('asig_nombre').value.trim());
        fd.append('tipo',        document.getElementById('asig_tipo').value);
        fd.append('valor',       document.getElementById('asig_valor').value);
        fd.append('formula',     document.getElementById('asig_formula').value.trim());
        fd.append('descripcion', document.getElementById('asig_desc').value.trim());

        const url = editAsigId ? `${BASE}/updateAsignacion` : `${BASE}/saveAsignacion`;
        try {
            const resp = await fetch(url, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            asignaciones = resp.asignaciones;
            document.getElementById('btnCancelAsig').click();
            renderAsignaciones();
            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false });
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    // =========================================================================
    // GUARDAR DEDUCCIÓN
    // =========================================================================
    document.getElementById('btnSaveDed')?.addEventListener('click', async () => {
        const fd = new FormData();
        if (editDedId) fd.append('id', editDedId);
        fd.append('nombre',      document.getElementById('ded_nombre').value.trim());
        fd.append('tipo',        document.getElementById('ded_tipo').value);
        fd.append('valor',       document.getElementById('ded_valor').value);
        fd.append('formula',     document.getElementById('ded_formula').value.trim());
        fd.append('descripcion', document.getElementById('ded_desc').value.trim());

        const url = editDedId ? `${BASE}/updateDeduccion` : `${BASE}/saveDeduccion`;
        try {
            const resp = await fetch(url, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }
            deducciones = resp.deducciones;
            document.getElementById('btnCancelDed').click();
            renderDeducciones();
            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false });
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    // =========================================================================
    // CONFIRMAR ELIMINAR
    // =========================================================================
    document.getElementById('btnConfirm')?.addEventListener('click', async () => {
        if (!pendingConfirm) return;
        const { tipo, id } = pendingConfirm;
        pendingConfirm = null;
        bootstrap.Modal.getInstance(document.getElementById('modalConfirm')).hide();

        const fd = new FormData();
        fd.append('id', id);

        try {
            let url, resp;
            if (tipo === 'deleteAsig') {
                url  = `${BASE}/deleteAsignacion`;
                resp = await fetch(url, { method: 'POST', body: fd }).then(r => r.json());
                if (resp.success) { asignaciones = resp.asignaciones; renderAsignaciones(); }
            } else {
                url  = `${BASE}/deleteDeduccion`;
                resp = await fetch(url, { method: 'POST', body: fd }).then(r => r.json());
                if (resp.success) { deducciones = resp.deducciones; renderDeducciones(); }
            }
            if (resp?.success) Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false });
            else if (resp) Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    // =========================================================================
    // INIT
    // =========================================================================
    renderAsignaciones();
    renderDeducciones();
});