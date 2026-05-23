/**
 * MÓDULO: ESTUDIANTES / ASSETS
 * ARCHIVO: public/assets/js/students.js
 * PROPÓSITO: Captura de parámetros URL para disparo de alertas SweetAlert2 y gestión de interfaz del panel.
 * VERSIÓN: 1.0.2 - Fix: Soporte para la subcarpeta /diplomatic/public/ y validación de carga de Swal.
 */

document.addEventListener("DOMContentLoaded", () => {
    // 1. Instanciamos el lector de parámetros de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const alertType = urlParams.get('alert');

    // 2. Si existe el parámetro "alert", ejecutamos la lógica de SweetAlert2
    if (alertType && typeof Swal !== 'undefined') {
        let text = "";

        if (alertType === 'needs_enrollment') {
            text = "Para registrar pagos debe tener inscrito al menos un diplomado.";
        } 
        else if (alertType === 'no_active_enrollment') {
            text = "Usted no tiene validado o no tiene una inscripción activa para ver/generar sus constancias.";
        }
        // --- AQUÍ PEGASTE EL NUEVO BLOQUE ---
        else if (alertType === 'no_statement_access') {
            text = "Su estado de cuenta estará disponible una vez que su inscripción sea validada por administración.";
        }

        // 3. Si encontramos un texto para la alerta, la disparamos
        if (text !== "") {
            Swal.fire({
                title: "¡Atención!",
                text: text,
                icon: "warning",
                confirmButtonText: "Entendido",
                confirmButtonColor: "#0d6efd",
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: {
                    confirmButton: 'rounded-pill px-4'
                }
            });
        }

        // 4. Limpieza de URL: Borramos el parámetro sin recargar la página
        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path: cleanUrl}, '', cleanUrl);
    }
});
