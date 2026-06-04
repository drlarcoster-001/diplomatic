/**
 * MÓDULO: GESTIÓN FINANCIERA / EFECTIVO (CASH)
 * ARCHIVO: public/assets/js/financial_cash_operations_efectivo.js
 * PROPÓSITO: Lógica de arqueo de billetes, conciliación en ventanilla y gestión de rechazos.
 * VERSIÓN: 1.3.0
 * INTEGRACIÓN: Sincronizado con Bootstrap.php (/financial/cash-operations/efectivo)
 * NOTA PARA PROGRAMADORES: Si hay problemas de redirección al dashboard, verifique que 
 * state.baseUrl en la consola coincida con la URL del servidor.
 */

(function() {
    "use strict";

    const state = {
        // Detección dinámica de la raíz del proyecto para compatibilidad local/producción
        baseUrl: window.location.origin + (window.location.pathname.startsWith('/diplomatic/public') ? '/diplomatic/public' : ''),
        currentPayments: [],
        activePaymentIndex: null,
        currentBreakdown: {}
    };

    /**
     * INICIALIZADOR
     */
    document.addEventListener('DOMContentLoaded', () => {
        console.log("%c🚀 Módulo Efectivo v1.3.0 Cargado", "color: #28a745; font-weight: bold;");
        console.log("Ruta Base Detectada:", state.baseUrl);

        initFilters();
        initRejectAction();
        fetchPendingPayments();
    });

    /**
     * 1. CARGA DE DATOS (GRID)
     * Consume: FinancialCashEfectivoController::getPendingPayments
     */
    async function fetchPendingPayments(filters = {}) {
        const tableBody = document.querySelector('#table-efectivo-pending tbody');
        if (!tableBody) return;

        tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-success" role="status"></div><br><small class="text-muted mt-2 d-block">Consultando compromisos pendientes...</small></td></tr>`;

        try {
            const query = new URLSearchParams(filters).toString();
            const endpoint = `${state.baseUrl}/financial/cash-operations/efectivo/getPendingPayments?${query}`;
            
            const response = await fetch(endpoint);
            
            // Si el servidor responde con 404 o redirige (HTML), lanzamos error
            if (!response.ok || response.headers.get('content-type')?.includes('text/html')) {
                throw new Error("El servidor devolvió una ruta no válida o error de acceso. Verifique Bootstrap.php");
            }
            
            const result = await response.json();

            if (!result.ok || !result.data || result.data.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-info-circle me-1"></i> No se encontraron pagos en efectivo pendientes.</td></tr>`;
                state.currentPayments = [];
                return;
            }

            state.currentPayments = result.data;
            let html = '';
            
            result.data.forEach((row, index) => {
                html += `
                    <tr class="align-middle" onclick="window.openEfectivoModal(${index})" style="cursor: pointer;">
                        <td class="ps-4 text-muted small">${row.fecha_inscripcion || 'N/A'}</td>
                        <td class="fw-bold text-dark">${row.estudiante}</td>
                        <td class="font-monospace small">${row.cedula}</td>
                        <td class="small text-muted">${row.diplomado}</td>
                        <td class="text-end fw-bold text-primary pe-4">$ ${parseFloat(row.monto_pactado).toFixed(2)}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
                                <i class="bi bi-cash"></i> Cobrar
                            </button>
                        </td>
                    </tr>`;
            });
            tableBody.innerHTML = html;

        } catch (e) { 
            console.error("🚨 Error en Grid Efectivo:", e.message);
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger small">Error: ${e.message}</td></tr>`;
        }
    }

    /**
     * 2. GESTIÓN DEL MODAL Y ARQUEO
     */
    window.openEfectivoModal = function(index) {
        const data = state.currentPayments[index];
        if (!data) return;
        
        state.activePaymentIndex = index;

        document.getElementById('v-estudiante').innerText = data.estudiante;
        document.getElementById('v-diplomado').innerText = data.diplomado;
        document.getElementById('v-monto-pactado').innerText = `$ ${parseFloat(data.monto_pactado).toFixed(2)}`;
        
        // Reset de arqueo
        document.querySelectorAll('.bill-input').forEach(input => input.value = 0);
        window.calculateTotal(); 

        const modalEl = document.getElementById('modalValidateEfectivo');
        // focus:false permite que SweetAlert2 maneje sus propios inputs de texto sin conflictos
        bootstrap.Modal.getOrCreateInstance(modalEl, { focus: false }).show();
    };

    /**
     * 3. MOTOR DE CÁLCULO DE BILLETES
     */
    window.calculateTotal = function() {
        let total = 0;
        const breakdown = {};
        const inputs = document.querySelectorAll('.bill-input');
        
        if (state.activePaymentIndex === null) return;
        const pactado = parseFloat(state.currentPayments[state.activePaymentIndex].monto_pactado);

        inputs.forEach(input => {
            const den = parseInt(input.dataset.den);
            const qty = parseInt(input.value) || 0;
            const sub = den * qty;
            total += sub;
            breakdown[den] = qty;
            
            const subEl = document.getElementById(`sub-${den}`);
            if (subEl) subEl.innerText = `$ ${sub.toFixed(2)}`;
        });

        const displayTotal = document.getElementById('monto-contado');
        const btnConfirm = document.getElementById('btn-confirm-cash');
        const statusMsg = document.getElementById('status-message');

        if (displayTotal) displayTotal.innerText = `$ ${total.toFixed(2)}`;

        // Validación con tolerancia de decimales
        if (Math.abs(total - pactado) < 0.01) {
            if (displayTotal) displayTotal.className = 'fs-1 fw-bold text-success animate__animated animate__pulse';
            if (btnConfirm) btnConfirm.disabled = false;
            if (statusMsg) statusMsg.innerHTML = '<span class="badge bg-success shadow-sm">Monto Exacto</span>';
        } else {
            if (displayTotal) displayTotal.className = 'fs-1 fw-bold text-danger';
            if (btnConfirm) btnConfirm.disabled = true;
            if (statusMsg) {
                statusMsg.innerHTML = total > pactado 
                    ? '<span class="badge bg-warning text-dark shadow-sm">Exceso detectado</span>' 
                    : '<span class="badge bg-danger shadow-sm">Falta dinero</span>';
            }
        }
        state.currentBreakdown = breakdown;
    };

    /**
     * 4. ACCIÓN: CONFIRMAR COBRO
     */
    const btnConfirmCash = document.getElementById('btn-confirm-cash');
    if (btnConfirmCash) {
        btnConfirmCash.onclick = async function() {
            const data = state.currentPayments[state.activePaymentIndex];
            
            const { isConfirmed } = await Swal.fire({
                title: '¿Confirmar Recepción?',
                text: `Se procesará el cobro físico de $${data.monto_pactado} para ${data.estudiante}.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, dinero recibido',
                confirmButtonColor: '#28a745',
                cancelButtonText: 'Cancelar'
            });

            if (isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('payment_id', data.payment_id || data.id);
                    formData.append('breakdown', JSON.stringify(state.currentBreakdown));

                    const response = await fetch(`${state.baseUrl}/financial/cash-operations/efectivo/validatePayment`, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const res = await response.json();
                    
                    if (res.ok) {
                        await Swal.fire({ icon: 'success', title: '¡Éxito!', text: res.message, timer: 2000, showConfirmButton: false });
                        bootstrap.Modal.getInstance(document.getElementById('modalValidateEfectivo')).hide();
                        fetchPendingPayments();
                    } else { 
                        throw new Error(res.message); 
                    }
                } catch (e) { 
                    Swal.fire('Error de Validación', e.message, 'error'); 
                }
            }
        };
    }

    /**
     * 5. ACCIÓN: RECHAZAR PAGO
     */
    function initRejectAction() {
        const btnReject = document.getElementById('btn-reject-cash');
        if (!btnReject) return;

        btnReject.onclick = async function() {
            const data = state.currentPayments[state.activePaymentIndex];
            
            const { value: reason, isConfirmed } = await Swal.fire({
                title: '¿Rechazar Cobro?',
                text: `Motivo por el cual no se recibe el efectivo de ${data.estudiante}:`,
                input: 'text',
                inputPlaceholder: 'Ej: Billetes falsos, dañados o monto incompleto...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Rechazar Pago',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Volver',
                inputValidator: (value) => {
                    if (!value) return '¡Es obligatorio indicar un motivo!';
                },
                didOpen: () => {
                    setTimeout(() => {
                        const input = Swal.getInput();
                        if (input) input.focus();
                    }, 150);
                }
            });

            if (isConfirmed) {
                try {
                    Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    const formData = new FormData();
                    formData.append('payment_id', data.payment_id || data.id);
                    formData.append('reason', reason);

                    const response = await fetch(`${state.baseUrl}/financial/cash-operations/efectivo/rejectPayment`, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const res = await response.json();

                    if (res.ok) {
                        await Swal.fire('¡Anulado!', res.message, 'success');
                        const modalEl = document.getElementById('modalValidateEfectivo');
                        bootstrap.Modal.getInstance(modalEl).hide();
                        fetchPendingPayments();
                    } else { 
                        throw new Error(res.message); 
                    }
                } catch (e) { 
                    Swal.fire('Error', e.message, 'error'); 
                }
            }
        };
    }

    /**
     * 6. FILTROS
     */
    function initFilters() {
        const form = document.getElementById('filter-form-efectivo');
        const btnClear = document.getElementById('btn-clear-filters');
        
        if (form) {
            form.onsubmit = (e) => {
                e.preventDefault();
                fetchPendingPayments({ text: document.getElementById('search-text').value });
            };
        }
        
        if (btnClear) {
            btnClear.onclick = () => {
                const input = document.getElementById('search-text');
                if (input) input.value = '';
                fetchPendingPayments();
            };
        }
    }

})();