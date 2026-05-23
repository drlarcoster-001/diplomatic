/**
 * MÓDULO: GESTIÓN FINANCIERA / ESTADOS DE CUENTA BANCARIOS
 * ARCHIVO: public/assets/js/financial_bank_statements_movimientos.js
 * PROPÓSITO: Lógica de administración y carga de movimientos bancarios Mercantil.
 *            Grid paginado con filtros por fecha, referencia, monto y descripción.
 * VERSIÓN: 1.0.0 - Creación inicial del módulo.
 */

document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    const state = {
        selectedFile: null,
        currentPage: 1,
        filterDate: '',
        filterReference: '',
        filterAmount: '',
        filterText: '',
    };

    // Inicializadores
    initModalExcel();
    initFilters();
    fetchTransactions();

    console.log("Módulo Bank Statements Movimientos v1.0.0: Operativo.");

    // =========================================================
    // FILTROS
    // =========================================================

    function initFilters() {
        const btnSearch = document.getElementById('btn-search');
        const btnClear  = document.getElementById('btn-clear-filters');

        if (btnSearch) {
            btnSearch.addEventListener('click', () => applyFilters());
        }

        if (btnClear) {
            btnClear.addEventListener('click', () => {
                document.getElementById('filter-date').value      = '';
                document.getElementById('filter-reference').value = '';
                document.getElementById('filter-amount').value    = '';
                document.getElementById('filter-text').value      = '';
                state.filterDate      = '';
                state.filterReference = '';
                state.filterAmount    = '';
                state.filterText      = '';
                state.currentPage     = 1;
                fetchTransactions();
            });
        }

        ['filter-date', 'filter-reference', 'filter-amount', 'filter-text'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') applyFilters();
                });
            }
        });
    }

    function applyFilters() {
        state.filterDate      = document.getElementById('filter-date').value.trim();
        state.filterReference = document.getElementById('filter-reference').value.trim();
        state.filterAmount    = document.getElementById('filter-amount').value.trim();
        state.filterText      = document.getElementById('filter-text').value.trim();
        state.currentPage     = 1;
        fetchTransactions();
    }

    // =========================================================
    // FETCH PRINCIPAL
    // =========================================================

    async function fetchTransactions() {
        const tbody = document.querySelector('#table-movimientos tbody');
        if (!tbody) return;

        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5">
                    <div class="spinner-border" role="status" style="color: #0dcaf0;"></div>
                </td>
            </tr>`;

        try {
            const params = new URLSearchParams({
                page:      state.currentPage,
                date:      state.filterDate,
                reference: state.filterReference,
                amount:    state.filterAmount,
                text:      state.filterText,
            });

            const res    = await fetch(`${BASE_URL}/financial/bank_statements/movimientos/getTransactions?${params}`);
            const result = await res.json();

            if (!result.ok || !result.data || result.data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                            No se encontraron registros con estos filtros.
                        </td>
                    </tr>`;
                updateTotalCounter(0);
                renderPagination(0, 1);
                return;
            }

            updateTotalCounter(result.pagination.total_records);

            const offset = (state.currentPage - 1) * 25;
            let html = '';

            result.data.forEach((row, i) => {
                const monto = parseFloat(row.amount || 0).toLocaleString('de-DE', { minimumFractionDigits: 2 });
                html += `
                <tr class="align-middle">
                    <td class="ps-4 text-muted small">#${offset + i + 1}</td>
                    <td><span class="badge bg-opacity-10 fw-bold px-2" style="background-color: rgba(13,202,240,0.15); color: #0dcaf0;">${row.op_type}</span></td>
                    <td class="font-monospace small">${row.op_date}</td>
                    <td class="font-monospace fw-bold small" style="color: #0dcaf0;">${row.reference_id}</td>
                    <td class="small text-muted">${row.description || '-'}</td>
                    <td class="text-end fw-bold">${monto} Bs.</td>
                    <td class="text-end pe-4 small text-muted">${row.created_at}</td>
                </tr>`;
            });

            tbody.innerHTML = html;
            renderPagination(result.pagination.total_records, result.pagination.total_pages);

        } catch (e) {
            console.error("Error fetch Movimientos:", e);
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger">
                        <i class="bi bi-exclamation-triangle me-1"></i> Error de comunicación con el servidor.
                    </td>
                </tr>`;
        }
    }

    // =========================================================
    // COUNTER
    // =========================================================

    function updateTotalCounter(total) {
        const el = document.getElementById('total-registros');
        if (el) el.textContent = total.toLocaleString('de-DE');
    }

    // =========================================================
    // PAGINACIÓN
    // =========================================================

    function renderPagination(totalRecords, totalPages) {
        const container = document.getElementById('pagination-container');
        if (!container) return;

        const totalText = `<span class="text-muted small">Total: <b>${totalRecords.toLocaleString('de-DE')}</b> registros</span>`;

        if (totalPages <= 1) {
            container.innerHTML = totalText;
            return;
        }

        let html = `${totalText}<nav class="ms-auto"><ul class="pagination pagination-sm mb-0">`;

        html += `<li class="page-item ${state.currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${state.currentPage - 1}">Ant</a>
                 </li>`;

        for (let i = 1; i <= totalPages; i++) {
            html += `<li class="page-item ${i === state.currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                     </li>`;
        }

        html += `<li class="page-item ${state.currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${state.currentPage + 1}">Sig</a>
                 </li>`;

        html += `</ul></nav>`;
        container.innerHTML = html;

        container.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const pg = parseInt(e.target.getAttribute('data-page'));
                if (pg && pg !== state.currentPage) {
                    state.currentPage = pg;
                    fetchTransactions();
                }
            });
        });
    }

    // =========================================================
    // MODAL CARGA EXCEL
    // =========================================================

    function initModalExcel() {
        const btnOpen    = document.getElementById('btn-open-upload-modal');
        const dropzone   = document.getElementById('dropzone-movimientos');
        const fileInput  = document.getElementById('excelFile');
        const fileInfo   = document.getElementById('file-info-container');
        const btnProcess = document.getElementById('btn-process-xlsx');
        const btnRemove  = document.getElementById('btn-remove-file');

        if (!btnOpen) return;

        btnOpen.addEventListener('click', () => {
            resetModal();
            const modalEl       = document.getElementById('modalUploadXlsx');
            const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl, { backdrop: 'static' });
            modalInstance.show();
        });

        dropzone.addEventListener('click', (e) => {
            if (e.target !== fileInput) fileInput.click();
        });

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => {
            dropzone.addEventListener(ev, (e) => { e.preventDefault(); e.stopPropagation(); });
        });

        dropzone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length) { fileInput.files = files; handleFile(files[0]); }
        });

        fileInput.addEventListener('change', function () {
            if (this.files.length) handleFile(this.files[0]);
        });

        if (btnRemove) btnRemove.addEventListener('click', () => resetModal());

        function resetModal() {
            state.selectedFile  = null;
            fileInput.value     = '';
            dropzone.classList.remove('d-none');
            fileInfo.classList.add('d-none');
            btnProcess.disabled = true;
        }

        function handleFile(file) {
            const ext = file.name.split('.').pop().toLowerCase();
            if (ext !== 'xlsx') {
                Swal.fire('Error', 'Formato no válido. Solo se admiten archivos Excel (.xlsx)', 'error');
                resetModal();
                return;
            }
            state.selectedFile = file;
            document.getElementById('selected-file-name').textContent = file.name;
            document.getElementById('selected-file-size').textContent = (file.size / 1024).toFixed(2) + ' KB';
            dropzone.classList.add('d-none');
            fileInfo.classList.remove('d-none');
            btnProcess.disabled = false;
        }

        btnProcess.addEventListener('click', async () => {
            if (!state.selectedFile) return;

            const fd = new FormData();
            fd.append('excelFile', state.selectedFile);

            Swal.fire({
                title: 'Procesando archivo...',
                text: 'Sincronizando movimientos bancarios...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const res    = await fetch(`${BASE_URL}/financial/bank_statements/movimientos/uploadFile`, { method: 'POST', body: fd });
                const result = await res.json();

                if (result.ok) {
                    await Swal.fire('¡Éxito!', result.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalUploadXlsx')).hide();
                    fetchTransactions();
                } else {
                    Swal.fire('Atención', result.message, 'warning');
                }
            } catch (e) {
                Swal.fire('Error', 'Fallo de red o servidor.', 'error');
            }
        });
    }

});