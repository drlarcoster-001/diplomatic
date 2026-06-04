/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/students_payment_registration_s4_handlers.js
 * PROPÓSITO: Manejo de cálculos, construcción de JSON Maestro y verificación detallada de reportes.
 * VERSIÓN: 1.1.6 - FIX: Validación de integridad de metadata antes de confirmación final.
 */

window.StudentsHandlers = {

    /**
     * Gestiona la selección de Efectivo como "Promesa de Pago".
     */
    handleCashPayment: function() {
        const montoActual = document.getElementById('amount')?.value || "0.00";

        Swal.fire({
            title: 'Reportar Pago en Efectivo',
            text: 'Ingrese el monto exacto que entregará en taquilla (USD)',
            input: 'text',
            inputValue: montoActual === "0.00" ? "" : montoActual,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Confirmar Compromiso de Pago',
            confirmButtonColor: '#198754',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value) return 'Debe ingresar un monto';
                const num = parseFloat(value.replace(',', '.'));
                if (isNaN(num) || num <= 0) return 'Ingrese un número válido mayor a 0';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const nuevoMonto = parseFloat(result.value.replace(',', '.'));

                if (typeof window.StudentsUI !== 'undefined') {
                    window.StudentsUI.actualizarMontoEnPantalla(nuevoMonto);
                    window.StudentsUI.highlightCard('btnOptCash');
                }

                const masterJson = {
                    "metodo": "CASH",
                    "monto_sistema_usd": window.StudentsUtils.round(nuevoMonto),
                    "tasa_cambio": 1.00,
                    "detalles_origen": { 
                        "identificador": window.StudentsUtils.getStudentCode(), 
                        "nombre_titular": window.StudentsUtils.getStudentName(),
                        "nota": "REPORTE DE INTENCIÓN DESDE PORTAL ESTUDIANTIL" 
                    },
                    "detalles_transaccion": { 
                        "referencia": "CASH-" + Date.now().toString().slice(-6), 
                        "fecha_comprobante": window.StudentsUtils.getSystemDate(),
                        "monto_nativo": window.StudentsUtils.round(nuevoMonto),
                        "moneda_nativa": "USD"
                    },
                    "auditoria": { 
                        "fecha_registro": window.StudentsUtils.getSystemDateTime(), 
                        "agente": "AUTOGESTION_ESTUDIANTE" 
                    }
                };

                document.getElementById('payment_method_type').value = 'CASH';
                document.getElementById('payment_metadata').value = JSON.stringify(masterJson);
                
                const dynFields = document.getElementById('dynamicFields');
                const screenCont = document.getElementById('screenshotContainer');
                if(dynFields) { dynFields.innerHTML = ''; dynFields.classList.add('d-none'); }
                if(screenCont) { screenCont.classList.add('d-none'); }
                
                Swal.fire({ icon: 'success', title: 'Compromiso Registrado', text: 'Presione "Finalizar Reporte" para completar el envío.', timer: 2000, showConfirmButton: false });
            }
        });
    },

    vincularObservadoresDinamicos: function() {
        document.querySelectorAll('.currency-field').forEach(i => {
            i.addEventListener('input', function(e) {
                if (typeof window.StudentsUtils !== 'undefined') {
                    window.StudentsUtils.formatCurrency(e);
                    window.StudentsHandlers.calcularBolivaresEnVivo(); 
                }
            });
        });
    },

    calcularBolivaresEnVivo: function() {
        const methodEl = document.getElementById('digitalMethod');
        if (!methodEl) return;
        
        const method = methodEl.value;
        const tasa = parseFloat(window.sysTasaBcv) || 1.00;

        if (method === 'PAGOMOVIL') {
            const valBs = window.StudentsUtils.parseCurrencyToFloat(document.getElementById('pm_amount_bs')?.value);
            const eqUsd = window.StudentsUtils.round(valBs / tasa);
            const lbl = document.getElementById('digital_amount_usd_lbl');
            if (lbl) lbl.innerText = '$ ' + window.StudentsUtils.formatNumberToCurrency(eqUsd);
        }
    },

    saveDigitalSelection: function() {
        const method = document.getElementById('digitalMethod')?.value;
        const screenshot = document.getElementById('pay_screenshot')?.files[0];
        const getVal = (id) => document.getElementById(id)?.value.trim() || '';
        
        if (!method) {
            Swal.fire('Atención', 'Debe seleccionar un canal de pago.', 'warning');
            return;
        }

        const fieldsConfig = {
            'PAGOMOVIL': ['pm_bank', 'pm_prefix', 'pm_phone', 'pm_ref', 'modal_pm_date', 'calc_bs'],
            'ZELLE': ['z_email', 'z_holder', 'z_ref', 'z_date', 'z_amount'],
            'BINANCE': ['b_uid', 'b_order', 'b_date', 'b_amount']
        };

        // Reset visual previo
        ['pm_bank','pm_prefix','pm_phone','pm_ref','modal_pm_date','calc_bs','z_email','z_holder','z_ref','z_date','z_amount','b_uid','b_order','b_date','b_amount'].forEach(id => {
            const f = document.getElementById(id);
            if (f) { f.style.borderColor = ''; f.style.boxShadow = ''; }
        });
        const sfReset = document.getElementById('pay_screenshot');
        if (sfReset) { sfReset.style.borderColor = ''; sfReset.style.boxShadow = ''; }

        const requiredFields = fieldsConfig[method];
        let firstInvalid = null;
        let hasErrors = false;

        requiredFields.forEach(id => {
            const field = document.getElementById(id);
            if (!field || field.value.trim() === '' || field.value === '---') {
                hasErrors = true;
                if (field) {
                    field.style.borderColor = '#dc3545';
                    field.style.boxShadow = '0 0 0 3px rgba(220,53,69,.25)';
                    if (!firstInvalid) firstInvalid = field;
                }
            }
        });

        if (hasErrors) {
            if (firstInvalid) firstInvalid.focus();
            setTimeout(() => {
                Swal.fire({ icon: 'error', title: 'Campos Incompletos', text: 'Complete los campos marcados en rojo.', confirmButtonColor: '#d33' });
            }, 100);
            return;
        }

        if (!screenshot) {
            const sf = document.getElementById('pay_screenshot');
            if (sf) { sf.style.borderColor = '#dc3545'; sf.style.boxShadow = '0 0 0 3px rgba(220,53,69,.25)'; sf.focus(); }
            setTimeout(() => {
                Swal.fire('Capture Requerido', 'Debe adjuntar la imagen del comprobante.', 'warning');
            }, 100);
            return;
        }


        const tasa = parseFloat(window.sysTasaBcv) || 1.00;
        let masterJson = {
            "metodo": method,
            "monto_sistema_usd": 0.00,
            "tasa_cambio": window.StudentsUtils.round(tasa),
            "detalles_origen": { "identificador": window.StudentsUtils.getStudentCode() },
            "detalles_transaccion": {},
            "auditoria": { "fecha_registro": window.StudentsUtils.getSystemDateTime(), "agente": "AUTOGESTION_ESTUDIANTE" }
        };

        let finalUsd = 0;
        if (method === 'PAGOMOVIL') {
            const bsTyped = window.StudentsUtils.parseCurrencyToFloat(getVal('pm_amount_bs'));
            finalUsd = window.StudentsUtils.round(bsTyped / tasa);
            masterJson.detalles_origen.cuenta_correo_telf = getVal('pm_prefix') + "-" + getVal('pm_phone');
            masterJson.detalles_origen.banco_emisor = getVal('pm_bank');
            masterJson.detalles_transaccion = {
                "referencia": getVal('pm_ref'),
                "fecha_comprobante": getVal('modal_pm_date'),
                "monto_nativo": window.StudentsUtils.round(bsTyped),
                "moneda_nativa": "BS"
            };
        } else if (method === 'ZELLE') {
            finalUsd = window.StudentsUtils.parseCurrencyToFloat(getVal('z_amount'));
            masterJson.detalles_origen.cuenta_correo_telf = getVal('z_email');
            masterJson.detalles_origen.nombre_titular = getVal('z_holder');
            masterJson.detalles_transaccion = {
                "referencia": getVal('z_ref'),
                "fecha_comprobante": getVal('z_date'),
                "monto_nativo": finalUsd,
                "moneda_nativa": "USD"
            };
        } else if (method === 'BINANCE') {
            finalUsd = window.StudentsUtils.parseCurrencyToFloat(getVal('b_amount'));
            masterJson.detalles_origen.identificador_alterno = getVal('b_uid');
            masterJson.detalles_transaccion = {
                "referencia": getVal('b_order'),
                "fecha_comprobante": getVal('b_date'),
                "monto_nativo": finalUsd,
                "moneda_nativa": "USDT"
            };
        }

        masterJson.monto_sistema_usd = finalUsd;
        document.getElementById('payment_method_type').value = method;
        document.getElementById('payment_metadata').value = JSON.stringify(masterJson);
        
        window.StudentsUI.actualizarMontoEnPantalla(finalUsd);
        window.StudentsUI.highlightCard('btnOptDigital');
        
        
        const modal = document.getElementById('modalDigital');
        if (modal) bootstrap.Modal.getOrCreateInstance(modal).hide();

        setTimeout(() => {
            window.StudentsHandlers.mostrarResumenYConfirmar();
        }, 400);
            },

    loadAccountStatus: async function() {
        const offeringId = document.getElementById('offering_id_val')?.value;
        if (!offeringId) {
            Swal.fire('Atención', 'Seleccione un programa primero.', 'warning');
            return;
        }

        const tableBody = document.getElementById('accountStatusBody');
        if (!tableBody) return;

        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando...</td></tr>';
        
        const modalEl = document.getElementById('modalAccountStatus');
        if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();

        try {
            const response = await fetch(`${BASE_URL}/students/payment_registration/getAccountStatus?offering_id=${offeringId}`);
            const res = await response.json();
            if (res.status === 'success') {
                window.StudentsUI.renderDebtTableCheckboxes(res.data);
                window.StudentsHandlers.vincularPrelacionCheckboxes();
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error de comunicación.</td></tr>`;
        }
    },

    vincularPrelacionCheckboxes: function() {
        const checkboxes = Array.from(document.querySelectorAll('.quota-check'));
        checkboxes.forEach((cb, index) => {
            cb.onclick = function(e) {
                if (this.checked) {
                    for (let i = 0; i < index; i++) {
                        if (!checkboxes[i].checked) {
                            e.preventDefault();
                            this.checked = false;
                            Swal.fire('Orden Obligatorio', 'Debe pagar las cuotas más antiguas primero.', 'warning');
                            return;
                        }
                    }
                } else {
                    for (let i = index + 1; i < checkboxes.length; i++) {
                        if (checkboxes[i].checked) {
                            e.preventDefault();
                            this.checked = true;
                            Swal.fire('Acción Inválida', 'No puede desmarcar una cuota antigua si tiene marcadas cuotas recientes.', 'error');
                            return;
                        }
                    }
                }
                window.StudentsHandlers.calcularSumaSeleccionada();
            };
        });
    },

    calcularSumaSeleccionada: function() {
        let suma = 0;
        document.querySelectorAll('.quota-check:checked').forEach(el => { suma += parseFloat(el.dataset.amount) || 0; });
        const display = document.getElementById('modalSelectedTotal');
        if (display) display.innerText = window.StudentsUtils.round(suma).toFixed(2);
    },

    /**
     * Genera el resumen final detallado (Formulario de Verificación).
     */
/**
     * Genera el resumen final detallado y despliega el Modal de Confirmación Avanzado.
     * VERSIÓN: 1.3.0 - Adaptado para nueva jerarquía visual (Tasa -> Bs -> USD).
     */
    mostrarResumenYConfirmar: function() {
        const monto = document.getElementById('amount').value;
        const metodo = document.getElementById('payment_method_type').value;
        const metadataRaw = document.getElementById('payment_metadata').value;
        const fileInput = document.getElementById('pay_screenshot');

        if (!metodo || !metadataRaw || metadataRaw === "" || metadataRaw === "null") {
            Swal.fire({
                title: 'Información Incompleta',
                text: 'Por favor, registre su método de pago y confirme los datos.',
                icon: 'warning'
            });
            return;
        }

        try {
            const meta = JSON.parse(metadataRaw);
            const isCash = metodo === 'CASH';

            // Estudiante
            const studentCode = document.getElementById('student_code_hidden')?.value || window.StudentsUtils.getStudentCode();
            const studentName = document.getElementById('full_name_hidden')?.value || window.StudentsUtils.getStudentName();
            const studentDoc = document.getElementById('document_id_hidden')?.value || 'N/A';
            
            document.getElementById('conf_student').innerText = `${studentDoc} - ${studentName}`;

            // Reset Imagen
            const imgPreview = document.getElementById('conf_image_preview');
            const imgPlaceholder = document.getElementById('conf_img_placeholder');
            imgPreview.classList.add('d-none');
            imgPreview.src = '';
            imgPlaceholder.classList.remove('d-none');
            imgPlaceholder.innerText = isCash ? 'No aplica comprobante' : 'Procesando imagen...';

            if (isCash) {
                document.getElementById('conf_rate_label').innerText = 'TASA: 1,00';
                document.getElementById('conf_bs').innerText = 'N/A';
                document.getElementById('conf_usd').innerText = `$ ${window.StudentsUtils.formatNumberToCurrency(monto)}`;
                document.getElementById('conf_date').innerText = window.StudentsUtils.formatDate(window.StudentsUtils.getSystemDate());
                document.getElementById('conf_ref').innerText = meta.detalles_transaccion.referencia;
                document.getElementById('conf_bank').innerText = 'EFECTIVO (TAQUILLA)';
                document.getElementById('conf_phone').innerText = 'N/A';
            } else {
                // AQUÍ FORZAMOS LA FECHA DEL INPUT DEL USUARIO
                let inputDateStr = '';
                
                if (metodo === 'PAGOMOVIL') {
                    inputDateStr = document.getElementById('pm_date')?.value || meta.detalles_transaccion.fecha_comprobante;
                    const inTasa = document.getElementById('calc_tasa')?.value || '0,00';
                    const inBs   = document.getElementById('calc_bs')?.value || '0,00';
                    const inUsd  = document.getElementById('calc_usd')?.value || '0,00';

                    document.getElementById('conf_rate_label').innerText = `TASA: ${inTasa}`;
                    document.getElementById('conf_bs').innerText = `Bs. ${inBs}`;
                    document.getElementById('conf_usd').innerText = `$ ${inUsd}`; // Ya viene truncado

                    document.getElementById('conf_bank').innerText = meta.detalles_origen.banco_emisor || 'N/A';
                    document.getElementById('conf_phone').innerText = meta.detalles_origen.cuenta_correo_telf || 'N/A';
                } else {
                    inputDateStr = meta.detalles_transaccion.fecha_comprobante;
                    document.getElementById('conf_rate_label').innerText = 'TASA: 1,00';
                    document.getElementById('conf_bs').innerText = 'N/A';
                    document.getElementById('conf_usd').innerText = `$ ${window.StudentsUtils.formatNumberToCurrency(meta.detalles_transaccion.monto_nativo)}`;
                    
                    document.getElementById('conf_bank').innerText = metodo === 'ZELLE' ? 'ZELLE' : 'BINANCE PAY';
                    document.getElementById('conf_phone').innerText = meta.detalles_origen.cuenta_correo_telf || meta.detalles_origen.identificador_alterno || 'N/A';
                }

                document.getElementById('conf_ref').innerText = meta.detalles_transaccion.referencia || 'N/A';
                
                // Mapeo seguro de la fecha a DD/MM/YYYY
                if (inputDateStr) {
                    const [y, m, d] = inputDateStr.split('-');
                    document.getElementById('conf_date').innerText = `${d}/${m}/${y}`;
                } else {
                    document.getElementById('conf_date').innerText = 'N/A';
                }

                // Cargar imagen
                if (fileInput && fileInput.files && fileInput.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imgPreview.src = e.target.result;
                        imgPreview.classList.remove('d-none');
                        imgPlaceholder.classList.add('d-none');
                    };
                    reader.readAsDataURL(fileInput.files[0]);
                }
            }

            const modalEl = document.getElementById('modalPaymentConfirmation');
            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();

            const btnConfirm = document.getElementById('btnConfirmFinalSubmit');
            btnConfirm.onclick = function() {
    btnConfirm.disabled = true;
    btnConfirm.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
    if (typeof window.ejecutarPeticionFinal === 'function') {
        window.ejecutarPeticionFinal().finally(() => {
            btnConfirm.disabled = false;
            btnConfirm.innerHTML = 'Confirmar y Enviar';
        });
    }
};

        } catch (e) {
            console.error(e);
            Swal.fire('Error', 'Error al reconstruir el resumen.', 'error');
        }
    }
};