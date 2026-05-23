/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: public/assets/js/students_inscriptions_create.js
 * PROPÓSITO: Controlador maestro del asistente (Wizard). Manejo de navegación y blindaje total de salida.
 * VERSIÓN: 1.3.0 - Fix: Eliminación dinámica del listener 'beforeunload' para forzar el silencio del navegador.
 */

// Global flag para silenciar alertas (mantenida por compatibilidad con s5.js)
window.isSubmitting = false;

document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. CONFIGURACIÓN DE RUTAS ---
    const getBasePath = () => {
        const path = window.location.pathname;
        const base = path.includes('/public/') ? path.split('/public/')[0] + '/public' : '/public';
        return window.location.origin + base;
    };
    
    const PUBLIC_URL = getBasePath();

    // --- 2. INTERCEPTOR DE SALIDA (BLINDAJE DE NAVEGACIÓN) ---

    /**
     * Definimos el interceptor como función nominada para poder removerlo físicamente.
     */
    const exitInterceptor = (e) => {
        if (window.isSubmitting) return;
        
        e.preventDefault();
        e.returnValue = '¿Cancelar inscripción?';
        return e.returnValue;
    };

    // Activación inicial
    window.addEventListener('beforeunload', exitInterceptor);

    /**
     * FUNCIÓN CENTRAL DE SALIDA
     * Remueve el listener y redirige.
     */
    const confirmExit = (targetUrl) => {
        Swal.fire({
            title: '¿Cancelar inscripción?',
            text: "Se perderán los datos que no hayas guardado en este proceso.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, salir',
            cancelButtonText: 'Continuar aquí',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // MATAMOS EL EVENTO FÍSICAMENTE
                window.isSubmitting = true;
                window.removeEventListener('beforeunload', exitInterceptor);
                window.location.href = targetUrl;
            }
        });
    };

    /**
     * Interceptar clics en enlaces externos
     */
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link || link.getAttribute('href')?.startsWith('#') || link.getAttribute('target') === '_blank') return;
        
        // No interceptar navegación interna del Wizard
        if (link.closest('.wizard-footer-actions') || link.classList.contains('btn-preview-resume')) return;

        e.preventDefault();
        confirmExit(link.href);
    });

    // --- 3. ELEMENTOS UI DEL WIZARD ---
    const btnNext = document.getElementById('btnNext');
    const btnPrev = document.getElementById('btnPrev');
    const btnSubmit = document.getElementById('btnSubmit');
    const btnCancel = document.getElementById('btnCancel');
    const progressBar = document.getElementById('wizardProgress');
    const stepIndicator = document.getElementById('stepIndicator');

    let currentStep = 1;
    const TOTAL_STEPS = 5;

    function updateWizard() {
        document.querySelectorAll('.wizard-step-content').forEach(step => {
            step.classList.add('d-none');
        });
        
        const currentStepEl = document.getElementById(`step${currentStep}`);
        if (currentStepEl) {
            currentStepEl.classList.remove('d-none');
        }

        if (stepIndicator) stepIndicator.innerText = `Paso ${currentStep} de ${TOTAL_STEPS}`;
        if (progressBar) progressBar.style.width = `${(currentStep / TOTAL_STEPS) * 100}%`;

        btnPrev.classList.toggle('d-none', currentStep === 1);
        btnNext.classList.toggle('d-none', currentStep === TOTAL_STEPS);
        btnSubmit.classList.toggle('d-none', currentStep !== TOTAL_STEPS);

        const cardBody = document.querySelector('.wizard-body-content');
        if (cardBody) cardBody.scrollTop = 0;

        document.dispatchEvent(new CustomEvent('stepChanged', { 
            detail: { step: currentStep } 
        }));
    }

    // --- 4. EVENTOS DE NAVEGACIÓN ---

    btnNext.addEventListener('click', () => {
        if (validarPasoActual()) {
            if (currentStep < TOTAL_STEPS) {
                currentStep++;
                updateWizard();
            }
        }
    });

    btnPrev.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateWizard();
        }
    });

    if (btnCancel) {
        btnCancel.addEventListener('click', (e) => {
            e.preventDefault();
            confirmExit(PUBLIC_URL + '/students/inscriptions');
        });
    }

    // --- 5. SISTEMA DE VALIDACIÓN ---
    function validarPasoActual() {
        if (currentStep === 1) {
            const userId = document.getElementById('user_id_val')?.value;
            if (!userId || userId === "0") {
                Swal.fire('Error', 'Sesión de usuario no válida.', 'error');
                return false;
            }
        }

        const validatorName = `validateStep${currentStep}`;
        if (typeof window[validatorName] === 'function') {
            return window[validatorName]();
        }

        return true; 
    }

    // Exponemos la limpieza para que s5.js pueda llamarla al finalizar con éxito
    window.killWizardAlarms = function() {
        window.isSubmitting = true;
        window.removeEventListener('beforeunload', exitInterceptor);
    };

    updateWizard();
});