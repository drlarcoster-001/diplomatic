/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/students_payment_registration_s2.js
 * PROPÓSITO: Renderizado de diplomados/cursos inscritos para el estudiante logueado (Paso 2).
 * VERSIÓN: 1.0.1 - FIX: Tipografía aumentada y selección por contorno (sin relleno).
 */

window.StudentsS2 = {
    /**
     * Carga los diplomados vinculados al usuario desde la sesión del servidor.
     */
    loadOfferings: async () => {
        const studentId = document.getElementById('user_id_val')?.value;
        const container = document.getElementById('offeringsContainer');
        const btnNext = document.getElementById('btnNext');
        
        if (!studentId || studentId === "0") return;

        if (container) {
            container.style.maxHeight = "500px"; 
            container.style.overflowY = "auto";
            container.className = "row g-3 p-2 custom-scroll"; 

            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted small mt-2">Sincronizando sus saldos...</p>
                </div>`;
        }

        try {
            const response = await fetch(`${BASE_URL}/students/payment_registration/getOfferingsByUser`);
            const res = await response.json();

            if (res.status === 'success' && res.data && res.data.length > 0) {
                window.StudentsS2.renderOfferings(res.data);
            } else {
                if (container) {
                    container.innerHTML = `
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-shield-check text-success fs-1"></i>
                            <h6 class="fw-bold mt-2">Sin Pagos Pendientes</h6>
                            <p class="text-muted small px-4">No tiene cuotas por pagar o sus inscripciones no requieren reporte administrativo.</p>
                        </div>`;
                }
                if (btnNext) btnNext.disabled = true;
            }
        } catch (error) {
            console.error("Error financiero:", error);
            if (container) {
                container.innerHTML = '<div class="alert alert-danger text-center smallest">Error de comunicación con el servidor de pagos.</div>';
            }
        }
    },

    /**
     * Renderiza la lista de tarjetas con diseño optimizado para móvil.
     */
    renderOfferings: (offerings) => {
        const container = document.getElementById('offeringsContainer');
        const selectedId = document.getElementById('offering_id_val')?.value;
        const btnNext = document.getElementById('btnNext');
        
        if (!container) return;
        container.innerHTML = ''; 

        offerings.forEach(offering => {
            const offeringId = offering.offering_id;
            const totalPending = parseFloat(offering.total_pending) || 0;
            const diplomaName = offering.diploma_name || 'Programa Académico';
            const cohortName = offering.cohort_name || 'Cohorte activa';

            const isSelected = (selectedId == offeringId);
            // FIX: Usamos la clase 'selected' para el contorno y eliminamos rellenos
            const activeClass = isSelected ? 'selected' : '';
            
            let montoDeudaFormateado = "0,00";
            if (typeof window.StudentsUtils !== 'undefined') {
                montoDeudaFormateado = window.StudentsUtils.formatNumberToCurrency(totalPending);
            } else if (typeof window.FinancialUtils !== 'undefined') {
                montoDeudaFormateado = window.FinancialUtils.formatNumberToCurrency(totalPending);
            }

            const item = document.createElement('div');
            item.className = 'col-12 col-lg-10 mx-auto'; 
            
            // FIX: Cambiado h6 por h5 para mayor tamaño de letra
            item.innerHTML = `
                <div class="card card-offering border-2 shadow-sm rounded-4 mb-3 btn text-start p-0 overflow-hidden ${activeClass}" 
                     onclick="window.StudentsS2.selectOffering(this, ${offeringId}, ${totalPending})">
                    
                    <div class="p-3 p-md-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center" style="max-width: 70%;">
                            <div class="bg-light border rounded-4 p-3 me-3 d-none d-sm-block">
                                <i class="bi bi-mortarboard-fill text-primary fs-3"></i>
                            </div>
                            <div class="text-truncate">
                                <h5 class="fw-bold mb-1 text-dark text-uppercase">${diplomaName}</h5>
                                <span class="badge bg-dark bg-opacity-10 text-dark smallest fw-bold mt-1 text-uppercase">${cohortName}</span>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <small class="smallest text-muted text-uppercase fw-bold d-block mb-1">Monto Pendiente</small>
                            <span class="fs-4 fw-bold text-danger">$ ${montoDeudaFormateado}</span>
                        </div>
                    </div>
                    
                    <div class="check-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>`;
            container.appendChild(item);
        });

        if (selectedId && selectedId !== "0" && btnNext) btnNext.disabled = false;
    },

    /**
     * Gestiona el evento de selección aplicando solo el contorno.
     */
    selectOffering: (element, offeringId, pendingDebt) => {
        // Limpiamos selecciones previas
        document.querySelectorAll('.card-offering').forEach(c => {
            c.classList.remove('selected');
        });

        // Aplicamos clase 'selected' (esto activará el borde definido en CSS)
        element.classList.add('selected');

        const inputId = document.getElementById('offering_id_val');
        if (inputId) inputId.value = offeringId;

        // Sincronización con el Paso 4 (UI del reporte)
        if (typeof window.StudentsUI !== 'undefined') {
            window.StudentsUI.actualizarMontoEnPantalla(pendingDebt);
        } else if (typeof window.FinancialUtils !== 'undefined') {
            // Backup en caso de usar el formateador financiero
            const labelVisual = document.getElementById('valAmountCash');
            if (labelVisual) labelVisual.innerText = window.FinancialUtils.formatNumberToCurrency(pendingDebt);
            const inputAmount = document.getElementById('amount');
            if (inputAmount) inputAmount.value = pendingDebt.toFixed(2);
        }

        const btnNext = document.getElementById('btnNext');
        if (btnNext) btnNext.disabled = false;
    }
};