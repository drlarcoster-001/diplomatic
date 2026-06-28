/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / CONSTANCIAS
 * ARCHIVO: public/assets/js/administrative_certificates.js
 * PROPÓSITO: Gestión de navegación jerárquica con persistencia oficial y sanitización de strings.
 * VERSIÓN: 4.6.0 - Team Edition: FIX SyntaxError (String Escaping) y sincronización de rutas.
 */

(function() {
    "use strict";

    // Configuración base del sistema para entorno XAMPP o Producción
    const BASE_URL = window.BASE_URL || '/diplomatic/public';

    const state = { 
        searchTimeout: null,
        modalInstance: null,
        selectedUserId: null, 
        selectedOfferingId: null,
        selectedType: null
    };

    // Referencias centralizadas al DOM
    const DOM = {
        searchInput:        document.getElementById('search-input'),
        btnSearch:          document.getElementById('btn-search'),
        btnClear:           document.getElementById('btn-clear-input'),
        searchResultsArea:  document.getElementById('search-results-area'),
        searchTableBody:    document.getElementById('search-table-body'),
        searchInfo:         document.getElementById('search-results-info'),
        paginTop:           document.getElementById('search-pagination-top'),
        paginBot:           document.getElementById('search-pagination-bottom'),
        
        certificatesArea: document.getElementById('certificates-area'),
        emptyState: document.getElementById('empty-state'),
        enrollmentsSection: document.getElementById('enrollments-section'),
        optionsSection: document.getElementById('options-section'),
        programsList: document.getElementById('programs-list'),
        
        selectedProgramLabel: document.getElementById('selected-program-name'),
        btnVolver: document.getElementById('btn-volver-diplomados'),
        
        modalEl: document.getElementById('modalPreview'),
        modalTitle: document.getElementById('modalPreviewTitle'),
        pdfFrame: document.getElementById('pdf-preview-frame'),
        pdfLoader: document.getElementById('pdf-loader'),
        
        btnDownload: document.getElementById('btn-download-pdf'),
        btnSendEmail: document.getElementById('btn-send-email')
    };

    /**
     * INICIALIZACIÓN DE COMPONENTES
     */
    document.addEventListener('DOMContentLoaded', () => {
        
        if (DOM.modalEl) {
            state.modalInstance = new bootstrap.Modal(DOM.modalEl);
            DOM.modalEl.addEventListener('hidden.bs.modal', () => {
                DOM.pdfFrame.src = '';
                DOM.pdfLoader.classList.remove('d-none');
                DOM.pdfFrame.style.opacity = '0';
                app.log("Recursos de vista previa liberados.");
            });
        }

        // 1. Buscar con Enter o botón
        if (DOM.searchInput) {
            DOM.searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') app.fetchStudents(1);
            });
        }
        DOM.btnSearch?.addEventListener('click', () => app.fetchStudents(1));

        // 2. Limpieza
        DOM.btnClear?.addEventListener('click', () => {
            DOM.searchInput.value = '';
            app.resetWorkflow();
        });

        // 3. Navegación entre niveles (Opciones -> Lista Diplomados)
        DOM.btnVolver?.addEventListener('click', () => {
            app.log("Retrocediendo a lista de diplomados", "#6c757d");
            DOM.optionsSection.classList.add('d-none');
            DOM.enrollmentsSection.classList.remove('d-none');
            state.selectedOfferingId = null;
        });

        // 4. Disparadores de Modal
        document.getElementById('btn-generate-inscripcion')?.addEventListener('click', () => app.openPreview('INSCRIPCION'));
        document.getElementById('btn-generate-estudios')?.addEventListener('click', () => app.openPreview('ESTUDIOS'));
        document.getElementById('btn-generate-estudios-horario')?.addEventListener('click', () => app.openPreview('ESTUDIOS_HORARIO'));
        
        // 5. Acciones de persistencia (Descarga / Email)
        DOM.btnDownload?.addEventListener('click', () => app.executeFinalizeDownload());
        DOM.btnSendEmail?.addEventListener('click', () => app.executeSendEmail());


        
    });

    /**
     * OBJETO MAESTRO - MANTENER ACCESIBLE PARA EVENTOS INLINE
     */
    window.app = {
        
        // Logger para coordinación del equipo
        log: (msg, color = "#00e5ff") => console.log(`%c[CERT-SYS] ${msg}`, `color: ${color}; font-weight: bold;`),

        /**
         * FUNCIÓN DE SANITIZACIÓN (CRÍTICA)
         * Evita que nombres con comillas o caracteres especiales rompan el JS inyectado.
         */
        escapeJS: (str) => {
            if (!str) return "";
            return str
                .replace(/'/g, "\\'") // Escapa comillas simples
                .replace(/"/g, "&quot;") // Escapa comillas dobles para HTML
                .replace(/[\r\n]+/g, " "); // Elimina saltos de línea
        },

        /**
         * NIVEL 1: Búsqueda
         */
        fetchStudents: async (page = 1) => {
    const term = document.getElementById('search-input')?.value.trim() || '';
    if (term.length < 2) return;

    try {
        const params = new URLSearchParams({ term, page });
        const res    = await fetch(`${BASE_URL}/administrative/certificates/search?${params}`);
        const result = await res.json();

        if (!result.ok || result.data.length === 0) {
            DOM.searchResultsArea.classList.add('d-none');
            return;
        }

        const offset = (page - 1) * 25;
        DOM.searchTableBody.innerHTML = result.data.map((s, i) => `
            <tr class="row-clickable" onclick="app.selectStudent(${s.user_id}, '${app.escapeJS(s.first_name + ' ' + s.last_name)}')" style="cursor:pointer;">
                <td class="ps-4 text-muted small">${offset + i + 1}</td>
                <td class="fw-bold">${s.document_id}</td>
                <td>${s.first_name} ${s.last_name}</td>
                <td class="text-muted small">${s.email}</td>
            </tr>
        `).join('');

        if (DOM.searchInfo) DOM.searchInfo.textContent = `${result.total} resultado${result.total !== 1 ? 's' : ''}`;

        const paginHTML = app.buildPagination(result.page, result.pages);
        if (DOM.paginTop) DOM.paginTop.innerHTML = paginHTML;
        if (DOM.paginBot) DOM.paginBot.innerHTML = paginHTML;

        DOM.searchResultsArea.classList.remove('d-none');
        DOM.emptyState.classList.add('d-none');

    } catch (err) {
        console.error('Error búsqueda:', err);
    }
},

buildPagination: (page, pages) => {
    if (pages <= 1) return '';
    let btns = '';
    btns += `<button class="btn btn-sm btn-light border rounded-pill px-3 me-1" ${page === 1 ? 'disabled' : ''} onclick="app.fetchStudents(${page - 1})">‹</button>`;
    for (let p = 1; p <= pages; p++) {
        if (pages > 7 && p > 2 && p < pages - 1 && Math.abs(p - page) > 1) {
            if (p === 3 || p === pages - 2) btns += `<span class="me-1">…</span>`;
            continue;
        }
        btns += `<button class="btn btn-sm ${p === page ? 'btn-warning' : 'btn-light border'} rounded-pill px-3 me-1" onclick="app.fetchStudents(${p})">${p}</button>`;
    }
    btns += `<button class="btn btn-sm btn-light border rounded-pill px-3" ${page === pages ? 'disabled' : ''} onclick="app.fetchStudents(${page + 1})">›</button>`;
    return btns;
},

        /**
         * NIVEL 2: Carga de Diplomados
         */
        selectStudent: (id, name) => {
            app.log(`2. Estudiante fijado: ${name} (ID: ${id})`, "#ffeb3b");
            state.selectedUserId = id; 
            DOM.searchInput.value = name;
            DOM.searchResultsArea.classList.add('d-none');
            
            DOM.emptyState.classList.add('d-none');
            DOM.certificatesArea.classList.remove('d-none');
            DOM.optionsSection.classList.add('d-none');
            DOM.enrollmentsSection.classList.remove('d-none');

            app.loadPrograms(id);
        },

        loadPrograms: async (userId) => {
            app.log(`3. Cargando tarjetas para UserID: ${userId}`, "#ffa726");
            DOM.programsList.innerHTML = '<div class="col-12 text-center py-5"><span class="spinner-border text-warning"></span></div>';
            
            try {
                // Sincronizado con Bootstrap.php -> getStudentPrograms
                const url = `${BASE_URL}/administrative/certificates/getStudentPrograms?user_id=${userId}`;
                const res = await fetch(url);
                const result = await res.json();

                app.log(`4. Datos recibidos. Items: ${result.data?.length || 0}`, "#66bb6a");

                if (result.ok && result.data.length > 0) {
                    DOM.programsList.innerHTML = result.data.map(p => {
                        const safeProgramName = app.escapeJS(p.diplomado);
                        return `
                        <div class="col-md-6 mb-3 animate__animated animate__fadeInUp">
                            <div class="card enrollment-card p-3 shadow-sm rounded-4 h-100" style="cursor:pointer;" 
                                 onclick="app.selectProgram(${p.offering_id}, '${safeProgramName}')">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="icon-box-diplomado me-3" style="width:50px; height:50px; background: rgba(102, 16, 242, 0.1); color:#6610f2; border-radius:12px; display:flex; align-items:center; justify-content:center;">
                                        <i class="bi bi-mortarboard-fill fs-4"></i>
                                    </div>
                                    <div style="max-width: 250px;">
                                        <h6 class="mb-0 fw-bold text-truncate">${p.diplomado}</h6>
                                        <small class="text-muted small">Cohorte: ${p.cohorte}</small>
                                    </div>
                                </div>
                                <div class="mt-2 border-top pt-2 d-flex justify-content-between align-items-center">
                                    <span class="text-primary small fw-bold">Gestionar <i class="bi bi-arrow-right"></i></span>
                                    <span class="badge bg-light text-success border-0 small px-2">Activo</span>
                                </div>
                            </div>
                        </div>`;
                    }).join('');
                } else {
                    DOM.programsList.innerHTML = '<div class="col-12 text-center py-5 text-muted">No se hallaron diplomados.</div>';
                }
            } catch (err) { 
                console.error("Error en renderizado:", err);
                DOM.programsList.innerHTML = '<div class="col-12 text-center py-5 text-danger">Error de procesamiento de datos.</div>';
            }
        },

        /**
         * NIVEL 3: Opciones
         */
        selectProgram: (offeringId, programName) => {
            app.log(`5. Selección de Diplomado: ${programName}`, "#f06292");
            state.selectedOfferingId = offeringId;
            DOM.selectedProgramLabel.innerText = programName;
            
            DOM.enrollmentsSection.classList.add('d-none');
            DOM.optionsSection.classList.remove('d-none');
            window.scrollTo({ top: DOM.optionsSection.offsetTop - 50, behavior: 'smooth' });
        },

        /**
         * VISTA PREVIA
         */
        openPreview: (type) => {
            state.selectedType = type;
            app.log(`6. Generando Vista Previa: ${type}`, "#ba68c8");
            
            DOM.pdfFrame.style.opacity = '0';
            DOM.pdfLoader.classList.remove('d-none');
            
            // Sincronizado con Bootstrap.php -> generate
            const url = `${BASE_URL}/administrative/certificates/generate?student_id=${state.selectedUserId}&offering_id=${state.selectedOfferingId}&type=${type}`;
            
            DOM.pdfFrame.src = url;
            state.modalInstance.show();

            DOM.pdfFrame.onload = () => {
                DOM.pdfLoader.classList.add('d-none');
                DOM.pdfFrame.style.opacity = '1';
            };
        },

        /**
         * FINALIZAR Y DESCARGAR
         */
        executeFinalizeDownload: () => {
            if (!state.selectedUserId || !state.selectedOfferingId) return;
            app.log("7. Disparando persistencia y descarga oficial", "#ff9800");
            state.modalInstance.hide();
            
            const url = `${BASE_URL}/administrative/certificates/finalizeAndDownload?student_id=${state.selectedUserId}&offering_id=${state.selectedOfferingId}&type=${state.selectedType}`;
            
            Swal.fire({
                title: 'Generando Documento',
                text: 'Registrando certificado oficial...',
                icon: 'info',
                timer: 2000,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });

            setTimeout(() => { window.location.href = url; }, 1200);
        },

        /**
         * FINALIZAR Y ENVIAR
         */
        executeSendEmail: async () => {
            if (!state.selectedUserId) return;
            app.log("7. Disparando persistencia y envío SMTP", "#2196f3");
            DOM.btnSendEmail.disabled = true;
            DOM.btnSendEmail.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

            const formData = new FormData();
            formData.append('student_id', state.selectedUserId);
            formData.append('offering_id', state.selectedOfferingId);
            formData.append('type', state.selectedType);

            try {
                const res = await fetch(`${BASE_URL}/administrative/certificates/sendEmail`, { method: 'POST', body: formData });
                const result = await res.json();
                
                state.modalInstance.hide();
                Swal.fire(result.ok ? '¡Enviado!' : 'Error', result.message, result.ok ? 'success' : 'error');
            } catch (err) {
                Swal.fire('Fallo de Red', 'No se pudo contactar con el servidor.', 'error');
            } finally {
                DOM.btnSendEmail.disabled = false;
                DOM.btnSendEmail.innerHTML = '<i class="bi bi-envelope-paper-fill me-2"></i> Enviar al Correo';
            }
        },

        resetWorkflow: () => {
            app.log("Módulo reiniciado");
            DOM.certificatesArea.classList.add('d-none');
            DOM.optionsSection.classList.add('d-none');
            DOM.searchResultsArea.classList.add('d-none');
            DOM.emptyState.classList.remove('d-none');
            state.selectedUserId = null;
        }
    };
})();