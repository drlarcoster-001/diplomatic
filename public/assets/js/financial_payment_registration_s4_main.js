/**
 * MÓDULO: GESTIÓN FINANCIERA / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/financial_payment_registration_s4_main.js
 * PROPÓSITO: Inicialización de eventos para modalidades de pago, tasa BCV y envío del formulario final con notificación asíncrona.
 * VERSIÓN: 2.2.0 - FIX: Inyección de disparador asíncrono para enviar correo de notificación (S5).
 */

(function() {
    window.sysTasaBcv = 1.00;

    document.addEventListener('DOMContentLoaded', () => {
        obtenerTasaActual();

        // --- EVENTOS DE OPCIONES DE PAGO ---
        document.getElementById('btnOptCash')?.addEventListener('click', window.FinancialHandlers.handleCashPayment);
        
        document.getElementById('btnOptDigital')?.addEventListener('click', () => {
            const modalEl = document.getElementById('modalDigital');
            if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });

        // --- EVENTO: EDICIÓN MANUAL DE MONTO (ABONOS PARCIALES) ---
        document.getElementById('btnEditAmount')?.addEventListener('click', function() {
            // Obtenemos el valor actual del input oculto (que es el valor real matemático)
            const inputMaster = document.getElementById('amount');
            const currentVal = inputMaster ? inputMaster.value : "0.00";

            Swal.fire({
                title: 'Modificar Monto',
                text: 'Ingrese el monto exacto a registrar (USD)',
                input: 'text',
                inputValue: currentVal,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) return 'Debe ingresar un monto';
                    const num = parseFloat(value.replace(',', '.'));
                    if (isNaN(num) || num <= 0) return 'Ingrese un número válido mayor a 0';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const nuevoMonto = parseFloat(result.value.replace(',', '.'));
                    // Usamos la UI consolidada para actualizar visual y lógicamente
                    if (typeof window.FinancialUI !== 'undefined') {
                        window.FinancialUI.actualizarMontoEnPantalla(nuevoMonto);
                        
                        // Pequeña notificación de confirmación
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({ icon: 'success', title: 'Monto ajustado correctamente' });
                    }
                }
            });
        });


// --- EVENTOS DIGITALES Y MODALES ---
        document.getElementById('digitalMethod')?.addEventListener('change', function() { 
            if (typeof window.FinancialUI !== 'undefined') {
                window.FinancialUI.renderFieldsByChannel(this.value, window.sysTasaBcv); 
                if (window.FinancialHandlers && window.FinancialHandlers.vincularObservadoresDinamicos) {
                    window.FinancialHandlers.vincularObservadoresDinamicos();
                }
            }
        });

        // 🚀 EMPAQUETADOR DEL JSON Y ANTIBLOQUEO
        document.getElementById('btnConfirmDigital')?.addEventListener('click', async (e) => {
            e.preventDefault();
            // Obligamos al botón a usar nuestra súper función que sí consulta la base de datos
            await window.FinancialHandlers.saveDigitalSelection();
        });

        // ENVÍO FINAL
        document.getElementById('btnSubmit')?.addEventListener('click', (e) => {
            e.preventDefault();
            if (window.FinancialHandlers && typeof window.FinancialHandlers.mostrarResumenYConfirmar === 'function') {
                window.FinancialHandlers.mostrarResumenYConfirmar();
            } else if (typeof window.ejecutarPeticionFinal === 'function') {
                window.ejecutarPeticionFinal();
            }
        });


        // --- ESTADO DE CUENTA (MODAL DE CUOTAS) ---
        document.getElementById('btnViewAccount')?.addEventListener('click', window.FinancialHandlers.loadAccountStatus);
        
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('quota-check')) window.FinancialHandlers.calcularSumaSeleccionada();
        });

        document.getElementById('btnConfirmSelection')?.addEventListener('click', () => {
            const sumaLabel = document.getElementById('modalSelectedTotal');
            if (!sumaLabel) return;
            
            const suma = parseFloat(sumaLabel.innerText) || 0;
            
            if (suma <= 0) {
                Swal.fire('Atención', 'Seleccione al menos una cuota para procesar.', 'warning');
                return;
            }

            if (typeof window.FinancialUI !== 'undefined') {
                window.FinancialUI.actualizarMontoEnPantalla(suma);
            }
            
            const modalStatus = document.getElementById('modalAccountStatus');
            if (modalStatus) {
                const instance = bootstrap.Modal.getInstance(modalStatus);
                if (instance) instance.hide();
            }
        });
    });

    async function obtenerTasaActual() {
        try {
            const response = await fetch(`${BASE_URL}/financial/payment_registration/getLatestExchangeRate`);
            const res = await response.json();
            if(res.status === 'success') {
                window.sysTasaBcv = parseFloat(res.rate) || 1.00;
                const label = document.getElementById('displayTasaBcv');
                if (label && typeof window.FinancialUtils !== 'undefined') {
                    label.innerText = window.FinancialUtils.formatNumberToCurrency(window.sysTasaBcv) + " Bs.";
                }
            }
        } catch (e) { console.error("Error tasa BCV:", e); }
    }

    window.ejecutarPeticionFinal = async function() {
        const form = document.getElementById('formRegistrationPayment');
        if (!form) return;

        const formData = new FormData(form);
        const screenshot = document.getElementById('pay_screenshot')?.files[0];
        if (screenshot) formData.append('pay_screenshot', screenshot);

        Swal.fire({ 
            title: 'Procesando pago...', 
            text: 'Registrando transacción en el libro contable. Por favor, espere.',
            allowOutsideClick: false, 
            didOpen: () => { Swal.showLoading(); } 
        });

        try {
            const response = await fetch(`${BASE_URL}/financial/payment_registration/store`, {
                method: 'POST',
                body: formData
            });
            const res = await response.json();
            
            if (res.status === 'success') {
                // --- INICIO MAGIA DE NOTIFICACIÓN DE CORREO (S5) ---
                const sendNotificationFlag = document.getElementById('send_notification_flag')?.value;
                const paymentId = res.payment_id; // <-- OJO: El controlador S4 debe devolver esto ahora
                
                if (sendNotificationFlag === '1' && paymentId) {
                    Swal.update({ text: 'Pago registrado. Enviando notificación por correo al estudiante...' });
                    
                    // Disparamos el correo en background (asíncrono) sin bloquear la recarga total
                    fetch(`${BASE_URL}/financial/payment_registration/sendPaymentEmail`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ 'payment_id': paymentId })
                    }).then(mailResp => mailResp.json())
                      .then(mailData => console.log('Resultado de Correo:', mailData))
                      .catch(err => console.error('Fallo silencioso al enviar correo:', err));
                }
                // --- FIN MAGIA CORREO ---

                Swal.fire('¡Éxito!', 'El pago ha quedado registrado en estatus PENDIENTE de revisión.', 'success').then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error de Comunicación', 'No se pudo conectar con el servidor para registrar el pago.', 'error');
        }
    };
})();