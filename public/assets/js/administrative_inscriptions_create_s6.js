/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: public/assets/js/administrative_inscriptions_create_s6.js
 * PROPÓSITO: Ejecutar el envío de correo de notificación tras inscripción manual.
 * VERSIÓN: 1.0.0 - Versión independiente para el flujo administrativo.
 */

window.processStep6Email = function(enrollmentId) {
    
    // Log profesional para identificar el proceso en consola
    console.group("%c📩 NOTIFICACIÓN ADMINISTRATIVA", "background: #0d6efd; color: #fff; padding: 2px 5px; border-radius: 3px;");
    console.log("📌 Iniciando notificación para Inscripción ID:", enrollmentId);
    
    if (!enrollmentId) {
        console.error("❌ ERROR: No se recibió enrollmentId. Proceso abortado.");
        console.groupEnd();
        return;
    }
    
    /**
     * Cálculo de URL:
     * Toma la ruta actual (ej: .../administrative/inscriptions/create)
     * y la transforma en (.../administrative/inscriptions/send-email)
     */
    const currentPath = window.location.pathname;
    const targetUrl = currentPath.substring(0, currentPath.lastIndexOf('/')) + '/send-email';
    
    const formData = new FormData();
    formData.append('enrollment_id', enrollmentId);
    
    /**
     * Ejecución Asíncrona del envío de correo
     */
    fetch(targetUrl, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        try {
            // Intentamos parsear la respuesta del controlador S6
            return JSON.parse(text);
        } catch (err) {
            console.error("📡 Error de formato en respuesta del servidor:", text);
            throw new Error("El servidor no devolvió un JSON válido.");
        }
    })
    .then(data => {
        // Validación correcta según los ENUMs y lógica del controlador S6 Administrativo
        if (data.success === true) {
            console.log("%c✅ ÉXITO: " + data.message, "color: #00ff00; font-weight: bold;");
        } else {
            // Si success es false, es un error reportado por el MailService o PHPMailer
            console.warn("⚠️ ADVERTENCIA: El servidor procesó la solicitud pero reportó un problema: " + data.message);
        }
    })
    .catch(err => {
        // Errores de red o de ejecución crítica de PHP
        console.error("🔥 ERROR CRÍTICO EN ENVÍO S6:", err.message);
    })
    .finally(() => {
        console.groupEnd();
    });
};