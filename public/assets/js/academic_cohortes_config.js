/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: public/assets/js/academic_cohortes_config.js
 * Propósito: Scripts de la configuración avanzada de cohortes (Estatus / Borrado físico).
 */

document.addEventListener('DOMContentLoaded', function() {
    const basePath = '/diplomatic/public/academic/cohortes-config';

    // 0. DETECTAR ERRORES ENVIADOS POR EL CONTROLADOR 
    const errorContainer = document.getElementById('error-container');
    if (errorContainer && errorContainer.dataset.error === 'has_movements') {
        Swal.fire({
            title: 'Error de Borrado', 
            text: 'La cohorte tiene registros vinculados (inscripciones, pagos o grupos). No puede ser borrada físicamente.', 
            icon: 'error',
            confirmButtonColor: '#d33'
        });
    }

    // 1. ABRIR MODAL PARA FORZAR ESTATUS
    document.querySelectorAll('.btn-status').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            
            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        const c = data.cohorte;
                        document.getElementById('status_id').value = c.id;
                        document.getElementById('status_name').value = `[${c.cohort_code}] ${c.name}`;
                        
                        const select = document.getElementById('status_select');
                        for (let i = 0; i < select.options.length; i++) {
                            if (select.options[i].value === c.cohort_status) {
                                select.selectedIndex = i;
                                break;
                            }
                        }
                        
                        new bootstrap.Modal(document.getElementById('modalForceStatus')).show();
                    }
                });
        });
    });

    // 2. BORRADO FÍSICO (HARD DELETE)
    document.querySelectorAll('.btn-hard-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            const name = this.dataset.name;

            Swal.fire({
                title: '¿ELIMINAR DEFINITIVAMENTE?',
                html: `<p class="text-danger">Se intentará borrar de la base de datos:<br><strong>${name}</strong></p>
                       <p class="small text-muted">Esta acción es irreversible y solo procederá si la cohorte no tiene ningún movimiento (inscripciones, grupos) registrado.</p>`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, Borrar Físicamente',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const f = document.createElement('form');
                    f.method = 'POST'; 
                    f.action = `${basePath}/hardDelete`;
                    
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