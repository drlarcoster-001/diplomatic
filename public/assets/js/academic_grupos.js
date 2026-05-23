/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: public/assets/js/academic_grupos.js
 * Propósito: Lógica de interfaz para Grupos con borrado físico condicional y auditoría.
 * Version: 1.2.0 - Mensajes genéricos, manejo de dependencias y auditoría de eventos.
 */

document.addEventListener('DOMContentLoaded', function() {
    const basePath = '/diplomatic/public/academic/grupos';

    // --- 0. GESTIÓN DE ERRORES DE INTEGRIDAD (VÍA URL) ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error') === 'has_dependencies') {
        Swal.fire({
            icon: 'error',
            title: 'No se puede eliminar',
            text: 'Este registro no se puede eliminar porque ha sido utilizado.',
            confirmButtonColor: '#4e73df'
        });
    }

    // --- 1. REGISTRAR NUEVO GRUPO ---
    const btnNuevo = document.getElementById('btnOpenNuevo');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
            // Auditoría: Registro de apertura de formulario
            fetch(`${basePath}/logAccess?action=CREATE_FORM`);
            
            const form = document.getElementById('formGrupo');
            if (form) {
                form.reset();
                document.getElementById('field_id').value = '';
                form.action = `${basePath}/save`;
                document.querySelector('#modalGrupoForm .modal-title').innerText = 'Registrar Nuevo Grupo';
            }
        });
    }

    // --- 2. EDITAR GRUPO ---
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            
            // Auditoría: Registro de intento de edición
            fetch(`${basePath}/logAccess?action=EDIT_FORM&id=${id}`);

            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        const g = data.grupo;
                        const form = document.getElementById('formGrupo');
                        
                        form.action = `${basePath}/update`;
                        document.getElementById('field_id').value = g.id;
                        document.getElementById('field_name').value = g.name;
                        document.getElementById('field_modality').value = g.modality;
                        document.getElementById('field_desc').value = g.description;

                        document.querySelector('#modalGrupoForm .modal-title').innerText = 'Editar Grupo';
                        // Inicialización segura del modal de Bootstrap
                        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalGrupoForm'));
                        modal.show();
                    }
                });
        });
    });

    // --- 3. ELIMINAR GRUPO (MENSAJE GENÉRICO Y BORRADO FÍSICO) ---
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;

            // Auditoría: Registro de intento de borrado
            fetch(`${basePath}/logAccess?action=DELETE_ATTEMPT&id=${id}`);

            Swal.fire({
                title: '¿Eliminar registro?',
                text: "¿Está seguro de eliminar este grupo del catálogo?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Creamos formulario dinámico para POST seguro
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