/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS
 * ARCHIVO: public/assets/js/financial_payment_validations.js
 * PROPÓSITO: Enrutamiento dinámico y carga de notificaciones de pagos pendientes.
 * VERSIÓN: 1.1.1 - Ajuste de etiquetas para Efectivo y soporte multi-tabla.
 */

$(document).ready(function() {
    // 1. Cargar las notificaciones al abrir la pantalla
    loadPendingCounts();

    // 2. Capturamos el clic en cualquier tarjeta
    $('.card-financial-option').on('click', function() {
        const route = $(this).data('route');
        if (route) {
            // Manejo especial para la ruta de reportes si no está dentro de payment_validations
            const targetPath = (route === 'reportes') ? '/financial/reports' : `/financial/payment_validations/${route}`;
            window.location.href = BASE_URL + targetPath;
        }
    });

    // Opcional: Recargar conteos cada 2 minutos para mantener la oficina alerta
    setInterval(loadPendingCounts, 120000);
});

function loadPendingCounts() {
    $.ajax({
        url: BASE_URL + '/financial/payment_validations/getPendingCounts',
        method: 'GET',
        dataType: 'json',
        success: function(result) {
            if (result.ok) {
                const counts = result.data;
                
                for (const [method, count] of Object.entries(counts)) {
                    const badge = $(`#badge-${method}`);
                    
                    if (count > 0) {
                        // Personalizamos el texto según el método
                        let label = (method === 'EFECTIVO') ? 'Pendiente' : 'Pago Pendiente';
                        if (count !== 1) label += 's'; // Pluralización

                        badge.text(`${count} ${label}`);
                        badge.removeClass('d-none').addClass('animate__animated animate__headShake');
                    } else {
                        badge.addClass('d-none').text('');
                    }
                }
            }
        },
        error: function() {
            console.error("Fallo de conexión al cargar notificaciones.");
        }
    });
}