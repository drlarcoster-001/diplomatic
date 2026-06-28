/**
 * MÓDULO: ACADÉMICO / IMPORTADOR
 * ARCHIVO: public/assets/js/academic_importador.js
 * PROPÓSITO: Confirmación antes de importar y resumen del período origen.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', () => {
    const selOrigen    = document.getElementById('selOrigen');
    const resumen      = document.getElementById('resumenOrigen');
    const btnImportar  = document.getElementById('btnImportar');
    const formImportador = document.getElementById('formImportador');
    const periodos     = window.PERIODOS_DATA || [];

    // Mostrar resumen del período origen seleccionado
    selOrigen?.addEventListener('change', () => {
        const id = parseInt(selOrigen.value);
        const p  = periodos.find(p => parseInt(p.id) === id);

        if (p) {
            resumen.innerHTML = `
                <div class="alert alert-info py-2 px-3 small mb-0">
                    <i class="bi bi-calendar-range me-1"></i>
                    <strong>${p.nombre}</strong> — 
                    Del ${p.fecha_inicio} al ${p.fecha_fin}
                    <span class="badge bg-${p.estado === 'Activo' ? 'success' : 'secondary'} ms-2">${p.estado}</span>
                </div>`;
            resumen.classList.remove('d-none');
        } else {
            resumen.classList.add('d-none');
        }
    });

    // Confirmación antes de importar
    btnImportar?.addEventListener('click', async () => {
        const origenId = selOrigen?.value;
        const codigo   = document.querySelector('input[name="periodo_code"]')?.value?.trim();
        const nombre   = document.querySelector('input[name="nombre"]')?.value?.trim();
        const inicio   = document.getElementById('inputInicio')?.value;
        const fin      = document.getElementById('inputFin')?.value;

        if (!origenId) {
            Swal.fire('Falta el período origen', 'Selecciona el período a clonar.', 'warning');
            return;
        }
        if (!codigo || !nombre || !inicio || !fin) {
            Swal.fire('Datos incompletos', 'Completa todos los campos obligatorios del nuevo período.', 'warning');
            return;
        }
        if (inicio > fin) {
            Swal.fire('Fechas inválidas', 'La fecha de inicio no puede ser mayor que la fecha fin.', 'warning');
            return;
        }

        const origenNombre = periodos.find(p => parseInt(p.id) === parseInt(origenId))?.nombre || '';

        const confirmed = await Swal.fire({
            title: '¿Confirmar importación?',
            html: `Se clonará la configuración de:<br><strong>${origenNombre}</strong><br>
                   al nuevo período:<br><strong>${nombre} (${codigo})</strong>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, importar',
            cancelButtonText: 'Cancelar'
        });

        if (confirmed.isConfirmed) {
            Swal.fire({
                title: 'Importando...',
                text: 'Por favor espera mientras se clonan los datos.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            formImportador.submit();
        }
    });

    // REVERSAR IMPORTACIÓN
    document.querySelectorAll('.btn-reversar').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id     = btn.dataset.id;
            const nombre = btn.dataset.nombre;

            const confirmed = await Swal.fire({
                title: '¿Reversar esta importación?',
                html: `Se eliminará <strong>permanentemente</strong> el período:<br><strong>${nombre}</strong><br>
                       junto con todas sus cohortes, ofertas, grupos, profesores y horarios.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Sí, reversar',
                cancelButtonText: 'Cancelar'
            });

            if (!confirmed.isConfirmed) return;

            Swal.fire({ title: 'Reversando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const fd = new FormData();
            fd.append('periodo_destino_id', id);

            try {
                const resp = await fetch(`${window.APP_BASE_PATH}/academic/importador/reversar`, {
                    method: 'POST', body: fd
                }).then(r => r.json());

                if (resp.success) {
                    await Swal.fire({ icon: 'success', title: '¡Reversado!', text: resp.message, timer: 2000, showConfirmButton: false });
                    window.location.reload();
                } else {
                    Swal.fire('Error', resp.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            }
        });
    });
});