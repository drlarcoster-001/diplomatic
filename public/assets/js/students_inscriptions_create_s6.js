/**
 * MÓDULO: EVENTOS / ESTUDIANTES / CORREOS
 * ARCHIVO: public/assets/js/students_inscriptions_create_s6.js
 * PROPÓSITO: Ejecutar el envío de correo de forma asíncrona apuntando al controlador S6.
 * VERSIÓN: 1.2.7 - Fix: Redirección dinámica a controlador _s6 y sincronización de llaves JSON (success).
 */

window.processStep6Email = function(enrollmentId) {
    
    // --- BLOQUE DE DIAGNÓSTICO INICIAL ---
    console.group("%c🔍 DEPURACIÓN PASO 6: INICIO DE PROCESO", "background: #222; color: #bada55; padding: 2px 5px;");
    console.log("📌 ID de Inscripción recibido:", enrollmentId);
    
    if (!enrollmentId) {
        console.error("❌ ERROR: No se proporcionó enrollmentId. El proceso se detiene.");
        console.groupEnd();
        return;
    }
    
    // FIX: Forzamos que la URL apunte al controlador _s6 independientemente de dónde estemos
    const basePath = window.location.pathname.split('/create')[0];
    const targetUrl = basePath.replace('_s5', '_s6') + '/send-email';
    
    console.log("🌐 URL de destino calculada (S6):", targetUrl);
    
    const formData = new FormData();
    formData.append('enrollment_id', enrollmentId);
    
    console.groupEnd();
    // -------------------------------------

    fetch(targetUrl, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        console.log("📡 Respuesta cruda del servidor:", text);
        
        try {
            return JSON.parse(text);
        } catch (err) {
            throw new Error("El servidor no devolvió un JSON válido.");
        }
    })
    .then(data => {
        // Sincronización: Aceptamos 'status' o 'success' para mayor compatibilidad
        if (data.success === true || data.status === 'success') {
            console.log("%c✅ ÉXITO S6: " + data.message, "color: #00ff00; font-weight: bold;");
            
            // Liberación de bloqueos del navegador
            if (typeof window.killWizardAlarms === 'function') {
                window.killWizardAlarms();
            } else {
                window.onbeforeunload = null; 
                window.isSubmitting = true;
            }
            
        } else {
            console.error("❌ FALLO CONTROLADO EN S6:", data.message);
        }
    })
    .catch(err => {
        console.error("🔥 ERROR CRÍTICO EN PROCESO S6:", err.message);
    });
};