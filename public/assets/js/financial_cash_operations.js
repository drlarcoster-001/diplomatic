/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN
 * ARCHIVO: public/assets/js/financial_cash_operations.js
 * PROPÓSITO: Manejo de navegación por tarjetas a los módulos de validación específicos.
 * VERSIÓN: 1.3.0
 * * NOTA PARA PROGRAMADORES:
 * Las rutas deben coincidir EXACTAMENTE con las definidas en app/core/Bootstrap.php.
 * Se ha cambiado 'physical' por 'efectivo' para corregir el redireccionamiento al dashboard.
 */

$(document).ready(function() {
    "use strict";

    // Verificación de constante global para el equipo
    if (typeof BASE_URL === 'undefined') {
        console.error("🚨 BASE_URL no está definida. Verifique el encabezado de la vista.");
    }

    // Manejo de clics en las tarjetas de opción financiera
    $('.card-financial-option').on('click', function() {
        const route = $(this).data('route');
        
        if (!route) {
            console.warn("⚠️ Intento de navegación sin ruta definida en data-route.");
            return;
        }

        // Construcción de la URL destino según la estructura del controlador
        let targetUrl = '';

        /**
         * LOG DE RASTREO PARA EL EQUIPO:
         * Permite ver en consola qué ruta se está intentando disparar antes del redireccionamiento.
         */
        console.log(`🚀 [RASTREO] Iniciando navegación hacia: ${route}`);

        switch(route) {
            case 'pagomovil':
                targetUrl = `${BASE_URL}/financial/cash-operations/pagomovil`;
                break;
            case 'zelle':
                targetUrl = `${BASE_URL}/financial/cash-operations/zelle`;
                break;
            case 'binance':
                targetUrl = `${BASE_URL}/financial/cash-operations/binance`;
                break;
            case 'efectivo':
                /**
                 * CORRECCIÓN CRÍTICA:
                 * Antes apuntaba a /physical. Se cambia a /efectivo para sincronizar 
                 * con Bootstrap.php v1.9.0 y evitar el rebote al dashboard.
                 */
                targetUrl = `${BASE_URL}/financial/cash-operations/efectivo`;
                break;
            default:
                console.warn(`❓ Ruta '${route}' no reconocida, enviando a panel principal.`);
                targetUrl = `${BASE_URL}/financial`;
        }

        // Feedback visual: Animación de salida antes de redirigir
        $(this).addClass('animate__animated animate__pulse bg-light');
        
        // Pequeño delay de 150ms para que el usuario perciba el clic antes del cambio de página
        setTimeout(() => {
            window.location.href = targetUrl;
        }, 150);
    });

    /**
     * EFECTO HOVER PARA EL EQUIPO:
     * Asegura que el puntero sea consistente en toda la tarjeta
     */
    $('.card-financial-option').css('cursor', 'pointer');
});