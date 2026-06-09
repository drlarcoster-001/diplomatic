/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * ARCHIVO: public/assets/js/resources_contratos_plantillas.js
 * PROPÓSITO: Gestionar la interactividad del módulo de plantillas de contratos con editor WYSIWYG Quill.
 * VERSIÓN: 1.1.0
 */

$(document).ready(function () {
    const basePath = '/diplomatic/public/resources/contratos/plantillas';
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
        MySwal.fire({ icon: 'error', title: 'No se puede eliminar', text: 'Esta plantilla tiene contratos generados vinculados.', confirmButtonColor: '#0d6efd' });
    }
    if (urlParams.get('error') === 'db') {
        MySwal.fire({ icon: 'error', title: 'Error al guardar', text: 'Ocurrió un error al procesar la solicitud.', confirmButtonColor: '#e74a3b' });
    }

    // === 1. INICIALIZAR QUILL ===
    const editorEl = document.getElementById('quill-editor');
    if (editorEl && typeof Quill !== 'undefined') {
        quill = new Quill('#quill-editor', {
            theme: 'snow',
            placeholder: 'Escriba aquí el contenido del contrato...',
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

        // Cargar contenido existente (modo edición)
        if (typeof window.contenidoInicial !== 'undefined' && window.contenidoInicial) {
            quill.clipboard.dangerouslyPasteHTML(window.contenidoInicial);
        }
    }

    // === 2. INSERTAR VARIABLE DEL SISTEMA EN QUILL ===
    document.querySelectorAll('.btn-insertar').forEach(btn => {
        btn.addEventListener('click', function () {
            insertarEnQuill(this.dataset.variable);
        });
    });

    // === 3. INSERTAR CAMPO PERSONALIZADO EN QUILL ===
    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-insertar-custom')) {
            const btn      = e.target.closest('.btn-insertar-custom');
            const etiqueta = btn.dataset.etiqueta;
            if (etiqueta) insertarEnQuill('{' + etiqueta + '}');
        }
    });

    // === 4. AGREGAR CAMPO PERSONALIZADO ===
    const btnAgregar = document.getElementById('btnAgregarCampo');
    if (btnAgregar) {
        btnAgregar.addEventListener('click', function () {
            agregarCampo();
        });
    }

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
                const nombreCampo = e.target.value
                    .toLowerCase()
                    .replace(/[\s\-]+/g, '_')
                    .replace(/[^a-z0-9_]/g, '');
                btnInsertar.dataset.etiqueta = nombreCampo;
            }
        }
    });

    // === 7. SUBMIT DEL FORMULARIO ===
    window.submitForm = function () {
        const form = document.getElementById('formPlantilla');
        if (!form) return;

        const nombre = form.querySelector('[name="nombre"]');
        const tipo   = form.querySelector('[name="tipo_contrato_id"]');

        if (!nombre || !nombre.value.trim()) {
            MySwal.fire({ icon: 'warning', title: 'Falta el nombre', text: 'Ingrese un nombre para la plantilla.', confirmButtonColor: '#0d6efd' });
            return;
        }
        if (!tipo || !tipo.value) {
            MySwal.fire({ icon: 'warning', title: 'Falta el tipo', text: 'Seleccione el tipo de contrato.', confirmButtonColor: '#0d6efd' });
            return;
        }

        // Pasar el HTML del editor al input hidden
        if (quill) {
            document.getElementById('contenido-hidden').value = quill.root.innerHTML;
        }

        form.submit();
    };

    // === 8. VER PLANTILLA ===
document.querySelectorAll('.btn-ver').forEach(btn => {
    btn.addEventListener('click', function () {
        const id     = this.dataset.id;
        const nombre = this.dataset.nombre;
        const tipo   = this.dataset.tipo;

        document.getElementById('modalVerNombre').innerText = nombre;
        document.getElementById('modalVerTipo').innerText   = tipo;
        document.getElementById('btn-modal-editar').href    = `/diplomatic/public/resources/contratos/plantillas/edit?id=${id}`;
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

    // === 8. ELIMINAR PLANTILLA DESDE EL INDEX ===
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const id    = this.dataset.id;
            const name  = this.dataset.name;
            const count = parseInt(this.dataset.count);

            if (count > 0) {
                MySwal.fire({
                    icon: 'warning',
                    title: 'No se puede eliminar',
                    text: `"${name}" tiene ${count} contrato(s) generado(s).`,
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }

            MySwal.fire({
                title: '¿Eliminar plantilla?',
                html: `Se eliminará permanentemente: <b>${name}</b>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor:  '#858796',
                confirmButtonText:  'Sí, eliminar',
                cancelButtonText:   'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = `${basePath}/delete`;
                    const i  = document.createElement('input');
                    i.type = 'hidden'; i.name = 'id'; i.value = id;
                    f.appendChild(i);
                    document.body.appendChild(f);
                    f.submit();
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

    function agregarCampo() {
        const container = document.getElementById('campos-container');
        if (!container) return;

        const div = document.createElement('div');
        div.className = 'campo-row mb-2';
        div.dataset.index = campoIndex;
        div.innerHTML = `
            <div class="input-group input-group-sm mb-1">
                <input type="text" name="campo_etiqueta[]"
                       class="form-control campo-etiqueta"
                       placeholder="Ej: Monto de pago">
                <button type="button" class="btn btn-outline-primary btn-sm btn-insertar-custom"
                        data-etiqueta="" title="Insertar en editor">
                    <i class="bi bi-arrow-right"></i>
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-campo">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <select name="campo_tipo[]" class="form-select form-select-sm">
                <option value="texto">Texto</option>
                <option value="numero">Número</option>
                <option value="fecha">Fecha</option>
                <option value="moneda">Moneda</option>
            </select>
        `;
        container.appendChild(div);
        div.querySelector('.campo-etiqueta').focus();
        campoIndex++;
    }
});