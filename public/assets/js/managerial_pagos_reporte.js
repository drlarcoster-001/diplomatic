/**
 * MÓDULO: GESTIÓN GERENCIAL / REPORTE DE PAGOS
 * ARCHIVO: public/assets/js/managerial_pagos_reporte.js
 * PROPÓSITO: Cascada Período → Oferta(Diplomado+Grupo) → Usuario.
 *            Lista inmediata al elegir oferta + buscador en memoria.
 *            Botón Limpiar filtros. Botón Buscar.
 * VERSIÓN: 1.3.0 - Cascada simplificada sin Cohorte.
 */

document.addEventListener('DOMContentLoaded', () => {
    const BASE         = window.APP_BASE_PATH || '';
    const selPeriodo   = document.getElementById('selPeriodo');
    const selDiplomado = document.getElementById('selDiplomado');
    const inputUsuario = document.getElementById('inputUsuario');
    const hidUsuario   = document.getElementById('hidUsuario');
    const resultados   = document.getElementById('usuarioResultados');
    const btnLimpiarU  = document.getElementById('btnLimpiarUsuario');
    const btnBuscar    = document.getElementById('btnBuscar');
    const btnLimpiar   = document.getElementById('btnLimpiar');

    let usuarioTimer  = null;
    let todosUsuarios = [];

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
    // USUARIO — lista + buscador
    // =========================================================================

    function resetUsuario() {
        todosUsuarios = [];
        if (inputUsuario) { inputUsuario.value = ''; inputUsuario.disabled = true; }
        if (hidUsuario)    hidUsuario.value = '0';
        if (resultados)    resultados.innerHTML = '';
        if (btnLimpiarU)   btnLimpiarU.classList.add('d-none');
    }

    function renderResultados(lista) {
        if (!lista.length) {
            resultados.innerHTML = '<div class="list-group-item text-muted small py-2">Sin resultados.</div>';
            return;
        }
        resultados.innerHTML = lista.map(u => `
            <button type="button"
                    class="list-group-item list-group-item-action small py-2 usuario-item"
                    data-uid="${u.id}" data-nombre="${u.nombre}">
                <i class="bi bi-person me-1 text-muted"></i>
                <strong>${u.nombre}</strong>
                ${u.document_id ? `<span class="text-muted"> — ${u.document_id}</span>` : ''}
            </button>`).join('');

        resultados.querySelectorAll('.usuario-item').forEach(btn => {
            btn.addEventListener('click', () => {
                hidUsuario.value   = btn.dataset.uid;
                inputUsuario.value = btn.dataset.nombre;
                resultados.innerHTML = '';
                if (btnLimpiarU) btnLimpiarU.classList.remove('d-none');
            });
        });
    }

    inputUsuario?.addEventListener('focus', () => {
        if (!inputUsuario.value.trim() && todosUsuarios.length) {
            renderResultados(todosUsuarios);
        }
    });

    inputUsuario?.addEventListener('input', () => {
        clearTimeout(usuarioTimer);
        hidUsuario.value = '0';
        if (btnLimpiarU) btnLimpiarU.classList.add('d-none');

        const term = inputUsuario.value.trim().toLowerCase();
        usuarioTimer = setTimeout(() => {
            if (!term) { renderResultados(todosUsuarios); return; }
            const filtrados = todosUsuarios.filter(u =>
                u.nombre.toLowerCase().includes(term) ||
                (u.document_id && u.document_id.toLowerCase().includes(term))
            );
            renderResultados(filtrados);
        }, 200);
    });

    document.addEventListener('click', (e) => {
        if (!inputUsuario?.contains(e.target) && !resultados?.contains(e.target)) {
            if (resultados) resultados.innerHTML = '';
        }
    });

    btnLimpiarU?.addEventListener('click', () => {
        inputUsuario.value = '';
        hidUsuario.value   = '0';
        resultados.innerHTML = '';
        btnLimpiarU.classList.add('d-none');
        inputUsuario.focus();
        if (todosUsuarios.length) renderResultados(todosUsuarios);
    });

    async function cargarUsuarios(offeringId, selectedUid) {
        todosUsuarios = await fetchData(`${BASE}/managerial/pagos-reporte/usuarios?offering_id=${offeringId}`);
        if (inputUsuario) inputUsuario.disabled = false;
        if (selectedUid) {
            const u = todosUsuarios.find(u => parseInt(u.id) === parseInt(selectedUid));
            if (u) {
                inputUsuario.value = u.nombre;
                hidUsuario.value   = u.id;
                if (btnLimpiarU) btnLimpiarU.classList.remove('d-none');
            }
        }
    }

    // =========================================================================
    // INICIALIZACIÓN
    // =========================================================================

    async function init() {
        const pId = window.PERIODO_ID;
        const oId = window.OFFERING_ID;
        const uId = window.USER_ID;

        if (pId) {
            const ofertas = await fetchData(`${BASE}/managerial/pagos-reporte/diplomados?periodo_id=${pId}`);
            populateSelect(selDiplomado, ofertas, 'name', 'id', oId);

            if (oId) await cargarUsuarios(oId, uId);

            btnBuscar.disabled = false;

            const userSearch = window.USER_SEARCH || '';
            if (userSearch && inputUsuario && !inputUsuario.value) inputUsuario.value = userSearch;
        }
    }

    init();

    // =========================================================================
    // CASCADA: PERÍODO → OFERTAS
    // =========================================================================

    selPeriodo.addEventListener('change', async () => {
        resetSelect(selDiplomado, '— Cargando... —');
        resetUsuario();
        btnBuscar.disabled = true;

        const pId = selPeriodo.value;
        if (!pId) { resetSelect(selDiplomado, '— Primero elige período —'); return; }

        const ofertas = await fetchData(`${BASE}/managerial/pagos-reporte/diplomados?periodo_id=${pId}`);
        populateSelect(selDiplomado, ofertas, 'name', 'id', 0);
        btnBuscar.disabled = false;
    });

    // =========================================================================
    // CASCADA: OFERTA → USUARIOS
    // =========================================================================

    selDiplomado.addEventListener('change', async () => {
        resetUsuario();
        const oId = selDiplomado.value;
        if (oId) await cargarUsuarios(oId, 0);
    });

    // =========================================================================
    // BUSCAR
    // =========================================================================

    btnBuscar.addEventListener('click', () => {
        const pId = selPeriodo.value;
        if (!pId) return;

        const params = new URLSearchParams({
            periodo_id:  pId,
            offering_id: selDiplomado.value || 0,
            user_id:     hidUsuario.value   || 0,
            user_search: inputUsuario?.value || '',
        });

        window.location.href = `${BASE}/managerial/pagos-reporte?${params.toString()}`;
    });

    // =========================================================================
    // LIMPIAR TODOS LOS FILTROS
    // =========================================================================

    btnLimpiar?.addEventListener('click', () => {
        window.location.href = `${BASE}/managerial/pagos-reporte`;
    });
});