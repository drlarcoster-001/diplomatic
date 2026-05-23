/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS (BINANCE)
 * ARCHIVO: public/assets/js/financial_payment_validations_binance.js
 * PROPÓSITO: Lógica de cliente para la auditoría técnica de cuotas en USDT y Cascada Ledger.
 * VERSIÓN: 1.0.1 - Fix: Saneamiento de rutas y persistencia de zoom.
 */

(function() {
    "use strict";

    const state = {
        baseUrl: window.BASE_URL || '/diplomatic/public',
        currentPayments: [],
        activePaymentIndex: null,
        currentZoom: 1
    };

    document.addEventListener('DOMContentLoaded', () => {
        initFilters();
        initValidationActions();
        initViewerControls();
        initSecurityProtocol();
        fetchPendingPayments();
    });

    /**
     * 1. CARGA DE DATOS
     */
    async function fetchPendingPayments(filters = {}) {
        const tableBody = document.querySelector('#table-binance-pending tbody');
        if (!tableBody) return;

        tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-warning" role="status"></div><br><small class="text-muted">Cargando Binance Pay...</small></td></tr>`;

        try {
            const query = new URLSearchParams(filters).toString();
            const response = await fetch(`${state.baseUrl}/financial/payment_validations/binance/getPendingPayments?${query}`);
            const result = await response.json();

            if (!result.ok || !result.data || result.data.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-info-circle me-1"></i> No hay pagos Binance pendientes.</td></tr>`;
                state.currentPayments = [];
                return;
            }

            state.currentPayments = result.data;
            renderTable(result.data);

        } catch (error) {
            console.error("Error Binance:", error);
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger small">Error de conexión con el servidor.</td></tr>`;
        }
    }

    function renderTable(data) {
        const tableBody = document.querySelector('#table-binance-pending tbody');
        tableBody.innerHTML = data.map((row, index) => `
            <tr onclick="window.openBinanceModal(${index})" style="cursor:pointer;">
                <td class="ps-4 fw-bold text-dark">${row.fecha_pago}</td>
                <td class="text-secondary fw-medium">${row.estudiante}</td>
                <td class="text-dark fw-bold">${row.titular || 'N/A'}</td>
                <td class="font-monospace text-primary fw-bold">${row.referencia}</td>
                <td class="text-end fw-bold">
                    <span class="badge bg-light text-success border px-2 py-1 fs-6">${parseFloat(row.monto_usd).toFixed(2)} USDT</span>
                </td>
                <td class="text-center pe-4">
                    <button class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3 shadow-sm">
                        <i class="bi bi-shield-check me-1"></i> Auditar
                    </button>
                </td>
            </tr>`).join('');
    }

    /**
     * 2. MODAL Y AUDITORÍA
     */
    window.openBinanceModal = function(index) {
        const data = state.currentPayments[index];
        if (!data) return;

        state.activePaymentIndex = index;

        document.getElementById('v-estudiante').innerText = data.estudiante;
        document.getElementById('v-titular').innerText = data.titular || 'N/A';
        document.getElementById('v-fecha').innerText = data.fecha_pago;
        document.getElementById('v-referencia-display').innerText = data.referencia;
        document.getElementById('v-monto').innerText = `${parseFloat(data.monto_usd).toFixed(2)} USDT`;
        
        const inputVerify = document.getElementById('input-verify-reference');
        inputVerify.value = '';
        inputVerify.classList.remove('is-valid', 'is-invalid');
        document.getElementById('btn-confirm-validation').disabled = true;

        const img = document.getElementById('v-screenshot');
        img.src = `${state.baseUrl}/${data.screenshot_path}?t=${new Date().getTime()}`;
        resetZoom();

        const modal = new bootstrap.Modal(document.getElementById('modalValidateBinance'));
        modal.show();
    };

    /**
     * 3. PROTOCOLO DE SEGURIDAD
     */
    function initSecurityProtocol() {
        const inputVerify = document.getElementById('input-verify-reference');
        const btnConfirm = document.getElementById('btn-confirm-validation');

        inputVerify.addEventListener('input', (e) => {
            const reported = document.getElementById('v-referencia-display').innerText.trim().toUpperCase();
            const typed = e.target.value.trim().toUpperCase();

            if (reported === typed) {
                inputVerify.classList.add('is-valid');
                btnConfirm.disabled = false;
            } else {
                inputVerify.classList.remove('is-valid');
                btnConfirm.disabled = true;
            }
        });
    }

    /**
     * 4. EJECUCIÓN (APROBAR / RECHAZAR)
     */
    function initValidationActions() {
        document.getElementById('btn-confirm-validation').onclick = () => {
            confirmAction('¿Confirmar Validación?', 'Se aplicará el pago al Ledger del estudiante.', 'validatePayment');
        };

        document.getElementById('btn-reject-validation').onclick = () => {
            confirmAction('¿Rechazar Transacción?', 'El registro será marcado como inválido.', 'rejectPayment', '#dc3545');
        };
    }

    async function confirmAction(title, text, endpoint, color = '#0d6efd') {
        const data = state.currentPayments[state.activePaymentIndex];
        const result = await Swal.fire({
            title: title,
            text: text,
            icon: endpoint === 'rejectPayment' ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            confirmButtonColor: color
        });

        if (result.isConfirmed) executeAction(endpoint, data.id);
    }

    async function executeAction(endpoint, paymentId) {
        Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const fd = new FormData();
            fd.append('payment_id', paymentId);

            const response = await fetch(`${state.baseUrl}/financial/payment_validations/binance/${endpoint}`, {
                method: 'POST',
                body: fd
            });
            const result = await response.json();

            if (result.ok) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalValidateBinance'));
                if (modal) modal.hide();

                // GATILLO DE NOTIFICACIÓN
                if (endpoint === 'validatePayment' && typeof PaymentNotificator !== 'undefined') {
                    PaymentNotificator.sendApprovedEmail(paymentId);
                }

                await Swal.fire({ icon: 'success', title: '¡Hecho!', text: result.message, timer: 2000, showConfirmButton: false });
                fetchPendingPayments();
            } else {
                throw new Error(result.message);
            }
        } catch (e) {
            Swal.fire('Fallo', e.message, 'error');
        }
    }

    /**
     * 5. ZOOM
     */
    function initViewerControls() {
        const img = document.getElementById('v-screenshot');
        document.getElementById('btn-zoom-in').onclick = () => { state.currentZoom += 0.25; updateZoom(); };
        document.getElementById('btn-zoom-out').onclick = () => { if(state.currentZoom > 0.5) state.currentZoom -= 0.25; updateZoom(); };
        document.getElementById('btn-reset-zoom').onclick = () => resetZoom();

        function updateZoom() { img.style.transform = `scale(${state.currentZoom})`; }
    }

    function resetZoom() {
        state.currentZoom = 1;
        const img = document.getElementById('v-screenshot');
        if (img) img.style.transform = `scale(1)`;
    }

    /**
     * 6. FILTROS
     */
    function initFilters() {
        const form = document.getElementById('filter-form-binance');
        form.onsubmit = (e) => {
            e.preventDefault();
            fetchPendingPayments({ text: document.getElementById('search-text').value });
        };
        document.getElementById('btn-clear-filters').onclick = () => {
            form.reset();
            fetchPendingPayments();
        };
    }

})();