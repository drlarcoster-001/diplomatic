/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/students_payment_registration_s4_ui.js
 * PROPÓSITO: Manejo de plantillas dinámicas, renderizado de tablas y estados visuales para el reporte del alumno.
 * VERSIÓN: 2.0.1 - FIX: Calculadora Vertical Cruzada y Búsqueda de Tasa en tiempo real integrada.
 */

window.StudentsUI = {
    /**
     * Renderiza los campos específicos según el canal digital seleccionado (Pago Móvil, Zelle, Binance).
     */
    /**
     * Renderiza los campos específicos según el canal digital seleccionado (Pago Móvil, Zelle, Binance).
     */
    renderFieldsByChannel: (method, sysTasaBcv) => {
        const dynamicFields = document.getElementById('dynamicFields');
        const screenContainer = document.getElementById('screenshotContainer');
        const inputAmount = document.getElementById('amount');
        
        if (!dynamicFields) return;
        
        dynamicFields.innerHTML = '';
        
        // Si no hay método seleccionado o es inválido para autogestión
        if (!method || method === '' || method === 'CASH') {
            dynamicFields.classList.add('d-none');
            if (screenContainer) screenContainer.classList.add('d-none');
            return;
        }
        
        dynamicFields.classList.remove('d-none');
        if (screenContainer) screenContainer.classList.remove('d-none');

        let html = '';
        const usdActual = (inputAmount && inputAmount.value) ? parseFloat(inputAmount.value) : 0;
        const tasaSegura = parseFloat(sysTasaBcv) || 1.00;
        const docId = document.getElementById('document_id_hidden')?.value || 'N/A';

        // 1. Identificación del Estudiante
        html += `
            <div class="mb-4 p-3 bg-light rounded-3 border text-start">
                <small class="text-muted d-block smallest text-uppercase fw-bold mb-1">Cédula / Identificación de Estudiante</small>
                <span class="fw-bold text-primary fs-5">${docId}</span>
            </div>`;

        // --- RENDERIZADO POR MÉTODO ---
        
        if (method === 'PAGOMOVIL') {
            const pmBanco = window.PAYMENT_DATA?.pago_movil?.banco || 'BANCO MERCANTIL';
            const pmTlf = window.PAYMENT_DATA?.pago_movil?.telefono || '04245024183';
            const pmCed = window.PAYMENT_DATA?.pago_movil?.cedula || '14399195';
            
            html += `
                <!-- DATOS DESTINO -->
                <div class="alert alert-info p-3 mb-4 border-0 rounded-4 shadow-sm text-start animate__animated animate__fadeIn">
                    <div class="fw-bold text-primary mb-2 text-uppercase border-bottom pb-1 smallest">Cuenta Destino a Pagar</div>
                    <div class="text-dark">
                        <div class="mb-1"><b>Banco:</b> ${pmBanco}</div>
                        <div class="mb-1"><b>Teléfono:</b> ${pmTlf}</div>
                        <div><b>Cédula:</b> ${pmCed}</div>
                    </div>
                </div>

                <div class="text-start animate__animated animate__fadeIn">
                    <!-- BANCO ORIGEN -->
                    <div class="mb-3">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Banco desde donde pagó</label>
                        <select name="pm_bank" class="form-select border-2 rounded-pill shadow-sm" required>
                            <option value="">Seleccione su banco</option>
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

                    <!-- TELÉFONO EMISOR -->
                    <div class="col-12 mb-3">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Teléfono que emitió el Pago</label>
                        <div class="input-group shadow-sm">
                            <select id="pm_prefix" name="pm_prefix" class="form-select border-2 rounded-start-pill" style="max-width: 95px;" required>
                                <option value="">Cód.</option>
                                <option value="0412">0412</option>
                                <option value="0422">0422</option>
                                <option value="0414">0414</option>
                                <option value="0424">0424</option>
                                <option value="0416">0416</option>
                                <option value="0426">0426</option>
                            </select>
                            <input type="tel" id="pm_phone" name="pm_phone" class="form-control border-2 rounded-end-pill" placeholder="Número de 7 dígitos" maxlength="7" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                        </div>
                    </div>

                    <!-- REFERENCIA -->
                    <div class="col-12 mb-3">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Número de Referencia (Últimos 4 u 8)</label>
                        <!-- CORRECCIÓN AQUÍ: Se agregó maxlength="25" -->
                        <input type="text" id="pm_ref" name="pm_ref" class="form-control border-2 rounded-pill shadow-sm" placeholder="Referencia del banco" maxlength="25" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                    </div>

                    <!-- FECHA -->
                    <div class="col-12 mb-4">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Fecha que realizó el pago</label>
                        <input type="date" id="modal_pm_date" name="pm_date" class="form-control border-2 rounded-pill shadow-sm" required onkeydown="return false;" style="cursor:pointer;">
                    </div>

                    <!-- BLOQUE DE CÁLCULO VERTICAL (CALCULADORA CRUZADA) -->
                    <div class="p-3 bg-light rounded-4 border mb-4 shadow-sm">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="smallest fw-bold text-success text-uppercase mb-1">Tasa Aplicada</label>
                                <input type="text" name="pm_tasa" id="calc_tasa" class="form-control fw-bold text-center border-success bg-light" placeholder="---" readonly>
                            </div>
                            <div class="col-6">
                                <label class="smallest fw-bold text-primary text-uppercase mb-1">Equivalencia USD ($)</label>
                                <input type="text" name="pm_amount_usd" id="calc_usd" class="form-control fw-bold text-center border-primary currency-field" placeholder="0,00">
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <label class="smallest fw-bold text-dark text-uppercase mb-1">Monto Exacto Transferido (Bs.)</label>
                            <input type="text" name="pm_amount" id="calc_bs" class="form-control fs-5 fw-bold text-center border-dark shadow-sm currency-field" placeholder="0,00" required>
                        </div>
                    </div>
                </div>`;
                
        } else if (method === 'ZELLE') {
            html += `
                <div class="row g-3 text-start animate__animated animate__fadeIn">
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Correo Electrónico de la Cuenta</label>
                        <input type="email" id="z_email" name="z_email" class="form-control border-2 rounded-pill shadow-sm" placeholder="correo@zelle.com" required>
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Nombre del Titular de la Cuenta</label>
                        <input type="text" id="z_holder" name="z_issuer" class="form-control border-2 rounded-pill shadow-sm" placeholder="Como aparece en el banco" required>
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Número de Referencia / ID</label>
                        <!-- CORRECCIÓN AQUÍ: Se agregó maxlength="25" -->
                        <input type="text" id="z_ref" name="z_ref" class="form-control border-2 rounded-pill shadow-sm" placeholder="ID de transacción" maxlength="25" required>
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Fecha de Operación</label>
                        <input type="date" id="z_date" name="z_date" class="form-control border-2 rounded-pill shadow-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-success text-uppercase mb-1">Monto Enviado (USD)</label>
                        <input type="text" id="z_amount" name="z_amount" class="form-control form-control-lg text-center fw-bold text-success currency-field border-2 rounded-pill shadow-sm" style="border: 2px solid #198754;" placeholder="0,00" required>
                    </div>
                </div>`;
                
        } else if (method === 'BINANCE') {
            html += `
                <div class="row g-3 text-start animate__animated animate__fadeIn">
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Binance ID o Correo</label>
                        <input type="text" id="b_uid" name="b_uid" class="form-control border-2 rounded-pill shadow-sm" placeholder="Su identificador en Binance" required>
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">ID de Orden (TXID)</label>
                        <!-- CORRECCIÓN AQUÍ: Se agregó maxlength="35" -->
                        <input type="text" id="b_order" name="b_order" class="form-control border-2 rounded-pill shadow-sm" placeholder="Referencia de Binance Pay" maxlength="35" required>
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Fecha del Pago</label>
                        <input type="date" id="b_date" name="b_date" class="form-control border-2 rounded-pill shadow-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-warning text-uppercase mb-1">Monto Enviado (USDT)</label>
                        <input type="text" id="b_amount" name="b_amount" class="form-control form-control-lg text-center fw-bold text-warning currency-field border-2 rounded-pill shadow-sm" style="border: 2px solid #ffc107;" placeholder="0,00" required>
                    </div>
                </div>`;
        }

        dynamicFields.innerHTML = html;

        // --- INICIALIZACIÓN DE COMPONENTES INTERACTIVOS ---
        
        // 1. Activar Formato Moneda Dinámico
        dynamicFields.querySelectorAll('.currency-field').forEach(i => {
            i.addEventListener('input', window.StudentsUI.formatCurrency);
            
            if (i.id === 'calc_bs') {
                i.addEventListener('input', () => window.StudentsUI.synchronizedCalculation(i.id));
            }
        });

        // 2. Escuchar la selección de fecha en Pago Móvil
        const inputFechaPm = document.getElementById('modal_pm_date');
        if (inputFechaPm) {
            inputFechaPm.addEventListener('change', async function() {
                await window.StudentsUI.buscarTasaPorFecha(this.value);
            });
        }
    },

    /**
     * Da formato monetario a un input (ej. 12000 -> 120,00)
     */
    formatCurrency: (e) => {
        let value = e.target.value.replace(/\D/g, "");
        if (value === "") return;
        value = (value / 100).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        e.target.value = value;
    },

/**
     * Busca la tasa al servidor (Regla T-1 aplicada en Backend) y refresca la calculadora.
     */
    buscarTasaPorFecha: async (fechaStr) => {
    const inputTasa = document.getElementById('calc_tasa');
    const labelConfFecha = document.getElementById('conf_date');
    const inUsd = document.getElementById('calc_usd');
    const inBs = document.getElementById('calc_bs');
    
    if (!inputTasa || !fechaStr || fechaStr.length < 10) return;
    
    const year = parseInt(fechaStr.split('-')[0]);
    if (year < 2000) return; 

    inputTasa.value = "Buscando..."; 

    try {
        const baseUrl = window.location.origin + '/diplomatic/public';
        const response = await fetch(`${baseUrl}/students/payment_registration/getLatestExchangeRate?date=${fechaStr}`);
        const data = await response.json();

        if (data.success && data.tasa > 0) {
            inputTasa.value = parseFloat(data.tasa).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (inUsd) inUsd.value = '---';
            if (inBs)  inBs.value  = '';
            
            if (labelConfFecha) {
                const [y, m, d] = fechaStr.split('-');
                labelConfFecha.innerText = `${d}/${m}/${y}`;
            }
        } else {
            throw new Error(data.message || "No se encontró una tasa oficial para la fecha seleccionada.");
        }
    } catch (error) {
        inputTasa.value = "0,00";
        if (inUsd) inUsd.value = "";
        if (inBs) inBs.value = "";

        // RESTAURACIÓN DE LA ALERTA
        Swal.fire({
            title: 'Tasa no encontrada',
            text: error.message,
            icon: 'warning',
            confirmButtonColor: '#0d6efd',
            confirmButtonText: 'Entendido'
        });

        console.error("Error al obtener tasa:", error.message);
    }
},

/**
     * Ejecuta el cálculo cruzado USD <-> Bs con regla de Truncamiento (Floor).
     */
    synchronizedCalculation: (origenId) => {
    const inTasa = document.getElementById('calc_tasa');
    const inUsd  = document.getElementById('calc_usd');
    const inBs   = document.getElementById('calc_bs');

    if (!inTasa || !inUsd || !inBs) return;

    let tasa = parseFloat(inTasa.value.replace(/\./g, '').replace(',', '.')) || 0;
    if (tasa <= 0) return;

    if (origenId === 'calc_bs') {
        let bs = parseFloat(inBs.value.replace(/\./g, '').replace(',', '.')) || 0;
        if (bs > 0) {
            // REGLA: Truncar a entero (No regalamos centavos)
            let resultadoUsd = Math.floor(bs / tasa); 
            inUsd.value = resultadoUsd.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            inUsd.value = "";
        }
    }
    
},
    

/**
     * Renderiza la tabla de estado de cuenta consolidado y calcula métricas globales.
     * VERSIÓN: 1.1.0 - Refactorización a visor de solo lectura con cálculo de totales.
     */
    renderDebtTableCheckboxes: (records) => {
        const tableBody = document.getElementById('accountStatusBody');
        if (!tableBody) return;

        // Limpiar métricas por defecto
        let totalAmount = 0;
        let totalPaid = 0;
        let totalPending = 0;

        if (!Array.isArray(records) || records.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-5">No existen registros financieros para este programa.</td></tr>';
            // Poner totales en cero si no hay registros
            document.getElementById('modalTotalAmount').innerText = '0,00';
            document.getElementById('modalTotalPaid').innerText = '0,00';
            document.getElementById('modalTotalPending').innerText = '0,00';
            return;
        }

        let rows = '';
        records.forEach(item => {
            const pending = parseFloat(item.amount_pending) || 0;
            const amountDue = parseFloat(item.amount_due) || 0;
            const amountPaid = parseFloat(item.amount_paid) || 0;
            const status = item.status;
            
            // Acumular totales globales
            totalAmount += amountDue;
            totalPaid += amountPaid;
            totalPending += pending;
            
            const statusBadge = status === 'PAGADO' 
                ? 'bg-success-subtle text-success border-success' 
                : status === 'ABONADO' ? 'bg-info-subtle text-info border-info' : 'bg-warning-subtle text-warning border-warning';

            rows += `
                <tr>
                    <td class="text-uppercase ps-4 smallest fw-bold">${item.concept || 'CUOTA ACADÉMICA'}</td>
                    <td class="text-center">$ ${amountDue.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td class="text-center text-success">$ ${amountPaid.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td class="text-center fw-bold text-danger">$ ${pending.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td class="text-center pe-4"><span class="badge ${statusBadge} border smallest rounded-pill px-3">${status}</span></td>
                </tr>`;
        });
        
        tableBody.innerHTML = rows;

        // Inyectar métricas globales en la vista con formato de moneda
        const elTotal = document.getElementById('modalTotalAmount');
        const elPaid = document.getElementById('modalTotalPaid');
        const elPending = document.getElementById('modalTotalPending');

        if (elTotal) elTotal.innerText = totalAmount.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (elPaid) elPaid.innerText = totalPaid.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (elPending) elPending.innerText = totalPending.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },


    /**
     * Resalta visualmente la opción de pago seleccionada.
     */
    highlightCard: (id) => {
        document.querySelectorAll('.payment-option-card').forEach(card => {
            card.classList.remove('border-primary', 'shadow-lg', 'active-selection');
            const check = card.querySelector('.selection-check');
            if (check) check.classList.add('d-none');
        });

        const targetCard = document.getElementById(id);
        if (targetCard) {
            targetCard.classList.add('border-primary', 'shadow-lg', 'active-selection');
            const check = targetCard.querySelector('.selection-check');
            if (check) check.classList.remove('d-none');
        }
    },

    /**
     * Actualiza los indicadores de monto en el resumen del wizard.
     */
    actualizarMontoEnPantalla: (monto) => {
        const montoSeguro = parseFloat(monto) || 0;
        const inputAmount = document.getElementById('amount');
        const labelVisual = document.getElementById('valAmountCash');
        
        if (inputAmount) inputAmount.value = montoSeguro.toFixed(2);
        
        if (labelVisual) {
            labelVisual.innerText = montoSeguro.toLocaleString('de-DE', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            });
        }
    }
};