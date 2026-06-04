/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: public/assets/js/academic_diplomados.js
 * Propósito: Auto-guardado, notificaciones de éxito y VISTA PREVIA CON EXPORTACIÓN PDF.
 * Version: 1.6.0 - Integración de disparador para generación de ficha técnica en PDF.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    const basePath = '/diplomatic/public/academic/diplomados';
    const form = document.getElementById('formDiplomado') || document.getElementById('formEditDiplomado');
    const modalElement = document.getElementById('previewModal');
    const btnDownloadPDF = document.getElementById('btnDownloadPDF'); // <--- NUEVO: Seleccionamos el botón
    
    let currentId = document.querySelector('input[name="id"]')?.value || 0;
    let autoSaveTimer;

    // --- 0. DETECTOR DE NOTIFICACIONES TRAS REDIRECCIÓN ---
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('success') === '1') {
        Swal.fire({
            icon: 'success',
            title: '¡Registro Exitoso!',
            text: 'El diplomado ha sido creado correctamente en el catálogo.',
            timer: 3000,
            showConfirmButton: false,
            confirmButtonColor: '#4e73df'
        });
    }

    if (urlParams.get('updated') === '1') {
        Swal.fire({
            icon: 'success',
            title: '¡Actualizado!',
            text: 'La información del diplomado se guardó correctamente.',
            timer: 2500,
            showConfirmButton: false,
            confirmButtonColor: '#4e73df'
        });
    }

    if (urlParams.get('deleted') === '1') {
        Swal.fire({
            icon: 'success',
            title: 'Eliminado',
            text: 'El registro ha sido borrado físicamente del sistema.',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // --- 1. CAPTURA DE ESTADO INICIAL ---
    const getFormSnapshot = () => {
        if (!form) return '';
        const data = new FormData(form);
        return new URLSearchParams(data).toString();
    };

    const initialState = getFormSnapshot();

    // --- 2. MOTOR DE AUTO-GUARDADO SILENCIOSO (DEBOUNCE) ---
    const autoSave = () => {
        const codeField = document.querySelector('input[name="code"]');
        if (!codeField || !codeField.value.trim()) return;

        const formData = new FormData(form);
        if (currentId) formData.append('id', currentId);

        fetch(`${basePath}/ajaxAutoSave`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok && data.id) {
                currentId = data.id;
                console.log('Sincronización silenciosa exitosa.');
            }
        })
        .catch(err => {});
    };

    const debounceSave = () => {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(autoSave, 2000);
    };

    const monitoredFields = ['input[name="code"]', 'textarea[name="name"]', 'textarea[name="description"]', 'textarea[name="directed_to"]', '#total_hours'];
    monitoredFields.forEach(selector => {
        const field = document.querySelector(selector);
        if (field) {
            field.addEventListener('input', debounceSave);
            field.addEventListener('blur', autoSave);
        }
    });

    // --- 3. INTERCEPCIÓN DEL BOTÓN VOLVER ---
    const btnVolver = document.querySelector('a[href*="/academic/diplomados"]');
    if (btnVolver && form) {
        btnVolver.addEventListener('click', function(e) {
            const currentState = getFormSnapshot();

            if (initialState !== currentState) {
                e.preventDefault();
                const targetUrl = this.href;

                Swal.fire({
                    title: 'Modificaciones detectadas',
                    text: 'Se han hecho modificaciones en este diplomado ¿desea guardar o descartar?',
                    icon: 'warning',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: '<i class="bi bi-save me-1"></i> Guardar',
                    denyButtonText: '<i class="bi bi-trash me-1"></i> Descartar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#4e73df',
                    denyButtonColor: '#858796',
                }).then((result) => {
                    if (result.isConfirmed) {
                        const statusSelect = document.getElementById('status_select');
                        const statusInput = document.createElement('input');
                        statusInput.type = 'hidden';
                        statusInput.name = 'status';
                        statusInput.value = (statusSelect && statusSelect.value === 'ACTIVO') ? 'ACTIVO' : 'BORRADOR';
                        form.appendChild(statusInput);
                        form.submit();
                    } else if (result.isDenied) {
                        window.location.href = targetUrl;
                    }
                });
            }
        });
    }

    // --- 4. GESTIÓN DEL BOTÓN GUARDAR (FORMULARIO NUEVO) ---
    if (form) {
        form.addEventListener('submit', function(e) {
            const isEdit = document.getElementById('formEditDiplomado') || document.getElementById('status_select');
            if (isEdit) return; 

            e.preventDefault();
            Swal.fire({
                title: 'Finalizar Registro',
                text: '¿Desea activar el diplomado o guardarlo como borrador?',
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Activar Ahora',
                denyButtonText: 'Dejar en Borrador',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#198754',
                denyButtonColor: '#ffc107',
                customClass: { denyButton: 'text-dark' }
            }).then((result) => {
                if (result.isConfirmed || result.isDenied) {
                    const statusInput = document.createElement('input');
                    statusInput.type = 'hidden';
                    statusInput.name = 'status';
                    statusInput.value = result.isConfirmed ? 'ACTIVO' : 'BORRADOR';
                    form.appendChild(statusInput);
                    form.submit();
                }
            });
        });
    }

    // --- 5. VISTA PREVIA (MODIFICADO PARA SOPORTAR PDF) ---
    const rows = document.querySelectorAll('.row-preview');
    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.btn-action')) return;

            const id = this.getAttribute('data-id');
            if (!id || !modalElement) return;

            // --- NUEVO: Pasamos el ID al botón de descarga para que sepa qué PDF generar ---
            if (btnDownloadPDF) {
                btnDownloadPDF.setAttribute('data-id', id);
            }

            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        const d = data.diplomado;
                        
                        document.getElementById('pv_name').innerText = d.name;
                        document.getElementById('pv_code').innerText = d.code;
                        document.getElementById('pv_directed').innerText = d.directed_to || 'Personal profesional.';
                        document.getElementById('pv_description').innerText = d.description || 'Sin descripción académica.';
                        document.getElementById('pv_hours').innerText = d.total_hours || '0';

                        const renderList = (contId, items, field) => {
                            const cont = document.getElementById(contId);
                            if (cont) {
                                cont.innerHTML = items.length 
                                    ? items.map(i => `<li class="mb-1">${i[field]}</li>`).join('') 
                                    : '<li class="text-muted italic">No especificado.</li>';
                            }
                        };

                        renderList('pv_requirements', data.requirements, 'requirement_text');
                        renderList('pv_conditions', data.conditions, 'condition_text');

                        bootstrap.Modal.getOrCreateInstance(modalElement).show();
                    }
                });
        });
    });

    // --- NUEVO: EVENTO CLICK PARA DESCARGAR EL PDF ---
    if (btnDownloadPDF) {
        btnDownloadPDF.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (!id) {
                Swal.fire('Error', 'No se pudo identificar el diplomado para generar la ficha.', 'error');
                return;
            }
            // Abrimos la ruta del controlador en una pestaña nueva para que procese el PDF
            window.open(`${basePath}/exportPdf?id=${id}`, '_blank');
        });
    }

    // --- 6. ELIMINACIÓN INTELIGENTE ---
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const id = this.getAttribute('data-id');
            
            Swal.fire({
                title: '¿Eliminar registro?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
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

    // Manejo de error de dependencias
    if (urlParams.get('error') === 'has_dependencies') {
        Swal.fire({
            icon: 'error',
            title: 'No se puede eliminar',
            text: 'Este registro no se puede eliminar porque ha sido utilizado.',
            confirmButtonColor: '#4e73df'
        });
    }
});

function addRow(containerId, name) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const div = document.createElement('div');
    div.className = 'input-group mb-3 animate__animated animate__fadeIn';
    div.innerHTML = `
        <textarea name="${name}[]" class="form-control" rows="2" placeholder="Describa el punto aquí..."></textarea>
        <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
    `;
    container.appendChild(div);
    div.querySelector('textarea').focus();
}