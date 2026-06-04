/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS (EFECTIVO)
 * ARCHIVO: public/assets/js/financial_payment_validations_efectivo.js
 * PROPÓSITO: Lógica de validación de reportes de efectivo, arqueo y rechazos.
 * VERSIÓN: 1.2.0 - FIX: Parche de foco global y lógica de rechazo manual integrada.
 */

/* ==========================================================================
   1. PARCHE GLOBAL DE FOCO (Bootstrap 5 vs SweetAlert2)
   Este bloque soluciona el problema de los campos de texto deshabilitados
   cuando se abre un SweetAlert sobre un modal de Bootstrap.
   ========================================================================== */
window.addEventListener('focusin', (e) => {
    if (document.querySelector('.swal2-container') !== null) {
        if (document.querySelector('.swal2-container').contains(e.target)) {
            e.stopImmediatePropagation();
        }
    }
}, true);

$(document).on('focusin', function(e) {
    if ($(e.target).closest(".swal2-container").length) {
        e.stopImmediatePropagation();
    }
});

/* ==========================================================================
   2. LÓGICA PRINCIPAL DEL MÓDULO
   ========================================================================== */
(function() {
    "use strict";

    const state = {
        baseUrl: window.BASE_URL || '/diplomatic/public',
        payments: [],
        activePaymentIndex: null,
        totalContado: 0
    };

    document.addEventListener('DOMContentLoaded', () => {
        initFilters();
        initTableEvents();
        initModalActions();
        fetchPendingPayments();
    });

    /**
     * CARGA DE PAGOS PENDIENTES
     */
    async function fetchPendingPayments(filters = {}) {
        const tableBody = document.querySelector('#table-efectivo-pending tbody');
        if (!tableBody) return;

        tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-success" role="status"></div><br><small class="text-muted">Buscando reportes en caja...</small></td></tr>`;

        try {
            const query = new URLSearchParams(filters).toString();
            const response = await fetch(`${state.baseUrl}/financial/payment_validations/efectivo/getPendingPayments?${query}`);
            const result = await response.json();

            if (!result.ok || !result.data || result.data.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-muted"><i class="bi bi-info-circle me-1"></i> No hay reportes de efectivo pendientes.</td></tr>`;
                state.payments = [];
                return;
            }

            state.payments = result.data;
            renderTable(result.data);

        } catch (error) {
            console.error("Error Cash:", error);
            tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-danger small">Error de comunicación con el servidor.</td></tr>`;
        }
    }

    function renderTable(data) {
        const tableBody = document.querySelector('#table-efectivo-pending tbody');
        tableBody.innerHTML = data.map((row, index) => `
            <tr data-index="${index}" style="cursor:pointer;" class="align-middle">
                <td class="ps-4 text-muted small">${row.fecha_reporte}</td>
                <td class="fw-bold text-dark">${row.estudiante} <br> <small class="text-muted fw-normal">${row.cedula}</small></td>
                <td class="text-end fw-bold text-primary pe-4">$ ${parseFloat(row.monto_reportado).toFixed(2)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-success rounded-pill px-3 shadow-sm fw-bold">
                        <i class="bi bi-shield-check me-1"></i> Validar
                    </button>
                </td>
            </tr>`).join('');
    }

    /**
     * GESTIÓN DEL MODAL Y ARQUEO
     */
    window.openEfectivoModal = function(index) {
        const data = state.payments[index];
        if (!data) return;

        state.activePaymentIndex = index;
        
        document.getElementById('v-estudiante').innerText = data.estudiante;
        document.getElementById('v-cedula').innerText = `C.I: ${data.cedula}`;
        document.getElementById('v-deuda-total').innerText = `$ ${parseFloat(data.monto_reportado).toFixed(2)}`;
        
        document.getElementById('input-amount').value = '';
        document.querySelectorAll('.bill-input').forEach(input => input.value = 0);
        window.calculateTotal();

        const modalEl = document.getElementById('modalValidateEfectivo');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    };

    window.calculateTotal = function() {
        let total = 0;
        document.querySelectorAll('.bill-input').forEach(input => {
            const den = parseFloat(input.getAttribute('data-den'));
            const qty = parseInt(input.value) || 0;
            const sub = den * qty;
            total += sub;
            const subDisplay = input.closest('tr').querySelector('.subtotal-display');
            if(subDisplay) subDisplay.innerText = `$ ${sub.toFixed(2)}`;
        });

        state.totalContado = total;
        document.getElementById('monto-contado').innerText = `$ ${total.toFixed(2)}`;
    };

    window.matchAmount = function() {
        if(state.totalContado > 0) {
            document.getElementById('input-amount').value = state.totalContado.toFixed(2);
            document.getElementById('input-currency').value = 'USD'; 
        }
    };

    /**
     * ACCIONES DEL MODAL: VALIDACIÓN Y RECHAZO
     */
    function initModalActions() {
        const btnConfirm = document.getElementById('btn-confirm-cash');
        const btnReject = document.getElementById('btn-reject-cash');

        // BOTÓN: CONFIRMAR VALIDACIÓN (APROBAR)
        btnConfirm.onclick = async () => {
            const payment = state.payments[state.activePaymentIndex];
            if (!payment) return Swal.fire('Error', 'No se pudo recuperar la información.', 'error');

            if (state.totalContado <= 0) {
                return Swal.fire({ icon: 'warning', title: 'Arqueo Vacío', text: 'Realice el conteo de billetes antes de validar.', confirmButtonColor: '#ffc107' });
            }

            const amount = document.getElementById('input-amount').value;
            const currency = document.getElementById('input-currency').value;

            if (parseFloat(amount) !== state.totalContado) {
                 return Swal.fire({ icon: 'error', title: 'Discrepancia', text: 'El monto a abonar no coincide con el arqueo.' });
            }

            const breakdown = {};
            document.querySelectorAll('.bill-input').forEach(input => {
                const den = input.getAttribute('data-den');
                const qty = parseInt(input.value) || 0;
                if(qty > 0) breakdown[den] = qty;
            });

            const result = await Swal.fire({
                title: '¿Confirmar Validación?',
                text: `Se aprobará el reporte de $${parseFloat(payment.monto_reportado).toFixed(2)} para ${payment.estudiante}.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, validar efectivo',
                confirmButtonColor: '#198754'
            });

            if (result.isConfirmed) {
                executeCashStore(payment.payment_id, amount, currency, breakdown);
            }
        };

        // BOTÓN: RECHAZAR PAGO (A PEDAL)
        btnReject.onclick = async () => {
            const payment = state.payments[state.activePaymentIndex];
            if (!payment) return;

            const { value: reason } = await Swal.fire({
                title: '¿Rechazar este pago?',
                html: `
                    <div class="text-start">
                        <p class="small text-muted mb-2">Escriba el motivo del rechazo para <b>${payment.estudiante}</b>:</p>
                        <textarea id="motivo-rechazo-manual" class="form-control" 
                                  style="height: 120px; resize: none; position: relative; z-index: 10005;" 
                                  placeholder="Ej: Billetes en mal estado, monto incompleto..."></textarea>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, Rechazar',
                cancelButtonText: 'Volver',
                confirmButtonColor: '#dc3545',
                backdrop: true,
                allowOutsideClick: false,
                returnFocus: false, // CLAVE: No devuelve el foco al botón de Bootstrap automáticamente
                preConfirm: () => {
                    const motivo = document.getElementById('motivo-rechazo-manual').value;
                    if (!motivo || motivo.trim() === "") {
                        Swal.showValidationMessage('¡El motivo es obligatorio!');
                        return false;
                    }
                    return motivo;
                },
                didOpen: () => {
                    const txt = document.getElementById('motivo-rechazo-manual');
                    setTimeout(() => {
                        txt.focus();
                        txt.disabled = false;
                    }, 300); // Retraso para vencer el "focus trap" del modal
                }
            });

            if (reason) {
                executeCashReject(payment.payment_id, reason);
            }
        };
    }

    /**
     * ENVÍO DE DATOS AL SERVIDOR
     */
    async function executeCashReject(paymentId, reason) {
        Swal.fire({ title: 'Procesando rechazo...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const fd = new FormData();
            fd.append('payment_id', paymentId);
            fd.append('reason', reason);

            const response = await fetch(`${state.baseUrl}/financial/payment_validations/efectivo/rejectPayment`, {
                method: 'POST',
                body: fd
            });
            const res = await response.json();

            if (res.ok) {
                bootstrap.Modal.getInstance(document.getElementById('modalValidateEfectivo')).hide();
                await Swal.fire('¡Rechazado!', res.message, 'success');
                fetchPendingPayments();
            } else {
                throw new Error(res.message);
            }
        } catch (e) {
            Swal.fire('Error', e.message, 'error');
        }
    }

    async function executeCashStore(paymentId, amount, currency, breakdown) {
        Swal.fire({ title: 'Procesando validación...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const fd = new FormData();
            fd.append('payment_id', paymentId);
            fd.append('amount', amount);
            fd.append('currency', currency);
            fd.append('breakdown', JSON.stringify(breakdown));

            const response = await fetch(`${state.baseUrl}/financial/payment_validations/efectivo/validatePayment`, {
                method: 'POST',
                body: fd
            });
            const res = await response.json();

            if (res.ok) {
                bootstrap.Modal.getInstance(document.getElementById('modalValidateEfectivo')).hide();
                await Swal.fire('¡Validado!', res.message, 'success');
                fetchPendingPayments();
            } else {
                throw new Error(res.message);
            }
        } catch (e) {
            Swal.fire('Error', e.message, 'error');
        }
    }

    function initFilters() {
        const form = document.getElementById('filter-form-efectivo');
        if(form) {
            form.onsubmit = (e) => {
                e.preventDefault();
                fetchPendingPayments({ text: document.getElementById('search-text').value });
            };
        }
    }

    function initTableEvents() {
        const tableBody = document.querySelector('#table-efectivo-pending tbody');
        if(!tableBody) return;
        tableBody.addEventListener('click', (e) => {
            const row = e.target.closest('tr');
            if(row) {
                const index = row.getAttribute('data-index');
                if(index !== null) window.openEfectivoModal(parseInt(index));
            }
        });
    }
})();