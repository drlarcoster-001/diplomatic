/**
 * MÓDULO - public/assets/js/company.js
 * Lógica de comunicación AJAX para el submódulo de Empresa.
 * Maneja el envío de datos y alertas de confirmación.
 */

async function saveCompanyInfo() {
    const form = document.getElementById('form-company');
    const basePath = form.getAttribute('data-basepath');
    
    Swal.fire({
        title: '¿Confirmar actualización?',
        text: "Los datos de identidad institucional serán guardados.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Procesando...', didOpen: () => Swal.showLoading() });

            try {
                const response = await fetch(`${basePath}/settings/empresa/save`, {
                    method: 'POST',
                    body: new FormData(form)
                });
                const data = await response.json();

                if (data.ok) {
                    Swal.fire('¡Éxito!', data.msg, 'success');
                } else {
                    Swal.fire('Error', data.msg, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'No se pudo procesar la solicitud.', 'error');
            }
        }
    });
}