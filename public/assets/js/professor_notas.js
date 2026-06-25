/**
 * MÓDULO: PORTAL DOCENTE / CARGAR NOTAS
 * ARCHIVO: public/assets/js/professor_notas.js
 * PROPÓSITO: Actualización de badges de estado en tiempo real al escribir
 *            la nota, contador de notas cargadas, validación de rango 0-20,
 *            guardado AJAX y generación de acta AJAX.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', () => {
    const BASE        = window.BASE_PATH    || '';
    const OFFERING_ID = window.OFFERING_ID  || 0;
    const MODALIDAD   = window.MODALIDAD    || '';
    const NOTA_MINIMA = window.NOTA_MINIMA  || 15;

    // =========================================================================
    // ACTUALIZAR BADGE Y FILA AL ESCRIBIR
    // =========================================================================
    function actualizarFila(input) {
        const eid   = input.dataset.eid;
        const nota  = parseFloat(input.value);
        const badge = document.getElementById('estado-' + eid);
        const fila  = document.getElementById('fila-' + eid);
        if (!badge || !fila) return;

        fila.classList.remove('fila-aprobado', 'fila-reprobado');

        if (isNaN(nota) || input.value === '') {
            badge.innerHTML = '<span class="badge bg-light text-muted border">Sin nota</span>';
        } else if (nota >= NOTA_MINIMA) {
            badge.innerHTML = '<span class="badge bg-success">Aprobado</span>';
            fila.classList.add('fila-aprobado');
        } else {
            badge.innerHTML = '<span class="badge bg-danger">Reprobado</span>';
            fila.classList.add('fila-reprobado');
        }

        actualizarContador();
    }

    function actualizarContador() {
        const inputs   = document.querySelectorAll('.input-nota');
        const cargadas = [...inputs].filter(i => i.value !== '').length;
        const cont = document.getElementById('contNotas');
        if (cont) cont.textContent = cargadas;

        // Mostrar u ocultar botón Generar Acta
        const btnActa = document.getElementById('btnGenerarActa');
        if (btnActa) {
            if (cargadas === inputs.length) {
                btnActa.classList.remove('d-none');
            } else {
                btnActa.classList.add('d-none');
            }
        }
    }

    document.querySelectorAll('.input-nota').forEach(input => {
        // Inicializar filas con notas ya cargadas
        if (input.value !== '') actualizarFila(input);

        input.addEventListener('input', () => actualizarFila(input));

        // Validar rango al salir del campo
        input.addEventListener('blur', () => {
            if (input.value === '') return;
            let val = parseFloat(input.value);
            if (val < 0) { input.value = '0.00'; actualizarFila(input); }
            if (val > 20) { input.value = '20.00'; actualizarFila(input); }
        });
    });

    // Inicializar contador
    actualizarContador();

    // =========================================================================
    // GUARDAR NOTAS
    // =========================================================================
    document.getElementById('btnGuardar')?.addEventListener('click', async () => {
        const inputs = document.querySelectorAll('.input-nota');
        if (!inputs.length) return;

        const fd = new FormData();
        fd.append('offering_id', OFFERING_ID);
        fd.append('modalidad', MODALIDAD);

        let tieneNotas = false;
        let hayError   = false;

        inputs.forEach(input => {
            if (input.value === '') return;
            const nota = parseFloat(input.value);
            if (nota < 0 || nota > 20) {
                hayError = true;
                return;
            }
            fd.append(`notas[${input.dataset.eid}]`, input.value);
            tieneNotas = true;
        });

        if (hayError) {
            Swal.fire('Error', 'Hay notas fuera del rango permitido (0-20).', 'warning');
            return;
        }
        if (!tieneNotas) {
            Swal.fire('Atención', 'Ingresa al menos una nota antes de guardar.', 'warning');
            return;
        }

        Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const resp = await fetch(`${BASE}/professor/notas/guardar`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire('Error', resp.message, 'error'); return; }

            Swal.fire({ icon: 'success', title: '¡Listo!', text: resp.message, timer: 1800, showConfirmButton: false });

            if (resp.todas_cargadas) {
                document.getElementById('btnGenerarActa')?.classList.remove('d-none');
            }
        } catch (e) {
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        }
    });

    // =========================================================================
    // GENERAR ACTA
    // =========================================================================
    document.getElementById('btnGenerarActa')?.addEventListener('click', async () => {
        const confirmed = await Swal.fire({
            title: '¿Generar y enviar el acta?',
            text: 'Se enviará al administrador para su aprobación.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, enviar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmed.isConfirmed) return;

        const fd = new FormData();
        fd.append('offering_id', OFFERING_ID);
        fd.append('modalidad', MODALIDAD);

        try {
            const resp = await fetch(`${BASE}/professor/notas/generar-acta`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) { Swal.fire('Error', resp.message, 'error'); return; }
            Swal.fire({ icon: 'success', title: '¡Acta enviada!', text: resp.message, timer: 2000, showConfirmButton: false })
                .then(() => window.location.reload());
        } catch (e) {
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        }
    });
});