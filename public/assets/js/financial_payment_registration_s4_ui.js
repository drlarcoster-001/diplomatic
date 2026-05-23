/**
 * MÓDULO: GESTIÓN FINANCIERA / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/financial_payment_registration_s4_ui.js
 * PROPÓSITO: Manejo de plantillas dinámicas, renderizado de tablas y estados visuales de la UI.
 * VERSIÓN: 5.0.0 - UPGRADE: Integración de Calculadora Cruzada y sincronización de tasa en tiempo real.
 */

window.FinancialUI = {

    /**
     * Renderiza los campos específicos según el canal digital seleccionado (Pago Móvil, Zelle, Binance).
     */
    renderFieldsByChannel: (method, sysTasaBcv) => {
        const dynamicFields = document.getElementById('dynamicFields');
        const screenContainer = document.getElementById('screenshotContainer');
        const inputAmount = document.getElementById('amount');

if (!dynamicFields) return;
        dynamicFields.innerHTML = '';
        
        if (!method || method === '' || method === 'CASH') {
            dynamicFields.classList.add('d-none');
            if (screenContainer) screenContainer.classList.add('d-none');
            return;
        }
        
        dynamicFields.classList.remove('d-none');
        if (screenContainer) screenContainer.classList.remove('d-none');

    // 1. Buscamos la cédula en los inputs ocultos
    let cedulaDetectada = document.getElementById('student_ci_hidden')?.value 
                        || document.getElementById('document_id_hidden')?.value 
                        || document.getElementById('user_id_val')?.value 
                        || '';

    // 2. Limpieza de errores de PHP o vacíos
    if (cedulaDetectada.includes('PHP') || cedulaDetectada === '') {
        cedulaDetectada = 'No encontrada';
    }

    // 3. Obtenemos el nombre (Esto ya lo tienes bien)
    const nombreDetectado = window.FinancialUtils ? window.FinancialUtils.getStudentName() : 'Estudiante';

        let html = '';
        const usdActual = (inputAmount && inputAmount.value) ? parseFloat(inputAmount.value) : 0;
        const tasaSegura = parseFloat(sysTasaBcv) || 1.00;

        // 1. Cabecera Visual del Estudiante (Cuadro azul)
        html += `
            <div class="mb-4 p-3 bg-light rounded-3 border text-start">
                <small class="text-muted d-block smallest text-uppercase fw-bold mb-1">Cédula / Identificación de Estudiante</small>
                <span class="fw-bold text-primary fs-5">${cedulaDetectada} <small class="text-secondary ms-2 fs-6">${nombreDetectado}</small></span>
            </div>`;


        // --- LÓGICA POR MÉTODO ---
        
        if (method === 'PAGOMOVIL') {
            const pmBanco = window.PAYMENT_DATA?.pago_movil?.banco || 'BANCO MERCANTIL';
            const pmTlf = window.PAYMENT_DATA?.pago_movil?.telefono || '04245024183';
            const pmCed = window.PAYMENT_DATA?.pago_movil?.cedula || '14399195';

            html += `
                <!-- DATOS DESTINO -->
                <div class="alert alert-info p-3 mb-4 border-0 rounded-4 shadow-sm text-start animate__animated animate__fadeIn">
                    <div class="fw-bold text-primary mb-2 text-uppercase border-bottom pb-1 smallest">Cuenta Receptora (Destino)</div>
                    <div class="text-dark">
                        <div class="mb-1"><b>Banco:</b> ${pmBanco}</div>
                        <div class="mb-1"><b>Teléfono:</b> ${pmTlf}</div>
                        <div><b>Cédula:</b> ${pmCed}</div>
                    </div>
                </div>

                <div class="row g-3 text-start animate__animated animate__fadeIn">
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Banco Origen</label>
                        <select id="pm_bank" name="pm_bank" class="form-select border-2 rounded-pill shadow-sm" required>
                            <option value="">Seleccione un banco</option>
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
                            <option value="0174 - BANPLUS">0174 - BANPLUS</option>
                            <option value="0175 - BANCO DIGITAL DE LOS TRABAJADORES">0175 - BANCO DIGITAL DE LOS TRABAJADORES</option>
                            <option value="0177 - BANFANB">0177 - BANFANB</option>
                            <option value="0178 - N58 BANCO DIGITAL">0178 - N58 BANCO DIGITAL</option>
                            <option value="0191 - BNC BANCO NACIONAL DE CRÉDITO">0191 - BNC BANCO NACIONAL DE CRÉDITO</option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Teléfono Emisor</label>
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

                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Número de Referencia (Últimos 4 u 8)</label>
                        <input type="text" id="pm_ref" name="pm_ref" class="form-control border-2 rounded-pill shadow-sm" placeholder="Número de confirmación" maxlength="25" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                    </div>

                    <div class="col-12 mb-2">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Fecha de Operación</label>
                        <input type="date" id="pm_date" name="pm_date" class="form-control border-2 rounded-pill shadow-sm" value="${window.FinancialUtils?.getSystemDate() || ''}" required onkeydown="return false;" style="cursor:pointer;">
                    </div>

                    <!-- CALCULADORA CRUZADA ADMINISTRATIVA -->
                    <div class="p-3 bg-light rounded-4 border mb-2 shadow-sm col-12">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="smallest fw-bold text-success text-uppercase mb-1">Tasa Aplicada</label>
                                <input type="text" name="pm_tasa" id="calc_tasa" class="form-control fw-bold text-center border-success bg-light" placeholder="---" readonly>
                            </div>
                            <div class="col-6">
                                <label class="smallest fw-bold text-primary text-uppercase mb-1">Equivalencia USD ($)</label>
                                <input type="text" name="pm_amount_usd" id="calc_usd" class="form-control fw-bold text-center border-primary" placeholder="---" readonly>
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
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Correo de la Cuenta Zelle</label>
                        <input type="email" id="z_email" name="z_email" class="form-control border-2 rounded-pill shadow-sm" placeholder="ejemplo@correo.com" required>
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Nombre del Titular</label>
                        <input type="text" id="z_holder" name="z_issuer" class="form-control border-2 rounded-pill shadow-sm" placeholder="Titular en Zelle" required>
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Código de Referencia / ID</label>
                        <input type="text" id="z_ref" name="z_ref" class="form-control border-2 rounded-pill shadow-sm" placeholder="ID de confirmación" maxlength="25" required>
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Fecha de Pago</label>
                        <input type="date" id="z_date" name="z_date" class="form-control border-2 rounded-pill shadow-sm" value="${window.FinancialUtils?.getSystemDate() || ''}" required onkeydown="return false;" style="cursor:pointer;">
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-success text-uppercase mb-1">Monto (USD)</label>
                        <input type="text" id="z_amount" name="z_amount" class="form-control form-control-lg text-center fw-bold text-success currency-field border-2 rounded-pill shadow-sm" style="border: 2px solid #198754;" placeholder="0,00" required>
                    </div>
                </div>`;
                
        } else if (method === 'BINANCE') {
            html += `
                <div class="row g-3 text-start animate__animated animate__fadeIn">
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Binance ID / Correo</label>
                        <input type="text" id="b_uid" name="b_uid" class="form-control border-2 rounded-pill shadow-sm" placeholder="Identificador del pagador" required>
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">ID de Orden (TXID)</label>
                        <input type="text" id="b_order" name="b_order" class="form-control border-2 rounded-pill shadow-sm" placeholder="Referencia de Binance" maxlength="35" required>
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-muted text-uppercase mb-1">Fecha de Pago</label>
                        <input type="date" id="b_date" name="b_date" class="form-control border-2 rounded-pill shadow-sm" value="${window.FinancialUtils?.getSystemDate() || ''}" required onkeydown="return false;" style="cursor:pointer;">
                    </div>
                    <div class="col-12">
                        <label class="smallest fw-bold text-warning text-uppercase mb-1">Monto (USDT)</label>
                        <input type="text" id="b_amount" name="b_amount" class="form-control form-control-lg text-center fw-bold text-warning currency-field border-2 rounded-pill shadow-sm" style="border: 2px solid #ffc107;" placeholder="0,00" required>
                    </div>
                </div>`;
        }

        dynamicFields.innerHTML = html;

        // --- INICIALIZACIÓN DE COMPONENTES INTERACTIVOS ---
        
        // 1. Activar Formato Moneda Dinámico
        dynamicFields.querySelectorAll('.currency-field').forEach(i => {
            i.addEventListener('input', window.FinancialUI.formatCurrency);

            if (i.id === 'calc_bs') {
                i.addEventListener('input', () => window.FinancialUI.synchronizedCalculation(i.id));
            }
        });

        // 2. Escuchar la selección de fecha en Pago Móvil
        const inputFechaPm = document.querySelector('input[name="pm_date"]');
        if (inputFechaPm) {
            inputFechaPm.value = '';
            inputFechaPm.addEventListener('change', async function() {
                await window.FinancialUI.buscarTasaPorFecha(this.value);
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
     * Busca la tasa al servidor financiero y la asigna al input
     */
    buscarTasaPorFecha: async (fechaStr) => {
        const inputTasa = document.getElementById('calc_tasa');
        if (!inputTasa || !fechaStr) return;

        inputTasa.value = "Buscando...";
        const inUsd = document.getElementById('calc_usd');
        const inBs  = document.getElementById('calc_bs');
        if (inUsd) inUsd.value = '---';
        if (inBs)  inBs.value  = '';
        const baseUrl = window.location.origin + '/diplomatic/public';
        
        // NOTA: Usamos la ruta administrativa para las tasas
        const urlPeticion = `${baseUrl}/financial/exchange_rates/getRateByDate?date=${fechaStr}`;

        try {
            const response = await fetch(urlPeticion);
            const data = await response.json();

            if (data.success && data.tasa > 0) {
                inputTasa.value = parseFloat(data.tasa).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (inUsd) inUsd.value = '---';
                if (inBs)  inBs.value  = '';
            } else {
                throw new Error(data.message || "Sin tasa");
            }
        } catch (error) {
            inputTasa.value = "0,00";
            Swal.fire({
                icon: 'warning',
                title: 'Aviso de Tasa',
                text: 'No se encontró tasa para la fecha seleccionada. Verifique la fecha.',
                toast: true, position: 'top-end', timer: 4000, showConfirmButton: false
            });
        }
    },

    /**
     * Ejecuta el cálculo cruzado USD <-> Bs.
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
                let resultadoUsd = Math.floor(bs / tasa * 100) / 100;
                inUsd.value = resultadoUsd.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                inUsd.value = '---';
            }
        }
    },

    /**
     * Renderiza la tabla de cuotas dentro del modal.
     */
    renderDebtTableCheckboxes: (records) => {
    const tableBody = document.getElementById('accountStatusBody');
        if (!tableBody) return;

        if (!Array.isArray(records) || records.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No hay registros de deuda.</td></tr>';
            return;
        }

        let rows = '';
        // Inicializamos los acumuladores para los 3 totales
        let acumuladoDiplomado = 0;
        let acumuladoPagado = 0;
        let acumuladoPendiente = 0;

        records.forEach((item, index) => {
            const amountDue = parseFloat(item.amount_due) || 0;
            const amountPaid = parseFloat(item.amount_paid) || 0;
            const pending = parseFloat(item.amount_pending) || 0;
            const status = item.status;

            // Sumamos a los totales globales
            acumuladoDiplomado += amountDue;
            acumuladoPagado += amountPaid;
            acumuladoPendiente += pending;
            
            const statusBadge = status === 'PAGADO' 
                ? 'bg-success-subtle text-success' 
                : status === 'ABONADO' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning';

            rows += `
                <tr>
                    <td class="ps-4 text-center text-muted smallest fw-bold">
                        ${index + 1}
                    </td>
                    <td class="text-uppercase small">${item.concept || 'CUOTA'}</td>
                    <td class="text-center text-dark">$ ${amountDue.toFixed(2)}</td>
                    <td class="text-center text-success">$ ${amountPaid.toFixed(2)}</td>
                    <td class="text-center fw-bold text-danger">$ ${pending.toFixed(2)}</td>
                    <td class="text-center pe-4"><span class="badge ${statusBadge} border smallest">${status}</span></td>
                </tr>`;
        });

        tableBody.innerHTML = rows;

        // --- ACTUALIZACIÓN DE LOS 3 TOTALES EN EL FOOTER DEL MODAL ---
        const elDiplomado = document.getElementById('totalDiplomado');
        const elPagado = document.getElementById('totalPagado');
        const elPendiente = document.getElementById('totalPendiente');

        if (elDiplomado) elDiplomado.innerText = acumuladoDiplomado.toFixed(2);
        if (elPagado) elPagado.innerText = acumuladoPagado.toFixed(2);
        if (elPendiente) elPendiente.innerText = acumuladoPendiente.toFixed(2);    
    },

    highlightCard: (id) => {
        document.querySelectorAll('.payment-option-card').forEach(card => {
            card.classList.remove('border-primary', 'border-success', 'shadow-lg', 'active-selection');
            const check = card.querySelector('.selection-check');
            if (check) check.classList.add('d-none');
        });

        const targetCard = document.getElementById(id);
        if (targetCard) {
            const borderClass = id === 'btnOptCash' ? 'border-success' : 'border-primary';
            targetCard.classList.add(borderClass, 'shadow-lg', 'active-selection');
            const check = targetCard.querySelector('.selection-check');
            if (check) check.classList.remove('d-none');
        }
    },

    actualizarMontoEnPantalla: (monto) => {
        const montoSeguro = parseFloat(monto) || 0;
        const inputAmount = document.getElementById('amount');
        const labelVisual = document.getElementById('valAmountCash');
        
        if (inputAmount) inputAmount.value = montoSeguro.toFixed(2);
        if (labelVisual) labelVisual.innerText = montoSeguro.toLocaleString('de-DE', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    }
};