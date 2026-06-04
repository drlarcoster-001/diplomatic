/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: public/assets/js/academic_campuses.js
 * Propósito: Gestión interactiva de sedes con blindaje contra errores de nulidad y carga dinámica segura.
 * Version: 1.1.1 - Versión Maestra. Implementación de validaciones preventivas de DOM y optimización de flujo Fetch.
 */

document.addEventListener('DOMContentLoaded', function() {
    const basePath = '/diplomatic/public/academic/campuses';

    // Helper para asignar valores de forma segura evitando errores de 'null'
    const safeSetVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val || '';
    };

    // === 0. NOTIFICACIONES DE ÉXITO ===
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('created') || urlParams.has('updated') || urlParams.has('deleted')) {
        let message = 'Operación realizada con éxito.';
        if (urlParams.has('created')) message = 'La sede ha sido registrada correctamente.';
        if (urlParams.has('updated')) message = 'La información de la sede ha sido actualizada.';
        if (urlParams.has('deleted')) message = 'La sede ha sido procesada correctamente.';

        Swal.fire({
            icon: 'success',
            title: '¡Logrado!',
            text: message,
            confirmButtonColor: '#4e73df'
        });
        
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // === 1. LÓGICA PARA NUEVA SEDE ===
    const btnNuevo = document.getElementById('btnOpenNuevoCampus');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            const form = document.getElementById('formCampus');
            const modalTitle = document.querySelector('#modalCampusForm .modal-title');
            
            if (form) {
                form.reset();
                form.action = `${basePath}/save`;
                safeSetVal('campus_field_id', '');
                if (modalTitle) modalTitle.innerText = 'Registrar Nueva Sede';
            }
        });
    }

    // === 2. LÓGICA PARA EDITAR SEDE (Carga Dinámica) ===
    document.querySelectorAll('.btn-edit-campus').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const form = document.getElementById('formCampus');
            const modalTitle = document.querySelector('#modalCampusForm .modal-title');

            // Consultamos los detalles al servidor (Esto disparará el VIEW_DETAILS en PHP)
            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok && data.campus) {
                        if (form) form.action = `${basePath}/update`;
                        
                        // Seteo seguro de campos
                        safeSetVal('campus_field_id', data.campus.id);
                        safeSetVal('campus_field_name', data.campus.name);
                        
                        if (modalTitle) modalTitle.innerText = 'Modificar Sede';
                        
                        const modalElement = document.getElementById('modalCampusForm');
                        if (modalElement) {
                            bootstrap.Modal.getOrCreateInstance(modalElement).show();
                        }
                    } else {
                        Swal.fire('Error', 'No se pudieron cargar los datos de la sede.', 'error');
                    }
                })
                .catch(err => {
                    console.error('Error en fetch:', err);
                    Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
                });
        });
    });

    // === 3. LÓGICA PARA ELIMINAR SEDE (Borrado Inteligente) ===
    document.querySelectorAll('.btn-delete-campus').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;

            Swal.fire({
                title: '¿Eliminar sede?',
                html: `¿Estás seguro de procesar la sede: <b>${name}</b>?<br><small class="text-muted">El sistema determinará si procede eliminación física o lógica.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Sí, procesar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
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
});