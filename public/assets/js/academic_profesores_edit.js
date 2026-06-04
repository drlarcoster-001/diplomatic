/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: public/assets/js/academic_profesores_edit.js
 * Propósito: Sistema de control interactivo para la gestión de expedientes docentes, incluyendo validaciones de formularios, persistencia de navegación y edición dinámica de registros.
 * Version: 1.1.0 - Última versión estable guardada. Implementación de persistencia de pestañas por URL y validación cronológica de trayectoria.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    const urlParams = new URLSearchParams(window.location.search);
    const activeTabParam = urlParams.get('tab');

    // === 0. PERSISTENCIA DE PESTAÑAS (POST-GUARDADO) ===
    // Si la URL contiene el parámetro 'tab', activamos esa pestaña inmediatamente
    if (activeTabParam) {
        const targetTabButton = document.querySelector(`#expedienteTabs button[data-bs-target="#${activeTabParam}"]`);
        if (targetTabButton) {
            bootstrap.Tab.getOrCreateInstance(targetTabButton).show();
        }
    }

    // === 1. NOTIFICACIÓN DE ÉXITO Y LIMPIEZA DE URL ===
    if (urlParams.has('updated') || urlParams.has('created')) {
        Swal.fire({
            icon: 'success',
            title: '¡Operación Exitosa!',
            text: 'La información del expediente se actualizó correctamente.',
            confirmButtonColor: '#4e73df'
        });
        
        // Limpiamos los parámetros de notificación para evitar re-lanzar el SweetAlert al refrescar
        const id = document.querySelector('input[name="id"]').value;
        const cleanUrl = window.location.pathname + "?id=" + id + (activeTabParam ? "&tab=" + activeTabParam : "");
        window.history.replaceState({}, document.title, cleanUrl);
    }

    // === 2. NAVEGACIÓN MANUAL DE PESTAÑAS ===
    const triggerTabList = document.querySelectorAll('#expedienteTabs button');
    triggerTabList.forEach(t => {
        t.addEventListener('click', function (e) {
            e.preventDefault();
            bootstrap.Tab.getOrCreateInstance(this).show();
        });
    });

    // === 3. LÓGICA DE CREACIÓN (+ Añadir) ===
    document.querySelectorAll('.btn-add-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.targetModal;
            const modal = document.querySelector(target);
            if (!modal) return;

            const form = modal.querySelector('form');
            if (form) form.reset();
            
            // Limpiamos el ID oculto para indicar que es un registro NUEVO
            if(form.querySelector('input[name="id"]')) form.querySelector('input[name="id"]').value = '';
            
            // Forzar habilitación de fecha fin en caso de venir de una edición previa
            const endField = form.querySelector('#work_end_date');
            if(endField) endField.disabled = false;

            const submitBtn = modal.querySelector('.btn-submit-modal');
            if (submitBtn) submitBtn.innerText = 'Guarda';
            
            bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    });

    // === 4. LÓGICA DE EDICIÓN (Clic en fila de tabla) ===
    document.querySelectorAll('.edit-row').forEach(row => {
        row.addEventListener('click', function(e) {
            // No abrir modal si se hace clic en el botón de borrar o en enlaces "Ver"
            if (e.target.closest('.btn-delete-record') || e.target.closest('.no-edit') || e.target.tagName === 'FORM') return;

            const target = this.dataset.targetModal;
            const modal = document.querySelector(target);
            if (!modal) return;

            const form = modal.querySelector('form');
            const data = JSON.parse(this.dataset.json);

            // Poblar campos del formulario dinámicamente
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if(input) {
                    if(input.type === 'checkbox') {
                        input.checked = data[key] == 1;
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

    // === 5. ELIMINAR REGISTROS (Formularios dentro de tablas) ===
    document.querySelectorAll('.btn-delete-record').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const f = this.closest('form');
            
            Swal.fire({
                title: '¿Confirmas la eliminación?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((res) => { if (res.isConfirmed) f.submit(); });
        });
    });

    // === 6. LÓGICA DE TRAYECTORIA LABORAL (Fechas y Cargo Actual) ===
    const chkCurrent = document.getElementById('check_current');
    const workEnd = document.getElementById('work_end_date');
    const workStart = document.getElementById('work_start_date');
    const formWork = document.querySelector('#modalWork form');

    if (chkCurrent && workEnd) {
        chkCurrent.addEventListener('change', function() { 
            workEnd.disabled = this.checked; 
            if (this.checked) {
                workEnd.value = ''; 
                workEnd.removeAttribute('required');
            } else {
                workEnd.setAttribute('required', 'required');
            }
        });
    }

    // Validación cronológica: Impide que FIN sea menor que INICIO
    if (formWork) {
        formWork.addEventListener('submit', function(e) {
            if (!chkCurrent.checked && workStart.value && workEnd.value) {
                if (new Date(workEnd.value) < new Date(workStart.value)) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de fechas',
                        text: 'La fecha de finalización debe ser posterior a la de inicio.',
                        confirmButtonColor: '#4e73df'
                    });
                }
            }
        });
    }

    // === 7. GESTIÓN DE FOTOGRAFÍA (CROPPER.JS) ===
    const btnPh = document.querySelector('.btn-change-photo');
    const inPh = document.getElementById('inputPhotoUpload');
    const imgCr = document.getElementById('imageToCrop');
    const modCr = document.getElementById('modalCrop');
    const svCr = document.getElementById('btnSaveCrop');
    let cropperInstance = null;

    if (btnPh) btnPh.addEventListener('click', () => inPh.click());
    
    if (inPh) {
        inPh.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (ev) => { 
                    imgCr.src = ev.target.result; 
                    bootstrap.Modal.getOrCreateInstance(modCr).show(); 
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (modCr) {
        modCr.addEventListener('shown.bs.modal', () => { 
            cropperInstance = new Cropper(imgCr, { aspectRatio: 1, viewMode: 1 }); 
        });
        modCr.addEventListener('hidden.bs.modal', () => { 
            if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; } 
        });
    }

    if (svCr) {
        svCr.addEventListener('click', function() {
            const canvas = cropperInstance.getCroppedCanvas({ width: 300, height: 300 });
            const base64Image = canvas.toDataURL('image/png');
            const professorId = document.querySelector('input[name="id"]').value;
            
            svCr.disabled = true;
            fetch('/diplomatic/public/academic/profesores/uploadPhoto', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${professorId}&image=${encodeURIComponent(base64Image)}`
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    document.getElementById('profile-img-preview').src = d.path;
                    bootstrap.Modal.getOrCreateInstance(modCr).hide();
                    Swal.fire({ icon: 'success', title: 'Foto actualizada', confirmButtonColor: '#4e73df' });
                }
            })
            .finally(() => { svCr.disabled = false; });
        });
    }
});