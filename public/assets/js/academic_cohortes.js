/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: public/assets/js/academic_cohortes.js
 * PROPÓSITO: Gestionar la interactividad de la interfaz de Cohortes Académicas. Controla la validación cronológica de fechas, la carga dinámica de detalles y el flujo de inactivación lógica.
 * ACTUALIZACIÓN: Reforzamiento de la UI para procesos de integridad referencial. Se ha modificado el flujo de borrado para que actúe como una inactivación (baja lógica). Se integró la detección del error 'in_use' para notificar al usuario el bloqueo de seguridad cuando una cohorte está vinculada a una Oferta Académica, impidiendo que el registro sea ocultado mientras sea operativo.
 * VERSIÓN: 1.1.30
 */

$(document).ready(function() {
    const basePath = '/diplomatic/public/academic/cohortes';
    const MySwal = (typeof Swal !== 'undefined') ? Swal : { fire: (obj) => alert(obj.text) };

    // === 0. GESTIÓN DE ALERTAS POR URL (RESPUESTAS DEL SERVIDOR) ===
    const urlParams = new URLSearchParams(window.location.search);
    const errorType = urlParams.get('error');
    const successType = urlParams.get('success');

    if (errorType === 'in_use') {
        MySwal.fire({
            icon: 'error',
            title: 'Acceso Denegado',
            text: 'Esta cohorte está siendo utilizada por una Oferta Académica activa. La integridad de los datos impide su inactivación o eliminación.',
            confirmButtonColor: '#4e73df'
        });
    } else if (errorType === 'restriction_active') {
        MySwal.fire({
            icon: 'warning',
            title: 'Operación Restringida',
            text: 'Solo se pueden modificar o inactivar cohortes que se encuentren en estado "Planificada".',
            confirmButtonColor: '#f6c23e'
        });
    } else if (errorType === 'db') {
        MySwal.fire({
            icon: 'error',
            title: 'Error de Sistema',
            text: 'No se pudo procesar la solicitud en la base de datos.',
            confirmButtonColor: '#e74a3b'
        });
    }

    if (successType === 'inactivated' || successType === 'deleted' || urlParams.get('created') || urlParams.get('updated')) {
        MySwal.fire({
            icon: 'success',
            title: '¡Operación Exitosa!',
            text: 'Los cambios se han aplicado correctamente en el sistema.',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // === 1. INICIALIZACIÓN DE SELECT2 ===
    const initSedesSelect = () => {
        $('.select2-multiple').each(function() {
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $(this).data('placeholder'),
                closeOnSelect: false,
                allowClear: true
            });
        });
    };
    initSedesSelect();

    // === 2. BOTÓN NUEVA COHORTE ===
    const btnNuevo = document.getElementById('btnOpenNuevo');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            fetch(`${basePath}/logAccess?action=CREATE_FORM`);
            const form = document.getElementById('formCohort');
            if (form) {
                form.reset();
                if(document.getElementById('field_id')) document.getElementById('field_id').value = '';
                $('#field_campuses').val(null).trigger('change'); 
                form.action = `${basePath}/save`;
                const title = document.querySelector('#modalCohortForm .modal-title');
                if(title) title.innerText = 'Registrar Nueva Cohorte';
            }
        });
    }

    // === 3. BOTÓN EDITAR ===
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
                        const c = data.cohorte;
                        const form = document.getElementById('formCohort');
                        form.action = `${basePath}/update`;
                        
                        if(document.getElementById('field_id')) document.getElementById('field_id').value = c.id;
                        if(document.getElementById('field_code')) document.getElementById('field_code').value = c.cohort_code;
                        if(document.getElementById('field_name')) document.getElementById('field_name').value = c.name;
                        if(document.getElementById('field_start')) document.getElementById('field_start').value = c.start_date;
                        if(document.getElementById('field_end')) document.getElementById('field_end').value = c.end_date;
                        if(document.getElementById('field_enroll_start')) document.getElementById('field_enroll_start').value = c.enrollment_start || '';
                        if(document.getElementById('field_enroll_end')) document.getElementById('field_enroll_end').value = c.enrollment_end || '';
                        if(document.getElementById('field_desc')) document.getElementById('field_desc').value = c.description || '';

                        if (c.campus_ids) {
                            $('#field_campuses').val(c.campus_ids).trigger('change');
                        }

                        const title = document.querySelector('#modalCohortForm .modal-title');
                        if(title) title.innerText = 'Editar Cohorte';
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCohortForm')).show();
                    }
                });
        });
    });

    // === 4. VALIDACIÓN DE FECHAS (CRONOLOGÍA ESTRICTA) ===
    const formCohort = document.getElementById('formCohort');
    if (formCohort) {
        formCohort.addEventListener('submit', function(e) {
            const startStr = document.getElementById('field_start').value;
            const endStr = document.getElementById('field_end').value;
            const enrollStartStr = document.getElementById('field_enroll_start').value;
            const enrollEndStr = document.getElementById('field_enroll_end').value;

            let errorMsg = '';

            if (startStr && endStr) {
                if (new Date(endStr) <= new Date(startStr)) {
                    errorMsg = 'La FECHA FIN de la cohorte debe ser posterior a la de INICIO.';
                }
            }

            if (!errorMsg && enrollStartStr && enrollEndStr) {
                if (new Date(enrollEndStr) <= new Date(enrollStartStr)) {
                    errorMsg = 'El CIERRE DE INSCRIPCIÓN debe ser posterior a la APERTURA.';
                }
            }

            if (errorMsg !== '') {
                e.preventDefault();
                MySwal.fire({ 
                    icon: 'error', 
                    title: 'Inconsistencia en fechas', 
                    text: errorMsg,
                    confirmButtonColor: '#4e73df'
                });
            }
        });
    }

    // === 5. BOTÓN ELIMINAR (SOLICITUD DE INACTIVACIÓN LÓGICA) ===
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); e.stopPropagation();
            const id = this.dataset.id;
            const name = this.dataset.name;

            MySwal.fire({
                title: '¿Inactivar cohorte?',
                html: `Se procederá a dar de baja a: <b>${name}</b>.<br><small class="text-muted">El registro se ocultará de la grid principal. El sistema bloqueará esta acción si la cohorte está en uso.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Sí, inactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const f = document.createElement('form');
                    f.method = 'POST'; f.action = `${basePath}/delete`;
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'id'; i.value = id;
                    f.appendChild(i); document.body.appendChild(f);
                    f.submit();
                }
            });
        });
    });

    // === 6. VISTA PREVIA (FICHA TÉCNICA) ===
    document.querySelectorAll('.cohorte-row').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.btn-group') || e.target.closest('button')) return;
            const id = this.dataset.id;
            
            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        const c = data.cohorte;
                        
                        const setVal = (id, val) => {
                            const el = document.getElementById(id);
                            if (el) el.innerText = val || '--';
                        };

                        setVal('prev_name', c.name);
                        setVal('prev_code', c.cohort_code);
                        setVal('prev_start', c.start_date);
                        setVal('prev_end', c.end_date);
                        setVal('prev_enroll_start', c.enrollment_start);
                        setVal('prev_enroll_end', c.enrollment_end);
                        setVal('prev_campus', c.campus_names);
                        setVal('prev_desc', c.description);

                        const bStart = document.getElementById('btn_start_action');
                        const bClose = document.getElementById('btn_close_action');
                        if(bStart) bStart.style.display = 'none';
                        if(bClose) bClose.style.display = 'none';

                        const status = (c.cohort_status || "").trim().toLowerCase();
                        if (status === 'planificada' && bStart) {
                            bStart.style.display = 'inline-block';
                            bStart.onclick = () => changeStatus(c.id, 'En curso');
                        } else if (status === 'en curso' && bClose) {
                            bClose.style.display = 'inline-block';
                            bClose.onclick = () => changeStatus(c.id, 'Finalizada');
                        }
                        
                        const modalEl = document.getElementById('modalCohortPreview');
                        if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                });
        });
    });

    function changeStatus(id, newStatus) {
        MySwal.fire({
            title: '¿Confirmar cambio de estado?',
            text: `La cohorte pasará a estado: ${newStatus}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4e73df'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `${basePath}/changeStatus?id=${id}&status=${newStatus}`;
            }
        });
    }
});