/**
 * MÓDULO: RECURSOS HUMANOS / NÓMINA
 * ARCHIVO: public/assets/js/resources_nomina_manage.js
 * PROPÓSITO: Buscar y agregar personal a la nómina (salario manual con botón
 *            copiar del contrato), agregar asignaciones/deducciones desde el
 *            catálogo a cada persona, ver totales, y procesar la nómina.
 * VERSIÓN: 1.0.0 - Creación inicial. Vanilla JS puro.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE      = (window.APP_BASE_PATH || '') + '/resources/nomina';
    const NOMINA_ID = window.NOMINA_ID;
    const TIPO      = window.NOMINA_TIPO;
    const ESTADO    = window.NOMINA_ESTADO;

    let personal       = window.PERSONAL_INIT    || [];
    let catAsignaciones = window.CAT_ASIGNACIONES || [];
    let catDeducciones  = window.CAT_DEDUCCIONES  || [];

    let personalParaAgregar = null;
    let activeNominaPersonalId = null;
    let debounceTimer = null;

    function formatFecha(fechaStr) {
        if (!fechaStr) return '—';
        const d = new Date(fechaStr + 'T12:00:00');
        return d.toLocaleDateString('es-VE', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // =========================================================================
    // BUSCADOR DE PERSONAL
    // =========================================================================
    const inputBuscar = document.getElementById('buscarPersonal');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const term = this.value.trim();
            if (term.length < 2) { ocultarDropdown(); return; }
            debounceTimer = setTimeout(() => buscarPersonal(term), 300);
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#buscarPersonal') && !e.target.closest('#personalDropdown')) {
                ocultarDropdown();
            }
        });
    }

    function ocultarDropdown() {
        const d = document.getElementById('personalDropdown');
        if (d) d.style.display = 'none';
    }

    async function buscarPersonal(term) {
        const resp = await fetch(
            `${BASE}/buscarPersonal?nomina_id=${NOMINA_ID}&tipo=${TIPO}&search=${encodeURIComponent(term)}`
        ).then(r => r.json());

        const dropdown = document.getElementById('personalDropdown');
        if (!dropdown) return;

        const data = resp.data || [];
        if (!data.length) {
            dropdown.innerHTML = `<div class="n-dropdown-item text-muted small">Sin resultados</div>`;
            dropdown.style.display = 'block';
            return;
        }

        dropdown.innerHTML = data.map(p => `
            <button type="button" class="n-dropdown-item" data-id="${p.id}"
                    data-nombre="${p.first_name} ${p.last_name}"
                    data-cedula="${p.document_id}"
                    data-tipo="${p.tipo_nombre}"
                    data-monto="${p.monto_contrato ?? ''}">
                <div class="fw-bold small">${p.last_name}, ${p.first_name}</div>
                <div class="text-muted" style="font-size:11px">${p.document_id} · ${p.tipo_nombre}</div>
            </button>`).join('');
        dropdown.style.display = 'block';

        dropdown.querySelectorAll('.n-dropdown-item[data-id]').forEach(btn => {
            btn.addEventListener('click', () => abrirModalAddPersonal(btn.dataset));
        });
    }

    // =========================================================================
    // COLA DE PROFESORES CON SESIONES PENDIENTES (POR_SESION)
    // =========================================================================
    async function cargarColaSesiones() {
        if (TIPO !== 'POR_SESION' || ESTADO !== 'BORRADOR') return;

        const card = document.getElementById('cardColaSesiones');
        const wrap = document.getElementById('colaSesiones');

        try {
            const resp = await fetch(`${BASE}/getColaSesiones?nomina_id=${NOMINA_ID}`).then(r => r.json());
            const data = resp.data || [];

            if (!data.length) {
                card.style.display = 'none';
                return;
            }

            card.style.display = '';
            wrap.innerHTML = data.map(p => {
                const tarifa = parseFloat(p.tarifa || 0);
                const sesiones = parseInt(p.sesiones_pendientes);
                const total = tarifa > 0 ? (tarifa * sesiones) : 0;
                const detalle = p.detalle || [];

                const filasFechas = detalle.map(s => `
                    <div class="n-cola-fecha-item">
                        <i class="bi bi-calendar-event me-1 text-muted"></i>
                        <strong>${formatFecha(s.fecha)}</strong>
                        <span class="text-muted">— ${s.diplomado_nombre || '—'} · ${s.horario_desc || '—'}</span>
                    </div>`).join('');

                return `<div class="n-cola-wrap" data-pid="${p.id}">
                    <div class="n-cola-item">
                        <div>
                            <div class="n-cola-nombre">${p.last_name}, ${p.first_name}</div>
                            <div class="n-cola-detalle">
                                ${sesiones} sesión${sesiones !== 1 ? 'es' : ''} dictada${sesiones !== 1 ? 's' : ''} pendiente${sesiones !== 1 ? 's' : ''}
                                ${tarifa > 0 ? ` × $${tarifa.toFixed(2)} c/u` : ' · sin tarifa de contrato'}
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="n-cola-monto">${tarifa > 0 ? '$' + total.toFixed(2) : '—'}</span>
                            <button class="btn btn-sm btn-warning rounded-pill px-3 fw-bold btn-agregar-cola"
                                    data-id="${p.id}" data-nombre="${p.last_name}, ${p.first_name}"
                                    data-cedula="${p.document_id}" data-tipo="${p.tipo_nombre}"
                                    data-total="${total}">
                                <i class="bi bi-plus-circle me-1"></i> Agregar
                            </button>
                        </div>
                    </div>
                    <div class="n-cola-fechas">${filasFechas}</div>
                </div>`;
            }).join('');

            wrap.querySelectorAll('.btn-agregar-cola').forEach(btn => {
                btn.addEventListener('click', () => {
                    abrirModalAddPersonal({
                        id: btn.dataset.id, nombre: btn.dataset.nombre,
                        cedula: btn.dataset.cedula, tipo: btn.dataset.tipo,
                        monto: btn.dataset.total > 0 ? btn.dataset.total : '',
                        origen: 'cola'
                    });
                });
            });

        } catch (e) { console.error(e); }
    }

    // =========================================================================
    // MODAL AGREGAR PERSONAL
    // =========================================================================
    function abrirModalAddPersonal(data) {
        personalParaAgregar = data;
        document.getElementById('modalPersonalInfo').innerHTML =
            `<i class="bi bi-person me-1"></i> <strong>${data.nombre}</strong> — ${data.cedula} (${data.tipo})`;
        document.getElementById('modalSalario').value = data.monto ? parseFloat(data.monto).toFixed(2) : '';

        if (data.origen === 'cola') {
            document.getElementById('montoContratoTxt').innerHTML =
                `<i class="bi bi-calculator me-1"></i> Calculado por sesiones dictadas pendientes. Puedes ajustarlo si hace falta.`;
            document.getElementById('btnCopiarContrato').style.display = 'none';
        } else if (data.monto && data.monto !== 'null' && data.monto !== '') {
            document.getElementById('montoContratoTxt').innerHTML =
                `<i class="bi bi-info-circle me-1"></i> El contrato registra: <strong>$${parseFloat(data.monto).toFixed(2)}</strong>`;
            document.getElementById('btnCopiarContrato').style.display = '';
        } else {
            document.getElementById('montoContratoTxt').innerHTML =
                `<i class="bi bi-exclamation-circle me-1"></i> Este personal no tiene contrato activo con monto registrado.`;
            document.getElementById('btnCopiarContrato').style.display = 'none';
        }

        ocultarDropdown();
        inputBuscar.value = '';
        new bootstrap.Modal(document.getElementById('modalAddPersonal')).show();
    }

    document.getElementById('btnCopiarContrato')?.addEventListener('click', () => {
        if (personalParaAgregar?.monto) {
            document.getElementById('modalSalario').value = parseFloat(personalParaAgregar.monto).toFixed(2);
        }
    });

    document.getElementById('btnConfirmAddPersonal')?.addEventListener('click', async () => {
        const salario = parseFloat(document.getElementById('modalSalario').value);
        if (!salario || salario <= 0) {
            Swal.fire({ icon: 'warning', title: 'Escribe el salario base', confirmButtonColor: '#dc3545' });
            return;
        }

        const fd = new FormData();
        fd.append('nomina_id',    NOMINA_ID);
        fd.append('personal_id',  personalParaAgregar.id);
        fd.append('salario_base', salario);

        try {
            const resp = await fetch(`${BASE}/addPersonal`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }

            personal = resp.personal;
            renderGrid();
            cargarColaSesiones();
            bootstrap.Modal.getInstance(document.getElementById('modalAddPersonal')).hide();
            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false });
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    // =========================================================================
    // MODAL EDITAR SALARIO
    // =========================================================================
    let editandoNominaPersonalId = null;
    let editandoPersonalId       = null;

    async function abrirModalEditSalario(data) {
        editandoNominaPersonalId = data.id;
        editandoPersonalId       = data.personalId;

        document.getElementById('editSalarioInfo').innerHTML =
            `<i class="bi bi-person me-1"></i> <strong>${data.nombre}</strong>`;
        document.getElementById('editSalarioInput').value = parseFloat(data.salario).toFixed(2);
        document.getElementById('montoContratoEditTxt').innerHTML =
            `<i class="bi bi-hourglass-split me-1"></i> Consultando contrato...`;

        new bootstrap.Modal(document.getElementById('modalEditSalario')).show();

        try {
            const resp = await fetch(`${BASE}/getMontoContrato?personal_id=${editandoPersonalId}`).then(r => r.json());
            if (resp.success && resp.monto) {
                document.getElementById('montoContratoEditTxt').innerHTML =
                    `<i class="bi bi-info-circle me-1"></i> El contrato registra: <strong>$${parseFloat(resp.monto).toFixed(2)}</strong>`;
                document.getElementById('btnCopiarContratoEdit').dataset.monto = resp.monto;
                document.getElementById('btnCopiarContratoEdit').style.display = '';
            } else {
                document.getElementById('montoContratoEditTxt').innerHTML =
                    `<i class="bi bi-exclamation-circle me-1"></i> Sin contrato activo con monto registrado.`;
                document.getElementById('btnCopiarContratoEdit').style.display = 'none';
            }
        } catch (e) {
            document.getElementById('montoContratoEditTxt').innerHTML = '';
        }
    }

    document.getElementById('btnCopiarContratoEdit')?.addEventListener('click', function () {
        if (this.dataset.monto) {
            document.getElementById('editSalarioInput').value = parseFloat(this.dataset.monto).toFixed(2);
        }
    });

    document.getElementById('btnConfirmEditSalario')?.addEventListener('click', async () => {
        const nuevoSalario = parseFloat(document.getElementById('editSalarioInput').value);
        if (!nuevoSalario || nuevoSalario <= 0) {
            Swal.fire({ icon: 'warning', title: 'Escribe un salario válido', confirmButtonColor: '#ffc107' });
            return;
        }

        const fd = new FormData();
        fd.append('nomina_personal_id', editandoNominaPersonalId);
        fd.append('nomina_id', NOMINA_ID);
        fd.append('salario_base', nuevoSalario);

        try {
            const resp = await fetch(`${BASE}/updateSalario`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }

            personal = resp.personal;
            renderGrid();
            bootstrap.Modal.getInstance(document.getElementById('modalEditSalario')).hide();
            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false });
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    // =========================================================================
    // RENDER GRID DE PERSONAL
    // =========================================================================
    function renderGrid() {
        const wrap = document.getElementById('gridPersonal');
        document.getElementById('badgeTotalPersonal').textContent = personal.length;

        if (!personal.length) {
            wrap.innerHTML = `<div class="n-empty">
                <i class="bi bi-people fs-2 d-block mb-2 opacity-25"></i>
                No hay personal agregado. Búscalo arriba para comenzar.
            </div>`;
            document.getElementById('totalesBar').innerHTML = '';
            return;
        }

        let totalUsdGeneral = 0, totalBsGeneral = 0;

        wrap.innerHTML = `<div class="table-responsive">
            <table class="table table-sm align-middle n-table">
                <thead>
                    <tr>
                        <th>Personal</th>
                        <th class="text-end">Salario Base</th>
                        <th class="text-end">+ Asig.</th>
                        <th class="text-end">- Deduc.</th>
                        <th class="text-end">Total USD</th>
                        <th class="text-end">Total Bs</th>
                        <th class="text-center">Conceptos</th>
                        ${ESTADO === 'BORRADOR' ? '<th class="text-end">Acción</th>' : ''}
                    </tr>
                </thead>
                <tbody>
                    ${personal.map(p => {
                        totalUsdGeneral += parseFloat(p.total_usd || 0);
                        totalBsGeneral  += parseFloat(p.total_bs  || 0);
                        return `
                        <tr>
                            <td>
                                <div class="fw-bold small">${p.last_name}, ${p.first_name}</div>
                                <div class="text-muted" style="font-size:11px">${p.tipo_nombre}</div>
                            </td>
                            <td class="text-end">
                                $${parseFloat(p.salario_base).toFixed(2)}
                                ${ESTADO === 'BORRADOR' ? `
                                <button class="btn btn-sm btn-link p-0 ms-1 btn-edit-salario"
                                        data-id="${p.id}" data-personal-id="${p.personal_id}"
                                        data-nombre="${p.last_name}, ${p.first_name}"
                                        data-salario="${p.salario_base}" title="Editar salario">
                                    <i class="bi bi-pencil-square"></i>
                                </button>` : ''}
                            </td>
                            <td class="text-end text-success">+$${parseFloat(p.total_asignaciones).toFixed(2)}</td>
                            <td class="text-end text-danger">-$${parseFloat(p.total_deducciones).toFixed(2)}</td>
                            <td class="text-end fw-bold">$${parseFloat(p.total_usd).toFixed(2)}</td>
                            <td class="text-end fw-bold text-muted">Bs. ${parseFloat(p.total_bs).toLocaleString('es-VE',{minimumFractionDigits:2})}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0 btn-conceptos"
                                        data-id="${p.id}" data-nombre="${p.last_name}, ${p.first_name}">
                                    <i class="bi bi-sliders"></i>
                                </button>
                            </td>
                            ${ESTADO === 'BORRADOR' ? `
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0 btn-remove-personal"
                                        data-id="${p.id}" data-nombre="${p.last_name}, ${p.first_name}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>` : ''}
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>`;

        document.getElementById('totalesBar').innerHTML = `
            <div class="d-flex justify-content-end gap-4">
                <div class="text-end">
                    <div class="small text-muted text-uppercase fw-bold">Total USD</div>
                    <div class="fs-5 fw-bold" style="color:#085041">$${totalUsdGeneral.toFixed(2)}</div>
                </div>
                <div class="text-end">
                    <div class="small text-muted text-uppercase fw-bold">Total Bs</div>
                    <div class="fs-5 fw-bold" style="color:#3C3489">Bs. ${totalBsGeneral.toLocaleString('es-VE',{minimumFractionDigits:2})}</div>
                </div>
            </div>`;

        wrap.querySelectorAll('.btn-conceptos').forEach(btn => {
            btn.addEventListener('click', () => abrirModalConceptos(parseInt(btn.dataset.id), btn.dataset.nombre));
        });

        wrap.querySelectorAll('.btn-edit-salario').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                abrirModalEditSalario(btn.dataset);
            });
        });

        wrap.querySelectorAll('.btn-remove-personal').forEach(btn => {
            btn.addEventListener('click', async () => {
                const confirmed = await Swal.fire({
                    title: '¿Quitar de la nómina?',
                    html: `Se quitará a <strong>${btn.dataset.nombre}</strong> y todos sus conceptos.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Sí, quitar',
                    cancelButtonText: 'Cancelar'
                });
                if (!confirmed.isConfirmed) return;

                const fd = new FormData();
                fd.append('id', btn.dataset.id);
                fd.append('nomina_id', NOMINA_ID);
                const resp = await fetch(`${BASE}/removePersonal`, { method: 'POST', body: fd }).then(r => r.json());
                if (resp.success) { personal = resp.personal; renderGrid(); }
            });
        });
    }

    // =========================================================================
    // MODAL CONCEPTOS (asignaciones / deducciones)
    // =========================================================================
    function abrirModalConceptos(nominaPersonalId, nombre) {
        activeNominaPersonalId = nominaPersonalId;
        document.getElementById('modalConceptosNombre').textContent = `Conceptos — ${nombre}`;

        const selA = document.getElementById('selectAsignacion');
        selA.innerHTML = '<option value="">Seleccionar del catálogo...</option>' +
            catAsignaciones.map(a => `<option value="${a.id}" data-nombre="${a.nombre}" data-valor="${a.valor || ''}">${a.nombre}</option>`).join('');

        const selD = document.getElementById('selectDeduccion');
        selD.innerHTML = '<option value="">Seleccionar del catálogo...</option>' +
            catDeducciones.map(d => `<option value="${d.id}" data-nombre="${d.nombre}" data-valor="${d.valor || ''}">${d.nombre}</option>`).join('');

        document.getElementById('montoAsignacion').value = '';
        document.getElementById('montoDeduccion').value  = '';

        renderConceptosListas();
        new bootstrap.Modal(document.getElementById('modalConceptos')).show();
    }

    selectMonto('selectAsignacion', 'montoAsignacion');
    selectMonto('selectDeduccion', 'montoDeduccion');

    function selectMonto(selectId, montoId) {
        document.getElementById(selectId)?.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const valor = opt.dataset.valor;
            if (valor) document.getElementById(montoId).value = valor;
        });
    }

    function renderConceptosListas() {
        const p = personal.find(x => x.id == activeNominaPersonalId);
        if (!p) return;

        const listaA = document.getElementById('listaAsigItems');
        listaA.innerHTML = (p.asignaciones || []).map(a => `
            <div class="n-concepto-item">
                <span>${a.nombre_concepto}</span>
                <div class="d-flex align-items-center gap-2">
                    <strong>$${parseFloat(a.monto).toFixed(2)}</strong>
                    <button class="n-btn-x" data-id="${a.id}" data-tipo="asig">✕</button>
                </div>
            </div>`).join('') || '<div class="text-muted small text-center py-2">Sin asignaciones</div>';

        const listaD = document.getElementById('listaDedItems');
        listaD.innerHTML = (p.deducciones || []).map(d => `
            <div class="n-concepto-item">
                <span>${d.nombre_concepto}</span>
                <div class="d-flex align-items-center gap-2">
                    <strong>$${parseFloat(d.monto).toFixed(2)}</strong>
                    <button class="n-btn-x" data-id="${d.id}" data-tipo="ded">✕</button>
                </div>
            </div>`).join('') || '<div class="text-muted small text-center py-2">Sin deducciones</div>';

        listaA.querySelectorAll('.n-btn-x').forEach(btn => btn.addEventListener('click', () => removeConcepto(btn.dataset.id, 'asig')));
        listaD.querySelectorAll('.n-btn-x').forEach(btn => btn.addEventListener('click', () => removeConcepto(btn.dataset.id, 'ded')));
    }

    document.getElementById('btnAddAsigItem')?.addEventListener('click', async () => {
        const sel    = document.getElementById('selectAsignacion');
        const opt    = sel.options[sel.selectedIndex];
        const monto  = parseFloat(document.getElementById('montoAsignacion').value);

        if (!sel.value || !monto || monto <= 0) {
            Swal.fire({ icon: 'warning', title: 'Selecciona un concepto y un monto válido', confirmButtonColor: '#198754' });
            return;
        }

        const fd = new FormData();
        fd.append('nomina_personal_id', activeNominaPersonalId);
        fd.append('nomina_id', NOMINA_ID);
        fd.append('asignacion_id', sel.value);
        fd.append('nombre', opt.dataset.nombre);
        fd.append('monto', monto);

        const resp = await fetch(`${BASE}/addAsignacion`, { method: 'POST', body: fd }).then(r => r.json());
        if (resp.success) {
            personal = resp.personal;
            renderConceptosListas();
            renderGrid();
            document.getElementById('montoAsignacion').value = '';
            sel.value = '';
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
        }
    });

    document.getElementById('btnAddDedItem')?.addEventListener('click', async () => {
        const sel    = document.getElementById('selectDeduccion');
        const opt    = sel.options[sel.selectedIndex];
        const monto  = parseFloat(document.getElementById('montoDeduccion').value);

        if (!sel.value || !monto || monto <= 0) {
            Swal.fire({ icon: 'warning', title: 'Selecciona un concepto y un monto válido', confirmButtonColor: '#198754' });
            return;
        }

        const fd = new FormData();
        fd.append('nomina_personal_id', activeNominaPersonalId);
        fd.append('nomina_id', NOMINA_ID);
        fd.append('deduccion_id', sel.value);
        fd.append('nombre', opt.dataset.nombre);
        fd.append('monto', monto);

        const resp = await fetch(`${BASE}/addDeduccion`, { method: 'POST', body: fd }).then(r => r.json());
        if (resp.success) {
            personal = resp.personal;
            renderConceptosListas();
            renderGrid();
            document.getElementById('montoDeduccion').value = '';
            sel.value = '';
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
        }
    });

    async function removeConcepto(id, tipo) {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('nomina_personal_id', activeNominaPersonalId);
        fd.append('nomina_id', NOMINA_ID);

        const url = tipo === 'asig' ? `${BASE}/deleteAsignacionItem` : `${BASE}/deleteDeduccionItem`;
        const resp = await fetch(url, { method: 'POST', body: fd }).then(r => r.json());
        if (resp.success) {
            personal = resp.personal;
            renderConceptosListas();
            renderGrid();
        }
    }

    // =========================================================================
    // PROCESAR NÓMINA
    // =========================================================================
    document.getElementById('btnProcesar')?.addEventListener('click', async () => {
        if (!personal.length) {
            Swal.fire({ icon: 'warning', title: 'Agrega al menos una persona', confirmButtonColor: '#dc3545' });
            return;
        }

        const confirmed = await Swal.fire({
            title: '¿Procesar esta nómina?',
            text: 'Una vez procesada no se podrá modificar.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, procesar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('nomina_id', NOMINA_ID);

        try {
            const resp = await fetch(`${BASE}/procesar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }

            Swal.fire({ icon: 'success', title: '¡Nómina procesada!', text: resp.message, timer: 2000, showConfirmButton: false })
                .then(() => window.location.reload());
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    // =========================================================================
    // REVERSAR NÓMINA
    // =========================================================================
    document.getElementById('btnReversarNomina')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Reversar a borrador?',
            text: 'La nómina volverá a estado BORRADOR y podrás editarla nuevamente.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Sí, reversar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('nomina_id', NOMINA_ID);

        try {
            const resp = await fetch(`${BASE}/reversar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire({ icon: 'error', title: 'Error', text: resp.message }); return; }

            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1500, showConfirmButton: false })
                .then(() => window.location.reload());
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
    });

    // =========================================================================
    // INIT
    // =========================================================================
    renderGrid();
    cargarColaSesiones();
});