/**
 * MÓDULO: ACADÉMICO / ASIGNACIÓN DE MODALIDAD A PROFESORES
 * ARCHIVO: public/assets/js/academic_profesor_modalidad.js
 * PROPÓSITO: Buscadores inteligentes (autocompletar en vivo) para Profesor,
 *            Oferta y Grupo, con dependencias en cascada (profesor habilita
 *            oferta; oferta carga grupos y aplica restricción Online), modal
 *            compartido crear/editar, checkboxes de modalidad (múltiples al
 *            crear, exclusivo al editar), botones X para limpiar cada campo.
 * VERSIÓN: 2.2.0 - Fix: el campo Grupo se carga al elegir la oferta sin
 *           importar si Teórica se marcó antes o después; encabezado corregido.
 */

$(document).ready(function () {
    const basePath = (window.APP_BASE_PATH || '') + '/academic/profesor-modalidad';
    const MySwal   = (typeof Swal !== 'undefined') ? Swal : { fire: (obj) => alert(obj.text) };

    const modalEl   = document.getElementById('modalAsignacion');
    const modal     = bootstrap.Modal.getOrCreateInstance(modalEl);
    const form      = document.getElementById('formAsignacion');
    const modalTitulo = document.getElementById('modalTitulo');

    // Referencias a elementos del DOM
    const bloqueGrupo  = document.getElementById('bloqueGrupo');
    const wrapperGrupo = document.querySelector('.buscador-inteligente[data-target="offering_group_id"]');
    const inputGrupo   = wrapperGrupo?.querySelector('.buscador-input');
    const wrapperOferta = document.querySelector('.buscador-inteligente[data-target="offering_id"]');
    const inputOferta   = wrapperOferta?.querySelector('.buscador-input');

    // === ALERTAS POR URL ===
    const urlParams = new URLSearchParams(window.location.search);
    const parcial = urlParams.get('parcial');
    if (urlParams.get('created') && parcial) {
        const labels = { TEORICA: 'Teórica', PRACTICA: 'Práctica', VIRTUAL: 'Virtual' };
        const omitidasTexto = parcial.split(',').map(m => labels[m] || m).join(', ');
        MySwal.fire({ icon: 'info', title: 'Creación parcial', text: `Se crearon las disponibles. Se omitió: ${omitidasTexto} (esa oferta ya tenía profesor en esa modalidad).`, confirmButtonColor: '#0d6efd' });
    } else if (urlParams.get('created')) {
        MySwal.fire({ icon: 'success', title: 'Asignación creada.', timer: 1500, showConfirmButton: false });
    }
    if (urlParams.get('updated')) MySwal.fire({ icon: 'success', title: 'Cambios guardados.', timer: 1500, showConfirmButton: false });
    if (urlParams.get('deleted')) MySwal.fire({ icon: 'success', title: 'Asignación eliminada.', timer: 1500, showConfirmButton: false });

    // === CATÁLOGOS Y MAPAS ===
    const mapaProfesorOfertas = window.MAPA_PROFESOR_OFERTAS || {};
    const mapaOfertaGrupos    = window.MAPA_OFERTA_GRUPOS || {};
    const catalogoOfertasCompleto = window.CATALOGO_OFERTAS || [];

    const catalogos = {
        professor_id: window.CATALOGO_PROFESORES || [],
        offering_id:  [],
        offering_group_id: [],
    };

    let modoEdicion = false;

    // === FUNCIONES DE BÚSQUEDA Y SELECCIÓN ===

    function bloquearOferta() {
        catalogos.offering_id = [];
        if (inputOferta) {
            inputOferta.value = '';
            inputOferta.disabled = true;
            inputOferta.placeholder = 'Primero elige un profesor...';
        }
        const hiddenOferta = wrapperOferta?.querySelector('.buscador-hidden');
        if (hiddenOferta) hiddenOferta.value = '';
        limpiarGrupo();
        actualizarBotonLimpiar(wrapperOferta);
    }

    function habilitarOfertaPara(professorId) {
        const idsPermitidos = mapaProfesorOfertas[String(professorId)] || [];
        catalogos.offering_id = catalogoOfertasCompleto.filter(o => idsPermitidos.includes(o.id));

        if (inputOferta) {
            inputOferta.disabled = false;
            inputOferta.placeholder = catalogos.offering_id.length
                ? 'Escribe para buscar una oferta...'
                : 'Este profesor no tiene ofertas asignadas en Ofertas Académicas';
            inputOferta.value = '';
        }

        const hiddenOferta = wrapperOferta?.querySelector('.buscador-hidden');
        if (hiddenOferta) hiddenOferta.value = '';
        actualizarBotonLimpiar(wrapperOferta);
        limpiarGrupo();
    }

    function limpiarGrupo() {
        catalogos.offering_group_id = [];
        const hiddenGrupo = wrapperGrupo?.querySelector('.buscador-hidden');
        if (hiddenGrupo) hiddenGrupo.value = '';
        if (inputGrupo) {
            inputGrupo.value = '';
            inputGrupo.placeholder = 'Primero elige profesor y oferta...';
        }
        actualizarBotonLimpiar(wrapperGrupo);
    }

    function cargarGruposPara(offeringId) {
        catalogos.offering_group_id = mapaOfertaGrupos[String(offeringId)] || [];
        if (inputGrupo) {
            inputGrupo.value = '';
            inputGrupo.placeholder = catalogos.offering_group_id.length
                ? 'Escribe o haz clic para ver los grupos...'
                : 'Esta oferta no tiene grupos configurados';
        }
        const hiddenGrupo = wrapperGrupo?.querySelector('.buscador-hidden');
        if (hiddenGrupo) hiddenGrupo.value = '';
        actualizarBotonLimpiar(wrapperGrupo);
    }

    function actualizarVisibilidadGrupo() {
        const teoricaMarcada = document.getElementById('chkTeorica')?.checked;
        bloqueGrupo?.classList.toggle('d-none', !teoricaMarcada);
        // NOTA: ya no limpia el grupo al ocultar — los grupos quedan cargados
        // según la oferta elegida, sin importar el orden en que se marque Teórica.
    }

    function aplicarRestriccionOnline(offeringId) {
        const oferta = catalogoOfertasCompleto.find(o => String(o.id) === String(offeringId));
        const esOnline = oferta && (oferta.grupos || '').toUpperCase().includes('ONLINE');

        const chkTeorica  = document.getElementById('chkTeorica');
        const chkPractica = document.getElementById('chkPractica');
        const chkVirtual  = document.getElementById('chkVirtual');
        const avisoOnline = document.getElementById('avisoOnline');

        [chkTeorica, chkPractica].forEach(chk => {
            if (chk) {
                chk.disabled = esOnline;
                if (esOnline) chk.checked = false;
            }
        });

        if (esOnline) {
            if (chkVirtual) chkVirtual.checked = true;
            avisoOnline?.classList.remove('d-none');
        } else {
            avisoOnline?.classList.add('d-none');
        }
        actualizarVisibilidadGrupo();
    }

    // === BOTÓN LIMPIAR (X) ===

    function actualizarBotonLimpiar(wrapper) {
        if (!wrapper) return;
        const hidden = wrapper.querySelector('.buscador-hidden');
        const btnLimpiar = wrapper.querySelector('.btn-limpiar');
        if (!hidden || !btnLimpiar) return;

        const tieneValor = hidden.value && hidden.value !== '';
        btnLimpiar.classList.toggle('d-none', !tieneValor);
        wrapper.classList.toggle('seleccion-activa', tieneValor);
    }

    function limpiarConCascada(targetName) {
        limpiarBuscador(targetName);

        if (targetName === 'professor_id') {
            bloquearOferta();
            bloqueGrupo?.classList.add('d-none');
            limpiarGrupo();
            const chkTeorica = document.getElementById('chkTeorica');
            const chkPractica = document.getElementById('chkPractica');
            if (chkTeorica) chkTeorica.disabled = false;
            if (chkPractica) chkPractica.disabled = false;
            document.getElementById('avisoOnline')?.classList.add('d-none');
            limpiarModalidades();
        }
        else if (targetName === 'offering_id') {
            limpiarGrupo();
            const chkTeorica = document.getElementById('chkTeorica');
            const chkPractica = document.getElementById('chkPractica');
            if (chkTeorica) chkTeorica.disabled = false;
            if (chkPractica) chkPractica.disabled = false;
            document.getElementById('avisoOnline')?.classList.add('d-none');
        }

        actualizarVisibilidadGrupo();
    }

    function inicializarBotonesLimpiar() {
        document.querySelectorAll('.buscador-inteligente').forEach(wrapper => {
            const targetName = wrapper.dataset.target;
            const btnLimpiar = wrapper.querySelector('.btn-limpiar');
            const input = wrapper.querySelector('.buscador-input');

            if (!btnLimpiar) return;

            btnLimpiar.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                limpiarConCascada(targetName);
                input?.focus();
            });

            input?.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    limpiarConCascada(targetName);
                }
            });

            actualizarBotonLimpiar(wrapper);
        });
    }

    // === INICIALIZAR BUSCADORES INTELIGENTES ===

    document.querySelectorAll('.buscador-inteligente').forEach(wrapper => {
        const targetName = wrapper.dataset.target;
        const input       = wrapper.querySelector('.buscador-input');
        const hidden      = wrapper.querySelector('.buscador-hidden');
        const dropdown    = wrapper.querySelector('.buscador-dropdown');

        if (!input || !hidden || !dropdown) return;

        function renderOpciones(lista) {
            if (!lista.length) {
                dropdown.innerHTML = '<div class="buscador-item text-muted">Sin resultados.</div>';
                dropdown.style.display = 'block';
                return;
            }
            dropdown.innerHTML = lista.slice(0, 30).map(item => `
                <div class="buscador-item" data-id="${item.id}" data-label="${item.label.replace(/"/g, '&quot;')}">${item.label}</div>
            `).join('');
            dropdown.style.display = 'block';
        }

        input.addEventListener('focus', function () {
            if (this.disabled) return;
            const texto = this.value.trim().toLowerCase();
            const lista = catalogos[targetName];
            const filtrado = texto ? lista.filter(c => c.label.toLowerCase().includes(texto)) : lista;
            renderOpciones(filtrado);
        });

        input.addEventListener('input', function () {
            hidden.value = '';
            actualizarBotonLimpiar(wrapper);
            const texto = this.value.trim().toLowerCase();
            const lista = catalogos[targetName];
            const filtrado = texto ? lista.filter(c => c.label.toLowerCase().includes(texto)) : lista;
            renderOpciones(filtrado);
        });

        dropdown.addEventListener('click', function (e) {
            const item = e.target.closest('.buscador-item');
            if (!item || !item.dataset.id) return;

            hidden.value = item.dataset.id;
            input.value  = item.dataset.label;
            dropdown.style.display = 'none';
            actualizarBotonLimpiar(wrapper);

            if (targetName === 'professor_id') {
                habilitarOfertaPara(item.dataset.id);
            }
            if (targetName === 'offering_id') {
                cargarGruposPara(item.dataset.id);
                aplicarRestriccionOnline(item.dataset.id);
            }
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) dropdown.style.display = 'none';
        });
    });

    // === FUNCIONES AUXILIARES ===

    function setBuscador(targetName, id, label) {
        const wrapper = document.querySelector(`.buscador-inteligente[data-target="${targetName}"]`);
        if (!wrapper) return;

        const hidden = wrapper.querySelector('.buscador-hidden');
        const input  = wrapper.querySelector('.buscador-input');

        if (hidden) hidden.value = id;
        if (input)  input.value  = label;

        actualizarBotonLimpiar(wrapper);
    }

    function limpiarBuscador(targetName) {
        const wrapper = document.querySelector(`.buscador-inteligente[data-target="${targetName}"]`);
        if (!wrapper) return;

        const hidden = wrapper.querySelector('.buscador-hidden');
        const input  = wrapper.querySelector('.buscador-input');

        if (hidden) hidden.value = '';
        if (input)  input.value = '';

        actualizarBotonLimpiar(wrapper);
    }

    // === SELECTOR DE MODALIDAD ===

    const modalidadHint = document.getElementById('modalidadHint');

    document.querySelectorAll('.chk-modalidad').forEach(chk => {
        chk.addEventListener('change', function () {
            if (modoEdicion && this.checked) {
                document.querySelectorAll('.chk-modalidad').forEach(other => {
                    if (other !== this) other.checked = false;
                });
            }
            actualizarVisibilidadGrupo();
        });
    });

    function limpiarModalidades() {
        document.querySelectorAll('.chk-modalidad').forEach(chk => chk.checked = false);
    }

    function marcarModalidad(valor) {
        limpiarModalidades();
        const target = document.querySelector(`.chk-modalidad[value="${valor}"]`);
        if (target) target.checked = true;
    }

    // === ABRIR MODAL: NUEVO ===

    document.getElementById('btnNuevo')?.addEventListener('click', function () {
        modoEdicion = false;
        if (modalidadHint) modalidadHint.textContent = '(puedes marcar varias)';
        form.action = `${basePath}/save`;
        document.getElementById('f_id').value = '';

        limpiarBuscador('professor_id');
        bloquearOferta();
        limpiarModalidades();

        const chkTeorica = document.getElementById('chkTeorica');
        const chkPractica = document.getElementById('chkPractica');
        if (chkTeorica) chkTeorica.disabled = false;
        if (chkPractica) chkPractica.disabled = false;

        document.getElementById('avisoOnline')?.classList.add('d-none');
        actualizarVisibilidadGrupo();

        if (modalTitulo) modalTitulo.innerHTML = '<i class="bi bi-person-video2 me-2 text-primary"></i> Nueva Asignación';

        document.querySelectorAll('.buscador-inteligente').forEach(w => actualizarBotonLimpiar(w));

        modal.show();
    });

    // === ABRIR MODAL: EDITAR ===

    function abrirEdicion(fila) {
        modoEdicion = true;
        if (modalidadHint) modalidadHint.textContent = '(una sola, esto edita esa asignación puntual)';
        form.action = `${basePath}/update`;

        document.getElementById('f_id').value = fila.dataset.id;

        setBuscador('professor_id', fila.dataset.professorId, fila.dataset.professorNombre);
        habilitarOfertaPara(fila.dataset.professorId);

        setBuscador('offering_id', fila.dataset.offeringId, fila.dataset.offeringNombre);
        // Cargar grupos de la oferta SIEMPRE (no solo si es teórica), para que
        // queden disponibles si el usuario cambia a teórica luego.
        cargarGruposPara(fila.dataset.offeringId);
        aplicarRestriccionOnline(fila.dataset.offeringId);

        marcarModalidad(fila.dataset.modalidad);

        if (fila.dataset.modalidad === 'TEORICA' && fila.dataset.groupId && inputGrupo) {
            const hiddenGrupo = wrapperGrupo?.querySelector('.buscador-hidden');
            if (hiddenGrupo) hiddenGrupo.value = fila.dataset.groupId;
            inputGrupo.value = fila.dataset.groupNombre;
            actualizarBotonLimpiar(wrapperGrupo);
        }

        // Re-marca después de aplicarRestriccionOnline, por si la restricción la desmarcó
        marcarModalidad(fila.dataset.modalidad);
        actualizarVisibilidadGrupo();

        if (modalTitulo) modalTitulo.innerHTML = '<i class="bi bi-pencil me-2 text-primary"></i> Editar Asignación';

        document.querySelectorAll('.buscador-inteligente').forEach(w => actualizarBotonLimpiar(w));

        modal.show();
    }

    document.querySelectorAll('.fila-asignacion').forEach(fila => {
        fila.addEventListener('click', function (e) {
            if (e.target.closest('.btn-delete')) return;
            abrirEdicion(fila);
        });
    });

    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            abrirEdicion(this.closest('.fila-asignacion'));
        });
    });

    // === SUBMIT CON VALIDACIÓN ===

    form?.addEventListener('submit', function (e) {
        const profId  = form.querySelector('[name="professor_id"]')?.value;
        const offId   = form.querySelector('[name="offering_id"]')?.value;
        const hayModalidad = document.querySelectorAll('.chk-modalidad:checked').length > 0
    || document.querySelector('input[name="modalidad[]"][type="hidden"]') !== null;

        if (!profId) {
            e.preventDefault();
            MySwal.fire({ icon: 'warning', title: 'Falta el profesor', text: 'Elige un profesor de la lista.', confirmButtonColor: '#0d6efd' });
            return;
        }
        if (!offId)  {
            e.preventDefault();
            MySwal.fire({ icon: 'warning', title: 'Falta la oferta', text: 'Elige una oferta de la lista.', confirmButtonColor: '#0d6efd' });
            return;
        }
        if (!hayModalidad) {
            e.preventDefault();
            MySwal.fire({ icon: 'warning', title: 'Falta la modalidad', text: 'Marca al menos una.', confirmButtonColor: '#0d6efd' });
            return;
        }

        const teoricaMarcada = document.getElementById('chkTeorica')?.checked;
        const grupoId = wrapperGrupo?.querySelector('.buscador-hidden')?.value;
        if (teoricaMarcada && !grupoId) {
            e.preventDefault();
            MySwal.fire({ icon: 'warning', title: 'Falta el grupo', text: 'La modalidad Teórica requiere elegir un grupo de esa oferta.', confirmButtonColor: '#0d6efd' });
            return;
        }
    });

    // === ELIMINAR ===

    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const id = this.dataset.id;
            MySwal.fire({
                title: '¿Eliminar esta asignación?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = `${basePath}/delete`;
                    const i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = 'id';
                    i.value = id;
                    f.appendChild(i);
                    document.body.appendChild(f);
                    f.submit();
                }
            });
        });
    });

    // === INICIALIZACIÓN ===
    bloquearOferta();
    inicializarBotonesLimpiar();
});