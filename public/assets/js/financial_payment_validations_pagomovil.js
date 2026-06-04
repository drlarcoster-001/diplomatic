/**
 * MÓDULO: GESTIÓN FINANCIERA / VALIDACIÓN DE PAGOS
 * ARCHIVO: public/assets/js/financial_payment_validations_pagomovil.js
 * PROPÓSITO: Lógica de validación de Pago Móvil con integración de notificaciones automáticas por PHPMailer.
 * VERSIÓN: 4.2.0 - Inclusión de disparador PaymentNotificator.sendApprovedEmail y soporte /diplomatic/public/.
 */

document.addEventListener("DOMContentLoaded", function() {
    "use strict";

    const state = {
        selectedFile: null,
        currentPayments: [],
        activePaymentIndex: null,
        currentZoom: 1,
        currentPage: 1,
        searchTerm: '',
        searchDateFrom: '',
        searchDateTo: '',
        searchOrder: 'DESC'
    };

    let searchTimeout = null; // ✅ AGREGAR ESTA LÍNEA AQUÍ

    // Inicializadores
    initClock();
    initModalExcel();
    initSearchEvent();
    fetchPendingPayments();
    initViewerControls();
    initValidationActions();
    
    console.log("Módulo Pago Móvil (Vanilla JS) v4.2.0: Operativo con Notificaciones Asíncronas.");

    function initSearchEvent() {
        // 1. Capturar los elementos del DOM de forma segura
        const searchInput = document.getElementById('search-input');
        const searchDate = document.getElementById('search-date');
        const btnSubmit = document.getElementById('btn-submit-filters'); // Ajusta si tu botón tiene otro ID
        const form = document.getElementById('filter-form-pagomovil');
        const btnClear = document.getElementById('btn-clear-filters');

        // Función centralizada para aplicar filtros
        const applyFilters = () => {
            state.searchTerm     = searchInput ? searchInput.value.trim() : '';
            state.searchDateFrom = document.getElementById('search-date-from')?.value || '';
            state.searchDateTo   = document.getElementById('search-date-to')?.value || '';
            state.searchOrder    = document.getElementById('search-order')?.value || 'DESC';
            state.currentPage    = 1;
            
            // 🔥 DEBUG FRONTEND: Verificar qué datos capturó el JS antes de enviar
            console.log("JS Aplicando Filtros -> Texto:", state.searchTerm, "| Fecha:", state.searchDate);
            
            fetchPendingPayments();
        };

        // 2. Si hay un formulario, interceptar el Enter / Submit
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                applyFilters();
            });
        }

        // 3. EVENTO INFALIBLE: Si hacen clic en cualquier botón dentro de los filtros
        // Busca el botón por el texto que tiene adentro ("Ver Resultados") por si el ID falla
        const botones = document.querySelectorAll('button');
        botones.forEach(btn => {
            if (btn.textContent.includes('Ver Resultados')) {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    applyFilters();
                });
            }
        });

        // 4. Búsqueda en tiempo real al escribir
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    applyFilters();
                }, 500);
            });
        }

        // 5. Botón limpiar
        if (btnClear) {
            btnClear.addEventListener('click', () => {
                if(searchInput) searchInput.value = '';
                const from = document.getElementById('search-date-from');
                const to   = document.getElementById('search-date-to');
                const ord  = document.getElementById('search-order');
                if(from) from.value = '';
                if(to)   to.value   = '';
                if(ord)  ord.value  = 'DESC';
                state.searchTerm     = '';
                state.searchDateFrom = '';
                state.searchDateTo   = '';
                state.searchOrder    = 'DESC';
                state.currentPage    = 1;
                fetchPendingPayments();
            });
        }
    }

    /**
     * 1. RELOJ EN TIEMPO REAL
     */
    function initClock() {
        const el = document.getElementById('real-time-clock');
        if (!el) return;
        const update = () => {
            el.textContent = new Date().toLocaleTimeString('en-US', { 
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true 
            });
        };
        update();
        setInterval(update, 1000);
    }

    /**
     * 2. CARGA DE PAGOS DESDE EL SERVIDOR (GRID)
     */
    async function fetchPendingPayments() {
        const tbody = document.querySelector('#table-pagomovil-pending tbody');
        const btnMassive = document.getElementById('btn-approve-massive');
        
        try {
            // 🔥 FIX CRÍTICO: Usar la ruta RESTful directa en lugar de ?ajax_action=
            const url = `${BASE_URL}/financial/payment_validations/pagomovil/getPendingPayments?page=${state.currentPage}&text=${encodeURIComponent(state.searchTerm)}&date_from=${encodeURIComponent(state.searchDateFrom)}&date_to=${encodeURIComponent(state.searchDateTo)}&order=${encodeURIComponent(state.searchOrder)}`;
            
            // Imprimir la URL en consola para garantizar que viajan los filtros
            console.log("Ejecutando Fetch URL:", url);

            const res = await fetch(url);
            const result = await res.json();

            if (!result.ok || !result.data || result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-info-circle me-1"></i> No se encontraron resultados con estos filtros.</td></tr>';
                if(btnMassive) btnMassive.classList.add('d-none');
                renderPagination(0, 1); 
                return;
            }

            state.currentPayments = result.data;
            let html = '';
            
            const reconciledCount = result.data.filter(p => parseInt(p.match_found, 10) > 0).length;
            if (btnMassive) {
                if (reconciledCount > 0) {
                    btnMassive.classList.remove('d-none');
                    btnMassive.innerHTML = `<i class="bi bi-check-all me-1"></i> Aprobar ${reconciledCount} Conciliados`;
                } else {
                    btnMassive.classList.add('d-none');
                }
            }

            result.data.forEach((row, i) => {
                const ledClass = parseInt(row.match_found, 10) > 0 ? 'bg-success' : 'bg-danger';
                const fBs = parseFloat(row.monto_bs_json || 0).toLocaleString('de-DE', {minimumFractionDigits: 2});
                const fUsd = parseFloat(row.monto_usd_json || 0).toLocaleString('de-DE', {minimumFractionDigits: 2});
                const correlativoID = ((state.currentPage - 1) * 25) + (i + 1);

                const refCorta = row.referencia_corta || row.referencia.slice(-6);

                html += `
                <tr onclick="window.showDetail(${i})" style="cursor:pointer" class="align-middle table-hover-row">
                    <td class="ps-4 fw-bold text-muted">#${correlativoID}</td>
                    <td class="fw-bold text-dark">${row.fecha_json}</td>
                    <td>${row.estudiante}</td>
                    <td class="font-monospace text-primary fw-bold">${row.referencia}</td>
                    <td class="text-end fw-bold">${fBs} Bs.</td>
                    <td class="text-end text-success fw-bold">$ ${fUsd}</td>
                    <td class="text-center">
                        <span class="d-inline-block rounded-circle ${ledClass}" 
                            style="width:12px; height:12px; box-shadow: 0 0 5px rgba(0,0,0,0.2);" 
                            title="Conciliación"></span>
                    </td>
                    <td class="text-center pe-4">
                        <button class="btn btn-sm btn-outline-primary rounded-circle shadow-sm">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
            
            if (result.pagination) {
                renderPagination(result.pagination.total_records, result.pagination.total_pages);
            }
            
        } catch (e) { 
            console.error("Error Fetch:", e);
            tbody.innerHTML = '<tr><td colspan="8" class="text-danger text-center py-4">Error de comunicación. Intente recargar.</td></tr>'; 
        }
    }

    /**
     * 3. VISOR TÉCNICO (Ficha JSON y Visor)
     */
    window.showDetail = (index) => {
        const p = state.currentPayments[index];
        if (!p) return;
        
        state.activePaymentIndex = index;
        state.currentZoom = 1;

        let meta = { detalles_origen: {}, detalles_transaccion: {} };
        try {
            meta = (typeof p.payment_metadata === 'string') ? JSON.parse(p.payment_metadata) : p.payment_metadata;
        } catch (e) { console.error("JSON Parse Error", e); }

        document.getElementById('v-estudiante').textContent = p.estudiante;
        document.getElementById('v-banco-emisor').textContent = meta.detalles_origen.banco_emisor || 'No especificado';
        document.getElementById('v-telefono-emisor').textContent = meta.detalles_origen.cuenta_correo_telf || '---';
        document.getElementById('v-titular-cuenta').textContent = meta.detalles_origen.nombre_titular || 'No reportado';
        
        document.getElementById('v-referencia').textContent = p.referencia;
        document.getElementById('v-fecha').textContent = p.fecha_json || 'S/F';
        
        const fBs = parseFloat(p.monto_bs_json || 0).toLocaleString('de-DE', {minimumFractionDigits: 2});
        const fUsd = parseFloat(p.monto_usd_json || 0).toLocaleString('de-DE', {minimumFractionDigits: 2});
        const fTasa = parseFloat(p.tasa_pago_json || 0).toLocaleString('de-DE', {minimumFractionDigits: 2});
        
        document.getElementById('v-monto-bs').textContent = fBs;
        document.getElementById('v-monto-usd').textContent = `$ ${fUsd}`;
        document.getElementById('v-tasa-usada').textContent = fTasa;
        
        const img = document.getElementById('v-screenshot');
        img.setAttribute('src', `${BASE_URL}/${p.screenshot_path}`);
        img.style.transform = 'scale(1)';

        const alertBox = document.getElementById('alert-conciliacion');
        if (parseInt(p.match_found, 10) > 0) {
            alertBox.className = 'alert d-flex align-items-center border-0 shadow-sm mb-0 alert-success';
            alertBox.innerHTML = '<i class="bi bi-check-circle-fill me-2 fs-5"></i> <div><h6 class="mb-0 fw-bold">CONCILIADO</h6><small>Los datos coinciden plenamente con el reporte del banco.</small></div>';
        } else {
            alertBox.className = 'alert d-flex align-items-center border-0 shadow-sm mb-0 alert-warning';
            alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> <div><h6 class="mb-0 fw-bold">REVISIÓN MANUAL</h6><small>El monto reportado (Bs. ${fBs}) o la referencia no figuran en el banco.</small></div>`;
        }

        const modalEl = document.getElementById('modalValidatePayment');
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.show();
    };

    /**
     * 4. GESTIÓN DE CARGA EXCEL (DROPZONE)
     */
    function initModalExcel() {
        const dropzone = document.getElementById('dropzone-pm');
        const fileInput = document.getElementById('excelFile');
        const fileInfo = document.getElementById('file-info-container');
        const btnProcess = document.getElementById('btn-process-xlsx');
        
        document.getElementById('btn-open-upload-modal').addEventListener('click', () => {
            resetExcelModal();
            const modalEl = document.getElementById('modalUploadXlsx');
            const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl, { backdrop: 'static' });
            modalInstance.show();
        });

        dropzone.addEventListener('click', (e) => {
            if (e.target !== fileInput) fileInput.click();
        });

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length) { fileInput.files = files; handleFile(files[0]); }
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length) handleFile(this.files[0]);
        });

        document.getElementById('btn-remove-file').addEventListener('click', () => resetExcelModal());

        function resetExcelModal() {
            state.selectedFile = null;
            fileInput.value = ''; 
            dropzone.classList.remove('d-none');
            fileInfo.classList.add('d-none');
            btnProcess.disabled = true;
        }

        function handleFile(file) {
            const ext = file.name.split('.').pop().toLowerCase();
            if (ext !== 'xlsx') {
                Swal.fire('Error', 'Formato no válido. Solo se admiten archivos Excel (.xlsx)', 'error');
                resetExcelModal();
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
            fd.append('ajax_action', 'uploadFile');

            Swal.fire({ 
                title: 'Procesando Banco', 
                text: 'Sincronizando transacciones...', 
                allowOutsideClick: false, 
                didOpen: () => Swal.showLoading() 
            });

            try {
                const res = await fetch(`${BASE_URL}/financial/payment_validations/pagomovil/uploadFile`, { 
                    method: 'POST', 
                    body: fd 
                });
                const r = await res.json();
                if (r.ok) {
                    Swal.fire('¡Éxito!', r.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Atención', r.message, 'warning');
                }
            } catch (e) { 
                Swal.fire('Error', 'Fallo de red o servidor.', 'error'); 
            }
        });
    }

    /**
     * 5. CONTROLES DEL VISOR (ZOOM)
     */
    function initViewerControls() {
        const img = document.getElementById('v-screenshot');
        document.getElementById('btn-zoom-in').addEventListener('click', () => { state.currentZoom += 0.25; img.style.transform = `scale(${state.currentZoom})`; });
        document.getElementById('btn-zoom-out').addEventListener('click', () => { if(state.currentZoom > 0.5) { state.currentZoom -= 0.25; img.style.transform = `scale(${state.currentZoom})`; } });
        document.getElementById('btn-reset-zoom').addEventListener('click', () => { state.currentZoom = 1; img.style.transform = `scale(${state.currentZoom})`; });
    }

    /**
     * 6. ACCIONES: APROBACIÓN, RECHAZO Y MASIVA
     */
    function initValidationActions() {
        
        // Aprobación Individual
        document.getElementById('btn-confirm-validation').addEventListener('click', async () => {
            const p = state.currentPayments[state.activePaymentIndex];
            const fd = new FormData();
            fd.append('payment_id', p.id);
            fd.append('ajax_action', 'validatePayment');

            Swal.fire({ title: 'Procesando abonos...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const res = await fetch(`${BASE_URL}/financial/payment_validations/pagomovil`, { 
                    method: 'POST', 
                    body: fd 
                });
                const r = await res.json();

                if (r.ok) {
                    // --- INTEGRACIÓN: DISPARADOR DE CORREO ---
                    Swal.fire({
                        title: '¡Pago Aprobado!',
                        text: 'Validando comprobante y enviando correo al alumno...',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Notificación Asíncrona (No usamos await para no bloquear la recarga)
                    PaymentNotificator.sendApprovedEmail(p.id);

                    setTimeout(() => location.reload(), 2000);
                } else {
                    Swal.fire('Error', r.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Fallo de red al intentar aprobar.', 'error');
            }
        });

        // Rechazo Individual
        document.getElementById('btn-reject-validation').addEventListener('click', async () => {
            const p = state.currentPayments[state.activePaymentIndex];
            const conf = await Swal.fire({ 
                title: '¿Rechazar Pago?', 
                text: 'El reporte será devuelto para corrección del alumno.', 
                icon: 'warning', 
                showCancelButton: true, 
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Sí, Rechazar'
            });
            if (conf.isConfirmed) executeAction('rejectPayment', p.id);
        });

        // Aprobación Masiva
        document.getElementById('btn-approve-massive').addEventListener('click', async () => {
            const reconciled = state.currentPayments.filter(p => parseInt(p.match_found, 10) > 0);
            if (!reconciled.length) return;

            const conf = await Swal.fire({
                title: '¿Aprobación Masiva?',
                text: `Se procesarán automáticamente ${reconciled.length} pagos conciliados.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                confirmButtonText: 'Procesar Lote',
                cancelButtonText: 'Cancelar'
            });

            if (!conf.isConfirmed) return;

            const fd = new FormData();
            reconciled.forEach((p, i) => { fd.append(`payments[${i}][id]`, p.id); });

            Swal.fire({ title: 'Procesando lote...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const res = await fetch(`${BASE_URL}/financial/payment_validations/pagomovil/approveMassivePayments`, { 
                    method: 'POST', 
                    body: fd 
                });
                const r = await res.json();

                if (r.ok) {
                    await Swal.fire({
                        title: '¡Lote Procesado!',
                        html: `<div class="text-start">
                                <p class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>${r.message}</p>
                                <small class="text-muted">Se procederá a notificar a cada estudiante.</small>
                               </div>`,
                        icon: 'success',
                        confirmButtonText: 'Entendido',
                        allowOutsideClick: false
                    });

                    // Envío de correos con progreso visual
                    const total = reconciled.length;
                    let enviados = 0;
                    let fallidos = 0;

                    Swal.fire({
                        title: 'Enviando correos...',
                        html: `Enviando correo <b>1</b> de <b>${total}</b>`,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => Swal.showLoading()
                    });

                    for (const p of reconciled) {
                        try {
                            await PaymentNotificator.sendApprovedEmail(p.id);
                            enviados++;
                        } catch(e) {
                            fallidos++;
                            console.warn(`Correo fallido para pago ID ${p.id}:`, e);
                        }

                        const siguiente = enviados + fallidos + 1;
                        if (siguiente <= total) {
                            Swal.update({ html: `Enviando correo <b>${siguiente}</b> de <b>${total}</b>` });
                        }

                        await new Promise(resolve => setTimeout(resolve, 2000));
                    }

                    await Swal.fire({
                        title: '¡Correos Enviados!',
                        html: `<i class="bi bi-envelope-check-fill text-success me-2"></i>
                               <b>${enviados}</b> correos enviados exitosamente.
                               ${fallidos > 0 ? `<br><small class="text-danger">${fallidos} no pudieron enviarse.</small>` : ''}`,
                        icon: 'success',
                        confirmButtonText: 'Finalizar'
                    });

                    location.reload();

                } else {
                    Swal.fire('Error', r.message, 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Fallo de red al procesar el lote.', 'error');
            }
        });

// WhatsApp
        document.getElementById('btn-whatsapp-modal').addEventListener('click', () => {
            const nombre   = document.getElementById('v-estudiante').textContent.trim();
            const telefono = document.getElementById('v-telefono-emisor').textContent.trim();
            document.getElementById('wa-nombre-display').textContent  = nombre;
            document.getElementById('wa-telefono-display').textContent = telefono;
            document.getElementById('wa-preview-nombre').textContent  = nombre;
            document.getElementById('wa-mensaje').value               = '';
            document.getElementById('wa-preview-msg').textContent     = '';
            new bootstrap.Modal(document.getElementById('modalWhatsapp')).show();
        });

        document.getElementById('wa-mensaje').addEventListener('input', function() {
            document.getElementById('wa-preview-msg').textContent = this.value;
        });

        document.getElementById('btn-wa-send').addEventListener('click', () => {
            const telefono = document.getElementById('v-telefono-emisor').textContent.trim().replace(/\D/g, '');
            const nombre   = document.getElementById('v-estudiante').textContent.trim();
            const mensaje  = document.getElementById('wa-mensaje').value.trim();

            if (!mensaje) {
                Swal.fire('Atención', 'Escribe el mensaje personalizado.', 'warning');
                return;
            }

            const textoCompleto = `Buenas ${nombre}\nLe escribimos de parte de la *Plataforma de Diplomados* para informarte que:\n${mensaje}\n\nAtentamente,\n*Coordinación de Diplomados*`;
            window.open(`https://web.whatsapp.com/send?phone=58${telefono.slice(-10)}&text=${encodeURIComponent(textoCompleto)}`, '_blank');
            bootstrap.Modal.getInstance(document.getElementById('modalWhatsapp')).hide();
        });


    }

    /**
     * Helper AJAX para acciones simples (Rechazo)
     */
    async function executeAction(endpoint, id) {
        const fd = new FormData();
        fd.append('payment_id', id);
        fd.append('ajax_action', endpoint);

        try {
            const res = await fetch(`${BASE_URL}/financial/payment_validations/pagomovil`, { 
                method: 'POST', 
                body: fd 
            });
            const r = await res.json();
            if (r.ok) Swal.fire('¡Listo!', r.message, 'success').then(() => location.reload());
            else Swal.fire('Error', r.message, 'error');
        } catch (e) { 
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error'); 
        }
    }

    /**
     * NUEVO: RENDERIZADO DE PAGINACIÓN DINÁMICA
     */
    function renderPagination(totalRecords, totalPages) {
        const container = document.getElementById('pagination-container');
        if (!container) return;
        
        if (totalPages <= 1) {
            container.innerHTML = `<span class="text-muted small">Total registros: ${totalRecords}</span>`;
            return;
        }

        let html = `<nav><ul class="pagination pagination-sm mb-0">`;
        
        // Botón Anterior
        html += `<li class="page-item ${state.currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="javascript:void(0)" data-page="${state.currentPage - 1}">Ant</a>
                 </li>`;

        // Números
        for (let i = 1; i <= totalPages; i++) {
            html += `<li class="page-item ${i === state.currentPage ? 'active' : ''}">
                        <a class="page-link" href="javascript:void(0)" data-page="${i}">${i}</a>
                     </li>`;
        }

        // Botón Siguiente
        html += `<li class="page-item ${state.currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="javascript:void(0)" data-page="${state.currentPage + 1}">Sig</a>
                 </li>`;
                 
        html += `</ul></nav>`;
        container.innerHTML = html;

        // Eventos a los botones
        container.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                const targetPage = parseInt(e.target.getAttribute('data-page'));
                if (targetPage && targetPage !== state.currentPage && !e.target.parentElement.classList.contains('disabled')) {
                    state.currentPage = targetPage;
                    fetchPendingPayments();
                }
            });
        });
    }

});