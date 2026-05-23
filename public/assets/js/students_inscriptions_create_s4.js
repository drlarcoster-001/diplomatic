/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: public/assets/js/students_inscriptions_create_s4.js
 * PROPÓSITO: Gestión de pagos con Arquitectura Metadata Maestro (v1.2).
 * VERSIÓN: 3.8.0 - FULL REPAIR: Datos dinámicos PM y habilitación de Zelle/Binance sin info box.
 */

(function() {
    const inputMethodType = document.getElementById('payment_method_type');
    const inputMetadata   = document.getElementById('payment_metadata');
    const inputAmount     = document.getElementById('amount'); 
    const dynamicFields   = document.getElementById('dynamicFields');
    const selectorMethod  = document.getElementById('digitalMethod');
    const cards           = document.querySelectorAll('.payment-option-card');

    // --- NUEVAS VARIABLES GLOBALES PARA LA CALCULADORA Y TASA ---
    let sysTasaBcv = 1.00;
    let sysMinimoUsd = 0.00;

    // --- LLAVE MAESTRA: Obtener datos del sistema ---
    const getStudentId = () => document.getElementById('document_id_hidden')?.value || 'N/A';
    window.getOfferingId = () => document.querySelector('input[name="offering_id"]')?.value || 0;

    const getAgentName = () => {
        // Busca el nombre en el input oculto que pusimos en el PHP
        const userName = document.getElementById('user_name_hidden')?.value;
        return userName ? userName : "ESTUDIANTE:" + getStudentId();
    };
    
    // Mejora de detección de ruta para evitar Error 400
    const getBaseUrl = () => {
        const path = window.location.pathname;
        if (path.includes('/public/')) {
            return window.location.origin + path.split('/public/')[0] + '/public';
        }
        return window.location.origin + '/diplomatic/public';
    };

    document.addEventListener('DOMContentLoaded', function() {
        
        // --- NUEVO: Precargar datos de la oferta (Tasa BCV y Monto Mínimo) en silencio ---
        fetch(`${getBaseUrl()}/students/inscriptions/getPaymentPlan?id=${getOfferingId()}`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    sysTasaBcv = parseFloat(data.tasa_bcv) || 1.00;
                    if(data.initial_payment) {
                        sysMinimoUsd = parseFloat(data.initial_payment.total_minimo_usd) || 0;
                        document.getElementById('display_amount_readonly').value = sysMinimoUsd.toFixed(2);
                        if (inputAmount) inputAmount.value = sysMinimoUsd.toFixed(2);
                        window.currentMontoUsdSelected = sysMinimoUsd;
                    }
                }
            }).catch(e => console.error("Error pre-cargando tasa:", e));

        // --- 1. MANEJO DE EFECTIVO (CASH) ---
/**
 * REEMPLAZO: Evento click de Efectivo
 * UBICACIÓN: Aproximadamente línea 45 del archivo JS
 */
document.getElementById('btnOptCash')?.addEventListener('click', async function() {
    // 1. LEER LA VERDAD ACTUAL: No usamos sysMinimoUsd, usamos lo que esté en la pantalla
    const montoActualEnPantalla = document.getElementById('display_amount_readonly').value;
    const sugerido = parseFloat(montoActualEnPantalla).toLocaleString('de-DE', { minimumFractionDigits: 2 });

    const { value: montoEscrito } = await Swal.fire({
        title: '<h5 class="fw-bold text-success mb-0">Registro de Efectivo</h5>',
        html: `
            <div class="text-center p-3">
                <i class="bi bi-cash-stack text-success fs-1 mb-2 d-block"></i>
                <label class="smallest fw-bold text-muted text-uppercase mb-2">Monto a recibir en Taquilla ($)</label>
                <input id="swal-monto-cash" class="form-control form-control-lg text-center fw-bold text-success shadow-sm" 
                       value="${sugerido}" style="border-radius: 15px; border: 2px solid #198754;">
                <div class="mt-3 p-2 bg-light rounded-3 border">
                    <small class="text-muted d-block smallest text-uppercase">Mínimo sugerido por Plan:</small>
                    <span class="fw-bold text-danger">$ ${montoActualEnPantalla}</span>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Confirmar Monto',
        confirmButtonColor: '#198754',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const input = document.getElementById('swal-monto-cash');
            input.focus();
            input.addEventListener('input', formatCurrency);
        },
        preConfirm: () => {
            const valor = document.getElementById('swal-monto-cash').value;
            const limpio = parseFloat(valor.replace(/\./g, '').replace(',', '.'));
            
            // Validación: No permitir menos de lo que el Plan de Pago exige actualmente
            //if (!limpio || limpio < parseFloat(montoActualEnPantalla)) {
            //    Swal.showValidationMessage(`El monto no puede ser menor al seleccionado en el plan ($${montoActualEnPantalla})`);
            //    return false;
            //}
            return limpio;
        }
    });

    if (montoEscrito) {
        const montoFinal = parseFloat(montoEscrito);

        // 2. ACTUALIZACIÓN BIDIRECCIONAL:
        // Escribimos en el cuadro de lectura para que se vea el cambio
        document.getElementById('display_amount_readonly').value = montoFinal.toFixed(2);
        // Escribimos en el input oculto para el POST del formulario
        if (inputAmount) inputAmount.value = montoFinal.toFixed(2);

        // Armamos el Metadata (Se mantiene igual tu lógica)
        const masterJson = {
            metodo: "CASH",
            monto_sistema_usd: montoFinal,
            tasa_cambio: 1.00,
            detalles_origen: { identificador: getStudentId(), cuenta_correo_telf: "RECEPCIÓN", nombre_titular: "ESTUDIANTE", banco_emisor: "CASH" },
            detalles_transaccion: { referencia: "CASH-" + Date.now().toString().slice(-6), fecha_comprobante: new Date().toISOString().split('T')[0], monto_nativo: montoFinal, moneda_nativa: "USD" },
            auditoria: { fecha_registro: new Date().toISOString().replace('T', ' ').split('.')[0], agente: getAgentName() }
        };

        inputMethodType.value = 'CASH';
        inputMetadata.value = JSON.stringify(masterJson);
        
        highlightCard('btnOptCash');
        document.getElementById('displayAmountCash')?.classList.remove('d-none');
        document.getElementById('valAmountCash').innerText = montoFinal.toFixed(2);
        document.getElementById('digitalSummary')?.classList.add('d-none');

        Swal.fire({ icon: 'success', title: 'Monto Actualizado', showConfirmButton: false, timer: 1000 });
    }
});



        // --- 2. MANEJO DE DIGITAL ---
        document.getElementById('btnOptDigital')?.addEventListener('click', function(e) {
            e.preventDefault();
            const modalEl = document.getElementById('modalDigital');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                setTimeout(() => modalInstance.show(), 10);
            }
        });

        if (selectorMethod) {
            selectorMethod.addEventListener('change', function() { renderFieldsByChannel(this.value); });
        }

        document.getElementById('btnConfirmDigital')?.addEventListener('click', saveDigitalSelection);
    });

    const formatCurrency = (e) => {
        let value = e.target.value.replace(/\D/g, "");
        if (value === "") return;
        value = (value / 100).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        e.target.value = value;
    };

    /**
     * GENERADOR DE CAMPOS - UI OPTIMIZADA Y SINCRONIZADA
     */
    

    function renderFieldsByChannel(method) {
        if (!dynamicFields) return;
        dynamicFields.innerHTML = '';
        dynamicFields.classList.toggle('d-none', !method);
        if (!method) return;

        // 1. Recuperar memoria y estados del sistema
        const displayInput = document.getElementById('display_amount_readonly');
        const valorPantalla = parseFloat(displayInput ? displayInput.value : 0);
        let montoUsdInicial = window.currentMontoUsdSelected || valorPantalla;
        if (isNaN(montoUsdInicial) || montoUsdInicial === 0) montoUsdInicial = sysMinimoUsd;

        const tasaStr = sysTasaBcv.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const selUsd = montoUsdInicial.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const config = window.PAYMENT_CONFIG || {};

        let html = '';

        // BLOQUE 1: ID / DOCUMENTO ESTUDIANTE
        html += `
            <div class="mb-3 p-2 bg-light rounded-3 border text-start animate__animated animate__fadeIn">
                <small class="text-muted d-block smallest text-uppercase fw-bold">ID / Documento Estudiante:</small>
                <span class="fw-bold text-primary fs-6">${getStudentId()}</span>
            </div>`;

        if (method === 'PAGOMOVIL') {
            const dataPM = config.pago_movil || { banco: 'BANCO MERCANTIL', telefono: '04245024183', cedula: '14399195' };
            
            // BLOQUE 2: DATOS PARA PAGAR (DESTINO)
            html += `
                <div class="alert alert-info py-2 px-3 mb-3 border-0 rounded-4 shadow-sm text-start animate__animated animate__fadeIn">
                    <div class="fw-bold text-primary mb-1 text-uppercase border-bottom pb-1 smallest">
                        <i class="bi bi-bank me-1"></i> Datos para Transferir (Destino):
                    </div>
                    <div class="text-dark smallest">
                        <b>BANCO:</b> ${dataPM.banco}<br>
                        <b>TELÉFONO:</b> ${dataPM.telefono}<br>
                        <b>CÉDULA:</b> ${dataPM.cedula}
                    </div>
                </div>`;

            // BLOQUE 3: CAMPOS DE ENTRADA REORGANIZADOS
            html += `
                <div class="text-start animate__animated animate__fadeIn">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Banco Origen</label>
                    <select name="pm_bank" class="form-select mb-3 rounded-3 shadow-sm" required>
                        <option value="">-- Seleccionar Banco --</option>
                        <option value="0102 - BANCO DE VENEZUELA">0102 - Banco de Venezuela</option>
                        <option value="0104 - VENEZOLANO DE CRÉDITO">0104 - Venezolano de Crédito</option>
                        <option value="0105 - BANCO MERCANTIL">0105 - Banco Mercantil</option>
                        <option value="0108 - BBVA PROVINCIAL">0108 - BBVA Provincial</option>
                        <option value="0114 - BANCARIBE">0114 - Bancaribe</option>
                        <option value="0115 - BANCO EXTERIOR">0115 - Banco Exterior</option>
                        <option value="0128 - BANCO CARONI">0128 - Banco Caroní</option>
                        <option value="0134 - BANESCO">0134 - Banesco</option>
                        <option value="0137 - BANCO SOFITASA">0137 - Banco Sofitasa</option>
                        <option value="0138 - BANCO PLAZA">0138 - Banco Plaza</option>
                        <option value="0146 - BANGENTE">0146 - Bangente</option>
                        <option value="0151 - BFC BANCO FONDO COMUN">0151 - BFC Banco Fondo Común</option>
                        <option value="0156 - 100% BANCO">0156 - 100% Banco</option>
                        <option value="0157 - DELSUR BANCO UNIVERSAL">0157 - DelSur Banco Universal</option>
                        <option value="0163 - BANCO DEL TESORO">0163 - Banco del Tesoro</option>
                        <option value="0166 - BANCO AGRICOLA DE VENEZUELA">0166 - Banco Agrícola de Venezuela</option>
                        <option value="0168 - BANCRECER">0168 - Bancrecer</option>
                        <option value="0169 - MI BANCO">0169 - Mi Banco</option>
                        <option value="0171 - BANCO ACTIVO">0171 - Banco Activo</option>
                        <option value="0172 - BANCAMIGA">0172 - Bancamiga</option>
                        <option value="0173 - BANCO INTERNACIONAL DE DESARROLLO">0173 - Banco Internacional de Desarrollo</option>
                        <option value="0174 - BANPLUS">0174 - Banplus</option>
                        <option value="0175 - BANCO DIGITAL DE LOS TRABAJADORES">0175 - Banco Digital de los Trabajadores</option>
                        <option value="0177 - BANFANB">0177 - BANFANB</option>
                        <option value="0178 - N58 BANCO DIGITAL">0178 - N58 Banco Digital</option>
                        <option value="0191 - BNC BANCO NACIONAL DE CREDITO">0191 - BNC Banco Nacional de Crédito</option>
                    </select>

                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Fecha de Pago</label>
                    <input type="date" name="pm_date" id="pm_date" class="form-control mb-3 rounded-3 shadow-sm" required onkeydown="return false;" style="cursor:pointer;">

                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Número de Teléfono Emisor</label>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <select name="pm_prefix" class="form-select rounded-3 shadow-sm" required>
                                <option value="">Cód.</option>
                                <option value="0412">0412</option><option value="0414">0414</option>
                                <option value="0424">0424</option><option value="0416">0416</option>
                                <option value="0426">0426</option>
                            </select>
                        </div>
                        <div class="col-8">
                            <input type="tel" name="pm_phone" class="form-control rounded-3 shadow-sm" placeholder="Celular" maxlength="7" pattern="[0-9]{7}" required>
                        </div>
                    </div>

                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Referencia Bancaria</label>
                    <input type="text" name="pm_ref" class="form-control mb-3 rounded-3 shadow-sm" placeholder="Número de confirmación" required>

                    <!-- CUADRO MÁGICO: TASA, USD Y BS[cite: 2] -->
                    <div class="p-3 bg-primary-subtle rounded-4 border border-primary mb-3 shadow-sm">
                        <div class="row g-2 mb-3 text-center">
                            <div class="col-6 text-start">
                                <label class="smallest fw-bold text-primary text-uppercase mb-1">Tasa:</label>
                                <input type="text" name="pm_tasa" id="calc_tasa" class="form-control fw-bold text-center border-2 bg-white" value="---" readonly>
                            </div>
                            <div class="col-6 text-start">
                                <label class="smallest fw-bold text-primary text-uppercase mb-1">Monto USD ($):</label>
                                <input type="text" id="calc_usd" name="pm_amount_usd" class="form-control fw-bold text-center border-2 shadow-sm" value="---" readonly>
                            </div>
                        </div>
                        <div class="pt-1">
                            <label class="smallest fw-bold text-dark text-uppercase mb-1">Monto Bs. Transferido:</label>
                            <input type="text" name="pm_amount" id="calc_bs" class="form-control fs-5 fw-bold text-primary text-center border-2 currency-field shadow-sm" placeholder="0,00" required>
                            <div id="conversion-live" class="text-end small mt-1 d-none" style="font-size: 0.75rem;"></div>
                        </div>
                    </div>
                </div>`;

        } else if (method === 'ZELLE') {
            // RESTAURACIÓN COMPLETA ZELLE[cite: 2]
            html += `
                <div class="text-start animate__animated animate__fadeIn">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Correo Electrónico Emisor</label>
                    <input type="email" name="z_email" class="form-control mb-3 rounded-3 shadow-sm" placeholder="correo@ejemplo.com" required>
                    
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Nombre del Titular de la Cuenta</label>
                    <input type="text" name="z_issuer" class="form-control mb-3 rounded-3 shadow-sm" placeholder="Como aparece en el banco" required>
                    
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Referencia / ID de Confirmación</label>
                    <input type="text" name="z_ref" class="form-control mb-3 rounded-3 shadow-sm" placeholder="Número de confirmación" required>
                    
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Fecha de Operación</label>
                    <input type="date" name="z_date" class="form-control mb-3 rounded-3 shadow-sm" required>
                    
                    <label class="smallest fw-bold text-success text-uppercase mb-1">Monto Enviado (USD)</label>
                    <input type="text" name="z_amount" class="form-control currency-field fs-5 fw-bold text-success text-center border-2 rounded-3 shadow-sm" placeholder="0,00" required>
                </div>`;

        } else if (method === 'BINANCE') {
            // RESTAURACIÓN COMPLETA BINANCE[cite: 2]
            html += `
                <div class="text-start animate__animated animate__fadeIn">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Binance ID o Correo</label>
                    <input type="text" name="b_uid" class="form-control mb-3 rounded-3 shadow-sm" placeholder="Su identificador en Binance" required>
                    
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">ID de Orden (TXID)</label>
                    <input type="text" name="b_order" class="form-control mb-3 rounded-3 shadow-sm" placeholder="Referencia de Binance Pay" required>
                    
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Fecha del Pago</label>
                    <input type="date" name="b_date" class="form-control mb-3 rounded-3 shadow-sm" required>
                    
                    <label class="smallest fw-bold text-warning text-uppercase mb-1">Monto Enviado (USDT)</label>
                    <input type="text" name="b_amount" class="form-control currency-field fs-5 fw-bold text-warning text-center border-2 rounded-3 shadow-sm" placeholder="0,00" required>
                </div>`;
        }

        // CAPTURE / COMPROBANTE FINAL[cite: 2]
        html += `
            <div class="text-start mt-3 animate__animated animate__fadeIn">
                <label class="smallest fw-bold text-muted text-uppercase mb-1">Capture / Comprobante de Pago:</label>
                <input type="file" name="pay_screenshot" class="form-control rounded-3 shadow-sm" accept="image/*" required>
            </div>`;
        
        dynamicFields.innerHTML = html;

        // ACTIVACIÓN DE REACTIVIDAD Y CÁLCULO MÁGICO[cite: 2]
        dynamicFields.querySelectorAll('.currency-field').forEach(input => {
            input.addEventListener('input', function(e) {
                formatCurrency(e);
                
                const tasaInput = document.getElementById('calc_tasa');
                if (!tasaInput) return; // Zelle/Binance no usan tasa dinámica en el front igual que PM

                const tasaVal = parseFloat(tasaInput.value.replace(/\./g, '').replace(',', '.')) || 0;
                if (tasaVal <= 0) return;

                if (this.id === 'calc_bs') {
                    const bs = parseFloat(this.value.replace(/\./g, '').replace(',', '.')) || 0;
                    const usdResult = Math.floor(bs / tasaVal * 100) / 100;
                    document.getElementById('calc_usd').value = usdResult.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
                
                // Actualizar comparativa visual con el mínimo[cite: 2]
                const currentUsd = parseFloat(document.getElementById('calc_usd').value.replace(/\./g, '').replace(',', '.')) || 0;
                const convDiv = document.getElementById('conversion-live');
                if (convDiv) {
                    convDiv.classList.remove('d-none');
                    const color = currentUsd >= (montoUsdInicial - 0.01) ? 'text-success' : 'text-danger';
                    convDiv.innerHTML = `<span class="${color} fw-bold">Calculado: $${currentUsd.toFixed(2)} USD</span>`;
                }
            });
        });

        // RE-SINCRONIZACIÓN DE TASA SEGÚN FECHA SELECCIONADA[cite: 2]
        const dateInput = document.getElementById('pm_date');
        if (dateInput) {
            dateInput.addEventListener('change', async function() {
                const fecha = this.value;
                const tasaInput = document.getElementById('calc_tasa');
                if (!fecha || !tasaInput) return;

                tasaInput.value = "Buscando...";
                const usdInput = document.getElementById('calc_usd');
                const bsInput  = document.getElementById('calc_bs');
                if (usdInput) usdInput.value = '---';
                if (bsInput)  bsInput.value  = '';
                try {
                    const response = await fetch(`${getBaseUrl()}/students/inscriptions/getRateByDate?date=${fecha}`);
                    const res = await response.json();
                    if (res.success && res.dolar_bcv > 0) {
                        sysTasaBcv = parseFloat(res.dolar_bcv);
                        tasaInput.value = sysTasaBcv.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        if (usdInput) usdInput.value = '---';
                    } else {
                        tasaInput.value = "0,00";
                        Swal.fire({ icon: 'warning', title: 'Tasa no encontrada', text: res.message || 'Verifique la fecha.' });
                    }
                } catch (e) {
                    tasaInput.value = "Error";
                }
            });
        }
    }


/**
 * EMPAQUETADOR DE METADATA Y SINCRONIZACIÓN (VERSIÓN SERIA)
 * Propósito: Confirmar el pago SIEMPRE y guardar los dólares exactos sin redondeos destructivos.
 */
function saveDigitalSelection() {
    const method = selectorMethod.value;
    const inputs = dynamicFields.querySelectorAll('input[required], select[required]');
    let valid = true;
    const rawData = {};
    
    inputs.forEach(i => { 
        if (!i.value || i.value === '---') { 
            i.style.borderColor = '#dc3545';
            i.style.boxShadow = '0 0 0 3px rgba(220,53,69,.25)';
            valid = false; 
        } else { 
            i.style.borderColor = '';
            i.style.boxShadow = '';
            rawData[i.name] = i.value; 
        } 
    });
    
    if (!valid) {
        setTimeout(() => {
            Swal.fire({ icon: 'error', title: 'Campos Incompletos', text: 'Complete los campos marcados en rojo.', confirmButtonColor: '#d33' });
        }, 100);
        return;
    }

    let calculatedUsd = 0;
    let finalValueToSave = 0; 

    if (method === 'PAGOMOVIL') {
        const montoBs = parseFloat(rawData.pm_amount.replace(/\./g, '').replace(',', '.'));
        
        // --- CÁLCULO FINANCIERO SERIO ---
        // Redondeo matemático a 2 decimales. Si el pago da 69.98 USD, guarda 69.98.
        calculatedUsd = Number(Math.round((montoBs / sysTasaBcv) + 'e2') + 'e-2'); 
        finalValueToSave = montoBs;
    } else {
        const field = rawData.z_amount || rawData.b_amount || "0";
        const montoManual = parseFloat(field.replace(/\./g, '').replace(',', '.'));
        
        // Para Zelle/Binance, respetamos el monto manual tal cual
        calculatedUsd = Number(Math.round(montoManual + 'e2') + 'e-2');
        finalValueToSave = montoManual;
    }

    Swal.fire({
        title: '¿Confirmar registro?',
        html: `<b>Método:</b> ${method}<br><b>Monto Reconocido:</b> $${calculatedUsd.toFixed(2)} USD`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Sí, confirmar pago',
        cancelButtonText: 'Revisar'
    }).then((result) => {
        if (result.isConfirmed) {
            
            // 1. CONSTRUCCIÓN DEL JSON (ESTRUCTURA MAESTRO V1.2 - SERIA)
            let masterJson = { 
                metodo: method, 
                // Se envía el monto exacto con sus decimales
                monto_sistema_usd: calculatedUsd, 
                tasa_cambio: (method === 'PAGOMOVIL') ? Number(sysTasaBcv.toFixed(2)) : 1.00,
                detalles_origen: { 
                    identificador: getStudentId(),
                    banco_emisor: (method === 'PAGOMOVIL') ? rawData.pm_bank : (method === 'ZELLE' ? 'ZELLE' : 'BINANCE'),
                    cuenta_correo_telf: (method === 'PAGOMOVIL') ? (rawData.pm_prefix + "-" + rawData.pm_phone) : (rawData.z_email || rawData.b_uid || 'N/A')
                }, 
                detalles_transaccion: {
                    referencia: rawData.pm_ref || rawData.z_ref || rawData.b_order,
                    fecha_comprobante: rawData.pm_date || rawData.z_date || rawData.b_date,
                    monto_nativo: finalValueToSave,
                    moneda_nativa: (method === 'PAGOMOVIL') ? "BS" : (method === 'BINANCE' ? "USDT" : "USD")
                },
                auditoria: { 
                    fecha_registro: new Date().toISOString().replace('T', ' ').split('.')[0], 
                    agente: getAgentName()
                }
            };

            // 2. GUARDAR EN INPUTS OCULTOS
            inputMethodType.value = method;
            inputMetadata.value = JSON.stringify(masterJson);
            inputAmount.value = finalValueToSave.toFixed(2);

            // 3. ACTUALIZACIÓN DE VISTA (GAVETA DE RESUMEN)
            window.lastPaymentData = { method, rawData, calculatedUsd, finalValueToSave, ref: masterJson.detalles_transaccion.referencia };

            const aplicarPintado = () => {
                const detailBox = document.getElementById('payment_detail_box');
                if (detailBox) {
                    const strongs = detailBox.querySelectorAll('strong');
                    if (strongs.length >= 5) {
                        if (method === 'PAGOMOVIL') {
                            strongs[0].innerText = rawData.pm_bank.includes(' - ') ? rawData.pm_bank.split(' - ')[1] : rawData.pm_bank;
                            strongs[2].innerText = rawData.pm_prefix + "-" + rawData.pm_phone;
                        } else {
                            strongs[0].innerText = method;
                            strongs[2].innerText = rawData.z_email || rawData.b_uid || '-';
                        }
                        strongs[3].innerText = window.lastPaymentData.ref;
                        strongs[4].innerText = (method === 'PAGOMOVIL') ? finalValueToSave.toLocaleString('de-DE', { minimumFractionDigits: 2 }) + " Bs." : "$ " + finalValueToSave.toLocaleString('de-DE', { minimumFractionDigits: 2 });
                        
                        const spanUsd = detailBox.querySelector('span.text-success');
                        if (spanUsd) spanUsd.innerText = `($ ${calculatedUsd.toFixed(2).replace('.', ',')} USD)`;
                    }
                }
            };

            aplicarPintado(); 
            setTimeout(aplicarPintado, 100); 

            document.getElementById('lblDigital').innerText = method;
            if (document.getElementById('resume_method_badge')) document.getElementById('resume_method_badge').innerText = method;
            document.getElementById('digitalSummary')?.classList.remove('d-none');
            highlightCard('btnOptDigital');
            
            const modalEl = document.getElementById('modalDigital');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
            
            Swal.fire({ icon: 'success', title: 'Pago Vinculado', timer: 1000, showConfirmButton: false });
        }
    });
}


    
// --- FUNCIÓN PARA RESALTAR O DESMARCAR ---
// --- FUNCIÓN PARA DESELECCIONAR TODO (AL CLIC EN LA X) ---
window.deselectMethod = function() {
    // 1. Limpieza visual
    const cards = document.querySelectorAll('.payment-option-card');
    cards.forEach(c => {
        c.classList.remove('border-primary', 'bg-primary-subtle', 'active-selection');
        c.querySelector('.selection-check')?.classList.add('d-none');
        c.querySelector('.btn-deselect')?.classList.add('d-none'); // Esconde la X
    });

    // 2. Limpieza de datos (Evita enviar basura al Backend)
    const inputMethodType = document.getElementById('payment_method_type');
    const inputMetadata   = document.getElementById('payment_metadata');
    
    if(inputMethodType) inputMethodType.value = '';
    if(inputMetadata) inputMetadata.value = '';

    // 3. Esconder los cuadros de resumen de las tarjetas
    document.getElementById('displayAmountCash')?.classList.add('d-none');
    document.getElementById('digitalSummary')?.classList.add('d-none');

    // 4. Feedback al usuario
    Swal.fire({ icon: 'info', title: 'Selección anulada', timer: 800, showConfirmButton: false });
};
// --- FUNCIÓN PARA RESALTAR LA TARJETA SELECCIONADA ---
function highlightCard(id) {
    // 1. Deseleccionamos TODO visualmente para evitar duplicados
    const cards = document.querySelectorAll('.payment-option-card');
    cards.forEach(c => {
        c.classList.remove('border-primary', 'bg-primary-subtle', 'active-selection');
        c.querySelector('.selection-check')?.classList.add('d-none');
        c.querySelector('.btn-deselect')?.classList.add('d-none'); // Ocultar X
    });

    // 2. Activamos solo la tarjeta clicada
    const activeCard = document.getElementById(id);
    if (activeCard) {
        activeCard.classList.add('border-primary', 'bg-primary-subtle', 'active-selection');
        activeCard.querySelector('.selection-check')?.classList.remove('d-none');
        
        // Mostrar la X roja solo en la tarjeta activa
        const badgeX = activeCard.querySelector('.btn-deselect');
        if (badgeX) badgeX.classList.remove('d-none');
    }
}

// --- AJUSTE EN EL CLIC DE DIGITAL ---
// --- 2. MANEJO DE DIGITAL ---
        document.getElementById('btnOptDigital')?.addEventListener('click', function(e) {
            e.preventDefault();
            // Ya no validamos si tiene 'active-selection' para apagarlo, 
            // porque de eso se encarga ahora la "X".
            // Simplemente abrimos el modal de Zelle/Binance/PM.
            const modalEl = document.getElementById('modalDigital');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                setTimeout(() => modalInstance.show(), 10);
            }
        });


    window.validateStep4 = () => {
        if (!inputMethodType.value) {
            Swal.fire('Pago Requerido', 'Debe registrar su modalidad de pago.', 'warning');
            return false;
        }
        return true;
    };

    // --- NUEVO: UI FINANCIERA DEL PLAN DE PAGOS (CON TASA BCV) ---

// --- MEMORIA DEL PLAN DE PAGOS ---
let sysSelectedQuotas = [0,1]; // 🔥 CAMBIO 1: Ahora iniciamos SOLO con Inscripción (0)


window.verPlanDePagosPaso4 = function(offeringId) {
    if (!offeringId) return;
    
    const urlPlan = `${getBaseUrl()}/students/inscriptions/getPaymentPlan?id=${offeringId}`;
    
    Swal.fire({ title: 'Cargando información...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch(urlPlan)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let totalDiplomadoUsd = 0;
                let html = `
                    <div class="table-responsive rounded-3 border mt-2">
                        <table class="table table-sm table-hover mb-0 text-start smallest" id="table-plan-pagos">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>Concepto</th>
                                    <th class="text-end pe-3">Monto $</th>
                                </tr>
                            </thead>
                            <tbody>`;
                
                data.plan.forEach((p, index) => {
                    let cUsd = parseFloat(p.amount) || 0;
                    totalDiplomadoUsd += cUsd;
                    
                    html += `
                        <tr>
                            <td class="text-center text-muted">${index + 1}</td>
                            <td class="fw-bold text-secondary text-uppercase">${p.name}</td>
                            <td class="text-end fw-bold text-dark pe-3">$ ${cUsd.toFixed(2)}</td>
                        </tr>`;
                });

                html += `</tbody></table></div>

                        <div class="mt-3 p-3 bg-primary text-white rounded-4 shadow-sm d-flex justify-content-between align-items-center">
                            <div class="text-start">
                                <small class="text-white-50 fw-bold d-block smallest text-uppercase">Inversión Académica</small>
                                <h4 class="fw-bold mb-0">Total Diplomado</h4>
                            </div>
                            <div class="text-end border-start border-white-50 ps-3">
                                <h3 class="fw-bold mb-0">$ ${totalDiplomadoUsd.toFixed(2)}</h3>
                            </div>
                        </div>`;

                Swal.fire({
                    title: '<h6 class="fw-bold text-primary mb-0"><i class="bi bi-info-circle me-2"></i>Información de Cuotas</h6>',
                    html: html,
                    width: '500px',
                    showCloseButton: true,
                    confirmButtonText: 'Cerrar',
                    confirmButtonColor: '#6c757d'
                });
            }
        })
        .catch(e => {
            console.error("Error cargando plan:", e);
            Swal.fire('Error', 'No se pudo cargar la información.', 'error');
        });
};

window.refreshDigitalAmount = function() {
    const fechaConsulta = window.currentFechaTasaSelected || new Date().toISOString().split('T')[0];
    
    const btn = document.querySelector('button[onclick="window.refreshDigitalAmount()"] i');
    if (btn) btn.classList.add('fa-spin');

    // 🔥 BUSCAMOS LA TASA POR LA FECHA GUARDADA, NO LA DE HOY
    fetch(`${getBaseUrl()}/students/inscriptions/getRateByDate?date=${fechaConsulta}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && parseFloat(data.dolar_bcv) > 0) {
                // 1. Actualizamos la memoria
                sysTasaBcv = parseFloat(data.dolar_bcv);
                
                // 2. Leemos la pantalla con seguro anti-errores
                const displayInput = document.getElementById('display_amount_readonly');
                let montoUsdActual = window.currentMontoUsdSelected || parseFloat(displayInput ? displayInput.value : 0);
                if (isNaN(montoUsdActual) || montoUsdActual === 0) montoUsdActual = sysMinimoUsd;

                const nuevoTotalBs = montoUsdActual * sysTasaBcv;

                // 3. INYECCIÓN DIRECTA AL DOM A TODOS LOS CAMPOS
                const spanTasa = document.getElementById('span-tasa-dinamica');
                const spanTotal = document.getElementById('span-total-bs-dinamico');
                const spanUsd = document.getElementById('span-usd-dinamico');
                const spanFecha = document.getElementById('span-fecha-dinamica');

                if (spanTasa) spanTasa.innerText = sysTasaBcv.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Bs.';
                if (spanTotal) spanTotal.innerText = nuevoTotalBs.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' Bs.';
                if (spanUsd) spanUsd.innerText = '$ ' + montoUsdActual.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (spanFecha) spanFecha.innerHTML = `<i class="bi bi-calendar-event me-1"></i>${data.found_date || fechaConsulta}`;

                // Actualizamos la conversión verde de Pago Móvil si ya escribió algo
                const inputPm = document.querySelector('input[name="pm_amount"]');
                if (inputPm && inputPm.value) {
                    inputPm.dispatchEvent(new Event('input'));
                }

                Swal.fire({ icon: 'success', title: 'Tasa Sincronizada', timer: 1000, showConfirmButton: false });
            } else {
                Swal.fire('Atención', 'No se encontró tasa registrada para la fecha: ' + fechaConsulta, 'warning');
            }
        })
        .catch(e => {
            console.error("Error al refrescar:", e);
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        })
        .finally(() => {
            if (btn) btn.classList.remove('fa-spin');
        });
};


})();

