/**
 * MÓDULO: GESTIÓN DE RECURSOS / CONTRATOS
 * ARCHIVO: public/assets/js/resources_contratos.js
 * PROPÓSITO: Interactividad del generador de contratos y del historial.
 * VERSIÓN: 1.0.0
 */

$(document).ready(function () {
    const basePath     = '/diplomatic/public/resources/contratos';
    const basePersonal = '/diplomatic/public/resources/contratos';
    const MySwal       = (typeof Swal !== 'undefined') ? Swal : { fire: (obj) => alert(obj.text) };

    // Estado local
    let personalSeleccionado = null;
    let plantillaSeleccionada = null;
    let debounceTimer = null;

    // === 0. ALERTAS POR URL ===
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('created')) {
        MySwal.fire({ icon: 'success', title: '¡Contrato generado!', text: 'El contrato fue guardado correctamente.', timer: 2000, showConfirmButton: false });
    }
    if (urlParams.get('updated')) {
        MySwal.fire({ icon: 'success', title: '¡Estado actualizado!', timer: 1500, showConfirmButton: false });
    }
    if (urlParams.get('error') === 'invalid') {
        MySwal.fire({ icon: 'error', title: 'Error', text: 'Personal o plantilla no válidos.', confirmButtonColor: '#198754' });
    }

    // =====================================================
    // GENERADOR DE CONTRATOS (create.php)
    // =====================================================

    // === 1. BUSCADOR DE PERSONAL ===
    const inputBuscar = document.getElementById('buscar-personal');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const term = this.value.trim();
            if (term.length < 2) {
                ocultarDropdown();
                return;
            }
            debounceTimer = setTimeout(() => buscarPersonal(term), 300);
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#buscar-personal') && !e.target.closest('#personal-dropdown')) {
                ocultarDropdown();
            }
        });
    }

    function buscarPersonal(term) {
        fetch(`${basePath}/buscarPersonal?term=${encodeURIComponent(term)}`)
            .then(res => res.json())
            .then(data => {
                const dropdown = document.getElementById('personal-dropdown');
                if (!dropdown) return;

                if (!data.ok || !data.data.length) {
                    dropdown.innerHTML = '<div class="dropdown-item text-muted small">Sin resultados</div>';
                    dropdown.style.display = 'block';
                    return;
                }

                dropdown.innerHTML = data.data.map(p => `
                    <button type="button" class="dropdown-item py-2 item-personal"
                            data-id="${p.id}"
                            data-nombre="${p.first_name} ${p.last_name}"
                            data-cedula="${p.document_id}"
                            data-tipo="${p.tipo_nombre}"
                            data-siglas="${p.siglas}"
                            data-expediente="${p.expediente ?? ''}">
                        <div class="fw-bold small">${p.first_name} ${p.last_name}</div>
                        <div class="text-muted" style="font-size:0.75rem;">${p.document_id} · ${p.tipo_nombre}</div>
                    </button>
                `).join('');
                dropdown.style.display = 'block';
            });
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('.item-personal')) {
            const btn = e.target.closest('.item-personal');
            seleccionarPersonal({
                id:         btn.dataset.id,
                nombre:     btn.dataset.nombre,
                cedula:     btn.dataset.cedula,
                tipo:       btn.dataset.tipo,
                siglas:     btn.dataset.siglas,
                expediente: btn.dataset.expediente
            });
        }
    });

    function seleccionarPersonal(p) {
        personalSeleccionado = p;
        document.getElementById('personal_id').value     = p.id;
        document.getElementById('buscar-personal').value = p.nombre;
        document.getElementById('ficha-nombre').innerText     = p.nombre + ' · CI: ' + p.cedula;
        document.getElementById('ficha-tipo').innerText       = p.tipo;
        document.getElementById('ficha-expediente').innerText = p.expediente;
        document.getElementById('personal-ficha').style.display = 'block';
        ocultarDropdown();
        actualizarPreview();
        checkAndShowPreview();
    }

    const btnLimpiar = document.getElementById('btn-limpiar-personal');
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function () {
            personalSeleccionado = null;
            document.getElementById('personal_id').value         = '';
            document.getElementById('buscar-personal').value     = '';
            document.getElementById('personal-ficha').style.display = 'none';
            ocultarPreview();
        });
    }

    function ocultarDropdown() {
        const d = document.getElementById('personal-dropdown');
        if (d) d.style.display = 'none';
    }

    // === 2. SELECTOR DE PLANTILLA ===
    const selectPlantilla = document.getElementById('select-plantilla');
    if (selectPlantilla) {
        selectPlantilla.addEventListener('change', function () {
            const id = this.value;
            if (!id) return;

            fetch(`${basePath}/getPlantilla?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.ok) return;
                    plantillaSeleccionada = data.plantilla;
                    renderCampos(data.plantilla.campos || []);
                    actualizarPreview();
                    checkAndShowPreview();
                });
        });
    }

    // === 3. RENDERIZAR CAMPOS PERSONALIZADOS ===
    function renderCampos(campos) {
        const container = document.getElementById('campos-dinamicos');
        const seccion   = document.getElementById('seccion-campos');
        if (!container || !seccion) return;

        if (!campos.length) {
            seccion.style.display = 'none';
            container.innerHTML   = '';
            return;
        }

        container.innerHTML = campos.map(c => {
            const tipo = c.tipo === 'fecha' ? 'date' : (c.tipo === 'numero' || c.tipo === 'moneda' ? 'number' : 'text');
            return `
                <div class="col-md-4">
                    <label class="form-label small fw-bold">${c.etiqueta.toUpperCase()}</label>
                    <input type="${tipo}"
                           name="field_valor[]"
                           class="form-control campo-personalizado"
                           placeholder="${c.etiqueta}"
                           data-campo="${c.nombre_campo}">
                    <input type="hidden" name="field_id[]"     value="${c.id}">
                    <input type="hidden" name="field_nombre[]" value="${c.nombre_campo}">
                </div>
            `;
        }).join('');

        seccion.style.display = 'block';

        // Actualizar preview al cambiar campos
        container.querySelectorAll('.campo-personalizado').forEach(inp => {
            inp.addEventListener('input', actualizarPreview);
        });
    }



    function checkAndShowPreview() {
        const pid = document.getElementById('personal_id').value;
        const tid = document.getElementById('select-plantilla').value;
        if (pid && tid) {
            document.getElementById('seccion-preview').style.display = 'block';
        }
    }

    // === 4. VISTA PREVIA EN TIEMPO REAL ===
    function actualizarPreview() {
        if (!personalSeleccionado || !plantillaSeleccionada) return;

        const seccion  = document.getElementById('seccion-preview');
        const preview  = document.getElementById('preview-contenido');
        if (!seccion || !preview) return;

        seccion.style.display = 'block';

        // Obtener datos del personal vía AJAX
        fetch(`${basePath}/getPersonal?id=${personalSeleccionado.id}`)
            .then(res => res.json())
            .then(data => {
                if (!data.ok) return;
                const p = data.persona;

                const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
                const hoy   = new Date();

                let contenido = plantillaSeleccionada.contenido || '';

                // Sustituir variables del sistema
                const vars = {
                    '{nombre_completo}':   (p.first_name + ' ' + p.last_name).trim(),
                    '{primer_nombre}':     p.first_name ?? '',
                    '{apellido}':          p.last_name ?? '',
                    '{cedula}':            p.document_id ?? '',
                    '{fecha_nacimiento}':  p.fecha_nacimiento ? formatFecha(p.fecha_nacimiento) : '',
                    '{direccion}':         p.direccion ?? '',
                    '{estado_civil}':      p.estado_civil ?? '',
                    '{email}':             p.email ?? '',
                    '{telefono_local}':    p.telefono_local ?? '',
                    '{telefono_celular}':  p.telefono_celular ?? '',
                    '{grado_instruccion}': p.grado_instruccion ?? '',
                    '{tipo_personal}':     p.tipo_personal_nombre ?? '',
                    '{fecha_inicio}':      p.fecha_inicio ? formatFecha(p.fecha_inicio) : '',
                    '{fecha_fin}':         p.fecha_fin    ? formatFecha(p.fecha_fin)    : '',
                    '{expediente}':        p.expediente ?? '',
                    '{fecha_contrato}':    `${hoy.getDate().toString().padStart(2,'0')}/${(hoy.getMonth()+1).toString().padStart(2,'0')}/${hoy.getFullYear()}`,
                    '{año_contrato}':      hoy.getFullYear().toString(),
                    '{mes_contrato}':      meses[hoy.getMonth()],
                    '{numero_contrato}':   '[ N° SE GENERARÁ AL GUARDAR ]',
                };

                for (const [key, val] of Object.entries(vars)) {
                    contenido = contenido.split(key).join(`<strong style="color:#198754;">${val}</strong>`);
                }

                // Sustituir campos personalizados
                document.querySelectorAll('.campo-personalizado').forEach(inp => {
                    const campo = inp.dataset.campo;
                    const valor = inp.value || `<em style="color:#aaa;">[${campo}]</em>`;
                    contenido = contenido.split('{' + campo + '}').join(`<strong style="color:#0d6efd;">${valor}</strong>`);
                });

                preview.innerHTML = contenido;
            });
    }

    const btnActualizar = document.getElementById('btn-actualizar-preview');
    if (btnActualizar) {
        btnActualizar.addEventListener('click', actualizarPreview);
    }

    function ocultarPreview() {
        const s = document.getElementById('seccion-preview');
        const c = document.getElementById('seccion-campos');
        if (s) s.style.display = 'none';
        if (c) c.style.display = 'none';
        plantillaSeleccionada = null;
        if (selectPlantilla) selectPlantilla.value = '';
    }

    function formatFecha(str) {
        if (!str) return '';
        const d = new Date(str);
        return `${d.getDate().toString().padStart(2,'0')}/${(d.getMonth()+1).toString().padStart(2,'0')}/${d.getFullYear()}`;
    }

    // =====================================================
    // HISTORIAL DE CONTRATOS (index.php)
    // =====================================================

    // === 5. CAMBIO DE ESTADO ===
    document.querySelectorAll('.btn-estado').forEach(btn => {
        btn.addEventListener('click', function () {
            const id     = this.dataset.id;
            const estado = this.dataset.estado;
            document.getElementById('estado_id').value    = id;
            document.getElementById('estado_select').value = estado;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEstado')).show();
        });
    });

   // === VER CONTRATO EN MODAL ===
document.addEventListener('click', function (e) {
    if (e.target.closest('.btn-ver-contrato')) {
        const btn     = e.target.closest('.btn-ver-contrato');
        const id      = btn.dataset.id;
        const numero  = btn.dataset.numero;
        const persona = btn.dataset.persona;

        document.getElementById('modalContratoNumero').innerText = numero;
        document.getElementById('modalContratoPersona').innerText = persona;
        document.getElementById('btn-modal-pdf').href = `/diplomatic/public/resources/contratos/pdf?id=${id}`;
        document.getElementById('modal-contrato-contenido').innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> Cargando...</div>';

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVerContrato')).show();

        fetch(`${basePath}/getDetails?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.ok && data.contrato) {
                    document.getElementById('modal-contrato-contenido').innerHTML = data.contrato.contenido_final || '<p class="text-muted">Sin contenido.</p>';
                }
            });
    }
}); 
});