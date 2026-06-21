/**
 * MÓDULO: RECURSOS HUMANOS / NÓMINA
 * ARCHIVO: public/assets/js/resources_nomina_create.js
 * PROPÓSITO: Crear nómina (tipo + fecha de pago) y redirigir a manage.php.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', function () {
    const BASE = (window.APP_BASE_PATH || '') + '/resources/nomina';

    document.getElementById('btnCrearNomina')?.addEventListener('click', async () => {
        const tipoEl = document.querySelector('input[name="tipo"]:checked');
        const fecha  = document.getElementById('fecha_pago').value;

        if (!tipoEl) {
            Swal.fire({ icon: 'warning', title: 'Selecciona un tipo de nómina', confirmButtonColor: '#dc3545' });
            return;
        }
        if (!fecha) {
            Swal.fire({ icon: 'warning', title: 'Selecciona la fecha de pago', confirmButtonColor: '#dc3545' });
            return;
        }

        const fd = new FormData();
        fd.append('tipo', tipoEl.value);
        fd.append('fecha_pago', fecha);

        try {
            const resp = await fetch(`${BASE}/store`, { method: 'POST', body: fd }).then(r => r.json());
            if (!resp.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
                return;
            }
            window.location.href = `${BASE}/manage?id=${resp.nomina_id}`;
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error de conexión' });
        }
    });

    // Resaltar tarjeta seleccionada
    document.querySelectorAll('.n-tipo-radio').forEach(r => {
        r.addEventListener('change', () => {
            document.querySelectorAll('.n-tipo-card').forEach(c => c.classList.remove('n-tipo-selected'));
            r.closest('.n-tipo-card').classList.add('n-tipo-selected');
        });
    });
});