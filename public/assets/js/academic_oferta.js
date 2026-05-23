/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: public/assets/js/academic_oferta.js
 * PROPÓSITO: Gestionar la interactividad y validaciones de la Oferta Académica.
 * VERSIÓN: 3.43.0 - Inserción de Fecha de Vencimiento en Esquema de Pagos y validación de integridad.
 */

document.addEventListener('DOMContentLoaded', function() {
    const MySwal = (typeof Swal !== 'undefined') ? Swal : { fire: (t, m, i) => alert(m) };
    const form = document.getElementById('formOferta');

    // 1. FILTROS DINÁMICOS
    document.querySelectorAll('.auto-filter').forEach(el => {
        el.addEventListener('change', () => { 
            const filterForm = document.getElementById('filterForm');
            if (filterForm) filterForm.submit(); 
        });
    });

    // 2. ENVÍO ASÍNCRONO Y VALIDACIÓN DE FORMULARIO (GUARDAR/ACTUALIZAR)
    if (form) {
        form.onsubmit = async function(e) {
            e.preventDefault(); 

            const totalCapacity = parseInt(document.getElementById('total_capacity').value) || 0;
            const sedesChecked = document.querySelectorAll('input[name="campuses[]"]:checked').length;
            const gruposChecked = document.querySelectorAll('input[name="groups_check[]"]:checked').length;
            const pagosGenerados = document.querySelectorAll('#plans_table tbody tr').length;
            const profesoresAsignados = document.querySelectorAll('#list_selected .prof-item').length;

            // --- VALIDACIÓN DE FECHAS ---
            // Buscamos todos los campos de fecha de pago generados
            const paymentDates = document.querySelectorAll('input[name^="payment_due_date"]');
            let datesComplete = true;
            paymentDates.forEach(input => {
                if (!input.value) datesComplete = false;
            });

            if (totalCapacity <= 0 || sedesChecked === 0 || gruposChecked === 0 || pagosGenerados === 0 || profesoresAsignados === 0 || !datesComplete) {
            
                let errorMsg = 'Debe completar todos los parámetros obligatorios, asignar profesores y generar el esquema de pagos.';
                if(!datesComplete && pagosGenerados > 0) errorMsg = 'Debe asignar una fecha de vencimiento a cada concepto de pago.';
                
                MySwal.fire('Atención', errorMsg, 'warning');
                return false;
            }

            MySwal.fire({
                title: 'Procesando...',
                text: 'Guardando los parámetros de la oferta académica.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    const errorHtml = await response.text();
                    console.error("❌ EL SERVIDOR DEVOLVIÓ ESTE HTML EN LUGAR DE JSON:\n", errorHtml);
                    throw new Error("El servidor devolvió un formato no válido (HTML). Revise la consola (F12) para ver el error.");
                }

                const result = await response.json();

                if (result.ok) {
                    MySwal.fire({
                        title: '¡Éxito!',
                        text: result.msg,
                        icon: 'success',
                        confirmButtonColor: '#0d6efd'
                    }).then(() => {
                        window.location.href = '/diplomatic/public/academic/oferta';
                    });
                } else {
                    MySwal.fire('Error', result.msg || 'No se pudo procesar la solicitud.', 'error');
                }
            } catch (error) {
                console.error('Error en el envío AJAX:', error);
                MySwal.fire('Error de Sistema', error.message || 'Hubo un problema de conexión con el servidor.', 'error');
            }
        };
    }

    // 3. CONFIRMACIÓN DE ELIMINACIÓN
    document.querySelectorAll('.form-delete-oferta').forEach(deleteForm => {
        deleteForm.onsubmit = function(e) {
            e.preventDefault();
            const row = this.closest('.row-oferta');
            const diplomaName = row ? row.dataset.diploma : 'esta oferta';

            MySwal.fire({
                title: '¿Eliminar Oferta?',
                html: `¿Está seguro de eliminar la oferta para:<br><b>${diplomaName}</b>?<br><br><span class="text-danger small">Esta acción no se puede deshacer.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        };
    });

    // 4. CONTROL DE CAPACIDAD
    const capInput = document.getElementById('total_capacity');
    if (capInput) {
        capInput.addEventListener('input', function() {
            if (parseInt(this.value) > 399) { 
                this.value = 399; 
                MySwal.fire('Información', 'La capacidad máxima permitida por oferta es de 399 cupos.', 'info'); 
            }
        });
    }

    // 5. LÓGICA DE COHORTE (Fechas y Sedes dinámicas)
    const cohSel = document.getElementById('cohort_id');
    if (cohSel) {
        cohSel.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (!opt.value) return;

            const fields = { 'reg_start': 'rstart', 'reg_end': 'rend', 'class_start': 'cstart', 'class_end': 'cend' };
            for (let id in fields) {
                const el = document.getElementById(id);
                if (el) el.value = opt.dataset[fields[id]] || '';
            }

            const ctnSedes = document.getElementById('campuses_container');
            if(ctnSedes) {
                ctnSedes.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
                fetch(`/diplomatic/public/academic/oferta/getCohortCampuses?cohort_id=${this.value}`)
                    .then(r => r.json())
                    .then(data => {
                        ctnSedes.innerHTML = '';
                        data.forEach(c => {
                            const d = document.createElement('div'); 
                            d.className = 'form-check border-bottom pb-2 mb-2 d-flex align-items-start';
                            d.innerHTML = `<input class="form-check-input me-3 mt-1" type="checkbox" name="campuses[]" value="${c.id}" id="cam_${c.id}" checked>
                                           <label class="form-check-label w-100" for="cam_${c.id}">
                                               <strong class="text-dark small">${c.name}</strong>
                                           </label>`;
                            ctnSedes.appendChild(d);
                        });
                    });
            }
        });
    }

    // 6. LISTA DUAL PROFESORES
    const listA = document.getElementById('list_available');
    const listS = document.getElementById('list_selected');
    if (listA && listS) {
        listA.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-add-prof'); if(!btn) return;
            const item = btn.closest('.prof-item'); 
            const id = item.dataset.id;
            const name = item.dataset.name; 
            item.classList.add('d-none');
            
            const idx = Date.now() + Math.floor(Math.random() * 100);
            const r = document.createElement('div'); 
            r.className = 'prof-item d-flex justify-content-between p-2 border-bottom bg-white'; 
            r.dataset.id = id;
            r.innerHTML = `<div class="text-truncate small" style="max-width:45%"><input type="hidden" name="professor_id[${idx}]" value="${id}">${name}</div>
                            <div><select name="professor_role[${idx}]" class="form-select form-select-sm d-inline-block w-auto me-1" style="font-size:0.7rem;"><option value="PRINCIPAL">Principal</option><option value="INVITADO">Invitado</option><option value="ASISTENTE">Asistente</option><option value="COORDINADOR">Coordinador</option></select>
                            <button type="button" class="btn btn-sm text-danger btn-remove-prof"><i class="bi bi-trash-fill"></i></button></div>`;
            listS.appendChild(r);
        });
        listS.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-remove-prof'); if(!btn) return;
            const item = btn.closest('.prof-item'); const id = item.dataset.id; item.remove();
            const leftItem = listA.querySelector(`.prof-item[data-id="${id}"]`); if (leftItem) leftItem.classList.remove('d-none');
        });
    }

    // 7. POPUP ACTIVACIÓN
    document.querySelectorAll('.row-oferta').forEach(row => {
        row.addEventListener('click', function() {
            const d = this.dataset; 
            if(d.status !== 'BORRADOR') {
                MySwal.fire('Gestión Protegida', 'No se puede cambiar el estado directamente. Use el candado administrativo.', 'info');
                return; 
            }
            
            const fdLog = new FormData();
            fdLog.append('id', d.id);
            fetch('/diplomatic/public/academic/oferta/logSummaryPopup', { method: 'POST', body: fdLog }).catch(()=>{});

            MySwal.fire({
                title: '¿Activar Convocatoria?',
                html: `<div class="text-start p-3 border rounded bg-light" style="font-size: 0.9rem;">
                        <div class="mb-2"><strong class="text-muted small text-uppercase">Diplomado:</strong><br><b class="text-dark">${d.diploma || 'N/A'}</b></div>
                        <div class="mb-0"><strong class="text-muted small text-uppercase">Cohorte:</strong><br><span class="text-dark">${d.cohort || 'N/A'}</span></div>
                    </div>`,
                icon: 'question', 
                showCancelButton: true, 
                confirmButtonText: 'Sí, Abrir Oferta', 
                cancelButtonText: 'Cancelar', 
                confirmButtonColor: '#198754', 
                reverseButtons: true
            }).then((r) => { 
                if (r.isConfirmed) {
                    const fd = new FormData(); fd.append('id', d.id);
                    fetch('/diplomatic/public/academic/oferta/executeOpen', { method: 'POST', body: fd })
                    .then(res => res.json()).then(resData => {
                        if (resData.ok) MySwal.fire('¡Éxito!', 'Oferta abierta correctamente.', 'success').then(() => window.location.reload());
                        else MySwal.fire('Error', 'Fallo al activar la oferta.', 'error');
                    });
                }
            });
        });
    });

    // 8. CANDADO ADMIN
    document.querySelectorAll('.btn-lock').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); const d = this.dataset;
            const fdLogLock = new FormData(); fdLogLock.append('id', d.id);
            fetch('/diplomatic/public/academic/oferta/logLockPopup', { method: 'POST', body: fdLogLock }).catch(()=>{});

            MySwal.fire({
                title: 'Cambio de Estatus Manual',
                html: `<p class="text-muted small">Estado actual: <b>${d.status}</b></p>
                    <input type="email" id="admin_email" class="form-control mb-3 text-center" placeholder="Email Admin">
                    <input type="password" id="admin_password" class="form-control text-center" placeholder="Contraseña">`,
                showCancelButton: true, confirmButtonText: 'Validar', confirmButtonColor: '#0d6efd',
                preConfirm: () => {
                    const email = Swal.getPopup().querySelector('#admin_email').value;
                    const password = Swal.getPopup().querySelector('#admin_password').value;
                    if (!email || !password) Swal.showValidationMessage('Datos incompletos');
                    return { email, password }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    MySwal.fire({ title: 'Validando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });
                    const fd = new FormData(); fd.append('email', result.value.email); fd.append('password', result.value.password);

                    fetch('/diplomatic/public/academic/oferta/verifyAdmin', { method: 'POST', body: fd })
                    .then(r => r.json()).then(res => {
                        if (res.ok) {
                            const allStatuses = ['BORRADOR', 'ABIERTA', 'CERRADA', 'SUSPENDIDA', 'CANCELADA'];
                            let optionsHtml = '';
                            allStatuses.forEach(st => { if (st !== d.status) optionsHtml += `<option value="${st}">${st}</option>`; });

                            MySwal.fire({
                                title: 'Seleccionar Nuevo Estado',
                                html: `<select id="new_status_select" class="form-select fw-bold">${optionsHtml}</select>`,
                                icon: 'info', showCancelButton: true, confirmButtonText: 'Aplicar',
                                preConfirm: () => { return Swal.getPopup().querySelector('#new_status_select').value; }
                            }).then(r2 => {
                                if(r2.isConfirmed) {
                                    const fd2 = new FormData(); fd2.append('id', d.id); fd2.append('status', r2.value);
                                    fetch('/diplomatic/public/academic/oferta/changeStatusAdmin', { method: 'POST', body: fd2 })
                                    .then(r3 => r3.json()).then(res3 => {
                                        if (res3.ok) MySwal.fire('Completado', 'El estado ha sido actualizado.', 'success').then(() => window.location.reload());
                                        else MySwal.fire('Error', res3.msg || 'Fallo técnico.', 'error');
                                    });
                                }
                            });
                        } else MySwal.fire('Denegado', res.msg, 'error');
                    });
                }
            });
        });
    });
});

/**
 * --- LÓGICA DE PAGOS ACTUALIZADA ---
 * Ahora incluye el campo 'due_date' (Vencimiento)
 */
window.calculatePayments = function() {
    const total = parseFloat(document.getElementById('calc_total').value) || 0;
    const hasInsc = document.getElementById('calc_has_inscripcion').checked;
    const inscAmount = hasInsc ? (parseFloat(document.getElementById('calc_inscripcion_amount').value) || 0) : 0;
    const cuotas = parseInt(document.getElementById('calc_cuotas').value) || 0;

    if (total <= 0) { Swal.fire('Error', 'Debe ingresar un costo total válido.', 'error'); return; }

    const tbody = document.querySelector('#plans_table tbody');
    tbody.innerHTML = '';
    let idx = 0;
    let rem = total;

    if (hasInsc) {
        rem -= inscAmount;
        const row = `<tr>
            <td><input type="text" name="payment_concept[${idx}]" class="form-control form-control-sm bg-light" value="INSCRIPCIÓN" readonly></td>
            <td><input type="number" step="0.01" name="payment_amount[${idx}]" class="form-control form-control-sm bg-light" value="${inscAmount.toFixed(2)}" readonly></td>
            <td><input type="date" name="payment_due_date[${idx}]" class="form-control form-control-sm border-primary" required></td> <td><input type="text" name="payment_description[${idx}]" class="form-control form-control-sm" placeholder="Nota obligatoria..."></td>
            <td class="text-center"><button type="button" class="btn btn-sm text-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
        </tr>`;
        tbody.insertAdjacentHTML('beforeend', row);
        idx++;
    }

    if (cuotas > 0 && rem > 0) {
        const amt = rem / cuotas;
        for (let i = 1; i <= cuotas; i++) {
            const row = `<tr>
                <td><input type="text" name="payment_concept[${idx}]" class="form-control form-control-sm bg-light" value="CUOTA ${i}" readonly></td>
                <td><input type="number" step="0.01" name="payment_amount[${idx}]" class="form-control form-control-sm bg-light" value="${amt.toFixed(2)}" readonly></td>
                <td><input type="date" name="payment_due_date[${idx}]" class="form-control form-control-sm border-primary" required></td> <td><input type="text" name="payment_description[${idx}]" class="form-control form-control-sm" placeholder="Opcional..."></td>
                <td class="text-center"><button type="button" class="btn btn-sm text-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
            </tr>`;
            tbody.insertAdjacentHTML('beforeend', row);
            idx++;
        }
    }
};