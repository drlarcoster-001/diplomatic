/**
 * MÓDULO: GESTIÓN FINANCIERA / ZELLE
 * ARCHIVO: public/assets/js/financial_cash_operations_zelle.js
 * PROPÓSITO: Lógica de validación, zoom de comprobantes, filas clickables y rechazo rápido.
 * VERSIÓN: 1.2.2 - FIX: Sincronización de rutas con Bootstrap.php (/financial/cash-operations/).
 */

(function() {
    "use strict";

    const state = {
        baseUrl: window.location.origin + (window.location.pathname.startsWith('/diplomatic/public') ? '/diplomatic/public' : ''),
        currentPayments: [],
        activeIndex: null,
        zoom: 1
    };

    document.addEventListener('DOMContentLoaded', () => {
        initFilters();
        initZoomControls();
        initVerifyInput();
        initRejectAction(); 
        fetchPendingPayments();
    });

    /**
     * CARGA DE DATOS: Filas clickables y visualización
     */
    async function fetchPendingPayments(filters = {}) {
        const tableBody = document.querySelector('#table-zelle-pending tbody');
        if (!tableBody) return;
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;

        try {
            const query = new URLSearchParams(filters).toString();
            // FIX: Ruta sincronizada con Bootstrap.php
            const response = await fetch(`${state.baseUrl}/financial/cash-operations/zelle/getPendingPayments?${query}`);
            
            if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
            
            const result = await response.json();

            if (!result.ok || !result.data || result.data.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted">No hay pagos Zelle pendientes.</td></tr>`;
                return;
            }

            state.currentPayments = result.data;
            let html = '';
            result.data.forEach((row, index) => {
                html += `
                    <tr class="align-middle" onclick="window.openZelleModal(${index})" style="cursor: pointer;">
                        <td class="ps-4 text-muted small">${row.fecha_pago || 'N/A'}</td>
                        <td class="fw-bold text-dark">${row.estudiante}</td>
                        <td class="small text-muted">${row.titular}</td>
                        <td class="small text-muted">${row.correo}</td>
                        <td class="font-monospace text-primary fw-bold">${row.referencia}</td>
                        <td class="text-end fw-bold pe-4">$ ${parseFloat(row.monto_usd).toFixed(2)}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                <i class="bi bi-search"></i> Auditar
                            </button>
                        </td>
                    </tr>`;
            });
            tableBody.innerHTML = html;
        } catch (e) { 
            console.error("Error en Grid Zelle:", e);
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Error de conexión o formato de respuesta.</td></tr>`;
        }
    }

    /**
     * APERTURA DEL MODAL Y RESET DE SEGURIDAD
     */
    window.openZelleModal = function(index) {
        const data = state.currentPayments[index];
        state.activeIndex = index;

        document.getElementById('v-estudiante').innerText = data.estudiante;
        document.getElementById('v-titular').innerText = data.titular;
        document.getElementById('v-correo').innerText = data.correo;
        document.getElementById('v-fecha').innerText = data.fecha_pago || 'No reportada';
        document.getElementById('v-referencia-display').innerText = data.referencia;
        document.getElementById('v-monto').innerText = `$ ${parseFloat(data.monto_usd).toFixed(2)}`;
        
        const img = document.getElementById('v-screenshot');
        img.src = `${state.baseUrl}/${data.screenshot_path}?t=${new Date().getTime()}`;
        img.style.transform = 'scale(1)';
        state.zoom = 1;

        const inputVerify = document.getElementById('input-verify-reference');
        inputVerify.value = '';
        inputVerify.classList.remove('is-valid', 'is-invalid');
        document.getElementById('btn-confirm-validation').disabled = true;

        new bootstrap.Modal(document.getElementById('modalValidateZelle')).show();
    };

    /**
     * PROTOCOLO DE SEGURIDAD: Match de referencia visual
     */
    function initVerifyInput() {
        const input = document.getElementById('input-verify-reference');
        const btnConfirm = document.getElementById('btn-confirm-validation');

        if (!input || !btnConfirm) return;

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

        btnConfirm.onclick = async function() {
            const data = state.currentPayments[state.activeIndex];
            try {
                const formData = new FormData();
                formData.append('payment_id', data.id);
                formData.append('reference', data.referencia);

                // FIX: Ruta sincronizada con Bootstrap.php
                const response = await fetch(`${state.baseUrl}/financial/cash-operations/zelle/validatePayment`, {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();

                if (res.ok) {
                    Swal.fire('¡Aprobado!', res.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalValidateZelle')).hide();
                    fetchPendingPayments();
                } else { throw new Error(res.message); }
            } catch (e) { Swal.fire('Error', e.message, 'error'); }
        };
    }

    /**
     * ACCIÓN: RECHAZAR PAGO (Rápido)
     */
    function initRejectAction() {
        const btnReject = document.getElementById('btn-reject-validation');
        if (!btnReject) return;

        btnReject.onclick = async function() {
            const data = state.currentPayments[state.activeIndex];
            
            const { isConfirmed } = await Swal.fire({
                title: '¿Rechazar este Zelle?',
                text: `¿Estás seguro que deseas rechazar el pago de ${data.estudiante}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, rechazar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            });

            if (isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('payment_id', data.id);
                    formData.append('reason', 'Rechazo administrativo rápido');

                    // FIX: Ruta sincronizada con Bootstrap.php
                    const response = await fetch(`${state.baseUrl}/financial/cash-operations/zelle/rejectPayment`, {
                        method: 'POST',
                        body: formData
                    });
                    const res = await response.json();

                    if (res.ok) {
                        Swal.fire('Rechazado', res.message, 'info');
                        bootstrap.Modal.getInstance(document.getElementById('modalValidateZelle')).hide();
                        fetchPendingPayments();
                    } else { throw new Error(res.message); }
                } catch (e) { Swal.fire('Error', e.message, 'error'); }
            }
        };
    }

    /**
     * CONTROLES DE ZOOM
     */
    function initZoomControls() {
        const img = document.getElementById('v-screenshot');
        if (!img) return;
        
        const btnIn = document.getElementById('btn-zoom-in');
        const btnOut = document.getElementById('btn-zoom-out');
        const btnReset = document.getElementById('btn-reset-zoom');

        if (btnIn) btnIn.onclick = () => { state.zoom += 0.2; img.style.transform = `scale(${state.zoom})`; };
        if (btnOut) btnOut.onclick = () => { if(state.zoom > 0.5) state.zoom -= 0.2; img.style.transform = `scale(${state.zoom})`; };
        if (btnReset) btnReset.onclick = () => { state.zoom = 1; img.style.transform = 'scale(1)'; };
    }

    function initFilters() {
        const form = document.getElementById('filter-form-zelle');
        if(form) {
            form.onsubmit = (e) => {
                e.preventDefault();
                fetchPendingPayments({
                    text: document.getElementById('search-text').value,
                    date_from: document.getElementById('date-from').value,
                    date_to: document.getElementById('date-to').value
                });
            };
        }
        const btnClear = document.getElementById('btn-clear-filters');
        if (btnClear) {
            btnClear.onclick = () => {
                if(form) form.reset();
                fetchPendingPayments();
            };
        }
    }

})();