/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * ARCHIVO: public/assets/js/academic_centros_medicos.js
 * PROPÓSITO: Interactividad del catálogo de Centros Médicos: confirmación de borrado
 *            inteligente (inactivación lógica) y alertas de éxito/error recibidas por
 *            query string tras los POST con redirect (crear/editar/eliminar).
 * VERSIÓN: 1.1.0 - Fix de rutas: usa window.APP_BASE_PATH (inyectado por la vista) en vez
 *           de un basePath hardcodeado, para soportar la subcarpeta /diplomatic/public/.
 */

document.addEventListener('DOMContentLoaded', function () {
    const basePath = (window.APP_BASE_PATH || '') + '/academic/centros-medicos';
    const MySwal = (typeof Swal !== 'undefined') ? Swal : { fire: (obj) => alert(obj.text) };

    // === ALERTAS POR QUERY STRING (RESPUESTA DEL SERVIDOR TRAS REDIRECT) ===
    const urlParams = new URLSearchParams(window.location.search);
    const errorType = urlParams.get('error');

    if (errorType === 'duplicado') {
        MySwal.fire({
            icon: 'warning',
            title: 'Nombre duplicado',
            text: 'Ya existe un centro médico con ese nombre.',
            confirmButtonColor: '#f6c23e'
        });
    } else if (errorType === 'db') {
        MySwal.fire({
            icon: 'error',
            title: 'Error de Sistema',
            text: 'No se pudo procesar la solicitud.',
            confirmButtonColor: '#e74a3b'
        });
    }

    if (urlParams.get('created') || urlParams.get('updated')) {
        MySwal.fire({
            icon: 'success',
            title: '¡Operación Exitosa!',
            text: 'Los cambios se han aplicado correctamente.',
            timer: 2000,
            showConfirmButton: false
        });
    }

    const successType = urlParams.get('success');
    if (successType === 'inactivated') {
        MySwal.fire({
            icon: 'success',
            title: 'Centro médico inactivado',
            text: 'Estaba en uso por algún horario de práctica, así que se inactivó en lugar de eliminarse.',
            timer: 2500,
            showConfirmButton: false
        });
    } else if (successType === 'deleted') {
        MySwal.fire({
            icon: 'success',
            title: 'Centro médico eliminado',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // === BOTÓN ELIMINAR (SOLICITUD DE BORRADO INTELIGENTE) ===
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const id = this.dataset.id;
            const name = this.dataset.name;

            MySwal.fire({
                title: '¿Eliminar centro médico?',
                html: `Se procederá a eliminar: <b>${name}</b>.<br><small class="text-muted">Si está en uso por algún horario de práctica, se inactivará en lugar de eliminarse.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Sí, continuar',
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