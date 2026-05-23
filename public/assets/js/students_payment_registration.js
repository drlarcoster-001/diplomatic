/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/students_payment_registration.js
 * PROPÓSITO: Orquestador central de navegación y progreso del Wizard (Versión Alumno).
 * VERSIÓN: 1.0.0 - FEATURE: Adaptación de flujo 1-2-4 y sincronización con módulos StudentsS1/S2.
 */

$(document).ready(function() {
    let currentStep = 1;

    // --- 1. NAVEGACIÓN DEL WIZARD ---
    
    /**
     * Manejador del botón "Siguiente"
     */
    $('#btnNext').on('click', function() {
        if (currentStep === 1) {
            // Al avanzar al Paso 2, cargamos los programas del estudiante logueado
            if (typeof window.StudentsS2 !== 'undefined') {
                window.StudentsS2.loadOfferings();
            } else {
                console.error("Error Crítico: El módulo StudentsS2 no está disponible.");
            }
            cambiarPaso(1, 2);
            
        } else if (currentStep === 2) {
            // Saltamos del Paso 2 al Paso 4 (Detalle de Pago Digital)
            cambiarPaso(2, 4); 
        }
    });

    /**
     * Manejador del botón "Anterior"
     */
    $('#btnPrev').on('click', function() {
        if (currentStep === 2) {
            cambiarPaso(2, 1);
        } else if (currentStep === 4) {
            cambiarPaso(4, 2);
        }
    });

    /**
     * Función maestra de transición entre pasos
     * @param {number} from - Paso origen
     * @param {number} to - Paso destino
     */
    function cambiarPaso(from, to) {
        // Transición visual de secciones
        $(`#step${from}`).addClass('d-none');
        $(`#step${to}`).removeClass('d-none');
        currentStep = to;

        // Sincronización de inputs y botones
        $('#current_step_val').val(to);
        $('#btnPrev').toggleClass('d-none', to === 1);
        $('#btnNext').toggleClass('d-none', to === 4);
        $('#btnSubmit').toggleClass('d-none', to !== 4);
        
        // Actualización de la Barra de Progreso (Lógica de 3 Pasos Visuales)
        const progress = to === 1 ? '33%' : (to === 2 ? '66%' : '100%');
        $('#wizardProgress').css('width', progress);
        
        // El label dice "Paso 3" cuando estamos técnicamente en el step4 del DOM
        const displayStep = to === 4 ? 3 : to;
        $('#stepIndicator').text(`Paso ${displayStep} de 3`);

        validarBotonesPersistentes();
        
        // Scroll automático al inicio de la tarjeta para UX móvil
        $('.wizard-card-container')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // --- 2. VALIDACIÓN DE PERSISTENCIA ---

    /**
     * Verifica si los datos necesarios están presentes para habilitar la navegación.
     * Previene que el usuario avance sin haber seleccionado un estudiante o programa.
     */
    function validarBotonesPersistentes() {
        if (currentStep === 1) {
            const userId = $('#user_id_val').val();
            // El botón se habilita si el StudentsS1 ya cargó el ID de sesión
            $('#btnNext').prop('disabled', !userId || userId === "0" || userId === "");
            
        } else if (currentStep === 2) {
            const offId = $('#offering_id_val').val();
            // El botón se habilita solo cuando el alumno selecciona un diplomado de la lista
            $('#btnNext').prop('disabled', !offId || offId === "0" || offId === "");
            
        } else {
            // En el paso final (4), el botón Next está oculto, pero lo dejamos habilitado por lógica
            $('#btnNext').prop('disabled', false);
        }
    }

    // Exposición global para que los módulos hijos puedan forzar validaciones
    window.validarBotonesPersistentes = validarBotonesPersistentes;
    
    // Inicialización de estado de botones
    validarBotonesPersistentes();
});