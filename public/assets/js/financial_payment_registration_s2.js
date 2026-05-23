/**
 * MÓDULO: GESTIÓN FINANCIERA / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/financial_payment_registration_s2.js
 * PROPÓSITO: Renderizado de ofertas académicas en formato de lista vertical para el Paso 2.
 * VERSIÓN: 2.4.0 - FIX CRÍTICO: Mapeo exacto de variables del Modelo (offering_id, total_pending) y sincronización de deuda.
 */

window.FinancialS2 = {
    loadOfferings: async () => {
        const studentId = document.getElementById('user_id_val')?.value;
        const container = document.getElementById('offeringsContainer');
        const btnNext = document.getElementById('btnNext');
        
        if (!studentId || studentId === "0") return;

        // Blindaje por si el contenedor no existe en la vista actual
        if (container) {
            container.style.maxHeight = "400px"; 
            container.style.overflowY = "auto";
            container.className = "row g-2 p-2 custom-scroll"; 

            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted small mt-2">Sincronizando saldos...</p>
                </div>`;
        }

        try {
            const response = await fetch(`${BASE_URL}/financial/payment_registration/getOfferingsByUser?user_id=${studentId}`);
            const res = await response.json();

            if (res.status === 'success' && res.data && res.data.length > 0) {
                window.FinancialS2.renderOfferings(res.data);
            } else {
                if (container) {
                    container.innerHTML = `
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-shield-check text-success fs-1"></i>
                            <h6 class="fw-bold mt-2">Sin Deudas Pendientes</h6>
                            <p class="text-muted small">Este estudiante se encuentra al día o no tiene inscripciones activas.</p>
                        </div>`;
                }
                if (btnNext) btnNext.disabled = true;
            }
        } catch (error) {
            if (container) {
                container.innerHTML = '<div class="alert alert-danger text-center">Error de comunicación con el servidor financiero.</div>';
            }
        }
    },

    renderOfferings: (offerings) => {
        const container = document.getElementById('offeringsContainer');
        const selectedId = document.getElementById('offering_id_val')?.value;
        const btnNext = document.getElementById('btnNext');
        
        if (!container) return;
        container.innerHTML = ''; 

        offerings.forEach(offering => {
            // FIX: Extracción exacta desde el JSON entregado por el modelo
            const offeringId = offering.offering_id;
            const totalPending = parseFloat(offering.total_pending) || 0;
            const diplomaName = offering.diploma_name || 'Programa sin nombre';
            const cohortName = offering.cohort_name || 'Sin cohorte';

            const isSelected = (selectedId == offeringId);
            const activeClass = isSelected ? 'border-primary bg-primary bg-opacity-5' : 'border-light';
            const indicatorDisplay = isSelected ? 'd-block' : 'd-none';
            
            // Formateo de moneda humano de forma segura
            let montoDeudaFormateado = "0,00";
            if (typeof window.FinancialUtils !== 'undefined') {
                montoDeudaFormateado = window.FinancialUtils.formatNumberToCurrency(totalPending);
            }

            const item = document.createElement('div');
            item.className = 'col-12'; 
            
            // Pasamos totalPending en el onclick para auto-llenar el cobro final
            item.innerHTML = `
                <div class="card offering-selection-card border-2 shadow-none rounded-4 mb-2 btn text-start p-0 overflow-hidden ${activeClass}" 
                     onclick="window.FinancialS2.selectOffering(this, ${offeringId}, ${totalPending})"
                     style="transition: all 0.2s ease; cursor: pointer;">
                    
                    <div class="p-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center" style="max-width: 75%;">
                            <div class="bg-white border rounded-3 p-2 me-3 shadow-sm">
                                <i class="bi bi-cash-coin text-primary fs-4"></i>
                            </div>
                            <div class="text-truncate">
                                <h6 class="fw-bold mb-0 text-dark text-uppercase small">${diplomaName}</h6>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary smallest fw-bold mt-1">${cohortName}</span>
                            </div>
                        </div>
                        
                        <div class="text-end pe-2">
                            <small class="smallest text-muted text-uppercase fw-bold d-block" style="font-size: 0.65rem;">Saldo Pendiente</small>
                            <span class="fs-5 fw-bold text-danger">$ ${montoDeudaFormateado}</span>
                        </div>
                    </div>
                    
                    <div class="selection-indicator ${indicatorDisplay} position-absolute top-0 start-0 h-100 bg-primary" style="width: 5px;"></div>
                </div>`;
            container.appendChild(item);
        });

        if (selectedId && selectedId !== "0" && btnNext) btnNext.disabled = false;
    },

    selectOffering: (element, offeringId, pendingDebt) => {
        // Limpiamos la selección de todas las tarjetas
        document.querySelectorAll('.offering-selection-card').forEach(c => {
            c.classList.remove('border-primary', 'bg-primary', 'bg-opacity-5');
            c.classList.add('border-light');
            const indicator = c.querySelector('.selection-indicator');
            if (indicator) indicator.classList.add('d-none');
        });

        // Activamos la tarjeta clickeada
        element.classList.remove('border-light');
        element.classList.add('border-primary', 'bg-primary', 'bg-opacity-5');
        const targetIndicator = element.querySelector('.selection-indicator');
        if (targetIndicator) targetIndicator.classList.remove('d-none');

        // Guardamos el ID en el contexto del formulario
        const inputId = document.getElementById('offering_id_val');
        if (inputId) inputId.value = offeringId;

        // Sincronizamos la deuda automáticamente con el paso de pago (UI)
        if (typeof window.FinancialUI !== 'undefined') {
            window.FinancialUI.actualizarMontoEnPantalla(pendingDebt);
        }

        // Habilitamos la navegación
        const btnNext = document.getElementById('btnNext');
        if (btnNext) btnNext.disabled = false;
    }
};