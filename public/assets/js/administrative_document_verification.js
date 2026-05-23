/**
 * MÓDULO: ADMINISTRATIVO / VERIFICACIÓN DE DOCUMENTOS
 * ARCHIVO: public/assets/js/administrative_document_verification.js
 * PROPÓSITO: Auditoría de recaudos, visor de PDF y persistencia de motivos de rechazo/observación en la BD.
 * VERSIÓN: 1.2.3 - Fix: Sincronización de llaves 'reason' y 'observation' con el controlador y blindaje de teclado.
 */

(function() {
    "use strict";

    const state = {
        // Aseguramos que la base URL siempre apunte a la raíz de la aplicación
        baseUrl: window.location.origin + (window.location.pathname.includes('/diplomatic/public') ? '/diplomatic/public' : ''),
        currentEnrollments: [],
        activeIndex: null,
        currentDocType: 'cedula'
    };

    document.addEventListener('DOMContentLoaded', () => {
        initFilters();
        initDocumentSelectors();
        initActionTriggers();
        fetchPendingVerifications();
    });

    /**
     * 1. CARGA DE LA COLA DE TRABAJO (AJAX)
     */
    async function fetchPendingVerifications(filters = {}) {
        const tableBody = document.querySelector('#table-docs-pending tbody');
        if (!tableBody) return;
        
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;

        try {
            if (!filters.status) {
                const activeTab = document.querySelector('button[data-bs-toggle="tab"].active');
                filters.status = activeTab ? activeTab.getAttribute('data-status') : 'REVISION';
            }

            const query = new URLSearchParams(filters).toString();
            const response = await fetch(`${state.baseUrl}/administrative/document-verification/getPending?${query}`);
            const result = await response.json();

            if (!result.ok || !result.data || result.data.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No hay expedientes en esta bandeja.</td></tr>`;
                return;
            }

            state.currentEnrollments = result.data;
            let html = '';
            result.data.forEach((row, index) => {
                let finStatusHtml = '';
                if (row.payment_status === 'APPROVED') {
                    finStatusHtml = '<span class="badge bg-success shadow-sm"><i class="bi bi-check-circle me-1"></i>Aprobado</span>';
                } else if (row.payment_status === 'PENDING') {
                    finStatusHtml = '<span class="badge bg-warning text-dark shadow-sm"><i class="bi bi-hourglass-split me-1"></i>En Revisión</span>';
                } else {
                    finStatusHtml = '<span class="badge bg-secondary shadow-sm">Sin Pago</span>';
                }

                html += `
                    <tr class="align-middle" onclick="window.openAuditModal(${index})" style="cursor: pointer;">
                        <td class="ps-4 text-muted small">${row.fecha_solicitud}</td>
                        <td class="fw-bold text-dark">${row.participante}</td>
                        <td class="font-monospace small">${row.cedula}</td>
                        <td class="small fw-bold text-secondary" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${row.diplomado}">
                            ${row.diplomado}
                        </td>
                        <td>${finStatusHtml}</td>
                        <td class="text-center pe-4">
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm">
                                <i class="bi bi-folder2-open"></i> Auditar
                            </button>
                        </td>
                    </tr>`;
            });
            tableBody.innerHTML = html;
        } catch (e) { 
            console.error(e);
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Error de conexión al cargar expedientes.</td></tr>`;
        }
    }

   /**
     * 2. APERTURA DEL VISOR DE AUDITORÍA (Sincronizado v1.2.5)
     */
    window.openAuditModal = function(index) {
        const data = state.currentEnrollments[index];
        state.activeIndex = index;

        // Inyectamos los nuevos campos y los existentes
        document.getElementById('v-diplomado').innerText = data.diplomado || 'N/A';
        document.getElementById('v-participante').innerText = data.participante;
        document.getElementById('v-cedula').innerText = `V-${data.cedula}`;
        document.getElementById('v-email').innerText = data.email || 'No registrado';
        document.getElementById('v-grado').innerText = data.undergraduate_degree || 'No especificado';
        document.getElementById('v-telefono').innerText = data.telefono || data.phone || 'Sin número';

        // Actualizamos el Estatus Financiero con el nuevo estilo visual
        const pagoBadge = document.getElementById('v-pago-status');
        if (data.payment_status === 'APPROVED') {
            pagoBadge.className = 'badge bg-success w-100 py-2 shadow-sm';
            pagoBadge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> SOLVENTE (Pago Aprobado)';
        } else {
            pagoBadge.className = 'badge bg-warning text-dark w-100 py-2 shadow-sm';
            pagoBadge.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> PAGO PENDIENTE';
        }

        // Reset de selectores de documentos
        document.querySelectorAll('.btn-doc-selector').forEach(btn => btn.classList.remove('active'));
        document.querySelector('.btn-doc-selector[data-doctype="cedula"]').classList.add('active');
        
        renderDocument('cedula');
        new bootstrap.Modal(document.getElementById('modalVerifyDocs')).show();
    };


    /**
     * 3. MOTOR DEL VISOR DE PDF
     */
    function initDocumentSelectors() {
        const buttons = document.querySelectorAll('.btn-doc-selector');
        buttons.forEach(btn => {
            btn.addEventListener('click', function() {
                buttons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const docType = this.getAttribute('data-doctype');
                renderDocument(docType);
            });
        });
    }

    function renderDocument(docType) {
        const data = state.currentEnrollments[state.activeIndex];
        const container = document.getElementById('pdf-container');
        const btnDownload = document.getElementById('btn-download-doc');
        const titleMap = {
            'cedula': 'Documento de Identidad',
            'titulo': 'Título Universitario',
            'cv': 'Resumen Curricular'
        };

        document.getElementById('visor-title').innerText = `Vista Previa: ${titleMap[docType]}`;

        let filePath = '';
        if (docType === 'cedula') filePath = data.doc_id_card;
        if (docType === 'titulo') filePath = data.doc_degree;
        if (docType === 'cv') filePath = data.doc_cv;

        if (!filePath || filePath === 'N/A' || filePath.trim() === '') {
            container.innerHTML = `
                <div class="text-white text-center">
                    <i class="bi bi-file-earmark-x display-1 d-block mb-3 text-secondary"></i>
                    <h5 class="text-secondary">Documento no cargado</h5>
                    <p class="small text-muted">El participante no adjuntó este archivo.</p>
                </div>`;
            btnDownload.style.display = 'none';
        } else {
            const fullUrl = `${state.baseUrl}/${filePath}?t=${new Date().getTime()}`;
            container.innerHTML = `<iframe src="${fullUrl}" class="w-100 h-100 border-0" title="${titleMap[docType]}"></iframe>`;
            btnDownload.href = fullUrl;
            btnDownload.style.display = 'inline-block';
        }
    }

    /**
     * 4. GATILLOS DE ACCIÓN (APROBAR, RECHAZAR, OBSERVAR)
     */
    function initActionTriggers() {
        const auditModalEl = document.getElementById('modalVerifyDocs');

        // GATILLO VERDE: APROBAR
        document.getElementById('btn-approve-docs').onclick = async function() {
            const data = state.currentEnrollments[state.activeIndex];
            
            if (data.payment_status !== 'APPROVED') {
                Swal.fire({
                    target: auditModalEl,
                    title: 'Acción Denegada',
                    text: 'El participante debe tener su pago validado y APROBADO por Finanzas.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
                return;
            }

            const { isConfirmed } = await Swal.fire({
                target: auditModalEl,
                title: '¿Formalizar Estudiante?',
                text: `Se creará el expediente ${data.participante}.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                confirmButtonText: 'Aprobar y Crear Expediente'
            });

            if (isConfirmed) {
                processAction('/administrative/document-verification/approve', { enrollment_id: data.enrollment_id });
            }
        };

        // GATILLO ROJO: RECHAZAR (Sincronizado con variable 'reason')
        document.getElementById('btn-reject-docs').onclick = async function() {
            const data = state.currentEnrollments[state.activeIndex];
            const { value: reason, isConfirmed } = await Swal.fire({
                target: auditModalEl,
                title: 'Rechazar Expediente',
                text: 'Indique el motivo del rechazo (esto cancelará la inscripción):',
                input: 'text',
                inputPlaceholder: 'Ej: Documentos falsos, no cumple el perfil...',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Rechazar Definitivamente',
                inputValidator: (value) => { if (!value) return 'El motivo es obligatorio'; }
            });

            if (isConfirmed) {
                processAction('/administrative/document-verification/reject', { 
                    enrollment_id: data.enrollment_id, 
                    reason: reason // Coincide con $_POST['reason'] en el controlador
                });
            }
        };

        // GATILLO AMARILLO: OBSERVAR (Sincronizado con variable 'observation')
        document.getElementById('btn-observe-docs').onclick = async function() {
            const data = state.currentEnrollments[state.activeIndex];
            const { value: observation, isConfirmed } = await Swal.fire({
                target: auditModalEl,
                title: 'Solicitar Corrección',
                text: 'Indique qué documento requiere corrección:',
                input: 'textarea',
                inputPlaceholder: 'Ej: La cédula está borrosa, por favor suba una imagen más clara.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Enviar Observación',
                inputValidator: (value) => { if (!value) return 'Debe escribir una observación'; }
            });

            if (isConfirmed) {
                processAction('/administrative/document-verification/observe', { 
                    enrollment_id: data.enrollment_id, 
                    observation: observation // Coincide con $_POST['observation'] en el controlador
                });
            }
        };
    }

/**
     * 5. PROCESADOR DE ACCIONES AJAX
     */
    async function processAction(endpoint, payload) {
        const auditModalEl = document.getElementById('modalVerifyDocs');
        
        // Loader inicial (este sí se dibuja sobre el modal)
        Swal.fire({
            target: auditModalEl,
            title: 'Procesando...',
            text: 'Por favor espere mientras actualizamos el expediente.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const formData = new FormData();
            for (const key in payload) { formData.append(key, payload[key]); }

            const response = await fetch(`${state.baseUrl}${endpoint}`, { 
                method: 'POST', 
                body: formData 
            });
            const res = await response.json();

            if (res.ok) {
                // 1. Cerramos el modal de auditoría PRIMERO
                const modalInstance = bootstrap.Modal.getInstance(auditModalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }

                // 2. Esperamos un microsegundo para que el modal termine de cerrarse
                setTimeout(() => {
                    // 3. Lanzamos el mensaje de éxito a nivel de página (sin 'target')
                    // Personalizamos el mensaje si la acción fue aprobar
                    let successTitle = '¡Acción Completada!';
                    let successText = res.message;

                    if (endpoint.includes('approve')) {
                        successTitle = '¡Estudiante Formalizado!';
                        successText = 'Se ha creado el estudiante exitosamente y se ha generado su expediente administrativo.';
                    }

                    Swal.fire({
                        title: successTitle,
                        text: successText,
                        icon: 'success',
                        confirmButtonColor: '#198754'
                    });
                    
                    // 4. Recargamos la tabla en el fondo
                    fetchPendingVerifications(); 
                }, 300);

            } else { 
                throw new Error(res.message); 
            }
        } catch (e) { 
            // Si hay error, sí lo mostramos sobre el modal para que no se pierda el trabajo
            Swal.fire({
                target: auditModalEl,
                title: 'Error de Procesamiento',
                text: e.message || 'Ocurrió un error inesperado de red.',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            }); 
        }
    }



    /**
     * 6. FILTROS Y PESTAÑAS
     */
    function initFilters() {
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (event) {
                document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(t => {
                    t.classList.remove('text-dark', 'bg-white', 'border-bottom-0', 'shadow-sm', 'active');
                    t.classList.add('text-muted', 'bg-light');
                });
                event.target.classList.remove('text-muted', 'bg-light');
                event.target.classList.add('text-dark', 'bg-white', 'border-bottom-0', 'shadow-sm', 'active');

                const currentStatus = event.target.getAttribute('data-status');
                fetchPendingVerifications({ search: document.getElementById('search-text').value, status: currentStatus });
            });
        });

        const form = document.getElementById('filter-form-docs');
        const btnClear = document.getElementById('btn-clear-filters');
        
        if(form) {
            form.onsubmit = (e) => {
                e.preventDefault();
                const activeTab = document.querySelector('button[data-bs-toggle="tab"].active');
                fetchPendingVerifications({
                    search: document.getElementById('search-text').value,
                    status: activeTab ? activeTab.getAttribute('data-status') : 'REVISION'
                });
            };
        }
        
        if(btnClear) {
            btnClear.onclick = () => {
                document.getElementById('search-text').value = '';
                const activeTab = document.querySelector('button[data-bs-toggle="tab"].active');
                fetchPendingVerifications({ status: activeTab ? activeTab.getAttribute('data-status') : 'REVISION' });
            };
        }
    }

    /**
     * NOTIFICADOR WHATSAPP
     */
    document.addEventListener('click', function(e) {
        const btnWa = e.target.closest('#btn-wa-notify');
        
        if (btnWa) {
            e.preventDefault();
            
            // Extraer datos del modal activo
            const telefonoRaw = document.getElementById('v-telefono').textContent.trim();
            const nombre = document.getElementById('v-participante').textContent.trim();
            const diplomado = document.getElementById('v-diplomado').textContent.trim();
            
            if (telefonoRaw === '---' || !telefonoRaw || telefonoRaw === 'Sin número') {
                Swal.fire({
                    target: document.getElementById('modalVerifyDocs'),
                    title: 'Atención', 
                    text: 'No hay un número de teléfono válido cargado.', 
                    icon: 'warning'
                });
                return;
            }
            
            // Limpiar el teléfono para WhatsApp (ej: +584248907682 -> 584248907682)
            const telefonoLimpio = telefonoRaw.replace(/\D/g, '');
            
            // Mensaje estructurado
            const mensaje = `Estimado(a) *${nombre}*,\n\nLe contactamos desde la Coordinación Académica de *Plataforma Diplomados*.\n\nHemos auditado su expediente para el programa *${diplomado}* y notamos que tiene documentos pendientes por cargar o con observaciones.\n\nLe invitamos a ingresar a su panel estudiantil para actualizar los recaudos solicitados y así poder formalizar su inscripción con éxito.\n\nQuedamos a su entera disposición ante cualquier duda.\n\nSaludos cordiales.`;
            
            const waUrl = `https://wa.me/${telefonoLimpio}?text=${encodeURIComponent(mensaje)}`;
            window.open(waUrl, '_blank');
        }
    });
})();