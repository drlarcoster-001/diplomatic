/**
 * MÓDULO: PORTAL DOCENTE / REGISTRAR ASISTENCIA
 * ARCHIVO: public/assets/js/professor_registrar_asistencia.js
 * PROPÓSITO: Contadores, resaltado de filas, botones masivos y envío AJAX.
 *            Al guardar exitosamente abre el PDF de asistencia marcada.
 * VERSIÓN: 1.1.0 - Al guardar abre PDF en nueva pestaña + botón volver.
 */

function actualizarContadores() {
    const p = document.querySelectorAll('input[value="1"].radio-asistencia:checked').length;
    const a = document.querySelectorAll('input[value="0"].radio-asistencia:checked').length;
    document.getElementById('contPresentes').textContent = p;
    document.getElementById('contAusentes').textContent  = a;
}

function actualizarFilas() {
    document.querySelectorAll('.radio-asistencia').forEach(r => {
        if (!r.checked) return;
        const fila = r.closest('tr');
        if (!fila) return;
        fila.classList.remove('fila-presente', 'fila-ausente');
        fila.classList.add(r.value === '1' ? 'fila-presente' : 'fila-ausente');
    });
}

function marcarTodos(valor) {
    document.querySelectorAll(`input[value="${valor}"].radio-asistencia`).forEach(r => r.checked = true);
    actualizarContadores();
    actualizarFilas();
}

document.addEventListener('DOMContentLoaded', () => {

    actualizarContadores();
    actualizarFilas();

    document.querySelectorAll('.radio-asistencia').forEach(r => {
        r.addEventListener('change', () => {
            actualizarContadores();
            actualizarFilas();
        });
    });

    document.getElementById('btnTodos')?.addEventListener('click',   () => marcarTodos('1'));
    document.getElementById('btnNinguno')?.addEventListener('click', () => marcarTodos('0'));

    document.getElementById('btnGuardar')?.addEventListener('click', () => {
        const radios = document.querySelectorAll('.radio-asistencia:checked');
        if (!radios.length) {
            Swal.fire('Atención', 'Marca la asistencia de al menos un estudiante.', 'warning');
            return;
        }

        const asistencia = {};
        radios.forEach(r => {
            const eid = r.getAttribute('name').match(/\d+/)[0];
            asistencia[eid] = r.value;
        });

        const form = new FormData();
        form.append('sesion_id', window.SESION_ID);
        for (const [k, v] of Object.entries(asistencia)) {
            form.append(`asistencia[${k}]`, v);
        }

        Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch(`${window.BASE_PATH}/professor/registrar-asistencia/guardar`, { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Asistencia guardada!',
                        html: `<p class="mb-3">${data.message}</p>
                               <a href="${data.pdf_url}" target="_blank"
                                  class="btn btn-primary rounded-pill px-4">
                                  <i class="bi bi-printer me-1"></i> Imprimir Asistencia
                               </a>`,
                        showConfirmButton: true,
                        confirmButtonText: 'Volver a mis sesiones',
                        confirmButtonColor: '#6c757d',
                    }).then(() => {
                        window.location.href = `${window.BASE_PATH}/professor/registrar-asistencia?offering_id=${window.OFFERING_ID}`;
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error'));
    });
});