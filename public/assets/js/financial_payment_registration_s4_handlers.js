/**
 * MÓDULO: GESTIÓN FINANCIERA / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/financial_payment_registration_s4_handlers.js
 * PROPÓSITO: Manejo de lógica de cálculos, construcción de JSON Maestro y confirmación final ultra-detallada.
 * VERSIÓN: 3.0.1 - FIX: Corrección de ID de formulario para inyección de flag de correo.
 */

window.FinancialHandlers = {

    consultarCedula: async function(studentId) {
        if (!studentId || studentId === "0") return "N/A";
        try {
            const response = await fetch(`${BASE_URL}/financial/payment_registration/getStudentIdentity?user_id=${studentId}`);
            const data = await response.json();
            if (data.status === 'success') return data.cedula; 
        } catch (error) {
            console.error("Error buscando cédula en BD:", error);
        }
        return "N/A";
    },
    // -------------------------------

    handleCashPayment: function() {
        const labelVisual = document.getElementById('valAmountCash');
        const montoActual = labelVisual ? labelVisual.innerText : '0,00';

        Swal.fire({
            title: '<h5 class="fw-bold text-success mb-0">Cobro en Efectivo</h5>',
            html: `<div class="text-center p-3">
                    <i class="bi bi-cash-stack text-success fs-1 mb-2 d-block"></i>
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Monto recibido en Taquilla ($)</label>
                    <input id="swal-monto" class="form-control form-control-lg text-center fw-bold text-success" value="${montoActual}">
                   </div>`,
            showCancelButton: true,
            confirmButtonText: 'Confirmar Monto',
            confirmButtonColor: '#198754',
            didOpen: () => { 
                const input = document.getElementById('swal-monto');
                if (input && typeof window.FinancialUtils !== 'undefined') {
                    input.addEventListener('input', window.FinancialUtils.formatCurrency); 
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const displayVal = document.getElementById('swal-monto').value;
                const cleanAmount = window.FinancialUtils.parseCurrencyToFloat(displayVal);
                
                const masterJson = {
                    "metodo": "CASH",
                    "monto_sistema_usd": window.FinancialUtils.round(cleanAmount),
                    "tasa_cambio": 1.00,
                    "detalles_origen": { 
                        "identificador": window.FinancialUtils.getStudentId() || "N/A",
                        "cuenta_correo_telf": "RECEPCIÓN", 
                        "nombre_titular": window.FinancialUtils.getStudentName(), 
                        "banco_emisor": "CASH" 
                    },
                    "detalles_transaccion": { 
                        "referencia": "CASH-" + Date.now().toString().slice(-6), 
                        "fecha_comprobante": window.FinancialUtils.getSystemDate(),
                        "monto_nativo": window.FinancialUtils.round(cleanAmount),
                        "moneda_nativa": "USD"
                    },
                    "auditoria": { 
                        "fecha_registro": window.FinancialUtils.getSystemDateTime(), 
                        "agente": window.FinancialUtils.getAgentId() 
                    }
                };

                document.getElementById('payment_method_type').value = 'CASH';
                document.getElementById('payment_metadata').value = JSON.stringify(masterJson);
                
                if (typeof window.FinancialUI !== 'undefined') {
                    window.FinancialUI.actualizarMontoEnPantalla(cleanAmount);
                    window.FinancialUI.highlightCard('btnOptCash');
                }
                
                Swal.fire({ icon: 'success', title: 'Efectivo Registrado', showConfirmButton: false, timer: 1000 });
            }
        });
    },

    vincularObservadoresDinamicos: function() {
        document.querySelectorAll('.currency-field').forEach(i => {
            i.addEventListener('input', function(e) {
                window.FinancialUtils.formatCurrency(e);
                window.FinancialHandlers.calcularBolivaresEnVivo(); 
            });
        });
    },

    calcularBolivaresEnVivo: function() {
        const methodEl = document.getElementById('digitalMethod');
        if (!methodEl) return;
        
        const method = methodEl.value;
        const tasa = parseFloat(window.sysTasaBcv) || 1.00;

        if (method === 'PAGOMOVIL') {
            const valBs = window.FinancialUtils.parseCurrencyToFloat(document.getElementById('pm_amount_bs')?.value);
            const eqUsd = window.FinancialUtils.round(valBs / tasa);
            const lbl = document.getElementById('digital_amount_usd_lbl');
            if (lbl) lbl.innerText = '$ ' + window.FinancialUtils.formatNumberToCurrency(eqUsd);
        } else if (method === 'ZELLE' || method === 'BINANCE') {
            const inputId = method === 'ZELLE' ? 'z_amount' : 'b_amount';
            const valUsd = window.FinancialUtils.parseCurrencyToFloat(document.getElementById(inputId)?.value);
            const eqBs = window.FinancialUtils.round(valUsd * tasa);
            const lbl = document.getElementById('digital_amount_bs_lbl');
            if (lbl) lbl.innerText = 'Bs. ' + window.FinancialUtils.formatNumberToCurrency(eqBs);
        }
    },

    saveDigitalSelection: async function() {


        
        const method = document.getElementById('digitalMethod')?.value;
        const screenshot = document.getElementById('pay_screenshot')?.files[0];

        // Reset screenshot visual
        const sfReset = document.getElementById('pay_screenshot');
        if (sfReset) { sfReset.style.borderColor = ''; sfReset.style.boxShadow = ''; }
        const getVal = (id) => document.getElementById(id)?.value.trim() || '';
        
        // 0. Reset visual previo de todos los campos posibles
        ['pm_bank','pm_prefix','pm_phone','pm_ref','pm_date','calc_bs','z_email','z_holder','z_ref','z_date','z_amount','b_uid','b_order','b_date','b_amount'].forEach(id => {
            const f = document.getElementById(id);
            if (f) { f.style.borderColor = ''; f.style.boxShadow = ''; }
        });
        
        // 1. Validaciones básicas de entrada

        if (!method || method === "") {
            Swal.fire('Atención', 'Debe seleccionar un canal de recepción.', 'warning');
            return;
        }

        // 2. Configuración de campos
        const fieldsConfig = {

            'PAGOMOVIL': ['pm_bank', 'pm_prefix', 'pm_phone', 'pm_ref', 'pm_date', 'calc_bs'],
            'ZELLE': ['z_email', 'z_holder', 'z_ref', 'z_date', 'z_amount'],
            'BINANCE': ['b_uid', 'b_order', 'b_date', 'b_amount']
        };

        const requiredFields = fieldsConfig[method];
        let firstInvalid = null;
        let hasErrors = false;

        // Limpiar errores previos
        requiredFields.forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                field.classList.remove('is-invalid');
                field.style.borderColor = '';
                field.style.boxShadow = '';
            }
        });

        // Marcar campos vacíos
        requiredFields.forEach(id => {
            const field = document.getElementById(id);
            if (!field || field.value.trim() === '' || field.value === '---') {
                hasErrors = true;
                if (field) {
                    field.classList.add('is-invalid');
                    field.style.borderColor = '#dc3545';
                    field.style.boxShadow = '0 0 0 3px rgba(220,53,69,.25)';
                    if (!firstInvalid) firstInvalid = field;
                }
            } else {
                if (field) {
                    field.classList.remove('is-invalid');
                    field.style.borderColor = '';
                    field.style.boxShadow = '';
                }
            }
        });
        

        if (hasErrors) {
            if (firstInvalid) firstInvalid.focus();
            setTimeout(() => {
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Campos Incompletos', 
                    text: 'Complete todos los campos marcados en rojo y adjunte el comprobante.', 
                    confirmButtonColor: '#d33' 
                });
            }, 100);
            return;
        }

        // Validar screenshot después de campos
        if (!screenshot) {
            const sf = document.getElementById('pay_screenshot');
            if (sf) {
                sf.style.borderColor = '#dc3545';
                sf.style.boxShadow = '0 0 0 3px rgba(220,53,69,.25)';
            }
            setTimeout(() => {
                Swal.fire('Comprobante Requerido', 'Debe adjuntar la imagen del capture para continuar.', 'warning');
            }, 100);
            return;
        }

        //const tasa = parseFloat(window.sysTasaBcv) || 1.00;
        const tasaVisual = document.getElementById('pm_tasa')?.value || document.getElementById('calc_tasa')?.value || window.sysTasaBcv;
        const tasaReal = window.FinancialUtils.parseCurrencyToFloat(tasaVisual) || 1.00;
        
        // --- LÓGICA DE IDENTIFICADOR (FLAG GLOBAL) ---
        // 1. Agarramos el ID del estudiante
        const studentId = window.FinancialUtils.getStudentId();

        // 2. Llamamos a tu nueva función y le pasamos el ID
        const ciFinal = await this.consultarCedula(studentId);
        

        

        // 3. Estructura base del JSON Maestro
        let masterJson = {
            "metodo": method,
            "monto_sistema_usd": 0.00, // Se calcula abajo
            //"tasa_cambio": window.FinancialUtils.round(tasa),
            "tasa_cambio": tasaReal,
            "detalles_origen": { 
                "identificador": ciFinal, 
                "nombre_titular": "NO_SUMINISTRADO", // <--- Cambiamos "N/A" por esto de una vez
                "banco_emisor": (method === 'PAGOMOVIL') ? getVal('pm_bank') : method,
                "cuenta_correo_telf": "" // Se llena abajo
            },
            "detalles_transaccion": {
                "referencia": "",
                "fecha_comprobante": "",
                "monto_nativo": 0,
                "moneda_nativa": ""
            },
            "auditoria": { 
                "fecha_registro": window.FinancialUtils.getSystemDateTime(), 
                "agente": window.FinancialUtils.getAgentId() 
            }
        };

        let finalUsd = 0;

        // 4. Procesamiento específico por método
        if (method === 'PAGOMOVIL') {
            const bsTyped = window.FinancialUtils.parseCurrencyToFloat(getVal('calc_bs'));
            // Intentamos leer el USD ya calculado por la UI, si no, dividimos manualmente
            finalUsd = window.FinancialUtils.parseCurrencyToFloat(getVal('calc_usd'));
            if (!finalUsd || finalUsd <= 0) {
                finalUsd = window.FinancialUtils.round(bsTyped / tasa);
            }

            masterJson.monto_sistema_usd = finalUsd;
            masterJson.detalles_origen.cuenta_correo_telf = getVal('pm_prefix') + "-" + getVal('pm_phone');
            masterJson.detalles_origen.banco_emisor = getVal('pm_bank');
            masterJson.detalles_transaccion.referencia = getVal('pm_ref');
            masterJson.detalles_transaccion.fecha_comprobante = getVal('pm_date');
            masterJson.detalles_transaccion.monto_nativo = window.FinancialUtils.round(bsTyped);
            masterJson.detalles_transaccion.moneda_nativa = "BS";

        } else if (method === 'ZELLE') {
            const montoUsd = window.FinancialUtils.parseCurrencyToFloat(getVal('z_amount'));
            finalUsd = window.FinancialUtils.round(montoUsd);
            masterJson.monto_sistema_usd = finalUsd;
            masterJson.detalles_origen.cuenta_correo_telf = getVal('z_email');
            masterJson.detalles_origen.nombre_titular = getVal('z_holder') || "NO_SUMINISTRADO";
            masterJson.detalles_transaccion.referencia = getVal('z_ref');
            masterJson.detalles_transaccion.fecha_comprobante = getVal('z_date');
            masterJson.detalles_transaccion.monto_nativo = finalUsd;
            masterJson.detalles_transaccion.moneda_nativa = "USD";

        } else if (method === 'BINANCE') {
            const montoUsdt = window.FinancialUtils.parseCurrencyToFloat(getVal('b_amount'));
            finalUsd = window.FinancialUtils.round(montoUsdt);
            masterJson.monto_sistema_usd = finalUsd;
            masterJson.detalles_origen.identificador_alterno = getVal('b_uid');
            masterJson.detalles_transaccion.referencia = getVal('b_order');
            masterJson.detalles_transaccion.fecha_comprobante = getVal('b_date');
            masterJson.detalles_transaccion.monto_nativo = finalUsd;
            masterJson.detalles_transaccion.moneda_nativa = "USDT";
        }


        // comentario
        //alert("🔥 LA VARIABLE ANTES DE GUARDAR ES: " + ciFinal);
        console.log("🔥 JSON COMPLETO ANTES DE GUARDAR: ", masterJson);


        // 5. Inyección de datos en el formulario principal
        const inputMethodType = document.getElementById('payment_method_type');
        const inputMetadata = document.getElementById('payment_metadata');
        const inputAmount = document.getElementById('amount');

        if (inputMethodType) inputMethodType.value = method;
        if (inputMetadata) inputMetadata.value = JSON.stringify(masterJson);
        if (inputAmount) inputAmount.value = finalUsd.toFixed(2);
        
        // 6. Actualización visual de la UI
        if (typeof window.FinancialUI !== 'undefined') {
            window.FinancialUI.actualizarMontoEnPantalla(finalUsd);
            window.FinancialUI.highlightCard('btnOptDigital');
        }
        
        // 7. Cierre de modal
        const modal = document.getElementById('modalDigital');
        if (modal) {
            const instance = bootstrap.Modal.getOrCreateInstance(modal);
            instance.hide();
        }

        Swal.fire({ icon: 'success', title: 'Pago Vinculado', text: 'Datos listos para procesar.', timer: 1500, showConfirmButton: false });
    },

    loadAccountStatus: async function() {
        const studentId = window.FinancialUtils.getStudentId();
        const offeringId = window.FinancialUtils.getOfferingId();
        if (!studentId || !offeringId || studentId === "0") {
            Swal.fire('Atención', 'Seleccione estudiante y diplomado primero.', 'warning');
            return;
        }
        const tableBody = document.getElementById('accountStatusBody');
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando historial...</td></tr>';
        const modal = document.getElementById('modalAccountStatus');
        bootstrap.Modal.getOrCreateInstance(modal).show();
        try {
            const response = await fetch(`${BASE_URL}/financial/payment_registration/getAccountStatus?user_id=${studentId}&offering_id=${offeringId}`);
            const res = await response.json();
            if (res.status === 'success' && typeof window.FinancialUI !== 'undefined') {
                window.FinancialUI.renderDebtTableCheckboxes(res.data);
                window.FinancialHandlers.vincularPrelacionCheckboxes();
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error al cargar datos</td></tr>`;
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
                            Swal.fire({ icon: 'warning', title: 'Orden Obligatorio', text: 'Debe seleccionar las cuotas anteriores primero.', confirmButtonColor: '#0d6efd' });
                            return;
                        }
                    }
                } else {
                    for (let i = index + 1; i < checkboxes.length; i++) {
                        if (checkboxes[i].checked) {
                            e.preventDefault();
                            this.checked = true;
                            Swal.fire({ icon: 'error', title: 'Acción No Permitida', text: 'Debe desmarcar primero las cuotas más recientes.', confirmButtonColor: '#d33' });
                            return;
                        }
                    }
                }
                window.FinancialHandlers.calcularSumaSeleccionada();
            };
        });
    },

    calcularSumaSeleccionada: function() {
        let suma = 0;
        document.querySelectorAll('.quota-check:checked').forEach(el => { suma += parseFloat(el.dataset.amount) || 0; });
        const display = document.getElementById('modalSelectedTotal');
        if (display) display.innerText = window.FinancialUtils.round(suma).toFixed(2);
    },

    mostrarResumenYConfirmar: function() {
        const inputAmount = document.getElementById('amount');
        const inputMethodType = document.getElementById('payment_method_type');
        const metadataRaw = document.getElementById('payment_metadata').value;

        if (!inputAmount || parseFloat(inputAmount.value) <= 0 || !inputMethodType.value) {
            Swal.fire('Atención', 'Complete el monto y el método de pago antes de finalizar.', 'warning');
            return;
        }

        const studentName = window.FinancialUtils.getStudentName();
        const finalAmountUsd = window.FinancialUtils.formatNumberToCurrency(inputAmount.value);
        
        // --- INICIO BLOQUE DE DEPURACIÓN (CAJA NEGRA) ---
        try {
            const debugMeta = JSON.parse(metadataRaw);
            const fileCheck = document.getElementById('pay_screenshot')?.files[0];
            
            console.group("%c🚀 DEPURACIÓN PRE-ENVÍO: DATOS PARA LA BASE DE DATOS", "color: #198754; font-weight: bold; font-size: 13px;");
            console.table({
                "Estudiante ID": window.FinancialUtils.getStudentId(),
                "Oferta ID": window.FinancialUtils.getOfferingId(),
                "Método": inputMethodType.value,
                "Monto Total ($)": inputAmount.value,
                "Referencia (Nativa)": debugMeta.detalles_transaccion?.referencia || "N/A"
            });
            console.log("📄 METADATA MAESTRO (COMPLETO):", debugMeta);
            console.log("📸 ARCHIVO ADJUNTO:", fileCheck ? fileCheck.name : "SIN ARCHIVO (CASH)");
            console.groupEnd();
        } catch (e) {
            console.error("❌ ERROR AL PARSEAR JSON MAESTRO PARA DEPURACIÓN:", e);
        }
        // --- FIN BLOQUE DE DEPURACIÓN ---

        let detailsHtml = "";
        try {
            const meta = JSON.parse(metadataRaw);
            const m = meta.metodo;
            const dO = meta.detalles_origen;
            const dT = meta.detalles_transaccion;

            if (m === 'PAGOMOVIL') {
                detailsHtml = `
                    <div class="p-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-4 mb-3">
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">TIPO DE PAGO:</small><span class="badge bg-primary px-3 rounded-pill">PAGO MÓVIL</span></div>
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">BANCO:</small><span class="smallest fw-bold text-dark">${dO.banco_emisor}</span></div>
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">TELÉFONO EMISOR:</small><span class="smallest fw-bold text-dark">${dO.cuenta_correo_telf}</span></div>
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">REFERENCIA:</small><span class="smallest fw-bold text-primary">${dT.referencia}</span></div>
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">FECHA PAGO:</small><span class="smallest fw-bold text-dark">${dT.fecha_comprobante}</span></div>
                        <hr class="my-2 opacity-25">
                        <div class="d-flex justify-content-between"><small class="text-muted fw-bold text-uppercase smallest">Monto en Bolívares:</small><span class="fw-bold text-primary">Bs. ${window.FinancialUtils.formatNumberToCurrency(dT.monto_nativo)}</span></div>
                    </div>`;
            } else if (m === 'ZELLE') {
                detailsHtml = `
                    <div class="p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-4 mb-3">
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">TIPO DE PAGO:</small><span class="badge bg-success px-3 rounded-pill">ZELLE (USD)</span></div>
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">CORREO CUENTA:</small><span class="smallest fw-bold text-dark">${dO.cuenta_correo_telf}</span></div>
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">TITULAR:</small><span class="smallest fw-bold text-uppercase text-dark">${dO.nombre_titular}</span></div>
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">REFERENCIA:</small><span class="smallest fw-bold text-success">${dT.referencia}</span></div>
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">FECHA PAGO:</small><span class="smallest fw-bold text-dark">${dT.fecha_comprobante}</span></div>
                        <hr class="my-2 opacity-25">
                        <div class="d-flex justify-content-between"><small class="text-muted fw-bold text-uppercase smallest">Monto Nativo:</small><span class="fw-bold text-success">$ ${window.FinancialUtils.formatNumberToCurrency(dT.monto_nativo)}</span></div>
                    </div>`;
            } else if (m === 'BINANCE') {
                detailsHtml = `
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-4 mb-3">
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">TIPO DE PAGO:</small><span class="badge bg-warning text-dark px-3 rounded-pill">BINANCE (USDT)</span></div>
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">IDENTIFICADOR:</small><span class="smallest fw-bold text-dark">${dO.identificador_alterno}</span></div>
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">TXID / ORDEN:</small><span class="smallest fw-bold text-dark text-break" style="max-width: 150px;">${dT.referencia}</span></div>
                        <div class="d-flex justify-content-between mb-1"><small class="text-muted fw-bold">FECHA PAGO:</small><span class="smallest fw-bold text-dark">${dT.fecha_comprobante}</span></div>
                        <hr class="my-2 opacity-25">
                        <div class="d-flex justify-content-between"><small class="text-muted fw-bold text-uppercase smallest">Monto en USDT:</small><span class="fw-bold text-warning" style="filter: brightness(0.8);">${window.FinancialUtils.formatNumberToCurrency(dT.monto_nativo)}</span></div>
                    </div>`;
            } else if (m === 'CASH') {
                detailsHtml = `
                    <div class="p-3 bg-secondary bg-opacity-10 border border-secondary border-opacity-25 rounded-4 mb-3 text-center">
                        <div class="badge bg-secondary px-4 rounded-pill mb-2">EFECTIVO EN TAQUILLA</div>
                        <div class="smallest fw-bold text-muted">REGISTRADO POR RECEPCIÓN</div>
                    </div>`;
            }
        } catch (e) {
            detailsHtml = `<div class="alert alert-danger p-2 smallest text-center">ERROR AL PROCESAR METADATA</div>`;
        }

        Swal.fire({
            title: '<span class="text-dark fw-bold">Confirmar Registro Final</span>',
            html: `
                <div class="text-start p-1" style="font-size: 0.9rem;">
                    <p class="mb-2 text-muted text-uppercase smallest fw-bold tracking-wider">Titular de la Operación:</p>
                    
                    <div class="p-3 bg-light rounded-4 border border-2 mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:30px; height:30px;"><i class="bi bi-person-fill"></i></div>
                            <div>
                                <small class="text-muted d-block smallest fw-bold text-uppercase" style="line-height:1;">Nombre del Estudiante:</small>
                                <span class="fw-bold text-dark text-uppercase">${studentName}</span>
                            </div>
                        </div>
                        <div class="ps-4 ms-2">
                            <small class="text-muted d-block smallest fw-bold text-uppercase">Monto Total a Validar:</small>
                            <span class="fs-2 fw-bold text-success" style="letter-spacing: -1px;">$ ${finalAmountUsd}</span>
                        </div>
                    </div>

                    <p class="mb-2 text-muted text-uppercase smallest fw-bold tracking-wider">Desglose Técnico de Pago:</p>
                    ${detailsHtml}

                    <div class="alert alert-warning border-0 rounded-4 p-2 mb-3 d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-4 me-2 text-warning"></i>
                        <small class="fw-bold" style="font-size: 0.75rem; line-height: 1.1;">Verifique que la referencia coincida con el capture antes de procesar.</small>
                    </div>

                    <div class="form-check form-switch d-flex justify-content-center align-items-center bg-light py-2 rounded border">
                        <input class="form-check-input me-2" type="checkbox" role="switch" id="swal-send-email" checked style="cursor: pointer; transform: scale(1.2);">
                        <label class="form-check-label smallest fw-bold text-muted" for="swal-send-email" style="cursor: pointer; padding-top: 2px;">
                            Enviar notificación por correo al estudiante
                        </label>
                    </div>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Sí, Procesar Registro',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            customClass: { popup: 'rounded-4 border-0 shadow-lg' }
        }).then((result) => {
            if (result.isConfirmed) {
                // CAPTURA DEL ESTADO DEL CHECKBOX
                const sendEmailChecked = document.getElementById('swal-send-email').checked;
                
                // GUARDAMOS EL ESTADO EN UN INPUT OCULTO PARA QUE LA FUNCIÓN FINAL LO LEA
                let emailInput = document.getElementById('send_notification_flag');
                if (!emailInput) {
                    emailInput = document.createElement('input');
                    emailInput.type = 'hidden';
                    emailInput.id = 'send_notification_flag';
                    
                    // FIX: Enganchamos el input al formulario correcto o directo al body si no lo encuentra
                    const formEl = document.getElementById('formRegistrationPayment') || document.body;
                    formEl.appendChild(emailInput);
                }
                emailInput.value = sendEmailChecked ? '1' : '0';

                if (typeof window.ejecutarPeticionFinal === 'function') {
                    window.ejecutarPeticionFinal(); 
                }
            }
        });
    }
};