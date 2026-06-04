/**
 * MÓDULO: GESTIÓN FINANCIERA / NOTIFICACIONES (JS)
 * ARCHIVO: public/assets/js/financial_payment_validations_notifications.js
 * PROPÓSITO: Gestor asíncrono para disparar notificaciones de correo tras validaciones de pago.
 * VERSIÓN: 1.1.2 - Sincronización con el endpoint 'Notifications' (con T).
 */

const PaymentNotificator = {
    /**
     * Dispara la petición al controlador de notificaciones.
     * @param {number} paymentId ID del pago aprobado para procesar el correo.
     */
    sendApprovedEmail: async function(paymentId) {
        const fd = new FormData();
        fd.append('payment_id', paymentId);

        try {
            const response = await fetch(`${BASE_URL}/financial/notifications/sendPaymentApprovedEmail`, {
                method: 'POST',
                body: fd
            });

            const rawText = await response.text();
            
            try {
                const result = JSON.parse(rawText);
                if (result.success) {
                    console.log("✔ Correo enviado satisfactoriamente.");
                } else {
                    console.error("✘ Error en el envío:", result.message);
                }
                return result;
            } catch (jsonErr) {
                console.error("EL SERVIDOR DEVOLVIÓ BASURA (HTML/Sintaxis):", rawText);
                return { success: false, message: "Error de sintaxis en el servidor." };
            }

        } catch (error) {
            console.error("Fallo de red en notificaciones:", error);
            return { success: false, message: "Error de conexión." };
        }
    }
};