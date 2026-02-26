/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: public/assets/js/academic_profesores_edit.js
 * Propósito: Script para la gestión interactiva de la vista de edición de profesores.
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // === 0. NOTIFICACIÓN DE ÉXITO ===
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('updated') || urlParams.has('created')) {
        Swal.fire({
            icon: 'success',
            title: '¡Operación Exitosa!',
            text: 'La información del expediente se actualizó correctamente.',
            confirmButtonColor: '#4e73df'
        });
        window.history.replaceState({}, document.title, window.location.pathname + "?id=" + document.querySelector('input[name="id"]').value);
    }

    // === 1. NAVEGACIÓN DE PESTAÑAS (SIN PERSISTENCIA) ===
    const triggerTabList = document.querySelectorAll('#expedienteTabs button');
    triggerTabList.forEach(t => {
        t.addEventListener('click', function (e) {
            e.preventDefault();
            bootstrap.Tab.getOrCreateInstance(this).show();
        });
    });

    // === 2. LÓGICA DE CREACIÓN (+ Añadir) ===
    document.querySelectorAll('.btn-add-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.targetModal;
            const modal = document.querySelector(target);
            if (!modal) return;

            const form = modal.querySelector('form');
            if (!form) return;

            form.reset();
            if(form.querySelector('input[name="id"]')) form.querySelector('input[name="id"]').value = '';
            
            // Forzar habilitación de fecha fin al resetear si es el modal de trabajo
            const endField = form.querySelector('#work_end_date');
            if(endField) endField.disabled = false;

            const submitBtn = modal.querySelector('.btn-submit-modal');
            if (submitBtn) submitBtn.innerText = 'Guarda';
            
            bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    });

    // === 3. LÓGICA DE EDICIÓN (Clic en la fila) ===
    document.querySelectorAll('.edit-row').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.btn-delete-record') || e.target.closest('.no-edit') || e.target.tagName === 'FORM') return;

            const target = this.dataset.targetModal;
            const modal = document.querySelector(target);
            if (!modal) return;

            const form = modal.querySelector('form');
            const data = JSON.parse(this.dataset.json);

            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if(input) {
                    if(input.type === 'checkbox') {
                        input.checked = data[key] == 1;
                        // Disparamos el evento change para que la lógica de deshabilitar fecha fin se ejecute
                        input.dispatchEvent(new Event('change'));
                    } else {
                        input.value = data[key];
                    }
                }
            });

            const submitBtn = modal.querySelector('.btn-submit-modal');
            if (submitBtn) submitBtn.innerText = 'Guarda';

            bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    });

    // === 4. ELIMINAR REGISTRO ===
    document.querySelectorAll('.btn-delete-record').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const f = this.closest('form');
            
            Swal.fire({
                title: '¿Eliminar registro?',
                text: "Esta acción borrará el dato permanentemente del expediente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((res) => { if (res.isConfirmed) f.submit(); });
        });
    });

    // === 5. LÓGICA DE EXPERIENCIA LABORAL (DATES & CHECKBOX) ===
    const chkCurrent = document.getElementById('check_current');
    const workEnd = document.getElementById('work_end_date');
    const workStart = document.getElementById('work_start_date');
    const formWork = document.querySelector('#modalWork form');

    // Manejo de checkbox 'Cargo Actual'
    if (chkCurrent && workEnd) {
        chkCurrent.addEventListener('change', function() { 
            workEnd.disabled = this.checked; 
            if (this.checked) {
                workEnd.value = ''; // Limpiamos la fecha si es el cargo actual
                workEnd.removeAttribute('required');
            } else {
                workEnd.setAttribute('required', 'required');
            }
        });
    }

    // Validación de fechas antes de enviar
    if (formWork) {
        formWork.addEventListener('submit', function(e) {
            if (!chkCurrent.checked && workStart.value && workEnd.value) {
                const start = new Date(workStart.value);
                const end = new Date(workEnd.value);

                if (end < start) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Fechas inconsistentes',
                        text: 'La fecha de finalización no puede ser anterior a la fecha de inicio.',
                        confirmButtonColor: '#4e73df'
                    });
                }
            }
        });
    }

    // === 6. CROPPER FOTO ===
    const btnPh = document.querySelector('.btn-change-photo');
    const inPh = document.getElementById('inputPhotoUpload');
    const imgCr = document.getElementById('imageToCrop');
    const modCr = document.getElementById('modalCrop');
    const svCr = document.getElementById('btnSaveCrop');
    let crop = null;

    if (btnPh) btnPh.addEventListener('click', () => inPh.click());
    if (inPh) inPh.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const rd = new FileReader();
            rd.onload = (ev) => { 
                imgCr.src = ev.target.result; 
                bootstrap.Modal.getOrCreateInstance(modCr).show(); 
            };
            rd.readAsDataURL(file);
        }
    });

    if (modCr) {
        modCr.addEventListener('shown.bs.modal', () => { 
            crop = new Cropper(imgCr, { aspectRatio: 1, viewMode: 1 }); 
        });
        modCr.addEventListener('hidden.bs.modal', () => { 
            if (crop) { crop.destroy(); crop = null; } 
        });
    }

    if (svCr) {
        svCr.addEventListener('click', function() {
            const canv = crop.getCroppedCanvas({ width: 300, height: 300 });
            const b64 = canv.toDataURL('image/png');
            const id = document.querySelector('input[name="id"]').value;
            svCr.disabled = true;
            fetch('/diplomatic/public/academic/profesores/uploadPhoto', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&image=${encodeURIComponent(b64)}`
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    document.getElementById('profile-img-preview').src = d.path;
                    bootstrap.Modal.getOrCreateInstance(modCr).hide();
                    Swal.fire({ icon: 'success', title: '¡Foto actualizada!', confirmButtonColor: '#4e73df' });
                }
            }).finally(() => { svCr.disabled = false; });
        });
    }
});