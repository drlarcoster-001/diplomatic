/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: public/assets/js/academic_cohortes.js
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('--> JS Cohortes Cargado');
    const basePath = '/diplomatic/public/academic/cohortes';

    // 1. BOTÓN "NUEVA COHORTE"
    const btnNuevo = document.getElementById('btnOpenNuevo');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            fetch(`${basePath}/logAccess?action=CREATE_FORM`).catch(e => console.error(e));
            const form = document.getElementById('formCohort');
            if (form) {
                form.reset();
                document.getElementById('field_id').value = '';
                form.action = `${basePath}/save`;
                document.querySelector('#modalCohortForm .modal-title').innerText = 'Registrar Nueva Cohorte';
            }
        });
    }

    // 2. BOTÓN EDITAR
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const targetBtn = e.target.closest('.btn-edit');
            const id = targetBtn.dataset.id;
            
            fetch(`${basePath}/logAccess?action=EDIT_FORM&id=${id}`);

            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        const c = data.cohorte;
                        const form = document.getElementById('formCohort');
                        form.action = `${basePath}/update`;
                        document.getElementById('field_id').value = c.id;
                        document.getElementById('field_code').value = c.cohort_code;
                        document.getElementById('field_name').value = c.name;
                        document.getElementById('field_start').value = c.start_date;
                        document.getElementById('field_end').value = c.end_date;
                        if(document.getElementById('field_enroll_start')) document.getElementById('field_enroll_start').value = c.enrollment_start;
                        if(document.getElementById('field_enroll_end')) document.getElementById('field_enroll_end').value = c.enrollment_end;
                        if(document.getElementById('field_campus')) document.getElementById('field_campus').value = c.base_campus;
                        if(document.getElementById('field_desc')) document.getElementById('field_desc').value = c.description;

                        document.querySelector('#modalCohortForm .modal-title').innerText = 'Editar Cohorte';
                        new bootstrap.Modal(document.getElementById('modalCohortForm')).show();
                    }
                });
        });
    });

    // 3. BOTÓN ELIMINAR
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const targetBtn = e.target.closest('.btn-delete');
            const id = targetBtn.dataset.id;
            const name = targetBtn.dataset.name;

            fetch(`${basePath}/logAccess?action=DELETE_ATTEMPT&id=${id}`);

            Swal.fire({
                title: '¿Eliminar Cohorte?',
                html: `Se dará de baja: <strong>${name}</strong><br><small class="text-muted">Solo permitido si está "Planificada".</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
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

    // 4. CLIC EN FILA
    document.querySelectorAll('.cohorte-row').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.btn-group') || e.target.closest('button')) return;

            const id = this.dataset.id;
            fetch(`${basePath}/logAccess?action=VIEW_DETAILS&id=${id}`);

            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        const c = data.cohorte;
                        document.getElementById('prev_name').innerText = c.name;
                        document.getElementById('prev_code').innerText = c.cohort_code;
                        document.getElementById('prev_start').innerText = c.start_date;
                        document.getElementById('prev_end').innerText = c.end_date;
                        document.getElementById('prev_campus').innerText = c.base_campus || 'No definida';
                        
                        const enrollStart = c.enrollment_start ? c.enrollment_start : 'No definida';
                        const enrollEnd = c.enrollment_end ? c.enrollment_end : 'No definida';
                        if(document.getElementById('prev_enroll_start')) document.getElementById('prev_enroll_start').innerText = enrollStart;
                        if(document.getElementById('prev_enroll_end')) document.getElementById('prev_enroll_end').innerText = enrollEnd;

                        const descEl = document.getElementById('prev_desc');
                        if(descEl) descEl.innerText = c.description || 'Sin observaciones.';

                        const btnStart = document.getElementById('btn_start_action');
                        const btnClose = document.getElementById('btn_close_action');

                        if(btnStart) btnStart.style.display = 'none';
                        if(btnClose) btnClose.style.display = 'none';

                        const status = (c.cohort_status || '').toLowerCase().trim();

                        if (status === 'planificada') {
                            if (btnStart) {
                                btnStart.style.display = 'inline-block';
                                btnStart.onclick = () => confirmChangeStatus(c.id, 'En curso', 'Iniciar Ciclo');
                            }
                        } 
                        else if (status === 'en curso') {
                            if (btnClose) {
                                btnClose.style.display = 'inline-block';
                                btnClose.onclick = () => confirmChangeStatus(c.id, 'Finalizada', 'Finalizar Ciclo');
                            }
                        }

                        new bootstrap.Modal(document.getElementById('modalCohortPreview')).show();
                    }
                });
        });
    });

    function confirmChangeStatus(id, newStatus, actionName) {
        Swal.fire({
            title: `¿${actionName}?`,
            text: `El estado cambiará a "${newStatus}".`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `${basePath}/changeStatus?id=${id}&status=${newStatus}`;
            }
        });
    }
});