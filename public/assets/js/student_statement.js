/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL
 * ARCHIVO: public/assets/js/student_statement.js
 * PROPÓSITO: Gestión dinámica de selección de programa, carga de movimientos y exportación PDF.
 * VERSIÓN: 2.4.0 - Integración Premium: Mapeo de nuevos IDs y captura de validación de expediente.
 */

(function() {
    "use strict";

    const state = {
        baseUrl: window.BASE_URL || '/diplomatic/public',
        selectedOfferingId: null
    };

    // --- Referencias al DOM ---
    const selectorArea = document.getElementById('program-selector-area');
    const resultArea = document.getElementById('statement-result');
    const btnShowSelector = document.getElementById('btn-show-selector');
    
    const btnViewPayments = document.getElementById('btn-view-payments');
    const btnPdfPayments = document.getElementById('btn-pdf-payments'); // Modal (Historial Global)
    const btnMainExportPdf = document.getElementById('btn-export-pdf'); // Inferior (Por programa)

    /**
     * FUNCIÓN DE BLINDAJE PARA ALERTAS (Popup)
     */
    const showAlert = (title, text, icon) => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                confirmButtonColor: '#6610f2'
            });
        } else {
            alert(`${title.toUpperCase()}: ${text}`);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        
        // 1. Inicializar Selector de Programas
        initProgramSelector();

        // 2. Lógica del Botón "Cambiar de Programa"
        if (btnShowSelector) {
            btnShowSelector.addEventListener('click', () => {
                // Alternar visibilidad para limpiar la pantalla
                resultArea.classList.add('d-none');
                btnShowSelector.classList.add('d-none');
                selectorArea.classList.remove('d-none');
                state.selectedOfferingId = null;
            });
        }

        // 3. Ver Historial de Pagos Global (Modal)
        if (btnViewPayments) {
                btnViewPayments.addEventListener('click', () => {
                    // Exponer baseUrl para el visor de comprobantes
                    window._voucherBaseUrl = state.baseUrl;
                    window._voucherModulo  = 'students';
                    fetchMyPaymentHistory();
    });
}

        // 4. Exportar PDF: Historial Global (Desde el Modal)
        if (btnPdfPayments) {
            btnPdfPayments.addEventListener('click', () => {
                window.open(`${state.baseUrl}/students/student_statement/exportMyPaymentPdf`, '_blank');
            });
        }

        // 5. Exportar PDF: Estado de Cuenta Específico (Botón Principal)
        if (btnMainExportPdf) {
            btnMainExportPdf.addEventListener('click', () => {
                if (!state.selectedOfferingId) return;
                window.open(`${state.baseUrl}/students/student_statement/exportMyStatementPdf?offering_id=${state.selectedOfferingId}`, '_blank');
            });
        }
    });

    /**
     * Gestiona el clic en las tarjetas de diplomados para cargar datos y ocultar el selector
     */
    function initProgramSelector() {
        const cards = document.querySelectorAll('.program-card');
        cards.forEach(card => {
            card.addEventListener('click', function() {
                const offeringId = this.getAttribute('data-offering-id');
                state.selectedOfferingId = offeringId;

                // Transición de interfaz: Ocultar selector y mostrar resultados
                selectorArea.classList.add('d-none');
                resultArea.classList.remove('d-none');
                if (btnShowSelector) btnShowSelector.classList.remove('d-none');

                fetchMyStatement(offeringId);
            });
        });
    }

    /**
     * Obtener datos financieros filtrados por el programa elegido
     */
    async function fetchMyStatement(offeringId) {
        const tbody = document.querySelector('#table-ledger tbody');
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Sincronizando movimientos...</td></tr>`;
        
        try {
            const response = await fetch(`${state.baseUrl}/students/student_statement/getMyStatement?offering_id=${offeringId}`);
            const result = await response.json();

            if (result.ok && result.data) {
                renderMyStatement(result.data);
            } else {
                // CAPTURA DE VALIDACIÓN: Si no tiene expediente, muestra el Popup y regresa a las tarjetas
                showAlert('Atención', result.message || 'Error al cargar datos.', 'warning');
                
                resultArea.classList.add('d-none');
                if (btnShowSelector) btnShowSelector.classList.add('d-none');
                selectorArea.classList.remove('d-none');
                state.selectedOfferingId = null;
            }
        } catch (error) {
            console.error("Error JS:", error);
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Error de comunicación con el servidor.</td></tr>`;
        }
    }

