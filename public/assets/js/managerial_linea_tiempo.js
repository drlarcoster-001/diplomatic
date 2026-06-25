/**
 * MÓDULO: GESTIÓN GERENCIAL / LÍNEA DE TIEMPO
 * ARCHIVO: public/assets/js/managerial_linea_tiempo.js
 * PROPÓSITO: Cascada Período → Oferta → Estudiante.
 *            Buscador con lista inmediata de estudiantes.
 *            Botón Limpiar y Buscar.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', () => {
    const BASE            = window.APP_BASE_PATH || '';
    const selPeriodo      = document.getElementById('selPeriodo');
    const selOferta       = document.getElementById('selOferta');
    const inputEstudiante = document.getElementById('inputEstudiante');
    const hidEnrollment   = document.getElementById('hidEnrollment');
    const resultados      = document.getElementById('estudianteResultados');
    const btnLimpiarEst   = document.getElementById('btnLimpiarEst');
    const btnBuscar       = document.getElementById('btnBuscar');
    const btnLimpiar      = document.getElementById('btnLimpiar');

    let estTimer   = null;
    let todosEst   = [];

    // =========================================================================
    // HELPERS
    // =========================================================================

    function resetSelect(sel, placeholder) {
        sel.innerHTML = `<option value="">${placeholder}</option>`;
        sel.disabled  = true;
    }

    async function fetchData(url) {
        const resp = await fetch(url);
        const json = await resp.json();
        return json.data || [];
    }

    function populateSelect(sel, items, labelKey, valKey, selectedVal) {
        sel.innerHTML = '<option value="">Todos</option>';
        items.forEach(item => {
            const opt       = document.createElement('option');
            opt.value       = item[valKey];
            opt.textContent = item[labelKey];
            if (parseInt(selectedVal) === parseInt(item[valKey])) opt.selected = true;
            sel.appendChild(opt);
        });
        sel.disabled = false;
    }

    // =========================================================================
    // BUSCADOR ESTUDIANTE
    // =========================================================================

    function resetEstudiante() {
        todosEst = [];
        if (inputEstudiante) { inputEstudiante.value = ''; inputEstudiante.disabled = true; }
        if (hidEnrollment)    hidEnrollment.value = '0';
        if (resultados)       resultados.innerHTML = '';
        if (btnLimpiarEst)    btnLimpiarEst.classList.add('d-none');
        if (btnBuscar)        btnBuscar.disabled = true;
    }

    function renderResultados(lista) {
        if (!lista.length) {
            resultados.innerHTML = '<div class="list-group-item text-muted small py-2">Sin resultados.</div>';
            return;
        }
        resultados.innerHTML = lista.map(e => `
            <button type="button"
                    class="list-group-item list-group-item-action small py-2 est-item"
                    data-eid="${e.enrollment_id}" data-nombre="${e.nombre}">
                <i class="bi bi-person me-1 text-muted"></i>
                <strong>${e.nombre}</strong>
                ${e.document_id ? `<span class="text-muted"> — ${e.document_id}</span>` : ''}
            </button>`).join('');

        resultados.querySelectorAll('.est-item').forEach(btn => {
            btn.addEventListener('click', () => {
                hidEnrollment.value      = btn.dataset.eid;
                inputEstudiante.value    = btn.dataset.nombre;
                resultados.innerHTML     = '';
                if (btnLimpiarEst) btnLimpiarEst.classList.remove('d-none');
                if (btnBuscar)     btnBuscar.disabled = false;
            });
        });
    }

    inputEstudiante?.addEventListener('focus', () => {
        if (!inputEstudiante.value.trim() && todosEst.length) renderResultados(todosEst);
    });

    inputEstudiante?.addEventListener('input', () => {
        clearTimeout(estTimer);
        hidEnrollment.value = '0';
        btnBuscar.disabled  = true;
        if (btnLimpiarEst) btnLimpiarEst.classList.add('d-none');

        const term = inputEstudiante.value.trim().toLowerCase();
        estTimer = setTimeout(() => {
            if (!term) { renderResultados(todosEst); return; }
            const filtrados = todosEst.filter(e =>
                e.nombre.toLowerCase().includes(term) ||
                (e.document_id && e.document_id.toLowerCase().includes(term))
            );
            renderResultados(filtrados);
        }, 200);
    });

    document.addEventListener('click', (e) => {
        if (!inputEstudiante?.contains(e.target) && !resultados?.contains(e.target)) {
            if (resultados) resultados.innerHTML = '';
        }
    });

    btnLimpiarEst?.addEventListener('click', () => {
        inputEstudiante.value = '';
        hidEnrollment.value   = '0';
        resultados.innerHTML  = '';
        btnLimpiarEst.classList.add('d-none');
        btnBuscar.disabled    = true;
        inputEstudiante.focus();
        if (todosEst.length) renderResultados(todosEst);
    });

    async function cargarEstudiantes(offeringId, selectedEid) {
        todosEst = await fetchData(`${BASE}/managerial/linea-tiempo/estudiantes?offering_id=${offeringId}`);
        if (inputEstudiante) inputEstudiante.disabled = false;
        if (selectedEid) {
            const e = todosEst.find(e => parseInt(e.enrollment_id) === parseInt(selectedEid));
            if (e) {
                inputEstudiante.value = e.nombre;
                hidEnrollment.value   = e.enrollment_id;
                if (btnLimpiarEst) btnLimpiarEst.classList.remove('d-none');
                if (btnBuscar)     btnBuscar.disabled = false;
            }
        }
    }

    // =========================================================================
    // INICIALIZACIÓN
    // =========================================================================

    async function init() {
        const pId = window.PERIODO_ID;
        const oId = window.OFFERING_ID;
        const eId = window.ENROLLMENT_ID;

        if (pId) {
            const ofertas = await fetchData(`${BASE}/managerial/linea-tiempo/ofertas?periodo_id=${pId}`);
            populateSelect(selOferta, ofertas, 'name', 'id', oId);
            if (oId) await cargarEstudiantes(oId, eId);
            if (btnBuscar && !eId) btnBuscar.disabled = true;
        }
    }

    init();

    // =========================================================================
    // CASCADA: PERÍODO → OFERTAS
    // =========================================================================

    selPeriodo.addEventListener('change', async () => {
        resetSelect(selOferta, '— Cargando... —');
        resetEstudiante();

        const pId = selPeriodo.value;
        if (!pId) { resetSelect(selOferta, '— Primero elige período —'); return; }

        const ofertas = await fetchData(`${BASE}/managerial/linea-tiempo/ofertas?periodo_id=${pId}`);
        populateSelect(selOferta, ofertas, 'name', 'id', 0);
    });

    // =========================================================================
    // CASCADA: OFERTA → ESTUDIANTES
    // =========================================================================

    selOferta.addEventListener('change', async () => {
        resetEstudiante();
        const oId = selOferta.value;
        if (oId) await cargarEstudiantes(oId, 0);
    });

    // =========================================================================
    // BUSCAR
    // =========================================================================

    btnBuscar.addEventListener('click', () => {
        const eId = hidEnrollment.value;
        if (!eId || eId === '0') return;

        const params = new URLSearchParams({
            periodo_id:    selPeriodo.value   || 0,
            offering_id:   selOferta.value    || 0,
            enrollment_id: eId,
            user_search:   inputEstudiante?.value || '',
        });

        window.location.href = `${BASE}/managerial/linea-tiempo?${params.toString()}`;
    });

    // =========================================================================
    // LIMPIAR
    // =========================================================================

    btnLimpiar?.addEventListener('click', () => {
        window.location.href = `${BASE}/managerial/linea-tiempo`;
    });
});