/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: public/assets/js/academic_cohortes_config.js
 * PROPÓSITO: Proporcionar la lógica de interacción para el panel de configuración técnica de cohortes. Gestiona la recuperación de detalles vía AJAX, el forzado de estados operativos y el borrado físico de registros mediante un flujo de doble factor de confirmación (validación por texto).
 * ACTUALIZACIÓN: Implementación de detectores de errores por integridad referencial mediante parámetros de URL. Se ha integrado el manejo de la alerta 'Acceso Denegado' cuando el controlador bloquea una eliminación física debido a la existencia de ofertas académicas vinculadas, garantizando que el usuario reciba una retroalimentación clara sobre la restricción de seguridad.
 * VERSIÓN: 1.2.0
 */

document.addEventListener('DOMContentLoaded', function() {
    const basePath = '/diplomatic/public/academic/cohortes-config';
    const MySwal = (typeof Swal !== 'undefined') ? Swal : { fire: (obj) => alert(obj.text) };

    // === 0. GESTIÓN DE ALERTAS (RESPUESTA DEL CONTROLADOR) === 
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const success = urlParams.get('deleted') || urlParams.get('updated');

    if (error === 'has_movements') {
        MySwal.fire({
            title: 'Acción Bloqueada', 
            text: 'Esta cohorte posee ofertas académicas vinculadas. La integridad referencial impide su eliminación física para no dejar registros huérfanos.', 
            icon: 'error',
            confirmButtonColor: '#4e73df'
        });
    } else if (error === 'system_fail') {
        MySwal.fire({
            title: 'Error de Sistema',
            text: 'Ocurrió un fallo en la transacción de base de datos. Intente nuevamente.',
            icon: 'warning'
        });
    }

    if (success) {
        MySwal.fire({
            title: '¡Operación Exitosa!',
            text: 'Los cambios se han aplicado correctamente.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // === 1. MODAL PARA FORZAR ESTATUS ===
    document.querySelectorAll('.btn-status').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            
            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        const c = data.cohorte;
                        const modalEl = document.getElementById('modalForceStatus');
                        
                        document.getElementById('status_id').value = c.id;
                        document.getElementById('status_name').value = `[${c.cohort_code}] ${c.name}`;
                        
                        const select = document.getElementById('status_select');
                        if (select) {
                            for (let i = 0; i < select.options.length; i++) {
                                if (select.options[i].value === c.cohort_status) {
                                    select.selectedIndex = i;
                                    break;
                                }
                            }
                        }
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                });
        });
    });

    // === 2. BORRADO FÍSICO CON DOBLE CONFIRMACIÓN ===
    document.querySelectorAll('.btn-hard-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            const name = this.dataset.name;

            // Image of a database deletion workflow with integrity checks
            

            MySwal.fire({
                title: '¿ELIMINAR DEFINITIVAMENTE?',
                html: `<div class="text-start">
                        <p class="text-danger fw-bold">Atención: Esta acción borrará físicamente el registro de la base de datos:</p>
                        <p class="bg-light p-2 border"><strong>${name}</strong></p>
                        <p class="small text-muted">Esta operación es irreversible. Para proceder, escriba <strong>ELIMINAR</strong> en el campo de abajo.</p>
                       </div>`,
                icon: 'warning',
                input: 'text',
                inputPlaceholder: 'Escriba ELIMINAR aquí...',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Confirmar Borrado Físico',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value || value.toUpperCase() !== 'ELIMINAR') {
                        return 'Debe escribir la palabra exacta para continuar';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const f = document.createElement('form');
                    f.method = 'POST'; 
                    f.action = `${basePath}/hardDelete`;
                    
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'id'; i.value = id;
                    
                    f.appendChild(i); 
                    document.body.appendChild(f); 
                    f.submit();
                }
            });
        });
    });

    // === SELECCIÓN MASIVA ===
const checkAll   = document.getElementById('checkAll');
const panelMasivo = document.getElementById('panelMasivo');
const countSelected = document.getElementById('countSelected');

function updatePanel() {
    const checked = document.querySelectorAll('.check-item:checked');
    if (checked.length > 0) {
        panelMasivo.classList.remove('d-none');
        countSelected.innerText = checked.length;
    } else {
        panelMasivo.classList.add('d-none');
    }
}

if (checkAll) {
    checkAll.addEventListener('change', function() {
        document.querySelectorAll('.check-item').forEach(c => c.checked = this.checked);
        updatePanel();
    });
}

document.querySelectorAll('.check-item').forEach(c => {
    c.addEventListener('change', updatePanel);
});

function ejecutarMasivo(accion) {
    const checked = document.querySelectorAll('.check-item:checked');
    if (checked.length === 0) return;

    const label = accion === 'reactivar' ? 'reactivar' : 'archivar';
    MySwal.fire({
        title: `¿${label.charAt(0).toUpperCase() + label.slice(1)} ${checked.length} cohorte(s)?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: accion === 'reactivar' ? '#198754' : '#6c757d',
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('accionMasiva').value = accion;
            const hiddenIds = document.getElementById('hiddenIds');
            hiddenIds.innerHTML = '';
            checked.forEach(c => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = c.value;
                hiddenIds.appendChild(input);
            });
            document.getElementById('formMasivo').submit();
        }
    });
}

const btnReactivar = document.getElementById('btnReactivar');
const btnArchivar  = document.getElementById('btnArchivar');
if (btnReactivar) btnReactivar.addEventListener('click', () => ejecutarMasivo('reactivar'));
if (btnArchivar)  btnArchivar.addEventListener('click',  () => ejecutarMasivo('archivar'));
});