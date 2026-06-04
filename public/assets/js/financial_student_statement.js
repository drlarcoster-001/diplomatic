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
                    hideSearchGrid();
                    return;
                }

                clearTimeout(state.searchTimeout);
                state.searchTimeout = setTimeout(() => {
                    fetchStudentSuggestions(term);
                }, 350);
            }); 
        }

        // 2. Evento del botón Limpiar (X)
        if (btnClear) {
            btnClear.addEventListener('click', () => {
                searchInput.value = '';
                hideSearchGrid();
                resetWorkflow(true)
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
            const section = document.getElementById('search-results-section');
            const searchGroup = document.querySelector('.search-group-modern');
            if (section && searchGroup && !searchGroup.contains(e.target) && !section.contains(e.target)) {
                hideSearchGrid();
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
        const section = document.getElementById('search-results-section');
        const tbody   = document.getElementById('search-results-tbody');
        const counter = document.getElementById('search-results-count');
        if (!tbody) return;

        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4">
            <span class="spinner-border spinner-border-sm me-2 text-primary"></span>
            <span class="text-muted">Buscando...</span>
        </td></tr>`;
        if (section) section.classList.remove('d-none');

        try {
            const response = await fetch(`${state.baseUrl}/financial/student_statement/searchStudents?term=${encodeURIComponent(term)}`);
            const result = await response.json();

            if (result.ok && result.data.length > 0) {
                renderSearchGrid(result.data);
            } else {
                if (counter) counter.textContent = '0';
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">
                    <i class="bi bi-search me-2"></i>No se encontraron coincidencias.
                </td></tr>`;
            }
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">
                <i class="bi bi-wifi-off me-2"></i>Error de conexión.
            </td></tr>`;
        }
    }

    function renderSearchGrid(students) {
        const tbody   = document.getElementById('search-results-tbody');
        const counter = document.getElementById('search-results-count');
        if (!tbody) return;

        if (counter) counter.textContent = students.length;

tbody.innerHTML = students.map((s, i) => `
            <tr style="cursor:pointer;" class="student-result-row" data-id="${s.user_id}" data-name="${s.first_name} ${s.last_name}">
                <td class="ps-4 text-muted">${i + 1}</td>
                <td>
                    <div class="fw-bold text-dark">${s.first_name} ${s.last_name}</div>
                    <small class="text-muted">${s.email || ''}</small>
                </td>
                <td class="font-monospace">${s.document_id}</td>
                <td><span class="badge bg-light text-primary border rounded-pill">${s.diplomado || 'Sin programa'}</span></td>
                <td class="text-center pe-4">
                    <button class="btn btn-sm btn-primary rounded-pill px-3 btn-select-student"
                            data-id="${s.user_id}"
                            data-name="${s.first_name} ${s.last_name}">
                        <i class="bi bi-folder2-open me-1"></i>Ver Cuenta
                    </button>
                </td>
            </tr>
        `).join('');

tbody.querySelectorAll('.btn-select-student').forEach(btn => {
            btn.addEventListener('click', function() {
                selectStudent(this.dataset.id, this.dataset.name);
                document.getElementById('search-results-section')?.classList.add('d-none');
                if (searchInput) searchInput.value = this.dataset.name;
            });
        });

        tbody.querySelectorAll('.student-result-row').forEach(row => {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.btn-select-student')) return;
                selectStudent(this.dataset.id, this.dataset.name);
                document.getElementById('search-results-section')?.classList.add('d-none');
                if (searchInput) searchInput.value = this.dataset.name;
            });
        });
    }

    function hideSearchGrid() {
        document.getElementById('search-results-section')?.classList.add('d-none');
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
            hideSearchGrid();
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
                    <tr data-bs="${montoBs}" data-usd="${montoUsd}">
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
                        <td class="text-center pe-3">
                            ${p.causa === 'Mensualidad'
                                ? `<button class="btn btn-sm btn-outline-danger rounded-circle btn-delete-payment"
                                        title="Eliminar pago"
                                        data-payment-id="${p.payment_id}"
                                        data-ref="${p.referencia || '---'}"
                                        data-usd="${montoUsd}"
                                        style="width:30px;height:30px;padding:0;">
                                        <i class="bi bi-trash3"></i>
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

// ── BOTÓN ELIMINAR PAGO ──────────────────────────────────────────────────
    document.getElementById('table-history-payments')?.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-delete-payment');
        if (!btn) return;

        const paymentId = btn.dataset.paymentId;
        const ref       = btn.dataset.ref;
        const amountUsd = btn.dataset.usd;
        const row       = btn.closest('tr');

        Swal.fire({
            title: '¿Eliminar este pago?',
            html: `
                <div class="text-start p-2">
                    <p class="mb-1"><strong>Referencia:</strong> <span class="font-monospace">${ref}</span></p>
                    <p class="mb-1"><strong>Monto:</strong> <span class="text-success fw-bold">$ ${amountUsd}</span></p>
                    <hr>
                    <p class="text-danger small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Esta acción genera una reversión contable en el estado de cuenta y no puede deshacerse.
                    </p>
                </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-trash3 me-1"></i>Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            // Cerrar el modal antes de proceder
            const modalEl = document.getElementById('modalPayments');
            if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();

            try {
                const response = await fetch(`${state.baseUrl}/financial/student_statement/deletePayment`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        payment_id:    paymentId,
                        student_id:    state.selectedStudentId,
                        enrollment_id: state.selectedEnrollmentId
                    })
                });
                const data = await response.json();

                if (data.ok) {
                    row.style.transition = 'opacity 0.4s';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        recalcModalTotals();
                    }, 400);

                    // Refresca el ledger principal

                    Swal.fire({
                        title: '¡Pago eliminado!',
                        text: 'El ledger ha sido actualizado.',
                        icon: 'success',
                        timer: 2500,
                        showConfirmButton: false
                    }).then(() => {
                        loadStatement(state.selectedEnrollmentId,
                            document.getElementById('info-current-diplomado')?.innerText || '');
                    });
                } else {
                    Swal.fire('Error', data.message || 'No se pudo anular el pago.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Fallo la conexión con el servidor.', 'error');
            }
        });
    });

    function recalcModalTotals() {
        let totalBs  = 0;
        let totalUsd = 0;

        document.querySelectorAll('#table-history-payments tbody tr').forEach(tr => {
            totalBs  += parseFloat(tr.dataset.bs  || 0);
            totalUsd += parseFloat(tr.dataset.usd || 0);
        });

        const totalBsEl  = document.getElementById('total-bs-modal');
        const totalUsdEl = document.getElementById('total-usd-modal');
        if (totalBsEl)  totalBsEl.innerText  = `Bs. ${totalBs.toLocaleString('es-VE', {minimumFractionDigits: 2})}`;
        if (totalUsdEl) totalUsdEl.innerText = `$ ${totalUsd.toFixed(2)}`;
    }

    // ── BOTÓN RECALCULAR LEDGER ──────────────────────────────────────────────
    document.getElementById('btn-recalculate-ledger')?.addEventListener('click', async function() {
        const btn = this;
        const modalEl = document.getElementById('modalPayments');
        if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
        await new Promise(resolve => setTimeout(resolve, 400));

        if (!state.selectedEnrollmentId) {
            Swal.fire('Atención', 'No hay un estado de cuenta activo.', 'info');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Recalculando...';

        try {
            const response = await fetch(`${state.baseUrl}/financial/student_statement/recalculateLedger`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_id:    state.selectedStudentId,
                    enrollment_id: state.selectedEnrollmentId
                })
            });
            const data = await response.json();

            if (data.ok) {
                // Refresca modal y ledger
                fetchPaymentHistory(state.selectedEnrollmentId);
                loadStatement(state.selectedEnrollmentId,
                    document.getElementById('info-current-diplomado')?.innerText || '');

                Swal.fire({
                    title: '¡Ledger actualizado!',
                    text: 'Los saldos han sido recalculados correctamente.',
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Error', data.message || 'No se pudo recalcular.', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Fallo la conexión.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Recalcular Ledger';
        }
    });


})();