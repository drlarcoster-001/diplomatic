/**
 * MÓDULO: GESTIÓN FINANCIERA / ESTADOS DE CUENTA
 * ARCHIVO: public/assets/js/financial_student_statement.js
 * PROPÓSITO: Navegación jerárquica: Búsqueda predictiva -> Cards de Diplomados -> Estado de Cuenta.
 * VERSIÓN: 3.2.0 - UI/UX Refactor: Lógica de navegación del botón "Volver" y envío seguro de user_id.
 */

(function() {
    "use strict";

    const state = {
        baseUrl: window.BASE_URL || '/diplomatic/public',
        searchTimeout: null,
        selectedStudentId: null,
        selectedEnrollmentId: null
    };

    // --- Referencias al DOM ---
    const searchInput = document.getElementById('search-input');
    const resultsContainer = document.getElementById('autocomplete-results');
    const btnClear = document.getElementById('btn-clear-input');
    
    const enrollmentSection = document.getElementById('enrollments-section');
    const enrollmentCardsList = document.getElementById('enrollment-cards-container');
    const statementResult = document.getElementById('statement-result');
    const emptyState = document.getElementById('empty-state');
    const actionButtons = document.getElementById('action-buttons');

    /**
     * FUNCIÓN DE BLINDAJE PARA ALERTAS
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
        
        // 1. Lógica de Búsqueda Predictiva (Autocomplete)
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const term = e.target.value.trim();
                
                if (btnClear) {
                    btnClear.classList.toggle('d-none', term.length === 0);
                }

                if (term.length < 3) {
                    hideAutocomplete();
                    return;
                }

                clearTimeout(state.searchTimeout);
                state.searchTimeout = setTimeout(() => {
                    fetchStudentSuggestions(term);
                }, 300);
            });
        }

        // 2. Evento del botón Limpiar (X)
        if (btnClear) {
            btnClear.addEventListener('click', () => {
                searchInput.value = '';
                resetWorkflow(true);
            });
        }

        // 3. Eventos de Exportación PDF
        document.getElementById('btn-export-pdf')?.addEventListener('click', () => {
            if (state.selectedEnrollmentId) {
                window.open(`${state.baseUrl}/financial/student_statement/exportStatementPdf?enrollment_id=${state.selectedEnrollmentId}&user_id=${state.selectedStudentId}`, '_blank');
            } else {
                showAlert('Atención', 'Debe seleccionar un diplomado primero.', 'warning');
            }
        });

        document.getElementById('btn-pdf-payments')?.addEventListener('click', () => {
            if (state.selectedEnrollmentId) {
                window.open(`${state.baseUrl}/financial/student_statement/exportPaymentPdf?enrollment_id=${state.selectedEnrollmentId}&user_id=${state.selectedStudentId}`, '_blank');
            }
        });

        // 4. Cerrar autocompletado si se hace clic fuera
        document.addEventListener('click', (e) => {
            if (searchInput && resultsContainer) {
                if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                    hideAutocomplete();
                }
            }
        });

        // 5. NUEVO: Evento del botón Volver a Diplomados
        document.getElementById('btn-volver-diplomados')?.addEventListener('click', () => {
            if (statementResult) statementResult.classList.add('d-none');
            if (actionButtons) actionButtons.classList.add('d-none');
            if (enrollmentSection) enrollmentSection.classList.remove('d-none');
        });
    });

    /**
     * PASO 1: Buscar Estudiantes (Dropdown predictivo)
     */
    async function fetchStudentSuggestions(term) {
        if (!resultsContainer) return;

        resultsContainer.innerHTML = '<div class="list-group-item text-center small py-3"><span class="spinner-border spinner-border-sm me-2"></span> Buscando...</div>';
        resultsContainer.classList.remove('d-none');

        try {
            const response = await fetch(`${state.baseUrl}/financial/student_statement/searchStudents?term=${encodeURIComponent(term)}`);
            const result = await response.json();

            if (result.ok && result.data.length > 0) {
                renderSuggestions(result.data);
            } else {
                resultsContainer.innerHTML = '<div class="list-group-item text-center text-muted small py-3">No se encontraron coincidencias.</div>';
            }
        } catch (error) {
            resultsContainer.innerHTML = '<div class="list-group-item text-center text-danger small py-3">Error de conexión.</div>';
        }
    }

function renderSuggestions(students) {
    resultsContainer.innerHTML = students.map(s => `
        <button type="button" class="list-group-item list-group-item-action py-3 student-suggestion-item" 
                data-id="${s.user_id}" 
                data-enrollment="${s.enrollment_id}" 
                data-name="${s.first_name} ${s.last_name}">
            <div class="d-flex align-items-center">
                <div class="icon-box-diplomado me-3 rounded-circle" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center; background: rgba(102, 16, 242, 0.1); color:#6610f2;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark">${s.first_name} ${s.last_name}</div>
                    <small class="text-muted">ID: ${s.document_id} | ${s.diplomado}</small>
                </div>
            </div>
        </button>
    `).join('');

    document.querySelectorAll('.student-suggestion-item').forEach(btn => {
        btn.addEventListener('click', function() {
            selectStudent(this.dataset.id, this.dataset.name);
        });
    });
}



    /**
     * PASO 2: Cargar Diplomados (Cards)
     */
    async function selectStudent(id, name) {
        state.selectedStudentId = id;
        if (searchInput) searchInput.value = name;
        hideAutocomplete();
        resetWorkflow(false);

        try {
            const response = await fetch(`${state.baseUrl}/financial/payment_registration/getOfferingsByUser?user_id=${id}`);
            const result = await response.json();

            if (result.status === "success" && result.data && result.data.length > 0) {
                renderEnrollmentCards(result.data);
            } else {
                showAlert('Información', 'El estudiante no posee diplomados registrados.', 'info');
                if (emptyState) emptyState.classList.remove('d-none');
            }
        } catch (error) {
            showAlert('Error', 'No se pudieron recuperar los programas académicos.', 'error');
        }
    }

    function renderEnrollmentCards(enrollments) {
        if (!enrollmentSection || !enrollmentCardsList) return;

        enrollmentSection.classList.remove('d-none');
        if (emptyState) emptyState.classList.add('d-none');

        enrollmentCardsList.innerHTML = enrollments.map(e => {
            // FIX CRÍTICO APLICADO AQUÍ: 
            // Se agrega 'e.offering_id' para que la tarjeta no quede con ID 0 cuando viene del módulo de pagos.
            const validEnrollId = e.enrollment_id || e.id || e.offering_id; 

            return `
                <div class="col-md-auto">
                    <div class="card enrollment-card p-3 shadow-sm rounded-4 h-100" 
                         data-enrollment="${validEnrollId}" 
                         data-program="${e.diploma_name}">
                        <div class="d-flex align-items-center mb-2">
                            <div class="icon-box-diplomado me-3">
                                <i class="bi bi-mortarboard-fill fs-4"></i>
                            </div>
                            <div style="max-width: 250px;">
                                <h6 class="mb-0 fw-bold text-truncate">${e.diploma_name}</h6>
                                <small class="text-muted small">Cohorte: ${e.cohort_name}</small>
                            </div>
                        </div>
                        <div class="mt-2 border-top pt-2 d-flex justify-content-between align-items-center">
                            <span class="text-primary small fw-bold">Ver Estado <i class="bi bi-arrow-right"></i></span>
                            <span class="badge bg-light text-danger border small">Pendiente: $ ${e.total_pending || '0.00'}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Re-vincular eventos de clic
        document.querySelectorAll('.enrollment-card').forEach(card => {
            card.addEventListener('click', function() {
                const enrollId = this.getAttribute('data-enrollment');
                const progName = this.getAttribute('data-program');

                if (enrollId && enrollId !== "undefined" && enrollId !== "0") {
                    loadStatement(enrollId, progName);
                    document.querySelectorAll('.enrollment-card').forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                } else {
                    showAlert('Error de Datos', 'No se detectó un ID de inscripción válido en esta tarjeta.', 'error');
                }
            });
        });
    }

    /**
     * PASO 3: Cargar Estado de Cuenta Detallado (Ledger)
     */
async function loadStatement(enrollmentId, programName) {
    state.selectedEnrollmentId = enrollmentId;
    // Exponer IDs para el visor de comprobantes
    window._voucherEnrollId = enrollmentId;
    window._voucherUserId   = state.selectedStudentId;

    try {
        // Enviamos AMBOS IDs para que el controlador verifique que el alumno es el dueño de la deuda
        const response = await fetch(`${state.baseUrl}/financial/student_statement/getStatement?enrollment_id=${enrollmentId}&user_id=${state.selectedStudentId}`);
        const result = await response.json();

        if (result.ok) {
            document.getElementById('info-current-diplomado').innerText = programName;
            renderLedgerData(result.data);

            if (enrollmentSection) enrollmentSection.classList.add('d-none');
            if (statementResult) statementResult.classList.remove('d-none');
            if (actionButtons) actionButtons.classList.remove('d-none');
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            showAlert('Error de Seguridad', result.message, 'error');
            resetWorkflow(true); 
        }
    } catch (error) {
        showAlert('Error', 'No se pudo conectar con el servidor financiero.', 'error');
    }
}


    function renderLedgerData(data) {
        const { student, ledger } = data;
        
        document.getElementById('info-student-name').innerText = `${student.first_name} ${student.last_name}`;
        document.getElementById('info-student-id').innerText = `C.I: ${student.document_id}`;
        document.getElementById('info-last-payment').innerText = student.last_payment_date || 'Sin registros';

        // Símbolo dinámico
        const simbol = (student.moneda === 'VES' || student.moneda === 'Bs') ? 'Bs.' : '$';

        document.getElementById('total-amount-due').innerText = `${simbol} ${parseFloat(student.total_due).toFixed(2)}`;
        document.getElementById('total-amount-paid').innerText = `${simbol} ${parseFloat(student.total_paid).toFixed(2)}`;
        
        const balance = parseFloat(student.balance);
        const balanceEl = document.getElementById('total-balance');
        if (balanceEl) {
            balanceEl.innerText = `${simbol} ${Math.abs(balance).toFixed(2)}`;
            balanceEl.classList.toggle('text-danger', balance > 0);
            balanceEl.classList.toggle('text-success', balance <= 0);
        }

        const tbody = document.querySelector('#table-ledger tbody');
        if (!tbody) return;

        let runningBalance = 0;
        
        if (!ledger || ledger.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Sin movimientos registrados.</td></tr>';
            return;
        }

        tbody.innerHTML = ledger.map(row => {
            const cargo = parseFloat(row.amount_due) || 0;
            const abono = parseFloat(row.amount_paid) || 0;
            runningBalance += (cargo - abono);

            // --- FIX: Limpieza de Referencia ---
            const hasRef = row.reference_id && row.reference_id !== 'N/A' && row.reference_id !== '0';
            const refHTML = hasRef ? `<small class="text-muted">Ref: ${row.reference_id}</small>` : '';

            return `
                <tr>
                    <td class="ps-4 text-muted">${row.formatted_date}</td>
                    <td>
                        <div class="fw-bold">${row.concept}</div>
                        ${refHTML}
                    </td>
                    <td class="text-end text-danger font-monospace-money">${cargo > 0 ? '+' + cargo.toFixed(2) : '-'}</td>
                    <td class="text-end text-success font-monospace-money">${abono > 0 ? '-' + abono.toFixed(2) : '-'}</td>
                    <td class="text-end fw-bold font-monospace-money">${simbol} ${runningBalance.toFixed(2)}</td>
                    <td class="text-center pe-4">
                        <span class="badge rounded-pill bg-soft-${row.status === 'PAGADO' ? 'success' : 'warning'} text-dark small">
                            ${row.status}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
    }




    // Botón Ver Pagos (Modal)
    document.getElementById('btn-view-payments')?.addEventListener('click', () => {
        if (state.selectedEnrollmentId) {
            fetchPaymentHistory(state.selectedEnrollmentId);
        }
    });

    function hideAutocomplete() {
        if(resultsContainer) {
            resultsContainer.classList.add('d-none');
            resultsContainer.innerHTML = '';
        }
    }

    function resetWorkflow(fullReset = true) {
        if(fullReset) {
            state.selectedStudentId = null;
            if (emptyState) emptyState.classList.remove('d-none');
            if (btnClear) btnClear.classList.add('d-none');
        }
        state.selectedEnrollmentId = null;
        if (enrollmentSection) enrollmentSection.classList.add('d-none');
        if (statementResult) statementResult.classList.add('d-none');
        if (actionButtons) actionButtons.classList.add('d-none');
        hideAutocomplete();
    }

    async function fetchPaymentHistory(enrollmentId) {
        const tbody = document.querySelector('#table-history-payments tbody');
        const totalBsEl = document.getElementById('total-bs-modal');
        const totalUsdEl = document.getElementById('total-usd-modal');
        
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Cargando historial...</td></tr>';
        
        const modalElement = document.getElementById('modalPayments');
        if (!modalElement) return;

        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        try {
            const response = await fetch(`${state.baseUrl}/financial/student_statement/getPaymentHistory?enrollment_id=${enrollmentId}&user_id=${state.selectedStudentId}`);
            const result = await response.json();

            if (result.ok && result.data.length > 0) {
                let totalBs = 0;
                let totalUsd = 0;

                tbody.innerHTML = result.data.map(p => {
                    const montoBs = parseFloat(p.monto_real_bs) || 0;
                    const montoUsd = parseFloat(p.monto_usd) || 0;
                    const tasa = parseFloat(p.tasa) || 0;

                    totalBs += montoBs;
                    totalUsd += montoUsd;

const tipoVoucher = p.causa === 'Inscripción' ? 'inscripcion' : 'cuota';
const tieneRef = p.referencia && p.referencia !== '---';

                return `
                    <tr>
                        <td class="ps-4 text-muted small">${p.formatted_date}</td>
                        <td>
                            <div class="fw-bold text-dark" style="font-size:0.85rem;">${p.concept}</div>
                            <small class="text-muted">Tasa Ref: ${tasa.toFixed(2)} Bs.</small>
                        </td>
                        <td class="text-end text-primary fw-bold">
                            Bs. ${montoBs.toLocaleString('es-VE', {minimumFractionDigits: 2})}
                        </td>
                        <td class="text-end text-success fw-bold">
                            $ ${montoUsd.toFixed(2)}
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

                if (totalBsEl) totalBsEl.innerText = `Bs. ${totalBs.toLocaleString('es-VE', {minimumFractionDigits: 2})}`;
                if (totalUsdEl) totalUsdEl.innerText = `$ ${totalUsd.toFixed(2)}`;

            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">No hay pagos registrados.</td></tr>';
                if (totalBsEl) totalBsEl.innerText = "Bs. 0,00";
                if (totalUsdEl) totalUsdEl.innerText = "$ 0.00";
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Error al conectar con el servidor.</td></tr>';
        }
    }

    // Evento: botón ver comprobante
document.getElementById('table-history-payments')?.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-view-voucher');
    if (!btn) return;

    const tipo = btn.dataset.tipo;
    const ref  = btn.dataset.ref;

    console.log('Voucher click:', tipo, ref);
});

})();