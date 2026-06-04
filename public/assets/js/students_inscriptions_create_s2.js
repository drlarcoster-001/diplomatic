/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: public/assets/js/students_inscriptions_create_s2.js
 * PROPÓSITO: Validación del Paso 2 (Información de Base) y gestión de bloqueo de flujo.
 * VERSIÓN: 1.2.6 - Fix de mensaje de redirección y soporte para rutas en /diplomatic/public/.
 */

(function() {
    /**
     * Monitorea el cambio de paso para ocultar o mostrar el botón "Siguiente"
     * dependiendo de si la información de perfil está completa.
     */
    document.addEventListener('stepChanged', function(e) {
        if (e.detail.step === 2) {
            checkStep2Requirements();
        }
    });

    /**
     * Verifica visualmente si el botón "Siguiente" debe estar bloqueado.
     */
    function checkStep2Requirements() {
        const isComplete = document.getElementById('profile_complete_flag')?.value === "1";
        const btnNext = document.getElementById('btnNext');

        if (!isComplete) {
            if (btnNext) btnNext.classList.add('d-none'); // Escondemos el botón por seguridad
        } else {
            if (btnNext) btnNext.classList.remove('d-none');
        }
    }

    /**
     * Validación obligatoria requerida por el orquestador principal.
     * Si falla, dispara el SweetAlert y redirecciona al perfil.
     */
    window.validateStep2 = function() {
        const isComplete = document.getElementById('profile_complete_flag')?.value === "1";

        if (!isComplete) {
            Swal.fire({
                title: 'Información Incompleta',
                text: 'Detectamos que su perfil no cuenta con la información académica base requerida. Debe comenzar de nuevo la inscripción luego de actualizar el usuario.',
                icon: 'error',
                showCancelButton: false,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Actualizar Usuario ahora'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Reconstrucción dinámica de la URL base para el redireccionamiento
                    const path = window.location.pathname;
                    const base = path.includes('/public/') ? path.split('/public/')[0] + '/public' : '/public';
                    const targetUrl = window.location.origin + base + '/profile';
                    
                    window.location.href = targetUrl;
                }
            });
            return false;
        }

        console.log("Paso 2 validado: Perfil académico completo.");
        return true;
    };

    // Ejecución inicial por si el wizard carga directamente en este paso
    setTimeout(checkStep2Requirements, 100);

})();