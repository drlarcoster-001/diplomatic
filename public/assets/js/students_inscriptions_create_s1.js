/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: public/assets/js/students_inscriptions_create_s1.js
 * PROPÓSITO: Lógica de validación de identidad y verificación de duplicados para el Paso 1.
 * VERSIÓN: 1.0.2 - Fix para evitar alerta de salida al detectar inscripciones duplicadas.
 */

(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const offeringId = document.getElementsByName('offering_id')[0]?.value;
        
        /**
         * VERIFICACIÓN INICIAL DE DUPLICADOS
         * Si el estudiante ya está inscrito, no lo dejamos ni empezar el wizard.
         */
        if (offeringId) {
            verificarInscripcionPrevia(offeringId);
        }
    });

    /**
     * Consulta al controlador S1 si ya existe un registro para este usuario y oferta.
     */
    async function verificarInscripcionPrevia(offeringId) {
        const baseUrl = window.location.origin + (window.location.pathname.includes('/public/') ? window.location.pathname.split('/public/')[0] + '/public' : '');
        
        try {
            const response = await fetch(`${baseUrl}/students/inscriptions/checkExisting?offering_id=${offeringId}`);
            const data = await response.json();

            if (data.exists) {
                Swal.fire({
                    title: 'Proceso Duplicado',
                    text: data.msg || 'Ya posees una inscripción registrada para este programa.',
                    icon: 'warning',
                    confirmButtonText: 'Volver al listado',
                    allowOutsideClick: false
                }).then(() => {
                    // FIX: Autorizar la salida para que no salte el "Abandonar sitio web"
                    if (typeof window.isSubmitting !== 'undefined') {
                        window.isSubmitting = true;
                    }
                    window.onbeforeunload = null;
                    
                    window.location.href = `${baseUrl}/students/inscriptions`;
                });
            }
        } catch (error) {
            console.error("Error en validación S1:", error);
        }
    }

    /**
     * FUNCIÓN DE VALIDACIÓN GLOBAL (Paso 1)
     * Requerida por el orquestador students_inscriptions_create.js para permitir el avance al Paso 2.
     */
    window.validateStep1 = function() {
        const userId = document.getElementById('user_id_val')?.value;
        const docId = document.getElementById('document_id_hidden')?.value;

        // Validación de integridad de la sesión
        if (!userId || userId === "0") {
            Swal.fire('Error de Sesión', 'No se detectó su identidad. Por favor, reinicie sesión.', 'error');
            return false;
        }

        // Validación de datos obligatorios en el perfil
        if (!docId || docId === "N/A" || docId.trim() === "") {
            Swal.fire({
                title: 'Perfil Incompleto',
                text: 'Su número de documento no está registrado. Debe actualizar su perfil para continuar.',
                icon: 'error'
            });
            return false;
        }

        console.log("Paso 1 validado con éxito para el usuario ID: " + userId);
        return true;
    };

})();