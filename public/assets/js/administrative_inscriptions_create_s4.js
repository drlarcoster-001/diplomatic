/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / FRONTEND
 * ARCHIVO: public/assets/js/administrative_inscriptions_create_s4.js
 * PROPÓSITO: Orquestador de Pagos con inyección dinámica de datos institucionales y trazabilidad.
 * VERSIÓN: 3.6.0 - FIX: Soporte dinámico para datos de transferencia y blindaje de instancia de Modal.
 */

(function() {
    const inputMethodType = document.getElementById('payment_method_type');
    const inputMetadata   = document.getElementById('payment_metadata');
    const inputAmount     = document.getElementById('amount'); 
    const inputExRate     = document.getElementById('exchange_rate'); 
    const dynamicFields   = document.getElementById('dynamicFields');
    const selectorMethod  = document.getElementById('digitalMethod');
    const cards           = document.querySelectorAll('.payment-option-card');
    
    const paymentPlanBody = document.getElementById('paymentPlanBody');
    const totalPlanAmount = document.getElementById('totalPlanAmount');
    
    let modalPayment;

    const getStudentId = () => document.getElementById('document_id_hidden')?.value || 'N/A';

    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('modalDigital');
        
        // Blindaje de instancia: Evita el error "Cannot read properties of undefined (reading 'backdrop')"
        if (modalEl) {
            modalPayment = bootstrap.Modal.getOrCreateInstance(modalEl);
        }

        // --- 1. RECEPCIÓN (CASH) ---
        document.getElementById('btnOptCash')?.addEventListener('click', function() {
            const planRows = document.querySelectorAll('#paymentPlanBody tr');
            let sugerido = "0,00";
            
            if (planRows.length >= 2) {
                const m1 = parseFloat(planRows[0].cells[2].innerText.replace(/[^0-9,]/g, '').replace(',', '.')) || 0;
                const m2 = parseFloat(planRows[1].cells[2].innerText.replace(/[^0-9,]/g, '').replace(',', '.')) || 0;
                const totalSugerido = m1 + m2;
                sugerido = totalSugerido.toLocaleString('de-DE', { minimumFractionDigits: 2 });
            }

            Swal.fire({
                title: '<h5 class="fw-bold text-success mb-0">Recepción de Efectivo</h5>',
                html: `
                    <div class="text-center p-3">
                        <i class="bi bi-cash-stack text-success fs-1 mb-2 d-block"></i>
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Monto a Recibir ($)</label>
                        <input id="swal-monto" class="form-control form-control-lg text-center fw-bold text-success" value="${sugerido}">
                        <div class="mt-3 p-2 bg-light rounded-3 border">
                           <small class="text-muted d-block smallest text-uppercase">Registrando para Estudiante</small>
                           <span class="fw-bold text-dark">${getStudentId()}</span>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Confirmar Recepción',
                confirmButtonColor: '#198754',
                didOpen: () => { document.getElementById('swal-monto').addEventListener('input', formatCurrency); }
            }).then((result) => {
                if (result.isConfirmed) {
                    let cleanMonto = parseFloat(document.getElementById('swal-monto').value.replace(/\./g, '').replace(',', '.'));
                    if (isNaN(cleanMonto)) cleanMonto = 0.00;
                    
                    const masterJson = {
                        metodo: "CASH",
                        monto_sistema_usd: cleanMonto,
                        tasa_cambio: 1.00,
                        detalles_origen: {
                            identificador: getStudentId(),
                            cuenta_correo_telf: "RECEPCIÓN",
                            nombre_titular: "N/A",
                            banco_emisor: "CASH"
                        },
                        detalles_transaccion: {
                            referencia: "CASH-" + Date.now().toString().slice(-6),
                            fecha_comprobante: new Date().toISOString().split('T')[0],
                            monto_nativo: cleanMonto,
                            moneda_nativa: "USD"
                        }
                    };

                    inputMethodType.value = 'CASH';
                    inputAmount.value = cleanMonto;
                    inputMetadata.value = JSON.stringify(masterJson);
                    highlightCard('btnOptCash');
                }
            });
        });

        // --- 2. APERTURA DE MODAL DIGITAL ---
        document.getElementById('btnOptDigital')?.addEventListener('click', (e) => {
    e.preventDefault();
    selectorMethod.value = "";
    dynamicFields.innerHTML = "";
    dynamicFields.classList.add('d-none');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDigital')).show();
});
        
        if (selectorMethod) selectorMethod.addEventListener('change', function() { renderFieldsByChannel(this.value); });
        document.getElementById('btnConfirmDigital')?.addEventListener('click', saveDigitalSelection);
    });

    const formatCurrency = (e) => {
        let value = e.target.value.replace(/\D/g, "");
        if (value === "") return;
        value = (value / 100).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        e.target.value = value;
    };

    async function loadOfferingPaymentPlan() {
        const offeringId = document.querySelector('input[name="offering_id"]')?.value;
        if (!offeringId || !paymentPlanBody) return;

        try {
            paymentPlanBody.innerHTML = '<tr><td colspan="3" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>';
            const response = await fetch(`${Wizard.apiBase()}/getPaymentPlan?offering_id=${offeringId}`);
            const data = await response.json();
            if (data.success && data.plan.length > 0) renderPaymentPlan(data.plan);
        } catch (error) {
            paymentPlanBody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-danger">Error de conexión.</td></tr>';
        }
    }

    function renderPaymentPlan(plan) {
        paymentPlanBody.innerHTML = '';
        let total = 0;
        plan.forEach(item => {
            const amount = parseFloat(item.amount);
            total += amount;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="ps-3 py-2 fw-semibold text-dark">${item.name}</td>
                <td class="py-2 text-center text-muted">${item.due_date}</td>
                <td class="py-2 text-end pe-3 fw-bold text-primary">$ ${amount.toLocaleString('de-DE', {minimumFractionDigits: 2})}</td>
            `;
            paymentPlanBody.appendChild(row);
        });
        if (totalPlanAmount) totalPlanAmount.innerText = `$ ${total.toLocaleString('de-DE', {minimumFractionDigits: 2})}`;
    }

    window.addEventListener('stepChanged', function(e) {
        if (e.detail === 4) {
            inputMethodType.value = "";
            inputMetadata.value = "";
            cards.forEach(c => c.classList.remove('border-primary', 'bg-primary-subtle', 'active-selection'));
            loadOfferingPaymentPlan();
        }
    });

    // --- 3. RENDERIZADO DINÁMICO DE CAMPOS (CON DATOS DE LA INSTITUCIÓN) ---
    
function renderFieldsByChannel(method) {
    if (!dynamicFields) return;
    dynamicFields.innerHTML = '';
    if (!method) { dynamicFields.classList.add('d-none'); return; }
    dynamicFields.classList.remove('d-none');

    // 1. Identificación del Estudiante
    let html = `
        <div class="mb-4 p-3 bg-light rounded-3 border text-start">
            <small class="text-muted d-block smallest text-uppercase fw-bold mb-1">Cedula / Identificacion de Estudiante</small>
            <span class="fw-bold text-primary fs-5">${getStudentId()}</span>
        </div>`;

    if (method === 'PAGOMOVIL') {
        const pm = window.PAYMENT_DATA?.pago_movil || { banco: 'BANCO MERCANTIL', telefono: '04245024183', cedula: '14399195' };
        
        html += `
            <!-- DATOS DESTINO -->
            <div class="alert alert-info p-3 mb-4 border-0 rounded-4 shadow-sm text-start">
                <div class="fw-bold text-primary mb-2 text-uppercase border-bottom pb-1 smallest">Cuenta Destino a Pagar</div>
                <div class="text-dark">
                    <div class="mb-1"><b>Banco:</b> ${pm.banco}</div>
                    <div class="mb-1"><b>Teléfono:</b> ${pm.telefono}</div>
                    <div><b>Cédula:</b> ${pm.cedula}</div>
                </div>
            </div>

            <div class="text-start">
                <!-- BANCO ORIGEN -->
                <div class="mb-3">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Banco Origen</label>
                    <select name="pm_bank" class="form-select shadow-sm" required>
                        <option value="">-- Seleccionar Banco --</option>
                        <option value="0102 - BANCO DE VENEZUELA">0102 - BANCO DE VENEZUELA</option>
                        <option value="0104 - VENEZOLANO DE CRÉDITO">0104 - VENEZOLANO DE CRÉDITO</option>
                        <option value="0105 - BANCO MERCANTIL">0105 - BANCO MERCANTIL</option>
                        <option value="0108 - BBVA PROVINCIAL">0108 - BBVA PROVINCIAL</option>
                        <option value="0114 - BANCARIBE">0114 - BANCARIBE</option>
                        <option value="0115 - BANCO EXTERIOR">0115 - BANCO EXTERIOR</option>
                        <option value="0128 - BANCO CARONÍ">0128 - BANCO CARONÍ</option>
                        <option value="0134 - BANCO BANESCO">0134 - BANCO BANESCO</option>
                        <option value="0137 - BANCO SOFITASA">0137 - BANCO SOFITASA</option>
                        <option value="0138 - BANCO PLAZA">0138 - BANCO PLAZA</option>
                        <option value="0146 - BANGENTE">0146 - BANGENTE</option>
                        <option value="0151 - BFC BANCO FONDO COMÚN">0151 - BFC BANCO FONDO COMÚN</option>
                        <option value="0156 - 100% BANCO">0156 - 100% BANCO</option>
                        <option value="0157 - DELSUR BANCO UNIVERSAL">0157 - DELSUR BANCO UNIVERSAL</option>
                        <option value="0163 - BANCO DEL TESORO">0163 - BANCO DEL TESORO</option>
                        <option value="0166 - BANCO AGRÍCOLA DE VENEZUELA">0166 - BANCO AGRÍCOLA DE VENEZUELA</option>
                        <option value="0168 - BANCRECER">0168 - BANCRECER</option>
                        <option value="0169 - MI BANCO">0169 - MI BANCO</option>
                        <option value="0171 - BANCO ACTIVO">0171 - BANCO ACTIVO</option>
                        <option value="0172 - BANCAMIGA">0172 - BANCAMIGA</option>
                        <option value="0173 - BANCO INTERNACIONAL DE DESARROLLO">0173 - BANCO INTERNACIONAL DE DESARROLLO</option>
                        <option value="0174 - BANPLUS">0174 - BANPLUS</option>
                        <option value="0175 - BANCO DIGITAL DE LOS TRABAJADORES">0175 - BANCO DIGITAL DE LOS TRABAJADORES</option>
                        <option value="0177 - BANFANB">0177 - BANFANB</option>
                        <option value="0178 - N58 BANCO DIGITAL">0178 - N58 BANCO DIGITAL</option>
                        <option value="0191 - BNC BANCO NACIONAL DE CRÉDITO">0191 - BNC BANCO NACIONAL DE CRÉDITO</option>
                    </select>
                </div>

                <!-- FECHA -->
                <div class="mb-3">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Fecha de Pago</label>
                    <input type="date" name="pm_date" class="form-control shadow-sm" required onkeydown="return false;" style="cursor:pointer;">
                </div>

                <!-- TELÉFONO EMISOR -->
                <div class="mb-3">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Teléfono Emisor</label>
                    <div class="input-group shadow-sm">
                        <select name="pm_prefix" class="form-select" style="max-width: 100px;" required>
                            <option value="0412">0412</option>
                            <option value="0422">0422</option>
                            <option value="0414">0414</option>
                            <option value="0424">0424</option>
                            <option value="0416">0416</option>
                            <option value="0426">0426</option>
                        </select>
                        <input type="tel" name="pm_phone" class="form-control" placeholder="7 dígitos" maxlength="7" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                    </div>
                </div>

                <!-- REFERENCIA -->
                <div class="mb-4">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Referencia</label>
                    <input type="text" name="pm_ref" class="form-control shadow-sm" placeholder="Solo números" maxlength="25" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                </div>

                <!-- BLOQUE DE CÁLCULO VERTICAL -->
                    <div class="p-3 bg-light rounded-4 border mb-4 shadow-sm">
                        <!-- Tasa y Dólares en la misma fila -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="smallest fw-bold text-success text-uppercase mb-1">Tasa Aplicada</label>
                                <input type="text" name="pm_tasa" id="calc_tasa" class="form-control fw-bold text-center border-success bg-light" placeholder="---" readonly>
                            </div>
                            <div class="col-6">
                                <label class="smallest fw-bold text-primary text-uppercase mb-1">Monto ($)</label>
                                <input type="text" name="pm_amount_usd" id="calc_usd" class="form-control fw-bold text-center border-primary" placeholder="---" readonly>
                            </div>
                        </div>
                        
                        <!-- Bolívares abajo, ocupando todo el ancho -->
                        <div class="mb-2">
                            <label class="smallest fw-bold text-dark text-uppercase mb-1">Total en Bolívares (Bs.)</label>
                            <input type="text" name="pm_amount" id="calc_bs" class="form-control fs-5 fw-bold text-center border-dark shadow-sm" placeholder="0,00" required>
                        </div>
                    </div>
            </div>`;

    } else if (method === 'ZELLE') {
        html += `
            <div class="text-start">
                <div class="mb-3">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Correo Emisor Zelle</label>
                    <input type="email" name="z_email" class="form-control shadow-sm" placeholder="ejemplo@correo.com" required>
                </div>
                <div class="mb-3">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Nombre Titular de la Cuenta</label>
                    <input type="text" name="z_issuer" class="form-control shadow-sm" placeholder="Nombre completo" required>
                </div>
                <div class="mb-3">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Referencia / Confirmación</label>
                    <input type="text" name="z_ref" class="form-control  shadow-sm" placeholder="Código alfanumérico" maxlength="25" required>
                </div>
                <div class="mb-3">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Fecha de Operación</label>
                    <input type="date" name="z_date" class="form-control  shadow-sm" required>
                </div>
                <div class="mb-4">
                    <label class="smallest fw-bold text-success text-uppercase mb-1">Monto en Dólares ($)</label>
                    <input type="text" name="z_amount" class="form-control  currency-field fs-4 fw-bold text-success text-center shadow-sm" style="border: 2px solid #198754;" placeholder="0,00" required>
                </div>
            </div>`;

    } else if (method === 'BINANCE') {
        html += `
            <div class="text-start">
                <div class="mb-3">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Binance ID / Correo</label>
                    <input type="text" name="b_uid" class="form-control  shadow-sm" placeholder="ID o Correo de Binance" required>
                </div>
                <div class="mb-3">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">ID de Orden / TxID</label>
                    <input type="text" name="b_order" class="form-control  shadow-sm" placeholder="Código de orden" maxlength="35" required>
                </div>
                <div class="mb-3">
                    <label class="smallest fw-bold text-muted text-uppercase mb-1">Fecha de Operación</label>
                    <input type="date" name="b_date" class="form-control  shadow-sm" required>
                </div>
                <div class="mb-4">
                    <label class="smallest fw-bold text-warning text-uppercase mb-1">Monto USDT</label>
                    <input type="text" name="b_amount" class="form-control  currency-field fs-4 fw-bold text-warning-emphasis text-center shadow-sm" style="border: 2px solid #ffc107;" placeholder="0,00" required>
                </div>
            </div>`;
    }

    // COMPROBANTE FINAL (Para todos los métodos)
    html += `
        <div class="mt-2 text-start border-top pt-4">
            <label class="smallest fw-bold text-muted text-uppercase mb-2">Adjuntar Comprobante (Obligatorio)</label>
            <input type="file" name="pay_screenshot" class="form-control  rounded-3 shadow-sm" accept="image/*" required>
        </div>`;
    
    dynamicFields.innerHTML = html;

    // Activamos formateadores y conversiones
dynamicFields.querySelectorAll('.currency-field, #calc_bs').forEach(i => {
        i.addEventListener('input', formatCurrency);
        if (i.id === 'calc_bs') {
            i.addEventListener('input', () => synchronizedCalculation(i.id));
        }
    });

    // NUEVO: Escuchador para la fecha de Pago Móvil
    const inputFechaPm = document.querySelector('input[name="pm_date"]');
    if (inputFechaPm) {
        inputFechaPm.addEventListener('change', async function() {
            await buscarTasaPorFecha(this.value);
        });
    }
}


    // --- 4. EMPAQUETADOR CON FLAGS DE TRAZABILIDAD ---
    /**
 * EMPAQUETADOR FINANCIERO: Congela la tasa y el monto USD.
 * Blindaje: Asegura que el JSON lleve la 'tasa_cambio' y el 'monto_sistema_usd' calculados.
 */
function saveDigitalSelection() {
    const method = selectorMethod.value;
    const inputs = dynamicFields.querySelectorAll('input[required], select[required]');
    let valid = true;
    const rawData = {};
    
    // 1. Validación de campos obligatorios
    inputs.forEach(i => { 
        if (!i.value || i.value === "0,00") { 
            i.classList.add('is-invalid'); 
            valid = false; 
        } else { 
            i.classList.remove('is-invalid'); 
            rawData[i.name] = i.value; 
        } 
    });

    if (!valid) return;

    // 2. CAPTURA DE LA TASA (El Ancla)
    // Prioridad: 1. Calculadora interna | 2. Input maestro del Wizard
    const tasaInput = document.getElementById('calc_tasa');
    let tasaFinal = parseFloat(inputExRate.value) || 0;
    
    if (method === 'PAGOMOVIL' && tasaInput) {
        tasaFinal = parseFloat(tasaInput.value.replace(/\./g, '').replace(',', '.')) || tasaFinal;
    }

    // 3. CÁLCULO DE BOLÍVARES A DÓLARES (Fórmula: $USD = \frac{Bs}{Tasa}$)
    let montoNativo = 0;
    let montoUSD = 0;

    if (method === 'PAGOMOVIL') {
        montoNativo = parseFloat(rawData.pm_amount.replace(/\./g, '').replace(',', '.'));
        // Calculamos y redondeamos a 2 decimales para el Ledger
        montoUSD = tasaFinal > 0 ? parseFloat((montoNativo / tasaFinal).toFixed(2)) : 0;
    } else {
        // Para Zelle/Binance, el monto nativo ya es USD/USDT
        const field = rawData.z_amount || rawData.b_amount || "0";
        montoNativo = parseFloat(field.replace(/\./g, '').replace(',', '.'));
        montoUSD = montoNativo;
        tasaFinal = 1.00; // Paridad 1:1 para USD
    }

    // 4. CONSTRUCCIÓN DEL JSON MAESTRO (La Verdad Histórica)
    const masterJson = { 
        metodo: method, 
        monto_sistema_usd: montoUSD, 
        tasa_cambio: tasaFinal, 
        detalles_origen: { 
            identificador: getStudentId(),
            banco_emisor: (method === 'PAGOMOVIL') ? rawData.pm_bank : method,
            cuenta_correo_telf: (method === 'PAGOMOVIL') ? (rawData.pm_prefix + "-" + rawData.pm_phone) : (rawData.z_email || rawData.b_uid || 'N/A'),
            nombre_titular: rawData.z_issuer || "NO_SUMINISTRADO"
        }, 
        detalles_transaccion: {
            referencia: rawData.pm_ref || rawData.z_ref || rawData.b_order,
            fecha_comprobante: rawData.pm_date || rawData.z_date || rawData.b_date || new Date().toISOString().split('T')[0],
            monto_nativo: montoNativo,
            moneda_nativa: (method === 'PAGOMOVIL') ? "BS" : "USD"
        },
        auditoria: { 
            fecha_registro: new Date().toISOString().replace('T', ' ').substring(0, 19), 
            agente: "ADMIN_WIZARD" 
        }
    };

    // 5. INYECCIÓN A CAMPOS OCULTOS PARA PHP
    inputMethodType.value = method;
    inputMetadata.value = JSON.stringify(masterJson);
    inputAmount.value = montoNativo.toFixed(2);
    
    // Actualizamos el input maestro para que el "Sanitizer S4" en PHP vea la misma tasa
    if (inputExRate) inputExRate.value = tasaFinal;

    // 6. CIERRE Y FEEDBACK
    highlightCard('btnOptDigital');
    modalPayment.hide();
    Swal.fire({ icon: 'success', title: 'Pago Vinculado Exitosamente', timer: 1500, showConfirmButton: false });
}



    function highlightCard(id) {
        cards.forEach(c => c.classList.remove('border-primary', 'bg-primary-subtle', 'active-selection'));
        document.getElementById(id)?.classList.add('border-primary', 'bg-primary-subtle', 'active-selection');
    }

    Wizard.validators[4] = () => {
        const selectedValue = inputMethodType.value.trim();
        if (!selectedValue) {
            Swal.fire({ icon: 'warning', title: 'Selección Obligatoria', text: 'Debe configurar un medio de pago para continuar.' });
            return false;
        }
        return true;
    };


    // Forzamos la función al objeto global para que el onclick del HTML la encuentre siempre
window.abrirCalculadora = function() {
    Swal.fire({
        title: '<div class="text-success fw-bold"><i class="bi bi-calculator me-2"></i>Calculadora de Conversión</div>',
        html: `
            <div class="text-start p-2">
                <!-- FECHA Y TASA -->
                <div class="row g-2 mb-3 bg-light p-3 rounded-4 border">
                    <div class="col-7">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1 d-block">Fecha para la Tasa</label>
                        <input type="date" id="calc-fecha" class="form-control shadow-sm border-0">
                    </div>
                    <div class="col-5">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1 d-block">Tasa Aplicada</label>
                        <input type="text" id="calc-tasa" class="form-control fw-bold text-center border-primary shadow-sm" 
                               placeholder="0,00" style="border-width: 2px;">
                    </div>
                </div>

                <!-- DOLARES -->
                <div class="mb-3">
                    <label class="smallest fw-bold text-primary text-uppercase mb-1">Monto en Dólares ($)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white border-primary shadow-sm">$</span>
                        <input type="text" id="calc-usd" class="form-control form-control-lg fw-bold border-primary shadow-sm" 
                               placeholder="0,00" style="border-width: 2px;">
                    </div>
                </div>

                <!-- BOLIVARES -->
                <div class="mb-4">
                    <label class="smallest fw-bold text-success text-uppercase mb-1">Monto en Bolívares (Bs.)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-success text-white border-success shadow-sm">Bs</span>
                        <input type="text" id="calc-bs" class="form-control form-control-lg fw-bold border-success shadow-sm" 
                               placeholder="0,00" style="border-width: 2px;">
                    </div>
                </div>

                <div class="alert alert-success py-2 px-3 border-0 rounded-3 smallest mb-0 d-flex align-items-center shadow-sm">
                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                    <span>La tasa es obligatoria para realizar la conversión.</span>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-check-lg me-1"></i> Aplicar Monto',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
        width: '450px',
        didOpen: () => {
            const inTasa = document.getElementById('calc-tasa');
            const inUsd  = document.getElementById('calc-usd');
            const inBs   = document.getElementById('calc-bs');

            // Formateador de moneda interno (0,00)
            const format = (el) => {
                let val = el.value.replace(/\D/g, "");
                if (val === "") return 0;
                let num = parseFloat(val) / 100;
                el.value = num.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                return num;
            };

            const convert = (origen) => {
                let tasa = parseFloat(inTasa.value.replace(/\./g, '').replace(',', '.')) || 0;
                if (tasa <= 0) return;

                if (origen === 'usd') {
                    let usd = parseFloat(inUsd.value.replace(/\./g, '').replace(',', '.')) || 0;
                    let resBs = usd * tasa;
                    inBs.value = resBs.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } else {
                    let bs = parseFloat(inBs.value.replace(/\./g, '').replace(',', '.')) || 0;
                    let resUsd = bs / tasa;
                    inUsd.value = resUsd.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            };

            // Listeners activos
            inTasa.addEventListener('input', (e) => { format(e.target); convert('usd'); });
            inUsd.addEventListener('input', (e) => { format(e.target); convert('usd'); });
            inBs.addEventListener('input', (e) => { format(e.target); convert('bs'); });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const valBs = document.getElementById('calc-bs').value;
            const valTasa = document.getElementById('calc-tasa').value;
            const valUsd = document.getElementById('calc-usd').value;
            const valFecha = document.getElementById('calc-fecha').value;

            // Actualizar visuales del cuadro verde
            if(document.getElementById('span-total-bs-dinamico')) document.getElementById('span-total-bs-dinamico').innerText = valBs + " Bs.";
            if(document.getElementById('span-tasa-dinamica')) document.getElementById('span-tasa-dinamica').innerText = valTasa + " Bs.";
            if(document.getElementById('span-usd-dinamico')) document.getElementById('span-usd-dinamico').innerText = "$ " + valUsd;
            if(document.getElementById('span-fecha-dinamica')) document.getElementById('span-fecha-dinamica').innerText = valFecha || 'No seleccionada';

            // Inyectar en el campo real del formulario
            const inputAmount = document.querySelector('input[name="pm_amount"]');
            if (inputAmount) {
                inputAmount.value = valBs;
                inputAmount.dispatchEvent(new Event('input')); // Formatea el destino
            }
        }
    });
};

// --- BÚSQUEDA DE TASA AL BACKEND ---
// ==========================================
// MÓDULO DE CÁLCULO Y CONSULTA DE TASAS
// ==========================================

/**
 * Consulta la tasa de cambio al servidor según la fecha seleccionada.
 */
/**
 * CONSULTA DE TASA HISTÓRICA
 */
async function buscarTasaPorFecha(fechaStr) {
    const inputTasa = document.getElementById('calc_tasa');
    if (!inputTasa || !fechaStr) return;

    inputTasa.value = "Consultando...";
        const usdInput = document.getElementById('calc_usd');
        const bsInput  = document.getElementById('calc_bs');
        if (usdInput) usdInput.value = '---';
        if (bsInput)  bsInput.value  = '';

    const baseUrl = window.location.origin + '/diplomatic/public';
    // Nota: Asegúrate de que esta ruta coincida con tu controlador de tasas
    const url = `${baseUrl}/financial/exchange_rates/getRateByDate?date=${fechaStr}`;

    try {
        const response = await fetch(url);
        const data = await response.json();

     if (data.success && data.tasa > 0) {
            const tasaFinal = Math.round(parseFloat(data.tasa) * 100) / 100;
            inputTasa.value = tasaFinal.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            // Habilitar el campo USD ahora que hay tasa
            if (usdInput) usdInput.value = '';
            
            Swal.fire({ icon: 'success', title: 'Tasa Actualizada', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
        } else {
            throw new Error(data.message || "No hay tasa registrada para esa fecha.");
        }
    } catch (error) {
        inputTasa.value = "0,00";
        Swal.fire({ icon: 'error', title: 'Atención', text: error.message, toast: true, position: 'top-end', timer: 4000, showConfirmButton: false });
    }
}

/**
 * Realiza la conversión cruzada (USD <-> Bs) según el campo modificado.
 */
// synchronizedCalculation definida abajo como única fuente de verdad


// --- LÓGICA DE CONVERSIÓN CRUZADA ---
window.synchronizedCalculation = function(origenId) {
    const inTasa = document.getElementById('calc_tasa');
    const inUsd  = document.getElementById('calc_usd');
    const inBs   = document.getElementById('calc_bs');

    if (!inTasa || !inUsd || !inBs) return;

    let tasa = parseFloat(inTasa.value.replace(/\./g, '').replace(',', '.')) || 0;
    if (tasa <= 0) return;

    // Solo dirección Bs → USD
    if (origenId === 'calc_bs') {
        let bs = parseFloat(inBs.value.replace(/\./g, '').replace(',', '.')) || 0;
        if (bs > 0) {
            let resultadoUsd = Math.floor(bs / tasa * 100) / 100;
            inUsd.value = resultadoUsd.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            inUsd.value = '';
        }
    }
};


})();