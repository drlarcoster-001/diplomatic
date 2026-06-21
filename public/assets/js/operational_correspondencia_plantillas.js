/**
 * MÓDULO: CORRESPONDENCIA / PLANTILLAS
 * ARCHIVO: public/assets/js/operational_correspondencia_plantillas.js
 * PROPÓSITO: Mismo patrón que resources_contratos_plantillas.js (editor Quill,
 *            inserción de variables, campos personalizados, guardado), más
 *            la novedad: al cambiar la Tabla Objetivo, el sidebar de
 *            "Variables del Sistema" se refresca vía AJAX sin recargar la
 *            página (para no perder lo ya escrito en el editor).
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

$(document).ready(function () {
    const basePath = (window.APP_BASE_PATH || '') + '/operational/correspondencia/plantillas';
    const MySwal   = (typeof Swal !== 'undefined') ? Swal : { fire: (obj) => alert(obj.text) };
    let campoIndex = document.querySelectorAll('.campo-row').length;
    let quill      = null;

    // === 0. ALERTAS POR URL ===
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('created')) {
        MySwal.fire({ icon: 'success', title: '¡Plantilla creada!', text: 'Puede seguir editando el contenido.', timer: 2000, showConfirmButton: false });
    }
    if (urlParams.get('updated')) {
        MySwal.fire({ icon: 'success', title: '¡Cambios guardados!', timer: 1500, showConfirmButton: false });
    }
    if (urlParams.get('deleted')) {
        MySwal.fire({ icon: 'success', title: 'Plantilla eliminada.', timer: 1500, showConfirmButton: false });
    }
    if (urlParams.get('error') === 'in_use') {
        MySwal.fire({ icon: 'error', title: 'No se puede eliminar', text: 'Esta plantilla tiene documentos generados vinculados.', confirmButtonColor: '#0d6efd' });
    }
    if (urlParams.get('error') === 'db') {
        MySwal.fire({ icon: 'error', title: 'Error al guardar', text: 'Ocurrió un error al procesar la solicitud.', confirmButtonColor: '#e74a3b' });
    }

    // === 1. INICIALIZAR QUILL ===
    const editorEl = document.getElementById('quill-editor');
    if (editorEl && typeof Quill !== 'undefined') {
        quill = new Quill('#quill-editor', {
            theme: 'snow',
            placeholder: 'Escriba aquí el contenido del documento...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, false] }],
                    [{ 'font': [] }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'indent': '-1' }, { 'indent': '+1' }],
                    ['blockquote'],
                    ['clean']
                ]
            }
        });

        if (typeof window.contenidoInicial !== 'undefined' && window.contenidoInicial) {
            quill.clipboard.dangerouslyPasteHTML(window.contenidoInicial);
        }
    }

    // === 2. CARGAR VARIABLES DEL SISTEMA SEGÚN TABLA OBJETIVO (AJAX) ===
    const tablaSelect = document.getElementById('tablaObjetivo');
    const camposContainer = document.getElementById('camposSistemaContainer');

    async function cargarCamposSistema(tabla) {
        if (!tabla) {
            camposContainer.innerHTML = '<p class="text-muted small mb-0">Elige una tabla objetivo arriba para ver sus variables.</p>';
            return;
        }
        camposContainer.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-secondary"></div></div>';

        try {
            const resp = await fetch(`${basePath}/getCamposSistema?tabla=${encodeURIComponent(tabla)}`).then(r => r.json());
            if (!resp.success || !Object.keys(resp.campos).length) {
                camposContainer.innerHTML = '<p class="text-muted small mb-0">Sin variables disponibles para esta tabla.</p>';
                return;
            }
            camposContainer.innerHTML = Object.entries(resp.campos).map(([variable, descripcion]) => `
                <button type="button" class="btn btn-sm btn-outline-primary text-start rounded-pill px-3 btn-insertar"
                        data-variable="${variable}" title="${descripcion}">
                    <code style="font-size:0.75rem;">${variable}</code>
                </button>
            `).join('');

            bindBotonesInsertar();
        } catch (e) {
            camposContainer.innerHTML = '<p class="text-danger small mb-0">Error al cargar variables.</p>';
        }
    }

    tablaSelect?.addEventListener('change', () => cargarCamposSistema(tablaSelect.value));

    // Precargar al entrar en modo edición (tabla ya seleccionada de antes)
    if (tablaSelect && tablaSelect.value) {
        cargarCamposSistema(tablaSelect.value);
    }

    function bindBotonesInsertar() {
        document.querySelectorAll('.btn-insertar').forEach(btn => {
            btn.addEventListener('click', function () {
                insertarEnQuill(this.dataset.variable);
            });
        });
    }

    // === 3. INSERTAR CAMPO PERSONALIZADO EN QUILL ===
    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-insertar-custom')) {
            const btn      = e.target.closest('.btn-insertar-custom');
            const etiqueta = btn.dataset.etiqueta;
            if (etiqueta) insertarEnQuill('{' + etiqueta + '}');
        }
    });

    // === 4. AGREGAR CAMPO PERSONALIZADO ===
    document.getElementById('btnAgregarCampo')?.addEventListener('click', () => agregarCampo());

    // === 5. ELIMINAR CAMPO PERSONALIZADO ===
    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-campo')) {
            const row = e.target.closest('.campo-row');
            if (row) row.remove();
        }
    });

    // === 6. ACTUALIZAR BOTÓN INSERTAR AL ESCRIBIR ETIQUETA ===
    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('campo-etiqueta')) {
            const row         = e.target.closest('.campo-row');
            const btnInsertar = row ? row.querySelector('.btn-insertar-custom') : null;
            if (btnInsertar) {
                const nombreCampo = e.target.value.toLowerCase().replace(/[\s\-]+/g, '_').replace(/[^a-z0-9_]/g, '');
                btnInsertar.dataset.etiqueta = nombreCampo;
            }
        }
    });

    // === 7. PRECARGAR CAMPOS PERSONALIZADOS (MODO EDICIÓN) ===
    if (Array.isArray(window.camposPersonalizadosIniciales) && window.camposPersonalizadosIniciales.length) {
        window.camposPersonalizadosIniciales.forEach(c => agregarCampo(c.etiqueta, c.tipo));
    }

    // === 8. SUBMIT DEL FORMULARIO ===
    window.submitForm = function () {
        const form = document.getElementById('formPlantilla');
        if (!form) return;

        const nombre = form.querySelector('[name="nombre"]');
        const tipo   = form.querySelector('[name="tipo_documento"]');
        const tabla  = form.querySelector('[name="tabla_objetivo"]');

        if (!nombre || !nombre.value.trim()) {
            MySwal.fire({ icon: 'warning', title: 'Falta el nombre', text: 'Ingrese un nombre para la plantilla.', confirmButtonColor: '#0d6efd' });
            return;
        }
        if (!tipo || !tipo.value) {
            MySwal.fire({ icon: 'warning', title: 'Falta el tipo', text: 'Seleccione el tipo de documento.', confirmButtonColor: '#0d6efd' });
            return;
        }
        if (!tabla || !tabla.value) {
            MySwal.fire({ icon: 'warning', title: 'Falta la tabla objetivo', text: 'Seleccione la tabla objetivo.', confirmButtonColor: '#0d6efd' });
            return;
        }

        if (quill) {
            document.getElementById('contenido-hidden').value = quill.root.innerHTML;
        }

        form.submit();
    };

    // === 9. VER PLANTILLA (modal en index) ===
    document.querySelectorAll('.btn-ver').forEach(btn => {
        btn.addEventListener('click', function () {
            const id     = this.dataset.id;
            const nombre = this.dataset.nombre;
            const tipo   = this.dataset.tipo;

            document.getElementById('modalVerNombre').innerText = nombre;
            document.getElementById('modalVerTipo').innerText   = tipo;
            document.getElementById('btn-modal-editar').href    = `${basePath}/edit?id=${id}`;
            document.getElementById('modal-contenido-plantilla').innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> Cargando...</div>';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVerPlantilla')).show();

            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok && data.plantilla) {
                        document.getElementById('modal-contenido-plantilla').innerHTML = data.plantilla.contenido || '<p class="text-muted">Sin contenido.</p>';
                    }
                });
        });
    });

    // === 10. ELIMINAR PLANTILLA (index) ===
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const id    = this.dataset.id;
            const name  = this.dataset.name;
            const count = parseInt(this.dataset.count);

            if (count > 0) {
                MySwal.fire({ icon: 'warning', title: 'No se puede eliminar', text: `"${name}" tiene ${count} documento(s) generado(s).`, confirmButtonColor: '#0d6efd' });
                return;
            }

            MySwal.fire({
                title: '¿Eliminar plantilla?', html: `Se eliminará permanentemente: <b>${name}</b>`, icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#e74a3b', cancelButtonColor: '#858796',
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

    // === FUNCIONES AUXILIARES ===
    function insertarEnQuill(texto) {
        if (!quill) return;
        const range = quill.getSelection(true);
        quill.insertText(range ? range.index : quill.getLength(), texto, 'user');
        quill.setSelection((range ? range.index : quill.getLength()) + texto.length);
    }

    function agregarCampo(etiquetaInicial, tipoInicial) {
        const container = document.getElementById('campos-container');
        if (!container) return;

        const div = document.createElement('div');
        div.className = 'campo-row mb-2';
        div.dataset.index = campoIndex;

        const etiqueta = etiquetaInicial || '';
        const slug = etiqueta ? etiqueta.toLowerCase().replace(/[\s\-]+/g, '_').replace(/[^a-z0-9_]/g, '') : '';

        div.innerHTML = `
            <div class="input-group input-group-sm mb-1">
                <input type="text" name="campo_etiqueta[]" class="form-control campo-etiqueta"
                       placeholder="Ej: Motivo" value="${etiqueta}">
                <button type="button" class="btn btn-outline-primary btn-sm btn-insertar-custom"
                        data-etiqueta="${slug}" title="Insertar en editor">
                    <i class="bi bi-arrow-right"></i>
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-campo">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <select name="campo_tipo[]" class="form-select form-select-sm">
                <option value="texto" ${tipoInicial==='texto'?'selected':''}>Texto</option>
                <option value="numero" ${tipoInicial==='numero'?'selected':''}>Número</option>
                <option value="fecha" ${tipoInicial==='fecha'?'selected':''}>Fecha</option>
                <option value="moneda" ${tipoInicial==='moneda'?'selected':''}>Moneda</option>
            </select>
        `;
        container.appendChild(div);
        if (!etiquetaInicial) div.querySelector('.campo-etiqueta').focus();
        campoIndex++;
    }
});