/**
 * MÓDULO: GESTIÓN FINANCIERA / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/financial_payment_registration.js
 * PROPÓSITO: Orquestador central exclusivo para navegación y progreso del Wizard.
 * VERSIÓN: 4.0.0 - FIX FINAL: Eliminación de duplicidad lógica. Delegación total a módulos S1 y S2.
 */

$(document).ready(function() {
    let currentStep = 1;

    // --- 1. NAVEGACIÓN DEL WIZARD ---
    $('#btnNext').on('click', function() {
        if (currentStep === 1) {
            // Al salir del Paso 1, le decimos al módulo S2 que cargue los diplomados
            if (typeof window.FinancialS2 !== 'undefined') {
                window.FinancialS2.loadOfferings();
            } else {
                console.error("Error: Archivo S2 no cargado.");
            }
            cambiarPaso(1, 2);
        } else if (currentStep === 2) {
            // Al salir del Paso 2, vamos al Paso 4 (Cobro)
            cambiarPaso(2, 4); 
        }
    });

    $('#btnPrev').on('click', function() {
        if (currentStep === 2) {
            cambiarPaso(2, 1);
        } else if (currentStep === 4) {
            cambiarPaso(4, 2);
        }
    });

    function cambiarPaso(from, to) {
        // Ocultar paso actual y mostrar el nuevo
        $(`#step${from}`).addClass('d-none');
        $(`#step${to}`).removeClass('d-none');
        currentStep = to;

        // Actualizar variables y botones de interfaz
        $('#current_step_val').val(to);
        $('#btnPrev').toggleClass('d-none', to === 1);
        $('#btnNext').toggleClass('d-none', to === 4);
        $('#btnSubmit').toggleClass('d-none', to !== 4);
        
        // Actualizar Progress Bar
        const progress = to === 1 ? '33%' : to === 2 ? '66%' : '100%';
        $('#wizardProgress').css('width', progress);
        $('#stepIndicator').text(`Paso ${to === 4 ? 3 : to} de 3`);

        validarBotonesPersistentes();
    }

    // --- 2. PERSISTENCIA DE BOTONES (Evita bloqueo al retroceder) ---
    function validarBotonesPersistentes() {
        if (currentStep === 1) {
            const userId = $('#user_id_val').val();
            // Si no hay ID, bloquea el botón. Si hay ID, lo deja encendido.
            $('#btnNext').prop('disabled', !userId || userId === "0" || userId === "");
        } else if (currentStep === 2) {
            const offId = $('#offering_id_val').val();
            $('#btnNext').prop('disabled', !offId || offId === "0" || offId === "");
        } else {
            $('#btnNext').prop('disabled', false);
        }
    }

    // Hacemos la validación accesible globalmente por si S1 o S2 la necesitan
    window.validarBotonesPersistentes = validarBotonesPersistentes;
    
    // Ejecución inicial al cargar la página
    validarBotonesPersistentes();
});