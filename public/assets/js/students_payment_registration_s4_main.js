/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/students_payment_registration_s4_main.js
 * PROPÓSITO: Orquestador de eventos (Versión Alumno). Clon funcional del administrativo.
 * VERSIÓN: 1.1.7 - FIX: Restauración de envío de correo en segundo plano.
 */

(function() {
    // Tasa referencial global
    window.sysTasaBcv = 1.00;

    document.addEventListener('DOMContentLoaded', () => {
        
        // 1. Cargar tasa BCV al iniciar
        obtenerTasaActual();

        // --- ASIGNACIÓN DE EVENTOS A MÉTODOS DE PAGO ---

        // OPCIÓN 1: EFECTIVO -> Dispara el Popup (Handlers)
        document.getElementById('btnOptCash')?.addEventListener('click', () => {
            if (typeof window.StudentsHandlers !== 'undefined') {
                window.StudentsHandlers.handleCashPayment();
            }
        });

        // OPCIÓN 2: PAGO ELECTRÓNICO -> Abre el Modal (Igual al Administrativo)
        document.getElementById('btnOptDigital')?.addEventListener('click', () => {
            const modalEl = document.getElementById('modalDigital');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });

        // --- GESTIÓN DE MONTOS Y CUOTAS ---

        // Editar monto manualmente (Reutilizamos la lógica de efectivo para evitar duplicar código)
        document.getElementById('btnEditAmount')?.addEventListener('click', function() {
             if (typeof window.StudentsHandlers !== 'undefined') {
                window.StudentsHandlers.handleCashPayment();
            }
        });

        // Abrir Modal de Estado de Cuenta (Ver Mis Cuotas)
        document.getElementById('btnViewAccount')?.addEventListener('click', () => {
            if (window.StudentsHandlers) window.StudentsHandlers.loadAccountStatus();
        });

        // DELEGACIÓN: Escuchar clics en los checkboxes dinámicos de cuotas
        document.addEventListener('change', (e) => {
            if (e.target && e.target.classList.contains('quota-check')) {
                if (typeof window.StudentsHandlers !== 'undefined') {
                    window.StudentsHandlers.calcularSumaSeleccionada();
                }
            }
        });

        // Confirmar selección de cuotas en el modal
        document.getElementById('btnConfirmSelection')?.addEventListener('click', () => {
            const sumaLabel = document.getElementById('modalSelectedTotal');
            if (!sumaLabel) return;
            
            const suma = parseFloat(sumaLabel.innerText) || 0;
            if (suma <= 0) {
                Swal.fire('Atención', 'Seleccione al menos una cuota.', 'warning');
                return;
            }

            if (window.StudentsUI) window.StudentsUI.actualizarMontoEnPantalla(suma);
            
            const modalStatus = document.getElementById('modalAccountStatus');
            if (modalStatus) bootstrap.Modal.getInstance(modalStatus)?.hide();
        });

        // --- LÓGICA DEL MODAL DIGITAL ---

        document.getElementById('digitalMethod')?.addEventListener('change', function() { 
            if (window.StudentsUI) {
                window.StudentsUI.renderFieldsByChannel(this.value, window.sysTasaBcv); 
                if (window.StudentsHandlers) window.StudentsHandlers.vincularObservadoresDinamicos();
            }
        });

        document.getElementById('btnConfirmDigital')?.addEventListener('click', (e) => {
            e.preventDefault(); // Evitamos envíos accidentales
            
            const selectorMethod = document.getElementById('digitalMethod');
            const dynamicFields = document.getElementById('dynamicFields');
            const inputMethodType = document.getElementById('payment_method_type');
            const inputMetadata   = document.getElementById('payment_metadata');
            const inputAmount     = document.getElementById('amount'); 
            
            if (!selectorMethod || !dynamicFields) return;

            const method = selectorMethod.value;
            const inputs = dynamicFields.querySelectorAll('input, select');
            let valid = true;
            const rawData = {};
            
            // 1. Recolección de datos del modal
            inputs.forEach(i => { 
                if (i.required && !i.value) { 
                    i.classList.add('is-invalid'); valid = false; 
                } else { 
                    i.classList.remove('is-invalid'); if(i.name) rawData[i.name] = i.value; 
                } 
            });

            if (!valid) {
                Swal.fire('Campos Vacíos', 'Por favor complete todos los datos solicitados', 'warning');
                return;
            }

            // 2. Extraer Tasa de la calculadora
            let tasaFinal = window.sysTasaBcv;
            if (method === 'PAGOMOVIL') {
                const tasaInput = document.getElementById('calc_tasa');
                if (tasaInput) tasaFinal = parseFloat(tasaInput.value.replace(/\./g, '').replace(',', '.')) || 0;
            }

            // 3. Cálculos de Monto
            let finalValueToSave = 0, calculatedUsd = 0;
            if (method === 'PAGOMOVIL') {
                const bsField = rawData.pm_amount || rawData.pm_amount_bs || "0";
                const montoBs = parseFloat(bsField.replace(/\./g, '').replace(',', '.'));
                calculatedUsd = tasaFinal > 0 ? parseFloat((montoBs / tasaFinal).toFixed(2)) : 0;
                finalValueToSave = montoBs;
            } else {
                const montoManual = parseFloat((rawData.z_amount || rawData.b_amount || "0").replace(/\./g, '').replace(',', '.'));
                calculatedUsd = finalValueToSave = montoManual;
            }

            // 4. Armar JSON Maestro
            const masterJson = { 
                metodo: method, 
                monto_sistema_usd: calculatedUsd, 
                tasa_cambio: tasaFinal, 
                detalles_origen: { 
                    identificador: document.getElementById('document_id_hidden')?.value || 'N/A',
                    banco_emisor: (method === 'PAGOMOVIL') ? rawData.pm_bank : method,
                    cuenta_correo_telf: (method === 'PAGOMOVIL') ? (rawData.pm_prefix + "-" + rawData.pm_phone) : (rawData.z_email || rawData.b_uid || 'N/A'),
                    nombre_titular: method === 'ZELLE' ? (rawData.z_issuer || "NO_SUMINISTRADO") : "NO_SUMINISTRADO"
                }, 
                detalles_transaccion: {
                    referencia: rawData.pm_ref || rawData.z_ref || rawData.b_order,
                fecha_comprobante: rawData.pm_date || rawData.z_date || rawData.b_date || new Date().toISOString().split('T')[0],
                    monto_nativo: finalValueToSave,
                    moneda_nativa: (method === 'PAGOMOVIL') ? "BS" : (method === 'BINANCE' ? "USDT" : "USD")
                },
                auditoria: { fecha_registro: new Date().toISOString().substring(0, 19).replace('T', ' '), agente: "AUTOGESTION_ESTUDIANTE" }
            };

            // 5. Inyectar en inputs ocultos
            if (inputMethodType) inputMethodType.value = method;
            if (inputMetadata) inputMetadata.value = JSON.stringify(masterJson);
            if (inputAmount) inputAmount.value = finalValueToSave.toFixed(2);

            // EL TRUCO: Quitar required a campos ocultos para que el navegador no te bloquee
            document.querySelectorAll('#formRegistrationPayment input[required], #formRegistrationPayment select[required]').forEach(el => {
                if (!dynamicFields.contains(el)) el.removeAttribute('required');
            });

            if (window.StudentsUI) window.StudentsUI.highlightCard('btnOptDigital');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDigital')).hide();
            Swal.fire({ icon: 'success', title: 'Pago Listo', timer: 1000, showConfirmButton: false });
        });

// --- ENVÍO FINAL (MODIFICADO) ---
        document.getElementById('btnSubmit')?.addEventListener('click', (e) => {
            e.preventDefault(); // Evitamos que el HTML intente validar campos ocultos
            
            // FIX: En lugar de enviar directamente, levantamos el modal de verificación visual
            if (typeof window.StudentsHandlers !== 'undefined' && typeof window.StudentsHandlers.mostrarResumenYConfirmar === 'function') {
                window.StudentsHandlers.mostrarResumenYConfirmar();
            } else {
                console.error("CRÍTICO: No se encontró el handler de confirmación.");
                // Fallback por si el handler no cargó
                if (window.ejecutarPeticionFinal) window.ejecutarPeticionFinal();
            }
        });

    });

    /**
     * Obtiene la tasa BCV desde el servidor
     */
    async function obtenerTasaActual() {
        try {
            const response = await fetch(`${BASE_URL}/students/payment_registration/getLatestExchangeRate`);
            const res = await response.json();
            if(res.status === 'success') {
                window.sysTasaBcv = parseFloat(res.rate) || 1.00;
                const label = document.getElementById('displayTasaBcv');
                if (label && window.StudentsUtils) {
                    label.innerText = window.StudentsUtils.formatNumberToCurrency(window.sysTasaBcv) + " Bs.";
                }
            }
        } catch (e) { console.error("Error tasa:", e); }
    }

    /**
     * Ejecución de la petición POST final
     */
    window.ejecutarPeticionFinal = async function() {
        const form = document.getElementById('formRegistrationPayment');
        if (!form) return;
        
        // FIX: Ocultar el modal de confirmación antes de iniciar el guardado
        const confModal = document.getElementById('modalPaymentConfirmation');
        if (confModal) bootstrap.Modal.getInstance(confModal)?.hide();

        const formData = new FormData(form);
        
        // El capture se adjunta si existe en el input (Digital)
        const fileInput = document.getElementById('pay_screenshot');
        if (fileInput && fileInput.files[0]) {
            formData.append('pay_screenshot', fileInput.files[0]);
        }

        Swal.fire({ 
            title: 'Procesando...', 
            text: 'Guardando reporte de pago',
            allowOutsideClick: false, 
            didOpen: () => { Swal.showLoading(); } 
        });

        try {
            const response = await fetch(`${BASE_URL}/students/payment_registration/store`, {
                method: 'POST',
                body: formData
            });
            const res = await response.json();
            
            if (res.status === 'success') {
                const paymentId = res.payment_id;
                
                // Disparamos la notificación por correo (S5) de forma asíncrona
                if (paymentId) {
                    fetch(`${BASE_URL}/students/payment_registration/sendPaymentEmail`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ 'payment_id': paymentId })
                    }).catch(err => console.error('Fallo silencioso en notificación por correo:', err));
                }

                Swal.fire('¡Éxito!', res.message, 'success').then(() => {
                    window.location.href = `${BASE_URL}/students`;
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Fallo de conexión con el servidor', 'error');
        }
    };
})();