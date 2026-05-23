/**
 * MÓDULO: GESTIÓN FINANCIERA / BINANCE PAY
 * ARCHIVO: public/assets/js/financial_cash_operations_binance.js
 * PROPÓSITO: Lógica de cliente para conciliación USDT, visor de pruebas y protocolo de seguridad (cross-check).
 * VERSIÓN: 1.1.0 - FIX: Sincronización de rutas con Bootstrap.php (/financial/cash-operations/binance).
 */

(function() {
    "use strict";

    const state = {
        baseUrl: window.location.origin + '/diplomatic/public',
        currentPayments: [],
        activePaymentIndex: null,
        currentZoom: 1
    };

    /**
     * INICIALIZADOR PRINCIPAL
     */
    document.addEventListener('DOMContentLoaded', () => {
        initClock();
        initFilters();
        initTableEvents();
        initValidationActions();
        initViewerControls();
        initSecurityProtocol(); 
        fetchPendingPayments();

        console.log("Módulo Binance Pay v1.1.0: Estandarizado y Sincronizado.");
    });

    /**
     * 1. RELOJ EN TIEMPO REAL
     */
    function initClock() {
        const el = document.getElementById('real-time-clock');
        if (!el) return;
        const update = () => {
            el.innerText = new Date().toLocaleTimeString('en-US', { 
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true 
            });
        };
        update();
        setInterval(update, 1000);
    }

    /**
     * 2. FILTROS DE BÚSQUEDA
     */
    function initFilters() {
        const form = document.getElementById('filter-form-binance');
        const btnClear = document.getElementById('btn-clear-filters');

        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                fetchPendingPayments({
                    text: document.getElementById('search-text').value,
                    date_from: document.getElementById('date-from').value,
                    date_to: document.getElementById('date-to').value
                });
            });
        }

        if (btnClear) {
            btnClear.onclick = () => {
                form.reset();
                fetchPendingPayments();
            };
        }
    }

    /**
     * 3. CARGA DE DATOS (FETCH API)
     * CORRECCIÓN: Endpoint sincronizado con Bootstrap.php
     */
    async function fetchPendingPayments(filters = {}) {
        const tableBody = document.querySelector('#table-binance-pending tbody');
        if (!tableBody) return;

        tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>`;

        try {
            const query = new URLSearchParams(filters).toString();
            // FIX: Ruta corregida de /cash/ a /cash-operations/ según Bootstrap.php
            const response = await fetch(`${state.baseUrl}/financial/cash-operations/binance/getPendingPayments?${query}`);
            
            if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
            
            const result = await response.json();

            if (!result.ok || !result.data || result.data.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-info-circle me-1"></i> No se encontraron pagos de Binance pendientes.</td></tr>`;
                state.currentPayments = [];
                return;
            }

            state.currentPayments = result.data;
            renderTable(result.data);

        } catch (error) {
            console.error("Error cargando Binance:", error);
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger small">Error de conexión: ${error.message}</td></tr>`;
        }
    }

    function renderTable(data) {
        const tableBody = document.querySelector('#table-binance-pending tbody');
        let html = '';

        data.forEach((row, index) => {
            html += `
                <tr data-index="${index}" style="cursor: pointer;" class="align-middle hover-shadow-sm transition-all">
                    <td class="ps-4 fw-bold text-dark">${row.fecha_pago}</td>
                    <td class="text-secondary fw-medium">${row.estudiante}</td>
                    <td class="text-dark fw-bold">${row.titular}</td>
                    <td class="text-muted small">${row.correo}</td>
                    <td class="font-monospace text-primary fw-bold">${row.referencia}</td>
                    <td class="text-end fw-bold">
                        <span class="badge bg-light text-dark border px-2 py-1 fs-6">$ ${parseFloat(row.monto_usd).toFixed(2)}</span>
                    </td>
                    <td class="text-center pe-3">
                        <button class="btn btn-sm btn-outline-primary rounded-circle" onclick="event.stopPropagation(); window.openBinanceModal(${index})">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>`;
        });
        tableBody.innerHTML = html;
    }

    /**
     * 4. GESTIÓN DEL MODAL Y VISOR
     */
    window.openBinanceModal = function(index) {
        const data = state.currentPayments[index];
        if (!data) return;

        state.activePaymentIndex = index;

        document.getElementById('v-estudiante').innerText = data.estudiante;
        document.getElementById('v-titular').innerText = data.titular;
        document.getElementById('v-correo').innerText = data.correo;
        document.getElementById('v-fecha').innerText = data.fecha_pago;
        document.getElementById('v-referencia-display').innerText = data.referencia;
        document.getElementById('v-monto').innerText = `$ ${parseFloat(data.monto_usd).toFixed(2)}`;
        
        const inputVerify = document.getElementById('input-verify-reference');
        if(inputVerify) {
            inputVerify.value = '';
            document.getElementById('btn-confirm-validation').disabled = true;
            inputVerify.classList.remove('is-valid', 'is-invalid');
        }

        const img = document.getElementById('v-screenshot');
        img.src = `${state.baseUrl}/${data.screenshot_path}?t=${new Date().getTime()}`;
        img.style.transform = 'scale(1)';
        state.currentZoom = 1;

        const modalElement = document.getElementById('modalValidateBinance');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    };

    /**
     * 5. PROTOCOLO DE SEGURIDAD (Cross-Check)
     */
    function initSecurityProtocol() {
        const inputVerify = document.getElementById('input-verify-reference');
        const btnConfirm = document.getElementById('btn-confirm-validation');

        if (inputVerify && btnConfirm) {
            inputVerify.addEventListener('input', (e) => {
                const referenceReported = document.getElementById('v-referencia-display').innerText.trim().toUpperCase();
                const referenceTyped = e.target.value.trim().toUpperCase();

                if (referenceTyped === referenceReported) {
                    inputVerify.classList.add('is-valid');
                    inputVerify.classList.remove('is-invalid');
                    btnConfirm.disabled = false;
                } else {
                    inputVerify.classList.remove('is-valid');
                    btnConfirm.disabled = true;
                }
            });
        }
    }

    /**
     * 6. ACCIONES DE APROBACIÓN / RECHAZO
     * CORRECCIÓN: Endpoints sincronizados con Bootstrap.php
     */
    function initValidationActions() {
        const btnConfirm = document.getElementById('btn-confirm-validation');
        const btnReject = document.getElementById('btn-reject-validation');

        if (btnConfirm) {
            btnConfirm.onclick = async () => {
                const data = state.currentPayments[state.activePaymentIndex];
                const confirm = await Swal.fire({
                    title: '¿Confirmar Aprobación?',
                    text: `Se registrará el ingreso de $${data.monto_usd} para ${data.estudiante}.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Aprobar Pago',
                    cancelButtonText: 'Cancelar'
                });

                if (confirm.isConfirmed) executeAction('validatePayment', data.id);
            };
        }

        if (btnReject) {
            btnReject.onclick = async () => {
                const data = state.currentPayments[state.activePaymentIndex];
                const confirm = await Swal.fire({
                    title: '¿Rechazar Pago Binance?',
                    text: "El estudiante deberá reportar el pago nuevamente.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, Rechazar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545'
                });

                if (confirm.isConfirmed) executeAction('rejectPayment', data.id);
            };
        }
    }

    async function executeAction(endpoint, paymentId) {
        try {
            const formData = new FormData();
            formData.append('payment_id', paymentId);

            // FIX: Ruta corregida a /cash-operations/ según Bootstrap.php
            const response = await fetch(`${state.baseUrl}/financial/cash-operations/binance/${endpoint}`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.ok) {
                await Swal.fire({ icon: 'success', title: '¡Éxito!', text: result.message, timer: 2000, showConfirmButton: false });
                const modalEl = document.getElementById('modalValidateBinance');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                fetchPendingPayments();
            } else {
                throw new Error(result.message);
            }
        } catch (e) {
            Swal.fire('Error', e.message, 'error');
        }
    }

    /**
     * 7. CONTROLES DEL VISOR
     */
    function initViewerControls() {
        const btnIn = document.getElementById('btn-zoom-in');
        const btnOut = document.getElementById('btn-zoom-out');
        const btnReset = document.getElementById('btn-reset-zoom');
        const img = document.getElementById('v-screenshot');

        if (btnIn) btnIn.onclick = () => { state.currentZoom += 0.25; updateZoom(); };
        if (btnOut) btnOut.onclick = () => { if(state.currentZoom > 0.5) state.currentZoom -= 0.25; updateZoom(); };
        if (btnReset) btnReset.onclick = () => resetZoom();

        function updateZoom() {
            if (img) img.style.transform = `scale(${state.currentZoom})`;
        }
    }

    function resetZoom() {
        state.currentZoom = 1;
        const img = document.getElementById('v-screenshot');
        if (img) img.style.transform = `scale(1)`;
    }

    function initTableEvents() {
        const tableBody = document.querySelector('#table-binance-pending tbody');
        if (!tableBody) return;
        
        tableBody.addEventListener('click', (e) => {
            const row = e.target.closest('tr');
            if (row && !row.classList.contains('placeholder-row')) {
                if (e.target.closest('button')) return; 
                const index = row.getAttribute('data-index');
                if (index !== null) window.openBinanceModal(parseInt(index));
            }
        });
    }

})();