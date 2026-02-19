/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: public/assets/js/academic_grupos.js
 * Propósito: Lógica de interfaz para el catálogo maestro de Grupos.
 */

document.addEventListener('DOMContentLoaded', function() {
    const basePath = '/diplomatic/public/academic/grupos';

    // 1. NUEVO GRUPO
    const btnNuevo = document.getElementById('btnOpenNuevo');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function() {
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

    // 2. EDITAR GRUPO
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.closest('.btn-edit').dataset.id;
            
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
                        new bootstrap.Modal(document.getElementById('modalGrupoForm')).show();
                    }
                });
        });
    });

    // 3. ELIMINAR GRUPO
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.closest('.btn-delete').dataset.id;
            const name = this.closest('.btn-delete').dataset.name;

            fetch(`${basePath}/logAccess?action=DELETE_ATTEMPT&id=${id}`);

            Swal.fire({
                title: '¿Eliminar Grupo?',
                html: `Se dará de baja el registro: <strong>${name}</strong>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
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