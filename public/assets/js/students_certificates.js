/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / CONSTANCIAS
 * ARCHIVO: public/assets/js/students_certificates.js
 * PROPÓSITO: Lógica de navegación jerárquica para estudiantes (Clon de motor Administrativo).
 * VERSIÓN: 2.2.0 - FIX TOTAL: Eliminación de parámetros en onclick para evitar SyntaxError y adición de telemetría (Flags).
 */

(function() {
    "use strict";

    const BASE_URL = window.BASE_URL || '/diplomatic/public';

    const state = { 
        modalInstance: null,
        selectedOfferingId: null,
        selectedType: null
    };

    const DOM = {
        selectorArea: document.getElementById('program-selector-area'),
        loadingPrograms: document.getElementById('loading-programs'),
        programsContainer: document.getElementById('programs-container'),
        btnShowSelector: document.getElementById('btn-show-selector'),
        
        certificatesArea: document.getElementById('certificates-area'),
        selectedProgramLabel: document.getElementById('selected-program-name'),
        
        btnCertInscripcion: document.getElementById('btn-cert-inscripcion'),
        btnCertEstudios: document.getElementById('btn-cert-estudios'),
        btnCertEstudiosHorario: document.getElementById('btn-cert-estudios-horario'),
        
        modalEl: document.getElementById('modalPreview'),
        modalTitle: document.getElementById('modalPreviewTitle'),
        pdfFrame: document.getElementById('pdf-preview-frame'),
        pdfLoader: document.getElementById('pdf-loader'),
        
        btnDownload: document.getElementById('btn-download-pdf'),
        btnSendEmail: document.getElementById('btn-send-email')
    };

    /**
     * FLAG: Limpiador de Modal
     */
    const forceCleanupModal = () => {
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(b => b.remove());
    };

    document.addEventListener('DOMContentLoaded', () => {
        app.log("DOM Cargado. Inicializando sistema...");
        
        if (DOM.modalEl) {
            state.modalInstance = new bootstrap.Modal(DOM.modalEl, { backdrop: 'static', keyboard: false });
            DOM.modalEl.addEventListener('hidden.bs.modal', () => {
                DOM.pdfFrame.src = '';
                DOM.pdfLoader.classList.remove('d-none');
                DOM.pdfFrame.style.opacity = '0';
                forceCleanupModal();
                app.log("Recursos de visor liberados.");
            });
        }

        // 1. Carga automática inicial
        app.loadPrograms();

        // 2. Eventos de navegación
        DOM.btnShowSelector?.addEventListener('click', () => {
            app.log("Acción: Retrocediendo a selector de programas", "#6c757d");
            DOM.certificatesArea.classList.add('d-none');
            DOM.btnShowSelector.classList.add('d-none');
            DOM.selectorArea.classList.remove('d-none');
            state.selectedOfferingId = null;
        });

        // 3. Disparadores de vista previa
        DOM.btnCertInscripcion?.addEventListener('click', () => app.openPreview('INSCRIPCION', 'Planilla de Inscripción'));
        DOM.btnCertEstudios?.addEventListener('click', () => app.openPreview('ESTUDIOS', 'Constancia de Estudios'));
        DOM.btnCertEstudiosHorario?.addEventListener('click', () => app.openPreview('ESTUDIOS_HORARIO', 'Constancia de Estudios con Horario'));

        // 4. Acciones de persistencia
        DOM.btnDownload?.addEventListener('click', () => app.executeFinalizeDownload());
        DOM.btnSendEmail?.addEventListener('click', () => app.executeSendEmail());

        // 5. Control de carga del Iframe
        DOM.pdfFrame.addEventListener('load', () => {
            if (DOM.pdfFrame.src && !DOM.pdfFrame.src.includes('about:blank')) {
                app.log("Iframe: PDF cargado correctamente.");
                DOM.pdfLoader.classList.add('d-none');
                DOM.pdfFrame.style.opacity = '1';
            }
        });
    });

    window.app = {
        
        log: (msg, color = "#00e5ff") => console.log(`%c[STUDENT-SYS] ${msg}`, `color: ${color}; font-weight: bold;`),

        /**
         * NIVEL 1: Carga de Diplomados (Inmune a comillas)
         */
        loadPrograms: async () => {
            app.log("FLAG 1: Consultando programas por sesión...");
            
            try {
                const res = await fetch(`${BASE_URL}/students/certificates/getPrograms`);
                const programs = await res.json();
                
                DOM.loadingPrograms.classList.add('d-none');
                
                if (programs && programs.length > 0) {
                    app.log(`FLAG 2: ${programs.length} programas recibidos.`);
                    
                    DOM.programsContainer.innerHTML = programs.map(p => {
                        // NO pasamos el nombre por el onclick, lo guardamos en data-name
                        return `
                        <div class="col-md-6 mb-3 animate__animated animate__fadeInUp">
                            <div class="card program-card" 
                                 data-oid="${p.offering_id}" 
                                 data-name="${p.diplomado.replace(/"/g, '&quot;')}"
                                 onclick="app.handleCardClick(this)">
                                <div class="card-body d-flex align-items-center">
                                    <div class="icon-box-cert me-3">
                                        <i class="bi bi-journal-bookmark-fill"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-0 text-dark">${p.diplomado}</h6>
                                        <small class="text-muted small">Cohorte: ${p.cohorte}</small>
                                    </div>
                                    <div class="text-primary"><i class="bi bi-chevron-right"></i></div>
                                </div>
                            </div>
                        </div>`;
                    }).join('');
                    DOM.programsContainer.classList.remove('d-none');
                } else {
                    DOM.programsContainer.innerHTML = '<div class="col-12 text-center py-5 text-muted">No se hallaron inscripciones.</div>';
                    DOM.programsContainer.classList.remove('d-none');
                }
            } catch (err) { 
                app.log("ERROR CRÍTICO: No se pudo conectar con el servidor.", "#ff1744");
            }
        },

        /**
         * MANEJADOR DE CLIC SEGURO (Dataset Bridge)
         */
        handleCardClick: (element) => {
            const oid = element.getAttribute('data-oid');
            const name = element.getAttribute('data-name');
            app.selectProgram(parseInt(oid), name);
        },

        /**
         * NIVEL 2: Selección de Diplomado
         */
        selectProgram: (offeringId, programName) => {
            app.log(`FLAG 3: Seleccionado ${programName} (ID: ${offeringId})`, "#ffeb3b");
            state.selectedOfferingId = offeringId;
            DOM.selectedProgramLabel.innerText = programName;
            
            DOM.selectorArea.classList.add('d-none');
            DOM.certificatesArea.classList.remove('d-none');
            DOM.btnShowSelector.classList.remove('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /**
         * NIVEL 3: Vista Previa (Modo PRE-XXXX)
         */
        openPreview: (type, title) => {
            if (!state.selectedOfferingId) {
                app.log("ERROR: No hay offeringId seleccionado", "#ff1744");
                return;
            }
            
            state.selectedType = type;
            app.log(`FLAG 4: Generando Vista Previa ${type}`, "#ba68c8");
            
            DOM.modalTitle.innerHTML = `<i class="bi bi-file-earmark-pdf-fill me-2 text-danger"></i> ${title}`;
            DOM.pdfFrame.style.opacity = '0';
            DOM.pdfLoader.classList.remove('d-none');
            
            // Inyectamos la URL al Iframe
            const url = `${BASE_URL}/students/certificates/generate?type=${type}&offering_id=${state.selectedOfferingId}`;
            DOM.pdfFrame.src = url;
            
            state.modalInstance.show();
        },

        /**
         * ACCIÓN: Descarga Oficial (Folio CRT-)
         */
        executeFinalizeDownload: () => {
            app.log("FLAG 5: Disparando Finalize & Download", "#ff9800");
            state.modalInstance.hide();
            
            const url = `${BASE_URL}/students/certificates/finalizeAndDownload?type=${state.selectedType}&offering_id=${state.selectedOfferingId}`;
            
            Swal.fire({
                title: 'Registrando Folio',
                text: 'Se está generando su código institucional único...',
                icon: 'info',
                timer: 1500,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });

            setTimeout(() => { window.location.href = url; }, 1000);
        },

        /**
         * ACCIÓN: Envío Email (Folio CRT-)
         */
        executeSendEmail: async () => {
            app.log("FLAG 6: Iniciando envío por SMTP...", "#2196f3");
            DOM.btnSendEmail.disabled = true;
            DOM.btnSendEmail.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

            const formData = new FormData();
            formData.append('type', state.selectedType);
            formData.append('offering_id', state.selectedOfferingId);

            try {
                const res = await fetch(`${BASE_URL}/students/certificates/sendEmail`, { 
                    method: 'POST', body: formData 
                });
                const result = await res.json();
                
                state.modalInstance.hide();
                Swal.fire(result.ok ? '¡Enviado!' : 'Error', result.message, result.ok ? 'success' : 'error');
            } catch (err) {
                Swal.fire('Error', 'Fallo de red al procesar el envío.', 'error');
            } finally {
                DOM.btnSendEmail.disabled = false;
                DOM.btnSendEmail.innerHTML = '<i class="bi bi-send-check me-2"></i> Enviar a mi Correo';
            }
        }
    };
})();