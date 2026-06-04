/**
 * MÓDULO: GESTIÓN FINANCIERA / PAGO MÓVIL
 * ARCHIVO: public/assets/js/financial_cash_operations_pagomovil.js
 * PROPÓSITO: Control integral de la interfaz con anclaje de tasa histórica mediante metadata.
 * VERSIÓN: 3.4.1 - FIX: Priorización de Tasa Histórica y Monto USD desde Metadata para conciliación exacta.
 */

(function() {
    "use strict";

    const state = {
        selectedFile: null,
        baseUrl: window.location.origin + '/diplomatic/public',
        currentPayments: [],
        activePaymentIndex: null,
        currentZoom: 1
    };

    const PaymentNotificator = {
        sendApprovedEmail: async function(paymentId) {
            const fd = new FormData();
            fd.append('payment_id', paymentId);
            try {
                const response = await fetch(`${state.baseUrl}/financial/notifications/sendPaymentApprovedEmail`, {
                    method: 'POST',
                    body: fd
                });
                const rawText = await response.text();
                const result = JSON.parse(rawText);
                if (result.success) {
                    console.log(`✔ Correo enviado para pago ID ${paymentId}`);
                } else {
                    console.error(`✘ Error correo pago ID ${paymentId}:`, result.message);
                }
                return result;
            } catch (error) {
                console.error(`Fallo de red para pago ID ${paymentId}:`, error);
                return { success: false, message: 'Error de conexión.' };
            }
        }
    };

    /**
     * INICIALIZADOR PRINCIPAL
     */
    document.addEventListener('DOMContentLoaded', () => {
        initClock();
        initModal();
        initFilters();
        initTableEvents(); 
        initValidationActions(); 
        initViewerControls(); 
        fetchPendingPayments(); 
        
        console.log("Módulo Pago Móvil v3.4.1: Sincronización de tasas históricas activada.");
    });

    /**
     * 1. RELOJ EN TIEMPO REAL
     */
    function initClock() {
        const el = document.getElementById('real-time-clock');
        if (!el) return;
        const update = () => {
            el.innerText = new Date().toLocaleTimeString('en-US', { 
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true 
            });
        };
        update();
        setInterval(update, 1000);
    }

    /**
     * 2. FILTROS DE BÚSQUEDA
     */
    function initFilters() {
        const form = document.getElementById('filter-form-pagomovil');
        const btnClear = document.getElementById('btn-clear-filters');
    if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                fetchPendingPayments({ 
                    text:      document.getElementById('search-text')?.value      || '',
                    date_from: document.getElementById('search-date-from')?.value || '',
                    date_to:   document.getElementById('search-date-to')?.value   || '',
                    order:     document.getElementById('search-order')?.value     || 'DESC'
                });
            });
        }
        if (btnClear) {
            btnClear.addEventListener('click', () => {
                form.reset();
                fetchPendingPayments();
            });
        }
    }

    /**
     * 3. GESTIÓN DE EVENTOS DE TABLA (CLIC EN FILA)
     */
    function initTableEvents() {
        const tableBody = document.querySelector('#table-pagomovil-pending tbody');
        if (!tableBody) return;

        tableBody.addEventListener('click', (e) => {
            const row = e.target.closest('tr');
            if (!row || row.classList.contains('placeholder-row')) return;

            if (e.target.closest('button')) return;

            const index = row.getAttribute('data-index');
            if (index !== null) {
                window.approvePayment(parseInt(index));
            }
        });
    }

    /**
     * 4. MOTOR DE CARGA DE LA GRID (MULTIMONEDA HISTÓRICA)
     */
    /**
     * MOTOR DE CARGA DE LA GRID
     * Incluye correlativo # y respeta la data del servidor sin cálculos.
     * Muestra el botón de aprobación masiva si hay conciliados.
     */
    async function fetchPendingPayments(filters = {}) {
        const tableBody = document.querySelector('#table-pagomovil-pending tbody');
        const btnMassive = document.getElementById('btn-approve-massive');
        if (!tableBody) return;

        // Colspan 9 por la nueva columna correlativa
        tableBody.innerHTML = `<tr class="placeholder-row"><td colspan="9" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>`;

        try {
            const query = new URLSearchParams(filters).toString();
            const response = await fetch(`${state.baseUrl}/financial/cash-operations/pagomovil/getPendingPayments?${query}`);
            const result = await response.json();

            if (!result.ok || !result.data || result.data.length === 0) {
                tableBody.innerHTML = `<tr class="placeholder-row"><td colspan="9" class="text-center py-5 text-muted">No hay registros pendientes.</td></tr>`;
                if (btnMassive) btnMassive.classList.add('d-none');
                return;
            }

            state.currentPayments = result.data;
            let html = '';
            
            // NUEVO: Variable para contar los registros conciliados
            let conciliadosCount = 0;

            result.data.forEach((row, index) => {
                const isConciliado = parseInt(row.match_found) > 0;
                const ledClass = isConciliado ? 'success' : 'danger';
                
                // Si está conciliado, sumamos al contador
                if (isConciliado) conciliadosCount++;
                
                // Formateo de números desde el servidor (Blindaje contra el 59,52)
                const formatBs = parseFloat(row.monto || 0).toLocaleString('de-DE', {minimumFractionDigits: 2});
                const formatTasa = parseFloat(row.tasa_bcv || 0).toLocaleString('de-DE', {minimumFractionDigits: 2});
                const formatUsd = parseFloat(row.monto_usd || 0).toLocaleString('de-DE', {minimumFractionDigits: 2});

                html += `
                    <tr data-index="${index}" style="cursor: pointer;" class="align-middle">
                        <td class="ps-4 text-muted small">#${index + 1}</td>
                        <td class="fw-bold">${row.fecha}</td>
                        <td class="text-secondary">${row.estudiante}</td>
                        <td class="font-monospace text-primary fw-bold">${row.referencia}</td>
                        <td class="text-end fw-bold">${formatBs} Bs.</td>
                        <td class="text-end text-muted small">${formatTasa}</td>
                        <td class="text-end">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-6 fw-bold">
                                $ ${formatUsd}
                            </span>
                        </td>
                        <td class="text-center"><span class="status-led ${ledClass}"></span></td>
                        <td class="text-center pe-3">
                            <button class="btn btn-sm btn-outline-primary rounded-circle" onclick="window.approvePayment(${index}); event.stopPropagation();">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>`;
            });
            tableBody.innerHTML = html;

            // NUEVO: Lógica para mostrar u ocultar el botón masivo
            if (btnMassive) {
                if (conciliadosCount > 0) {
                    btnMassive.classList.remove('d-none');
                    // Opcional: Actualizamos el texto del botón para que sea más descriptivo
                    btnMassive.innerHTML = `<i class="bi bi-check-all me-1"></i> Aprobar ${conciliadosCount} Pagos Conciliados`;
                } else {
                    btnMassive.classList.add('d-none');
                }
            }

        } catch (error) {
            tableBody.innerHTML = `<tr class="placeholder-row"><td colspan="9" class="text-center py-4 text-danger">Error: ${error.message}</td></tr>`;
        }
    }

    /**
     * 5. GESTIÓN DE MODAL CARGA EXCEL
     */
    /**
     * GESTIÓN DE MODAL CARGA EXCEL
     * Resetea el input y libera el espacio visual al presionar la X.
     */
    function initModal() {
        const modalEl = document.getElementById('modalUploadXlsx');
        const btnProcess = document.getElementById('btn-process-xlsx');
        const fileInput = document.getElementById('excelFile');
        const dropzone = document.getElementById('dropzone-pm');
        const fileInfo = document.getElementById('file-info-container');
        const btnRemove = document.getElementById('btn-remove-file');
        const btnOpen = document.getElementById('btn-open-upload-modal');

        if (!modalEl || !btnOpen) return;
        const modal = new bootstrap.Modal(modalEl);

        btnOpen.onclick = () => modal.show();
        if(dropzone) dropzone.onclick = () => fileInput.click();

        // EVENTO DE LA "X": Limpia todo y permite cargar de nuevo
        if(btnRemove) {
            btnRemove.onclick = () => {
                fileInput.value = ''; // Libera el espacio del input
                state.selectedFile = null;
                fileInfo.classList.add('d-none'); // Oculta nombre del archivo
                dropzone.classList.remove('d-none'); // Muestra zona de carga
                if (btnProcess) btnProcess.disabled = true;
                console.log("Espacio liberado: Input de archivo reseteado.");
            };
        }

        if(fileInput) {
            fileInput.onchange = function() {
                if (this.files[0]) {
                    const file = this.files[0];
                    state.selectedFile = file;
                    document.getElementById('selected-file-name').innerText = file.name;
                    fileInfo.classList.remove('d-none');
                    dropzone.classList.add('d-none');
                    if (btnProcess) btnProcess.disabled = false;
                }
            };
        }

        if(btnProcess) {
            btnProcess.onclick = async function() {
                if (!state.selectedFile) return;
                const formData = new FormData();
                formData.append('excelFile', state.selectedFile);

                if (typeof Swal !== 'undefined') {
                    Swal.fire({ 
                        title: 'Procesando archivo...', 
                        text: 'Cruzando datos con el banco.', 
                        allowOutsideClick: false, 
                        didOpen: () => Swal.showLoading() 
                    });
                }

                try {
                    const res = await fetch(`${state.baseUrl}/financial/cash-operations/pagomovil/uploadFile`, { method: 'POST', body: formData });
                    const json = await res.json();
                    if (json.ok) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('¡Éxito!', json.message, 'success').then(() => { 
                                modal.hide(); 
                                // Limpieza tras éxito
                                fileInput.value = '';
                                state.selectedFile = null;
                                fileInfo.classList.add('d-none');
                                dropzone.classList.remove('d-none');
                                fetchPendingPayments(); 
                            });
                        }
                    } else {
                        if (typeof Swal !== 'undefined') Swal.fire('Error', json.message, 'warning');
                    }
                } catch (e) {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', 'No se pudo procesar.', 'error');
                }
            };
        }
    }

    /**
     * 6. LÓGICA DEL POPUP DE VALIDACIÓN CON VISOR Y TASA HISTÓRICA
     */
    window.approvePayment = function(index) {
        const data = state.currentPayments[index];
        if (!data) return;

        state.activePaymentIndex = index; 

        // Extracción de metadata para el modal
        let metadata = {};
        try {
            metadata = typeof data.payment_metadata === 'string' ? JSON.parse(data.payment_metadata) : (data.payment_metadata || {});
        } catch(e) { metadata = {}; }

        const tasaHistorica = metadata.tasa_cambio || data.tasa_bcv || 0;
        const usdHistorico = metadata.monto_sistema_usd || data.monto_usd || 0;

        document.getElementById('v-estudiante').innerText = data.estudiante;
document.getElementById('v-banco').innerText = data.banco_origen || 'No suministrado';
        // --- INICIO INYECCIÓN BOTÓN WHATSAPP ---
        const telContainer = document.getElementById('v-telefono');
        const telefono = data.telefono_origen || '';
        const nombreEstudiante = data.estudiante || 'Estudiante';

        if (telefono && telefono !== 'No suministrado') {
            telContainer.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <span>${telefono}</span>
                    <button type="button" id="btn-dynamic-wa" class="btn btn-sm btn-success d-flex align-items-center gap-1 px-2 py-1 rounded-pill shadow-sm" title="Escribir por WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </button>
                </div>
            `;
            
            // Usamos setTimeout para asegurar que el botón ya está en el DOM antes de darle el evento
            setTimeout(() => {
                const btnWa = document.getElementById('btn-dynamic-wa');
                if (btnWa) {
                    btnWa.addEventListener('click', () => {
                        // Limpiamos el número y le agregamos el código de Venezuela (58)
                        const cleanPhone = telefono.replace(/\D/g, '');
                        const fullPhone = cleanPhone.startsWith('0') ? '58' + cleanPhone.substring(1) : cleanPhone;
                        
                        // Mensaje predeterminado con la despedida incluida
                        const defaultMsg = `Buenas ${nombreEstudiante},\n\nNos comunicamos cordialmente desde la *plataforma de diplomados* para indicarle que:\n\n\n\nAtentamente,\nCoordinación de Diplomados`;

                        Swal.fire({
                            // FIX CRÍTICO: Evita que Bootstrap bloquee la escritura en el campo de texto
                            target: document.getElementById('modalValidatePayment'),
                            title: '<h5 class="fw-bold text-success mb-0"><i class="bi bi-whatsapp me-2"></i>Enviar WhatsApp</h5>',
                            html: `
                                <div class="text-start border-top border-success border-3 pt-3 mt-2">
                                    <label class="form-label small text-muted fw-bold">Redacte el motivo del mensaje:</label>
                                    <textarea id="wa-message-text" class="form-control bg-white shadow-sm border-success" rows="8" style="resize: none;">${defaultMsg}</textarea>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonColor: '#198754',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: '<i class="bi bi-send-fill me-1"></i> Enviar a WhatsApp',
                            cancelButtonText: 'Cerrar',
                            customClass: { popup: 'rounded-4 shadow-lg' },
                            preConfirm: () => {
                                return document.getElementById('wa-message-text').value;
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const finalMsg = encodeURIComponent(result.value);
                                window.open(`https://wa.me/${fullPhone}?text=${finalMsg}`, '_blank');
                            }
                        });
                    });
                }
            }, 50);
        } else {
            telContainer.innerText = 'No suministrado';
        }
        // --- FIN INYECCIÓN BOTÓN WHATSAPP ---


        document.getElementById('v-fecha').innerText = data.fecha;
        document.getElementById('v-referencia').innerText = data.referencia;
        
        const formatBs = parseFloat(data.monto).toLocaleString('de-DE', {minimumFractionDigits: 2});
        const formatUsd = parseFloat(usdHistorico).toLocaleString('de-DE', {minimumFractionDigits: 2});
        
        const tasaFormateada = parseFloat(tasaHistorica).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        document.getElementById('v-monto').innerHTML = `
            ${formatBs} Bs. 
            <span class="badge bg-success ms-2 fs-6">$ ${formatUsd}</span>
            <div class="small text-muted fw-normal mt-1" style="font-size: 0.75rem;">Tasa Aplicada al Pago: ${tasaFormateada}</div>
        `;
        

        const img = document.getElementById('v-screenshot');
        if (img) {
            img.src = `${state.baseUrl}/${data.screenshot_path}`;
            resetZoom();
        }

        const alertBox = document.getElementById('alert-conciliacion');
        if (alertBox) {
            if (parseInt(data.match_found) > 0) { 
                alertBox.className = "alert alert-success d-flex align-items-center mt-4 mb-0 border-0 shadow-sm";
                alertBox.innerHTML = '<i class="bi bi-check-circle-fill me-3 fs-3"></i> <div><h6 class="mb-0 fw-bold">Conciliado</h6><small>Referencia verificada en estado de cuenta.</small></div>';
            } else {
                alertBox.className = "alert alert-warning d-flex align-items-center mt-4 mb-0 border-0 shadow-sm";
                alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-3 fs-3"></i> <div><h6 class="mb-0 fw-bold">Pendiente Bancario</h6><small>Esta referencia aún no cruza con el banco.</small></div>';
            }
        }

        const modalElement = document.getElementById('modalValidatePayment');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    };

    /**
     * 7. ACCIONES DE CONFIRMACIÓN
     */
    function initValidationActions() {
        const btnConfirm = document.getElementById('btn-confirm-validation');
        const btnMassive = document.getElementById('btn-approve-massive');
        const btnReject = document.getElementById('btn-reject-validation');

        if (btnConfirm) {
            btnConfirm.addEventListener('click', async () => {
                const data = state.currentPayments[state.activePaymentIndex];
                if (!data) return;
                const confirm = await Swal.fire({ 
                    title: '¿Confirmar Aprobación?', 
                    html: `Se aprobará y conciliará el pago de <b>${data.estudiante}</b>.`, 
                    icon: 'question', 
                    showCancelButton: true,
                    confirmButtonText: 'Aprobar Pago',
                    cancelButtonText: 'Cancelar'
                });
                if (confirm.isConfirmed) await executeApproval([{ id: data.id, reference: data.referencia }], 'individual');
            });
        }

        if (btnMassive) {
            btnMassive.addEventListener('click', async () => {
                const reconciled = state.currentPayments.filter(p => parseInt(p.match_found) > 0);
                if (reconciled.length === 0) return;
                const confirm = await Swal.fire({ 
                    title: `Aprobación Masiva`, 
                    text: `Se activarán automáticamente ${reconciled.length} pagos conciliados.`, 
                    icon: 'warning', 
                    showCancelButton: true, 
                    confirmButtonColor: '#198754',
                    confirmButtonText: '<i class="bi bi-check-all"></i> Sí, Aprobar Lote',
                    cancelButtonText: 'Cancelar'
                });
                if (confirm.isConfirmed) {
                    const payload = reconciled.map(p => ({ id: p.id, reference: p.referencia }));
                    await executeApproval(payload, 'massive');
                }
            });
        }

        if (btnReject) {
            btnReject.addEventListener('click', async () => {
                const data = state.currentPayments[state.activePaymentIndex];
                if (!data) return;

                const confirm = await Swal.fire({
                    title: '¿Rechazar Pago Móvil?',
                    text: "Este comprobante será marcado como rechazado.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: '<i class="bi bi-x-circle-fill"></i> Sí, Rechazar Pago',
                    cancelButtonText: 'Cancelar'
                });

                if (confirm.isConfirmed) {
                    try {
                        const formData = new FormData();
                        formData.append('payment_id', data.id);

                        const response = await fetch(`${state.baseUrl}/financial/cash-operations/pagomovil/rejectPayment`, { 
                            method: 'POST', 
                            body: formData 
                        });
                        const result = await response.json();

                        if (result.ok) {
                            await Swal.fire({icon: 'success', title: '¡Rechazado!', text: result.message, timer: 2000, showConfirmButton: false});
                            const modalEl = document.getElementById('modalValidatePayment');
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                            fetchPendingPayments();
                        } else {
                            throw new Error(result.message);
                        }
                    } catch (error) {
                        if (typeof Swal !== 'undefined') Swal.fire('Error al Rechazar', error.message, 'error');
                    }
                }
            });
        }
    }

    async function executeApproval(payments, type) {
        try {
            const formData = new FormData();
            if (type === 'massive') {
                payments.forEach((p, i) => {
                    formData.append(`payments[${i}][id]`, p.id);
                    formData.append(`payments[${i}][reference]`, p.referencia);
                });
            } else {
                formData.append('payment_id', payments[0].id);
                formData.append('reference', payments[0].referencia);
            }

            const endpoint = type === 'massive' ? 'approveMassivePayments' : 'validatePayment';
            const response = await fetch(`${state.baseUrl}/financial/cash-operations/pagomovil/${endpoint}`, { method: 'POST', body: formData });
            const result = await response.json();

            if (result.ok) {
                if (type === 'individual') {
                    await Swal.fire({icon: 'success', title: '¡Éxito!', text: result.message, timer: 2000, showConfirmButton: false});
                    const modalEl = document.getElementById('modalValidatePayment');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    fetchPendingPayments();
                } else {
                    // Confirmación del lote
                    await Swal.fire({
                        title: '¡Lote Procesado!',
                        html: `<div class="text-start">
                                <p class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>${result.message}</p>
                                <small class="text-muted">Se procederá a notificar a cada estudiante.</small>
                               </div>`,
                        icon: 'success',
                        confirmButtonText: 'Entendido',
                        allowOutsideClick: false
                    });

                    // Envío de correos con progreso visual
                    const total = payments.length;
                    let enviados = 0;
                    let fallidos = 0;

                    Swal.fire({
                        title: 'Enviando correos...',
                        html: `Enviando correo <b>1</b> de <b>${total}</b>`,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => Swal.showLoading()
                    });

                    for (const p of payments) {
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

                    fetchPendingPayments();
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) { 
            if (typeof Swal !== 'undefined') Swal.fire('Error de Aprobación', error.message, 'error');
        }
    }

    /**
     * 8. CONTROLES DEL VISOR DE IMÁGENES
     */
    function initViewerControls() {
        const btnIn = document.getElementById('btn-zoom-in');
        const btnOut = document.getElementById('btn-zoom-out');
        const btnReset = document.getElementById('btn-reset-zoom');
        const img = document.getElementById('v-screenshot');

        if (btnIn) btnIn.onclick = () => { state.currentZoom += 0.25; updateImageTransform(); };
        if (btnOut) btnOut.onclick = () => { if(state.currentZoom > 0.5) state.currentZoom -= 0.25; updateImageTransform(); };
        if (btnReset) btnReset.onclick = () => resetZoom();

        function updateImageTransform() {
            if (img) img.style.transform = `scale(${state.currentZoom})`;
        }
    }

    function resetZoom() {
        state.currentZoom = 1;
        const img = document.getElementById('v-screenshot');
        if (img) img.style.transform = `scale(1)`;
    }

})();