/**
     * Mapear datos a la UI (Expediente, Totales y Ledger)
     * Versión 2.5.0 - Símbolo dinámico y limpieza de Referencia
     */
    function renderMyStatement(data) {
        const { student, ledger } = data;
        
        // Símbolo dinámico según el programa
        const simbol = (student.moneda === 'VES' || student.moneda === 'Bs') ? 'Bs.' : '$';

        document.getElementById('info-student-name').innerText = `${student.first_name} ${student.last_name}`;
        document.getElementById('info-student-id').innerHTML = `C.I: ${student.document_id} | Código: <span id="info-codigo-student">#${student.student_id}</span>`;
        document.getElementById('info-current-diplomado').innerText = student.diplomado;
        document.getElementById('info-last-payment').innerText = student.last_payment_date || 'Sin registros';

        document.getElementById('total-amount-due').innerText = `${simbol} ${parseFloat(student.total_due).toFixed(2)}`;
        document.getElementById('total-amount-paid').innerText = `${simbol} ${parseFloat(student.total_paid).toFixed(2)}`;
        
        const balance = parseFloat(student.balance);
        const balanceEl = document.getElementById('total-balance');
        if(balanceEl) {
            balanceEl.innerText = `${simbol} ${Math.abs(balance).toFixed(2)}`;
            balanceEl.className = balance < 0 ? 'fw-bold text-success mb-0' : 'fw-bold text-danger mb-0';
            if(balance < 0) balanceEl.innerText += " (A Favor)";
        }

        const tbody = document.querySelector('#table-ledger tbody');
        let rb = 0;

        if (!ledger || ledger.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Sin movimientos registrados.</td></tr>`;
        } else {
            tbody.innerHTML = ledger.map(row => {
                const cargo = parseFloat(row.amount_due) || 0;
                const abono = parseFloat(row.amount_paid) || 0;
                rb += (cargo - abono);
                
                // Limpieza de referencia
                const hasRef = row.reference_id && row.reference_id !== 'N/A' && row.reference_id !== '0';
                const refHTML = hasRef ? `<div class="text-muted" style="font-size:0.7rem;">Ref: ${row.reference_id}</div>` : '';

                return `
                    <tr>
                        <td class="ps-4 text-muted small">${row.formatted_date}</td>
                        <td><div class="fw-bold text-dark">${row.concept}</div>${refHTML}</td>
                        <td class="text-end font-monospace-money text-danger">${cargo > 0 ? simbol + ' ' + cargo.toFixed(2) : '-'}</td>
                        <td class="text-end font-monospace-money text-success">${abono > 0 ? simbol + ' ' + abono.toFixed(2) : '-'}</td>
                        <td class="text-end font-monospace-money fw-bold">${simbol} ${rb.toFixed(2)}</td>
                        <td class="text-center pe-4">
                            <span class="badge rounded-pill bg-soft-primary text-primary border small">${row.status}</span>
                        </td>
                    </tr>
                `;
            }).join('');
        }
    }

/**
     * Cargar historial global de pagos para el Modal (Doble Moneda)
     * Versión Corregida: Sincronizada con el Modelo y la Vista
     */
    async function fetchMyPaymentHistory() {
        const modalElement = document.getElementById('modalPayments');
        const tbody = document.querySelector('#table-history-payments tbody');
        const totalBsEl = document.getElementById('total-bs-modal');
        const totalUsdEl = document.getElementById('total-usd-modal');
        const emptyState = document.getElementById('payments-empty');
        
        // Estado de carga
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span> Sincronizando recibos...</td></tr>';
        
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        try {
            const response = await fetch(`${state.baseUrl}/students/student_statement/getMyPaymentHistory`);
            const result = await response.json();

            if (result.ok && result.data.length > 0) {
                let tBs = 0;
                let tUsd = 0;
                if(emptyState) emptyState.classList.add('d-none');

                tbody.innerHTML = result.data.map(p => {
                    // Extraemos los valores del nuevo Modelo blindado
                    const mBs = parseFloat(p.monto_real_bs) || 0;
                    const mUsd = parseFloat(p.monto_usd) || 0;
                    const t = parseFloat(p.tasa) || 1;

                    tBs += mBs;
                    tUsd += mUsd;

const tipoVoucher = p.causa === 'Inscripción' ? 'inscripcion' : 'cuota';
const tieneRef = p.referencia && p.referencia !== '---';

            return `
                <tr>
                    <td class="ps-4 small text-muted">${p.formatted_date}</td>
                    <td>
                        <div class="fw-bold text-dark" style="font-size:0.85rem;">${p.concepto}</div>
                        <small class="text-muted">Tasa Ref: ${t.toFixed(2)} Bs.</small>
                    </td>
                    <td class="text-end text-primary fw-bold">
                        Bs. ${mBs.toLocaleString('es-VE', {minimumFractionDigits: 2})}
                    </td>
                    <td class="text-end text-success fw-bold">
                        $ ${mUsd.toFixed(2)}
                    </td>
                    <td class="text-center font-monospace small">${p.referencia || '---'}</td>
                    <td class="text-center pe-3">
                        ${tieneRef
                            ? `<button class="btn btn-sm btn-outline-primary rounded-circle btn-view-voucher"
                                    title="Ver comprobante"
                                    data-tipo="${tipoVoucher}"
                                    data-ref="${p.referencia}"
                                    style="width:30px;height:30px;padding:0;">
                                    <i class="bi bi-eye"></i>
                                </button>`
                            : `<span class="text-muted">—</span>`
                        }
                    </td>
                </tr>
            `;


                }).join('');

                // Inyectar totales en el pie del modal
                if (totalBsEl) totalBsEl.innerText = `Bs. ${tBs.toLocaleString('es-VE', {minimumFractionDigits: 2})}`;
                if (totalUsdEl) totalUsdEl.innerText = `$ ${tUsd.toFixed(2)}`;

            } else {
                tbody.innerHTML = '';
                if(emptyState) emptyState.classList.remove('d-none');
                if (totalBsEl) totalBsEl.innerText = "Bs. 0,00";
                if (totalUsdEl) totalUsdEl.innerText = "$ 0.00";
            }
        } catch (error) {
            console.error("Error modal pagos:", error);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Error al cargar el historial.</td></tr>';
        }
    }


})();