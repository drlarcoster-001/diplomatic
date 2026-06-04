/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / EXCEPCIONES
 * ARCHIVO: public/assets/js/administrative_annulments.js
 * PROPÓSITO: Lógica de búsqueda reactiva, visor de detalles en modal y procesamiento de cancelación.
 * VERSIÓN: 1.3.1 - UI Fix: Disposición vertical de bloques de información en modal.
 */

(function() {
    "use strict";

    const state = {
        baseUrl: window.BASE_URL || '/diplomatic/public',
        debounceTimer: null,
        isProcessing: false,
        modalInstance: null,
        currentId: null,
        currentPage: 1
    };

 

    document.addEventListener('DOMContentLoaded', () => {
    window.DOM = {
        filterStudent:   document.getElementById('filter-student'),
        filterDiplomado: document.getElementById('filter-diplomado'),
        filterDateFrom:  document.getElementById('filter-date-from'),
        filterDateTo:    document.getElementById('filter-date-to'),
        btnSearch:       document.getElementById('btn-search'),
        btnReset:        document.getElementById('btn-clear-filter'),
        resultsArea:     document.getElementById('results-area'),
        emptyState:      document.getElementById('empty-state'),
        tableBody:       document.getElementById('annulments-table-body'),
        resultsInfo:     document.getElementById('results-info'),
        paginationTop:   document.getElementById('pagination-top'),
        paginationBot:   document.getElementById('pagination-bottom'),
        modalEl:         document.getElementById('modalDetail'),
        modalBody:       document.getElementById('modal-content-body'),
        btnExecute:      document.getElementById('btn-execute-annulment')
    };


        if (DOM.modalEl && typeof bootstrap !== 'undefined') {
            state.modalInstance = new bootstrap.Modal(DOM.modalEl);
        }

        // Carga inicial
        app.loadDiplomados();
        app.load(1);

        // Buscar al presionar Enter en cualquier filtro
        [DOM.filterStudent, DOM.filterDiplomado, DOM.filterDateFrom, DOM.filterDateTo].forEach(el => {
            if (el) el.addEventListener('keydown', e => {
                if (e.key === 'Enter') app.load(1);
            });
        });

        // Botón buscar
        if (DOM.btnSearch) DOM.btnSearch.addEventListener('click', () => app.load(1));

        // Limpiar filtros
        if (DOM.btnReset) DOM.btnReset.addEventListener('click', () => {
            DOM.filterStudent.value  = '';
            DOM.filterDiplomado.value = '';
            DOM.filterDateFrom.value = '';
            DOM.filterDateTo.value   = '';
            app.load(1);
        });

        // Botón confirmar en modal
        if (DOM.btnExecute) DOM.btnExecute.addEventListener('click', () => {
            if (state.currentId) app.confirmAction(state.currentId);
        });
    });

    window.app = {

        loadDiplomados: async () => {
    try {
        const res  = await fetch(`${state.baseUrl}/administrative/annulments/getDiplomados`);
        const data = await res.json();
        const sel  = document.getElementById('filter-diplomado');
        if (!sel) return;
        data.forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.name;
            opt.textContent = d.name;
            sel.appendChild(opt);
        });
    } catch (err) {
        console.error('Error cargando diplomados:', err);
    }
},

load: async (page = 1) => {
    state.currentPage = page;
    const params = new URLSearchParams({
        student:   document.getElementById('filter-student')?.value.trim()   || '',
        diplomado: document.getElementById('filter-diplomado')?.value.trim() || '',
        date_from: document.getElementById('filter-date-from')?.value        || '',
        date_to:   document.getElementById('filter-date-to')?.value          || '',
        page:      page
    });

            try {
                const res  = await fetch(`${state.baseUrl}/administrative/annulments/list?${params}`);
                if (!res.ok) throw new Error(`Error ${res.status}`);
                const json = await res.json();

                if (json.data && json.data.length > 0) {
                    DOM.emptyState.classList.add('d-none');
                    DOM.resultsArea.classList.remove('d-none');
                    app.renderTable(json.data, page);
                    app.renderPagination(json.page, json.pages, json.total);
                } else {
                    DOM.resultsArea.classList.add('d-none');
                    DOM.emptyState.classList.remove('d-none');
                }
            } catch (err) {
                console.error("Error al cargar:", err);
            }
        },

        renderTable: (data, page) => {
            const offset = (page - 1) * 25;
            DOM.tableBody.innerHTML = data.map((item, i) => `
                <tr class="row-clickable" onclick="app.showDetails(${item.enrollment_id})" style="cursor:pointer;">
                    <td class="ps-4 text-muted small">${offset + i + 1}</td>
                    <td class="fw-bold text-dark">${item.document_id}</td>
                    <td>${item.first_name} ${item.last_name}</td>
                    <td>
                        <span class="badge bg-light text-primary border border-primary border-opacity-25 px-2 py-1">
                            <i class="bi bi-bookmark-fill me-1"></i>${item.diplomado}
                        </span>
                    </td>
                    <td>${new Date(item.created_at).toLocaleDateString()}</td>
                    <td class="text-end pe-4">
                        <button class="btn btn-dark btn-sm rounded-pill px-3 fw-bold"
                                onclick="event.stopPropagation(); app.showDetails(${item.enrollment_id})">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        },

        renderPagination: (page, pages, total) => {
            if (DOM.resultsInfo) {
                DOM.resultsInfo.textContent = `${total} registro${total !== 1 ? 's' : ''} encontrado${total !== 1 ? 's' : ''}`;
            }

            const html = (page, pages) => {
                if (pages <= 1) return '';
                let btns = '';
                btns += `<button class="btn btn-sm btn-light border rounded-pill px-3 me-1" ${page === 1 ? 'disabled' : ''} onclick="app.load(${page - 1})">‹</button>`;
                for (let p = 1; p <= pages; p++) {
                    if (pages > 7 && p > 2 && p < pages - 1 && Math.abs(p - page) > 1) {
                        if (p === 3 || p === pages - 2) btns += `<span class="me-1">…</span>`;
                        continue;
                    }
                    btns += `<button class="btn btn-sm ${p === page ? 'btn-danger' : 'btn-light border'} rounded-pill px-3 me-1" onclick="app.load(${p})">${p}</button>`;
                }
                btns += `<button class="btn btn-sm btn-light border rounded-pill px-3" ${page === pages ? 'disabled' : ''} onclick="app.load(${page + 1})">›</button>`;
                return btns;
            };

            const paginHTML = html(page, pages);
            if (DOM.paginationTop) DOM.paginationTop.innerHTML = paginHTML;
            if (DOM.paginationBot) DOM.paginationBot.innerHTML = paginHTML;
        },

        showDetails: async (id) => {
            state.currentId = id;
            DOM.modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-danger" role="status"></div>
                    <p class="small text-muted mt-2">Consultando expediente...</p>
                </div>`;
            state.modalInstance.show();

            try {
                const res = await fetch(`${state.baseUrl}/administrative/annulments/getDetails?id=${id}`);
                if (!res.ok) throw new Error("No se pudo obtener la información.");
                const d = await res.json();
                if (d.error) throw new Error(d.error);

                DOM.modalBody.innerHTML = `
                    <div class="mb-4">
                        <label class="text-muted small fw-bold text-uppercase">Datos del Estudiante</label>
                        <h5 class="fw-bold mb-1">${d.full_name}</h5>
                        <p class="text-secondary small mb-0">Cédula: ${d.document_id} | Correo: ${d.email}</p>
                    </div>
                    <div class="mb-2">
                        <div class="p-3 border rounded-3 bg-light">
                            <label class="text-muted small d-block mb-1">Programa</label>
                            <span class="fw-bold small text-primary d-block">${d.diplomado}</span>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <label class="text-muted small d-block mb-1">Estado Académico</label>
                            <span class="badge bg-success bg-opacity-10 text-success px-3 rounded-pill">${d.academic_status}</span>
                        </div>
                    </div>
                    <div class="alert border-0 bg-danger bg-opacity-10 text-danger rounded-4 p-3 mb-0 text-center">
                        <i class="bi bi-exclamation-triangle-fill fs-4 d-block mb-2"></i>
                        <p class="small mb-0"><b>Atención:</b> La inscripción volverá a <b>${d.payment_method === 'CASH' ? 'COMPROMISO' : 'REVISIÓN'}</b>.</p>
                    </div>`;
            } catch (err) {
                DOM.modalBody.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-x-circle-fill me-2"></i><b>Error:</b> ${err.message}
                    </div>`;
            }
        },

        confirmAction: async (id) => {
            const result = await Swal.fire({
                title: '¿Confirmar eliminación?',
                text: "Esta acción es irreversible en los registros académicos.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#b02a37',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, anular definitivamente',
                cancelButtonText: 'No, cancelar'
            });
            if (result.isConfirmed) app.executeAnnulment(id);
        },

        executeAnnulment: async (id) => {
            if (state.isProcessing) return;
            state.isProcessing = true;

            Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const res    = await fetch(`${state.baseUrl}/administrative/annulments/process`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ enrollment_id: id })
                });
                const result = await res.json();

                if (result.success) {
                    state.modalInstance.hide();
                    Swal.fire({ icon: 'success', title: '¡Logrado!', text: result.message, confirmButtonColor: '#198754' });
                    app.load(state.currentPage);
                } else {
                    Swal.fire({ icon: 'error', title: 'Acción Denegada', text: result.message, confirmButtonColor: '#dc3545' });
                }
            } catch (err) {
                Swal.fire('Error', 'Fallo crítico de comunicación.', 'error');
            } finally {
                state.isProcessing = false;
            }
        }
    };
})();