/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS (ZELLE)
 * ARCHIVO: public/assets/js/financial_payment_validations_zelle.js
 * PROPÓSITO: Lógica de auditoría, zoom de comprobantes y cross-check de seguridad.
 * VERSIÓN: 1.1.6 - Fix: Gatillo de notificaciones y normalización de rutas.
 */

(function() {
    "use strict";

    const state = {
        baseUrl: window.BASE_URL || '/diplomatic/public',
        currentPayments: [],
        activeIndex: null,
        zoom: 1
    };

    document.addEventListener('DOMContentLoaded', () => {
        fetchPayments();
        initSecurityProtocol();
        initZoomControls();
        initFilterHandlers();
    });

    async function fetchPayments(filters = {}) {
        const tableBody = document.querySelector('#table-zelle-pending tbody');
        if (!tableBody) return;

        tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>`;

        try {
            const query = new URLSearchParams(filters).toString();
            const response = await fetch(`${state.baseUrl}/financial/payment_validations/zelle/getPendingPayments?${query}`);
            const result = await response.json();

            if (!result.ok || !result.data || result.data.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No hay pagos Zelle pendientes.</td></tr>`;
                return;
            }

            state.currentPayments = result.data;
            tableBody.innerHTML = result.data.map((row, index) => `
                <tr onclick="window.openZelleModal(${index})">
                    <td class="ps-4">${row.fecha_pago}</td>
                    <td class="fw-bold">${row.estudiante}</td>
                    <td>${row.titular}</td>
                    <td>${row.correo}</td>
                    <td class="font-monospace text-primary fw-bold">${row.referencia}</td>
                    <td class="text-end fw-bold pe-4">$ ${parseFloat(row.monto_usd).toFixed(2)}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-sm">
                            <i class="bi bi-search"></i> Auditar
                        </button>
                    </td>
                </tr>
            `).join('');
        } catch (error) {
            console.error("Error financiero:", error);
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Error de comunicación.</td></tr>`;
        }
    }

    window.openZelleModal = function(index) {
        const d = state.currentPayments[index];
        state.activeIndex = index;

        document.getElementById('v-estudiante').innerText = d.estudiante;
        document.getElementById('v-titular').innerText = d.titular;
        document.getElementById('v-correo').innerText = d.correo;
        document.getElementById('v-fecha').innerText = d.fecha_pago;
        document.getElementById('v-referencia-display').innerText = d.referencia;
        document.getElementById('v-monto').innerText = `$ ${parseFloat(d.monto_usd).toFixed(2)}`;

        const img = document.getElementById('v-screenshot');
        img.src = `${state.baseUrl}/${d.screenshot_path}?t=${new Date().getTime()}`;
        img.style.transform = 'scale(1)';
        state.zoom = 1;

        const input = document.getElementById('input-verify-reference');
        input.value = '';
        input.classList.remove('is-valid', 'is-invalid');
        document.getElementById('btn-confirm-validation').disabled = true;

        new bootstrap.Modal(document.getElementById('modalValidateZelle')).show();
    };

    function initSecurityProtocol() {
        const input = document.getElementById('input-verify-reference');
        const btnConfirm = document.getElementById('btn-confirm-validation');
        const btnReject = document.getElementById('btn-reject-validation');

        input.oninput = function() {
            const target = state.currentPayments[state.activeIndex].referencia.trim().toUpperCase();
            const current = this.value.trim().toUpperCase();

            if (current === target) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
                btnConfirm.disabled = false;
            } else {
                this.classList.remove('is-valid');
                btnConfirm.disabled = true;
            }
        };

        btnConfirm.onclick = function() {
            const d = state.currentPayments[state.activeIndex];
            Swal.fire({
                title: '¿Confirmar Aprobación?',
                text: "El pago se aplicará al Ledger y se notificará al estudiante.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Sí, Validar'
            }).then((result) => {
                if (result.isConfirmed) processValidation(d.id, d.referencia);
            });
        };

        btnReject.onclick = function() {
            const d = state.currentPayments[state.activeIndex];
            
            Swal.fire({
                title: '¿Desea rechazar el registro de pago?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6e7881',
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Enviamos 'Rechazo Administrativo' como motivo por defecto
                    // para que el modelo/controlador no reciba un campo vacío.
                    processRejection(d.id, 'Rechazo Administrativo');
                }
            });
};
    }

    async function processValidation(paymentId, reference) {
        try {
            const fd = new FormData();
            fd.append('payment_id', paymentId);
            fd.append('reference', reference);

            const res = await fetch(`${state.baseUrl}/financial/payment_validations/zelle/validatePayment`, {
                method: 'POST',
                body: fd
            });
            const result = await res.json();

            if (result.ok) {
                // GATILLO DE NOTIFICACIÓN POR CORREO
                if (typeof PaymentNotificator !== 'undefined') {
                    PaymentNotificator.sendApprovedEmail(paymentId);
                }

                Swal.fire({
                    title: '¡Éxito!',
                    text: result.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });

                bootstrap.Modal.getInstance(document.getElementById('modalValidateZelle')).hide();
                fetchPayments();
            } else {
                throw new Error(result.message);
            }
        } catch (e) { 
            Swal.fire('Error Contable', e.message, 'error'); 
        }
    }

    async function processRejection(paymentId, reason) {
        try {
            const fd = new FormData();
            fd.append('payment_id', paymentId);
            fd.append('reason', reason);

            const res = await fetch(`${state.baseUrl}/financial/payment_validations/zelle/rejectPayment`, {
                method: 'POST',
                body: fd
            });
            const result = await res.json();

            if (result.ok) {
                Swal.fire('Procesado', 'El pago ha sido rechazado correctamente.', 'warning');
                bootstrap.Modal.getInstance(document.getElementById('modalValidateZelle')).hide();
                fetchPayments();
            } else {
                throw new Error(result.message);
            }
        } catch (e) { 
            Swal.fire('Error', e.message, 'error'); 
        }
    }

    function initZoomControls() {
        const img = document.getElementById('v-screenshot');
        document.getElementById('btn-zoom-in').onclick = () => { state.zoom += 0.2; img.style.transform = `scale(${state.zoom})`; };
        document.getElementById('btn-zoom-out').onclick = () => { if(state.zoom > 0.5) state.zoom -= 0.2; img.style.transform = `scale(${state.zoom})`; };
        document.getElementById('btn-reset-zoom').onclick = () => { state.zoom = 1; img.style.transform = 'scale(1)'; };
    }

    function initFilterHandlers() {
        const form = document.getElementById('filter-form-zelle');
        if (form) {
            form.onsubmit = (e) => {
                e.preventDefault();
                fetchPayments({ text: document.getElementById('search-text').value });
            };
        }
        document.getElementById('btn-clear-filters').onclick = () => {
            document.getElementById('search-text').value = '';
            fetchPayments();
        };
    }
})();