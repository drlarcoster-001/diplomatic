/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: public/assets/js/academic_periodos.js
 * PROPÓSITO: Gestionar la interactividad de la interfaz de Períodos Institucionales. Controla la validación cronológica de fechas, la carga dinámica de detalles y el flujo de inactivación lógica. Un período agrupa múltiples cohortes bajo un mismo contexto operativo y controla la ventana global de inscripciones del programa.
 * VERSIÓN: 1.1.0
 */

$(document).ready(function() {
    const basePath = '/diplomatic/public/academic/periodos';
    const MySwal = (typeof Swal !== 'undefined') ? Swal : { fire: (obj) => alert(obj.text) };

    // === 0. GESTIÓN DE ALERTAS POR URL (RESPUESTAS DEL SERVIDOR) ===
    const urlParams    = new URLSearchParams(window.location.search);
    const errorType    = urlParams.get('error');
    const successType  = urlParams.get('success');

    if (errorType === 'in_use') {
        MySwal.fire({
            icon: 'error',
            title: 'Acción Bloqueada',
            text: 'Este período tiene cohortes activas vinculadas. La integridad de los datos impide su inactivación mientras posea cohortes en operación.',
            confirmButtonColor: '#198754'
        });
    } else if (errorType === 'restriction_finalizado') {
        MySwal.fire({
            icon: 'warning',
            title: 'Operación Restringida',
            text: 'Los períodos en estado "Finalizado" no pueden ser modificados.',
            confirmButtonColor: '#f6c23e'
        });
    } else if (errorType === 'invalid_dates') {
        MySwal.fire({
            icon: 'error',
            title: 'Inconsistencia en fechas',
            text: 'Verifique que las fechas de inicio, fin e inscripciones sean cronológicamente correctas.',
            confirmButtonColor: '#e74a3b'
        });
    } else if (errorType === 'db') {
        MySwal.fire({
            icon: 'error',
            title: 'Error de Sistema',
            text: 'No se pudo procesar la solicitud en la base de datos.',
            confirmButtonColor: '#e74a3b'
        });
    }

    if (successType === 'inactivated' || urlParams.get('created') || urlParams.get('updated')) {
        MySwal.fire({
            icon: 'success',
            title: '¡Operación Exitosa!',
            text: 'Los cambios se han aplicado correctamente en el sistema.',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // === 1. BOTÓN NUEVO PERÍODO ===
    const btnNuevo = document.getElementById('btnOpenNuevo');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            fetch(`${basePath}/logAccess?action=CREATE_FORM`);
            const form = document.getElementById('formPeriodo');
            if (form) {
                form.reset();
                if (document.getElementById('field_id')) document.getElementById('field_id').value = '';
                form.action = `${basePath}/save`;
                const title = document.getElementById('modalPeriodoTitle');
                if (title) title.innerText = 'Registrar Nuevo Período';
                // Limpiar restricciones de fechas
                ['field_fin','field_apertura','field_cierre'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) { el.min = ''; el.max = ''; }
                });
            }
        });
    }

    // === 2. BOTÓN EDITAR ===
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id = this.dataset.id;

            fetch(`${basePath}/logAccess?action=EDIT_FORM&id=${id}`);

            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        const p    = data.periodo;
                        const form = document.getElementById('formPeriodo');
                        form.action = `${basePath}/update`;

                        const setField = (fieldId, val) => {
                            const el = document.getElementById(fieldId);
                            if (el) el.value = val || '';
                        };

                        setField('field_id',         p.id);
                        setField('field_code',        p.periodo_code);
                        setField('field_nombre',      p.nombre);
                        setField('field_inicio',      p.fecha_inicio);
                        setField('field_fin',         p.fecha_fin);
                        setField('field_apertura',    p.apertura_inscripcion);
                        setField('field_cierre',      p.cierre_inscripcion);
                        setField('field_descripcion', p.descripcion);

                        // Aplicar restricciones de fechas con datos cargados
                        if (p.fecha_inicio) {
                            const finEl      = document.getElementById('field_fin');
                            const aperturaEl = document.getElementById('field_apertura');
                            if (finEl)      finEl.min      = p.fecha_inicio;
                            if (aperturaEl) aperturaEl.min = p.fecha_inicio;
                        }
                        if (p.apertura_inscripcion) {
                            const cierreEl = document.getElementById('field_cierre');
                            if (cierreEl) cierreEl.min = p.apertura_inscripcion;
                        }
                        if (p.fecha_fin) {
                            const cierreEl = document.getElementById('field_cierre');
                            if (cierreEl) cierreEl.max = p.fecha_fin;
                        }

                        const title = document.getElementById('modalPeriodoTitle');
                        if (title) title.innerText = 'Editar Período Institucional';

                        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPeriodoForm')).show();
                    }
                });
        });
    });

    // === 3. VALIDACIÓN DE FECHAS (CRONOLOGÍA ESTRICTA) ===
    const formPeriodo = document.getElementById('formPeriodo');
    if (formPeriodo) {
        formPeriodo.addEventListener('submit', function(e) {
            const inicioStr   = document.getElementById('field_inicio').value;
            const finStr      = document.getElementById('field_fin').value;
            const aperturaStr = document.getElementById('field_apertura').value;
            const cierreStr   = document.getElementById('field_cierre').value;

            let errorMsg = '';

            if (inicioStr && finStr) {
                if (new Date(finStr) <= new Date(inicioStr)) {
                    errorMsg = 'El FIN DE PERÍODO debe ser posterior al INICIO DE PERÍODO.';
                }
            }

            if (!errorMsg && aperturaStr && cierreStr) {
                if (new Date(cierreStr) <= new Date(aperturaStr)) {
                    errorMsg = 'El CIERRE DE INSCRIPCIÓN debe ser posterior a la APERTURA DE INSCRIPCIÓN.';
                }
            }

            if (errorMsg !== '') {
                e.preventDefault();
                MySwal.fire({
                    icon: 'error',
                    title: 'Inconsistencia en fechas',
                    text: errorMsg,
                    confirmButtonColor: '#198754'
                });
            }
        });
    }

    // === 4. BOTÓN ELIMINAR (INACTIVACIÓN LÓGICA) ===
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id   = this.dataset.id;
            const name = this.dataset.name;

            MySwal.fire({
                title: '¿Inactivar período?',
                html: `Se procederá a dar de baja a: <b>${name}</b>.<br><small class="text-muted">El registro se ocultará de la grid principal. El sistema bloqueará esta acción si el período tiene cohortes activas vinculadas.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Sí, inactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = `${basePath}/delete`;
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'id'; i.value = id;
                    f.appendChild(i);
                    document.body.appendChild(f);
                    f.submit();
                }
            });
        });
    });

    // === 5. VISTA PREVIA (FICHA TÉCNICA) ===
    document.querySelectorAll('.periodo-row').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.btn-group') || e.target.closest('button')) return;
            const id = this.dataset.id;

            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        const p = data.periodo;

                        const setVal = (elId, val) => {
                            const el = document.getElementById(elId);
                            if (el) el.innerText = val || '--';
                        };

                        setVal('prev_nombre',      p.nombre);
                        setVal('prev_code',        p.periodo_code);
                        setVal('prev_inicio',      p.fecha_inicio);
                        setVal('prev_fin',         p.fecha_fin);
                        setVal('prev_apertura',    p.apertura_inscripcion);
                        setVal('prev_cierre',      p.cierre_inscripcion);
                        setVal('prev_cohortes',    p.total_cohortes);
                        setVal('prev_estado',      p.estado);
                        setVal('prev_descripcion', p.descripcion);

                        const btnActivar   = document.getElementById('btn_activar');
                        const btnFinalizar = document.getElementById('btn_finalizar');
                        if (btnActivar)   btnActivar.style.display   = 'none';
                        if (btnFinalizar) btnFinalizar.style.display  = 'none';

                        const estado = (p.estado || '').trim().toLowerCase();

                        if (estado === 'planificado') {
                            btnActivar.style.display = 'inline-block';
                            btnActivar.innerText = 'Activar Período';
                            btnActivar.onclick = () => changeStatus(p.id, 'Activo');
                        } else if (estado === 'activo') {
                            btnActivar.style.display = 'inline-block';
                            btnActivar.innerText = 'Volver a Planificado';
                            btnActivar.onclick = () => changeStatus(p.id, 'Planificado');
                            btnFinalizar.style.display = 'inline-block';
                            btnFinalizar.onclick = () => changeStatus(p.id, 'Finalizado');
                        } else if (estado === 'finalizado') {
                            btnFinalizar.style.display = 'inline-block';
                            btnFinalizar.innerText = 'Reabrir Período';
                            btnFinalizar.onclick = () => changeStatus(p.id, 'Activo');
                        }

                        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPeriodoPreview')).show();
                    }
                });
        });
    });

    // === 6. CAMBIO DE ESTADO DEL CICLO DE VIDA ===
    function changeStatus(id, newStatus) {
        const mensajes = {
            'Activo':     'El período pasará a estado Activo y estará disponible para operaciones.',
            'Finalizado': 'El período quedará cerrado definitivamente. Esta acción no se puede revertir desde este módulo.'
        };

        MySwal.fire({
            title: '¿Confirmar cambio de estado?',
            text: mensajes[newStatus] || `El período pasará a estado: ${newStatus}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#858796',
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `${basePath}/changeStatus?id=${id}&status=${encodeURIComponent(newStatus)}`;
            }
        });
    }

    // === 7. RESTRICCIÓN DINÁMICA DE FECHAS EN FORMULARIO ===
    const fieldInicio   = document.getElementById('field_inicio');
    const fieldFin      = document.getElementById('field_fin');
    const fieldApertura = document.getElementById('field_apertura');
    const fieldCierre   = document.getElementById('field_cierre');

    if (fieldInicio) {
        fieldInicio.addEventListener('change', function() {
            if (this.value) {
                if (fieldFin)      fieldFin.min      = this.value;
                if (fieldApertura) fieldApertura.min = this.value;
            }
        });
    }

    if (fieldApertura) {
        fieldApertura.addEventListener('change', function() {
            if (this.value) {
                if (fieldCierre) fieldCierre.min = this.value;
            }
        });
    }

    if (fieldFin) {
        fieldFin.addEventListener('change', function() {
            if (this.value) {
                if (fieldCierre) fieldCierre.max = this.value;
            }
        });
    }

});