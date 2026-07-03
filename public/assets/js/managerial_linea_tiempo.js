/**
 * MÓDULO: GESTIÓN GERENCIAL / LÍNEA DE TIEMPO
 * ARCHIVO: public/assets/js/managerial_linea_tiempo.js
 * PROPÓSITO: Cascada Usuario (búsqueda global con debounce) → Período
 *            (solo los que ese usuario tiene) → Diplomado (los de ese
 *            usuario en ese período, value = enrollment_id).
 * VERSIÓN: 2.0.0 - Cascada invertida: Usuario → Período → Diplomado.
 *          Reemplaza el buscador de "estudiante por oferta" por búsqueda
 *          global de usuario con debounce contra el servidor.
 */

document.addEventListener('DOMContentLoaded', () => {
    const BASE              = window.APP_BASE_PATH || '';
    const inputUsuario      = document.getElementById('inputUsuario');
    const hidUserId         = document.getElementById('hidUserId');
    const usuarioResultados = document.getElementById('usuarioResultados');
    const btnLimpiarUsuario = document.getElementById('btnLimpiarUsuario');
    const selPeriodo        = document.getElementById('selPeriodo');
    const selOferta         = document.getElementById('selOferta');
    const btnBuscar         = document.getElementById('btnBuscar');
    const btnLimpiar        = document.getElementById('btnLimpiar');

    let usuarioTimer = null;

    // =========================================================================
    // HELPERS
    // =========================================================================

    async function fetchData(url) {
        const resp = await fetch(url);
        const json = await resp.json();
        return json.data || [];
    }

    function resetSelect(sel, placeholder) {
        sel.innerHTML = `<option value="">${placeholder}</option>`;
        sel.disabled  = true;
    }

    function populateSelect(sel, items, labelKey, valKey, selectedVal) {
        sel.innerHTML = '<option value="">— Selecciona —</option>';
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
    // PASO 1: BÚSQUEDA GLOBAL DE USUARIO
    // =========================================================================

    function resetDesdeUsuario() {
        hidUserId.value = '0';
        resetSelect(selPeriodo, '— Primero elige un usuario —');
        resetSelect(selOferta, '— Primero elige un período —');
        btnBuscar.disabled = true;
    }

    function renderResultadosUsuario(lista) {
        if (!lista.length) {
            usuarioResultados.innerHTML = '<div class="list-group-item text-muted small py-2">Sin resultados.</div>';
            return;
        }
        usuarioResultados.innerHTML = lista.map(u => `
            <button type="button"
                    class="list-group-item list-group-item-action small py-2 usr-item"
                    data-uid="${u.id}" data-nombre="${u.nombre}">
                <i class="bi bi-person me-1 text-muted"></i>
                <strong>${u.nombre}</strong>
                ${u.document_id ? `<span class="text-muted"> — ${u.document_id}</span>` : ''}
            </button>`).join('');

        usuarioResultados.querySelectorAll('.usr-item').forEach(btn => {
            btn.addEventListener('click', async () => {
                hidUserId.value    = btn.dataset.uid;
                inputUsuario.value = btn.dataset.nombre;
                usuarioResultados.innerHTML = '';
                btnLimpiarUsuario.classList.remove('d-none');

                resetSelect(selOferta, '— Primero elige un período —');
                btnBuscar.disabled = true;

                resetSelect(selPeriodo, '— Cargando... —');
                const periodos = await fetchData(`${BASE}/managerial/linea-tiempo/periodos?user_id=${hidUserId.value}`);
                populateSelect(selPeriodo, periodos, 'nombre', 'id', 0);
            });
        });
    }

    inputUsuario?.addEventListener('input', () => {
        clearTimeout(usuarioTimer);
        resetDesdeUsuario();
        btnLimpiarUsuario.classList.add('d-none');

        const term = inputUsuario.value.trim();
        if (term.length < 2) {
            usuarioResultados.innerHTML = '';
            return;
        }

        usuarioTimer = setTimeout(async () => {
            const resultados = await fetchData(`${BASE}/managerial/linea-tiempo/usuarios?search=${encodeURIComponent(term)}`);
            renderResultadosUsuario(resultados);
        }, 250);
    });

    document.addEventListener('click', (e) => {
        if (!inputUsuario?.contains(e.target) && !usuarioResultados?.contains(e.target)) {
            if (usuarioResultados) usuarioResultados.innerHTML = '';
        }
    });

    btnLimpiarUsuario?.addEventListener('click', () => {
        inputUsuario.value = '';
        usuarioResultados.innerHTML = '';
        btnLimpiarUsuario.classList.add('d-none');
        resetDesdeUsuario();
        inputUsuario.focus();
    });

    // =========================================================================
    // PASO 2: CASCADA PERÍODO → DIPLOMADOS DE ESE USUARIO
    // =========================================================================

    selPeriodo.addEventListener('change', async () => {
        resetSelect(selOferta, '— Cargando... —');
        btnBuscar.disabled = true;

        const pId = selPeriodo.value;
        const uId = hidUserId.value;
        if (!pId || !uId || uId === '0') {
            resetSelect(selOferta, '— Primero elige un período —');
            return;
        }

        const ofertas = await fetchData(`${BASE}/managerial/linea-tiempo/ofertas?user_id=${uId}&periodo_id=${pId}`);
        populateSelect(selOferta, ofertas, 'name', 'enrollment_id', 0);
    });

    // =========================================================================
    // PASO 3: SELECCIÓN DE DIPLOMADO → HABILITA BÚSQUEDA
    // =========================================================================

    selOferta.addEventListener('change', () => {
        btnBuscar.disabled = !selOferta.value;
    });

    // =========================================================================
    // BUSCAR
    // =========================================================================

    btnBuscar.addEventListener('click', () => {
        const enrollmentId = selOferta.value;
        if (!enrollmentId) return;

        const params = new URLSearchParams({
            user_id:       hidUserId.value    || 0,
            periodo_id:    selPeriodo.value    || 0,
            enrollment_id: enrollmentId,
            user_search:   inputUsuario?.value || '',
        });

        window.location.href = `${BASE}/managerial/linea-tiempo?${params.toString()}`;
    });

    // =========================================================================
    // LIMPIAR
    // =========================================================================

    btnLimpiar?.addEventListener('click', () => {
        window.location.href = `${BASE}/managerial/linea-tiempo`;
    });

    // =========================================================================
    // INICIALIZACIÓN (repoblar cascada si venimos de una búsqueda ya hecha)
    // =========================================================================

    async function init() {
        const uId = window.USER_ID;
        const pId = window.PERIODO_ID;
        const eId = window.ENROLLMENT_ID;

        if (!uId) return;

        hidUserId.value = uId;
        if (inputUsuario) inputUsuario.value = window.USER_SEARCH || '';
        if (btnLimpiarUsuario) btnLimpiarUsuario.classList.remove('d-none');

        const periodos = await fetchData(`${BASE}/managerial/linea-tiempo/periodos?user_id=${uId}`);
        populateSelect(selPeriodo, periodos, 'nombre', 'id', pId);

        if (pId) {
            const ofertas = await fetchData(`${BASE}/managerial/linea-tiempo/ofertas?user_id=${uId}&periodo_id=${pId}`);
            populateSelect(selOferta, ofertas, 'name', 'enrollment_id', eId);
            if (eId) btnBuscar.disabled = false;
        }
    }

    init();
});