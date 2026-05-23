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
        currentId: null
    };

    // Referencias DOM centralizadas
    const DOM = {
        searchInput: document.getElementById('search-annulment'),
        btnReset: document.getElementById('btn-clear-filter'),
        resultsArea: document.getElementById('results-area'),
        emptyState: document.getElementById('empty-state'),
        tableBody: document.getElementById('annulments-table-body'),
        
        // Elementos del Modal
        modalEl: document.getElementById('modalDetail'),
        modalBody: document.getElementById('modal-content-body'),
        btnExecute: document.getElementById('btn-execute-annulment')
    };

    document.addEventListener('DOMContentLoaded', () => {
        // 1. Inicialización del Modal de Bootstrap
        if (DOM.modalEl && typeof bootstrap !== 'undefined') {
            state.modalInstance = new bootstrap.Modal(DOM.modalEl);
        }

        // 2. CARGA INICIAL: Llenar la grid automáticamente al entrar
        app.loadInscriptions('');

        // 3. Evento de búsqueda inteligente (Debounce de 300ms)
        if (DOM.searchInput) {
            DOM.searchInput.addEventListener('input', (e) => {
                clearTimeout(state.debounceTimer);
                const term = e.target.value.trim();
                
                state.debounceTimer = setTimeout(() => {
                    app.loadInscriptions(term);
                }, 300);
            });
        }

        // 4. Botón para limpiar filtros
        if (DOM.btnReset) {
            DOM.btnReset.addEventListener('click', () => {
                DOM.searchInput.value = '';
                app.loadInscriptions('');
            });
        }

        // 5. Botón de ejecución final dentro del Modal
        if (DOM.btnExecute) {
            DOM.btnExecute.addEventListener('click', () => {
                if (state.currentId) app.confirmAction(state.currentId);
            });
        }
    });

    window.app = {
        /**
         * Carga los datos de inscripciones aprobadas mediante Fetch
         */
        loadInscriptions: async (term = '') => {
            try {
                const res = await fetch(`${state.baseUrl}/administrative/annulments/list?term=${encodeURIComponent(term)}`);
                
                if (!res.ok) throw new Error(`Error de servidor: ${res.status}`);

                const data = await res.json();

                if (data && data.length > 0) {
                    DOM.emptyState.classList.add('d-none');
                    DOM.resultsArea.classList.remove('d-none');
                    app.renderTable(data);
                } else {
                    DOM.resultsArea.classList.add('d-none');
                    DOM.emptyState.classList.remove('d-none');
                }
            } catch (err) {
                console.error("Error al cargar inscripciones:", err);
                if (err.message.includes('Unexpected token')) {
                    console.warn("ALERTA TÉCNICA: Verifique rutas en app/core/Bootstrap.php");
                }
            }
        },

        /**
         * Renderiza las filas de la tabla con soporte de clic en fila
         */
        renderTable: (data) => {
            DOM.tableBody.innerHTML = data.map(item => `
                <tr class="animate__animated animate__fadeIn row-clickable" 
                    onclick="app.showDetails(${item.enrollment_id})"
                    style="cursor: pointer;">
                    <td class="ps-4 fw-bold text-dark">${item.document_id}</td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-medium">${item.first_name} ${item.last_name}</span>
                            <!--<small class="text-muted" style="font-size: 0.75rem;">ID Inscripción: #${item.enrollment_id}</small>-->
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-light text-primary border border-primary border-opacity-25 px-2 py-1">
                            <i class="bi bi-bookmark-fill me-1"></i> ${item.diplomado}
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

        /**
         * Obtiene detalles del servidor y abre el modal con disposición vertical de cajas
         */
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

                // ESTRUCTURA CORREGIDA: Bloques apilados verticalmente (uno arriba del otro)
                DOM.modalBody.innerHTML = `
                    <div class="mb-4">
                        <label class="text-muted small fw-bold text-uppercase">Datos del Estudiante</label>
                        <h5 class="fw-bold mb-1">${d.full_name}</h5>
                        <p class="text-secondary small mb-0">Cédula: ${d.document_id} | Correo: ${d.email}</p>
                    </div>
                    
                    <div class="mb-2">
                        <div class="p-3 border rounded-3 bg-light">
                            <label class="text-muted small d-block mb-1">Programa</label>
                            <span class="fw-bold small text-primary d-block">
                                ${d.diplomado}
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <label class="text-muted small d-block mb-1">Estado Académico</label>
                            <span class="badge bg-success bg-opacity-10 text-success px-3 rounded-pill">
                                ${d.academic_status}
                            </span>
                        </div>
                    </div>

                    <div class="alert border-0 bg-danger bg-opacity-10 text-danger rounded-4 p-3 mb-0 text-center">
                        <i class="bi bi-exclamation-triangle-fill fs-4 d-block mb-2"></i>
                        <p class="small mb-0"><b>Atención:</b> Se eliminará la matrícula oficial. La inscripción volverá a <b>${d.payment_method === 'CASH' ? 'COMPROMISO' : 'REVISIÓN'}</b>.</p>
                    </div>
                `;
            } catch (err) {
                DOM.modalBody.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-x-circle-fill me-2"></i> 
                        <b>Error:</b> ${err.message}
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

            if (result.isConfirmed) {
                app.executeAnnulment(id);
            }
        },

        executeAnnulment: async (id) => {
            if (state.isProcessing) return;
            state.isProcessing = true;

            Swal.fire({
                title: 'Procesando Reversión...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const res = await fetch(`${state.baseUrl}/administrative/annulments/process`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ enrollment_id: id })
                });

                const result = await res.json();

                if (result.success) {
                    state.modalInstance.hide();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Logrado!',
                        text: result.message,
                        confirmButtonColor: '#198754'
                    });
                    app.loadInscriptions(DOM.searchInput.value);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Acción Denegada',
                        text: result.message,
                        confirmButtonColor: '#dc3545'
                    });
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Fallo crítico de comunicación.', 'error');
            } finally {
                state.isProcessing = false;
            }
        }
    };
})();