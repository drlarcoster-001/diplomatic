/**
 * MÓDULO: CORRESPONDENCIA / GENERADOR DE DOCUMENTOS
 * ARCHIVO: public/assets/js/operational_correspondencia_documentos.js
 * PROPÓSITO: Al elegir la plantilla (paso 1), carga vía AJAX los registros
 *            de su tabla objetivo (paso 2, con checkboxes de selección
 *            múltiple + buscador) y sus campos personalizados (se llenan
 *            una sola vez, aplican a todo el lote). Al generar, arma los
 *            inputs ocultos del formulario y envía por POST normal.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

$(document).ready(function () {
    const basePath = (window.APP_BASE_PATH || '') + '/operational/correspondencia/documentos';
    const MySwal   = (typeof Swal !== 'undefined') ? Swal : { fire: (obj) => alert(obj.text) };

    let tablaActual = null;
    let registrosCache = [];

    const plantillaSelect   = document.getElementById('plantillaSelect');
    const pasoDosContainer  = document.getElementById('pasoDosContainer');
    const registrosContainer = document.getElementById('registrosContainer');
    const camposContainer   = document.getElementById('camposPersonalizadosContainer');
    const contador          = document.getElementById('contadorSeleccionados');

    // === 0. ALERTAS POR URL ===
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error') === 'incompleto') {
        MySwal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Elige una plantilla y al menos un registro.', confirmButtonColor: '#0d6efd' });
    }
    if (urlParams.get('error') === 'db') {
        MySwal.fire({ icon: 'error', title: 'Error al generar', text: 'Ocurrió un error al producir los documentos.', confirmButtonColor: '#e74a3b' });
    }
    if (urlParams.get('deleted')) {
        MySwal.fire({ icon: 'success', title: 'Documento eliminado.', timer: 1500, showConfirmButton: false });
    }

    // === ELIMINAR DOCUMENTO (index del historial) ===
    document.querySelectorAll('.btn-delete-doc').forEach(btn => {
        btn.addEventListener('click', function () {
            const id     = this.dataset.id;
            const codigo = this.dataset.codigo;

            MySwal.fire({
                title: '¿Eliminar documento?',
                html: `Se eliminará permanentemente el documento <b>${codigo}</b> y su PDF. Su código QR dejará de ser válido.`,
                icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#e74a3b', cancelButtonColor: '#858796',
                confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    const f = document.createElement('form');
                    f.method = 'POST'; f.action = `${basePath}/delete`;
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'id'; i.value = id;
                    f.appendChild(i); document.body.appendChild(f); f.submit();
                }
            });
        });
    });

    // === 1. AL ELEGIR PLANTILLA: cargar info + campos personalizados + registros ===
    plantillaSelect?.addEventListener('change', async function () {
        const id = this.value;
        if (!id) { pasoDosContainer.style.display = 'none'; return; }

        registrosContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-secondary"></div></div>';
        camposContainer.innerHTML = '';

        try {
            const info = await fetch(`${basePath}/getPlantillaInfo?id=${id}`).then(r => r.json());
            if (!info.success) return;

            tablaActual = info.tabla_objetivo;
            pasoDosContainer.style.display = 'block';

            // Pintar campos personalizados
            if (info.campos_personalizados && info.campos_personalizados.length) {
                camposContainer.innerHTML = info.campos_personalizados.map(c => `
                    <div class="mb-3">
                        <label class="form-label small fw-bold">${c.etiqueta.toUpperCase()}</label>
                        <input type="${c.tipo === 'numero' ? 'number' : (c.tipo === 'fecha' ? 'date' : 'text')}"
                               class="form-control campo-personalizado-input"
                               data-slug="${c.slug}" placeholder="${c.etiqueta}">
                    </div>
                `).join('');
            } else {
                camposContainer.innerHTML = '<p class="text-muted small mb-0">Esta plantilla no tiene campos personalizados.</p>';
            }

            await cargarRegistros('');
        } catch (e) {
            registrosContainer.innerHTML = '<p class="text-danger small mb-0">Error al cargar la plantilla.</p>';
        }
    });

    // === 2. CARGAR REGISTROS (AJAX, con búsqueda) ===
    async function cargarRegistros(search) {
        if (!tablaActual) return;
        registrosContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-secondary"></div></div>';

        try {
            const resp = await fetch(`${basePath}/getRegistros?tabla=${encodeURIComponent(tablaActual)}&search=${encodeURIComponent(search)}`).then(r => r.json());
            registrosCache = resp.registros || [];

            if (!registrosCache.length) {
                registrosContainer.innerHTML = '<p class="text-muted small mb-0">Sin registros encontrados.</p>';
                return;
            }

            registrosContainer.innerHTML = registrosCache.map(r => `
                <div class="form-check py-1 border-bottom">
                    <input class="form-check-input registro-checkbox" type="checkbox" value="${r.id}" id="reg-${r.id}">
                    <label class="form-check-label w-100" for="reg-${r.id}">
                        ${r.etiqueta} ${r.subtitulo ? `<span class="text-muted small">— ${r.subtitulo}</span>` : ''}
                    </label>
                </div>
            `).join('');

            bindCheckboxes();
        } catch (e) {
            registrosContainer.innerHTML = '<p class="text-danger small mb-0">Error al cargar registros.</p>';
        }
    }

    function bindCheckboxes() {
        document.querySelectorAll('.registro-checkbox').forEach(cb => {
            cb.addEventListener('change', actualizarContador);
        });
    }

    function actualizarContador() {
        const n = document.querySelectorAll('.registro-checkbox:checked').length;
        contador.innerText = n;
    }

    // === 3. BUSCADOR DE REGISTROS ===
    let debounceTimer = null;
    document.getElementById('buscarRegistro')?.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const valor = this.value;
        debounceTimer = setTimeout(() => cargarRegistros(valor), 350);
    });

    // === 4. MARCAR TODOS ===
    document.getElementById('btnSeleccionarTodos')?.addEventListener('click', function () {
        const checkboxes = document.querySelectorAll('.registro-checkbox');
        const todosMarcados = [...checkboxes].every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !todosMarcados);
        this.innerText = todosMarcados ? 'Marcar todos' : 'Desmarcar todos';
        actualizarContador();
    });

    // === 5. GENERAR ===
    document.getElementById('btnGenerar')?.addEventListener('click', function () {
        const seleccionados = [...document.querySelectorAll('.registro-checkbox:checked')].map(cb => cb.value);

        if (!plantillaSelect.value) {
            MySwal.fire({ icon: 'warning', title: 'Falta la plantilla', confirmButtonColor: '#0d6efd' });
            return;
        }
        if (!seleccionados.length) {
            MySwal.fire({ icon: 'warning', title: 'Sin registros', text: 'Selecciona al menos un registro.', confirmButtonColor: '#0d6efd' });
            return;
        }

        const form = document.getElementById('formGenerar');
        const idsContainer = document.getElementById('camposIdsContainer');
        idsContainer.innerHTML = '';

        seleccionados.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'registro_ids[]'; input.value = id;
            idsContainer.appendChild(input);
        });

        document.querySelectorAll('.campo-personalizado-input').forEach(inp => {
            const slugInput = document.createElement('input');
            slugInput.type = 'hidden'; slugInput.name = 'valor_slug[]'; slugInput.value = inp.dataset.slug;
            idsContainer.appendChild(slugInput);

            const valorInput = document.createElement('input');
            valorInput.type = 'hidden'; valorInput.name = 'valor_valor[]'; valorInput.value = inp.value;
            idsContainer.appendChild(valorInput);
        });

        MySwal.fire({
            title: `¿Generar ${seleccionados.length} documento(s)?`, icon: 'question',
            showCancelButton: true, confirmButtonColor: '#0d6efd', cancelButtonColor: '#858796',
            confirmButtonText: 'Sí, generar', cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) form.submit();
        });
    });
});