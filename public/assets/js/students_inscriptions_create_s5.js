/**
 * MÓDULO: EVENTOS / ESTUDIANTES / INSCRIPCIONES
 * ARCHIVO: public/assets/js/students_inscriptions_create_s5.js
 * PROPÓSITO: Generación de resumen visual, envío atómico y disparo de notificaciones por email.
 * VERSIÓN: 2.1.9 - FIX: Sincronización de llave enrollment_id para disparo de correo asíncrono.
 */

(function() {
    
    document.addEventListener('stepChanged', function(e) {
        if (e.detail.step === 5) {
            populateResume();
        }
    });

   function populateResume() {
    const degree = document.getElementById('undergraduate_degree_s2')?.value || 'N/A';
    const provenance = document.getElementById('provenance_s2')?.value || 'N/A';
    
    document.getElementById('resume_degree').innerText = degree;
    document.getElementById('resume_provenance').innerText = provenance;

    const method = document.getElementById('payment_method_type')?.value;
    const metadataRaw = document.getElementById('payment_metadata')?.value;
    
    console.log("%c FLAG S5-1: Método detectado:", "color: orange; font-weight: bold;", method);
    console.log("%c FLAG S5-2: Metadata Raw (Lo que viene de S4):", "color: cyan;", metadataRaw);

    const detailBox = document.getElementById('payment_detail_box');
    const tableContent = document.getElementById('payment_table_content');

    if (!method || method === '') {
        detailBox.classList.add('d-none');
    } else {
        detailBox.classList.remove('d-none');
        try {
            const data = JSON.parse(metadataRaw || '{}');
            console.log("%c FLAG S5-3: JSON procesado para la tabla:", "color: green;", data);
            
            // Aquí es donde ocurre la magia o el desastre
            tableContent.innerHTML = generatePaymentTable(method, data);
            
        } catch (e) {
            console.error("FLAG S5-ERROR: Falló el parseo del JSON", e);
            tableContent.innerHTML = '<span class="text-danger">Error al cargar datos de pago.</span>';
        }
    }
}

function generatePaymentTable(method, data) {
        // --- 1. CASO EFECTIVO ---
        if (method === 'CASH') {
            const formattedAmount = parseFloat(data.monto_sistema_usd || 0).toLocaleString('de-DE', { minimumFractionDigits: 2 });
            return `
            <div class="d-flex justify-content-between align-items-center p-2">
                <span class="text-muted small text-uppercase fw-bold">Monto a consignar en Sede:</span>
                <strong class="text-success fs-5">$ ${formattedAmount}</strong>
            </div>`;
        }
        
        // --- 2. CASO PAGO MÓVIL ---
        if (method === 'PAGOMOVIL') {
            const montoBs = parseFloat(data.detalles_transaccion?.monto_nativo || 0);
            const tasa = parseFloat(data.tasa_cambio || 1);
            const montoUsd = montoBs / tasa; 

            const strBs = montoBs.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const strUsd = montoUsd.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            return `
            <div class="row g-2 align-items-center">
                <div class="col-4"><small class="text-muted d-block text-uppercase smallest">Banco</small><strong>${data.detalles_origen?.banco_emisor || '-'}</strong></div>
                <div class="col-4"><small class="text-muted d-block text-uppercase smallest">Identificador</small><strong>${data.detalles_origen?.identificador || '-'}</strong></div>
                <div class="col-4"><small class="text-muted d-block text-uppercase smallest">Teléfono</small><strong>${data.detalles_origen?.cuenta_correo_telf || '-'}</strong></div>
                
                <div class="col-12 mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-outline-success rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm me-3 btn-preview-payment" style="width: 38px; height: 38px; border-width: 2px;" title="Ver Capture Adjunto">
                            <i class="bi bi-eye-fill fs-5"></i>
                        </button>
                        <div>
                            <small class="text-muted d-block text-uppercase smallest">Referencia</small>
                            <strong class="text-dark">${data.detalles_transaccion?.referencia || '-'}</strong>
                        </div>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block text-uppercase smallest">Monto Abonado</small>
                        <strong class="text-primary fs-5">${strBs} Bs.</strong><br>
                        <span class="text-success fw-bold smallest">($ ${strUsd} USD)</span>
                    </div>
                </div>
            </div>`;
        }

        // --- 3. CASO ZELLE O BINANCE ---
        if (method === 'ZELLE' || method === 'BINANCE') {
            const moneda = (method === 'ZELLE') ? 'USD' : 'USDT';
            const colorClass = (method === 'ZELLE') ? 'text-success' : 'text-warning';
            const monto = parseFloat(data.detalles_transaccion?.monto_nativo || 0).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            return `
            <div class="row g-2">
                <div class="col-6"><small class="text-muted d-block text-uppercase smallest">Cuenta Origen</small><strong>${data.detalles_origen?.cuenta_correo_telf || '-'}</strong></div>
                <div class="col-6"><small class="text-muted d-block text-uppercase smallest">Referencia</small><strong>${data.detalles_transaccion?.referencia || '-'}</strong></div>
                
                <div class="col-12 mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-outline-success rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm me-3 btn-preview-payment" style="width: 38px; height: 38px; border-width: 2px;" title="Ver Capture Adjunto">
                            <i class="bi bi-eye-fill fs-5"></i>
                        </button>
                        <small class="text-muted text-uppercase smallest fw-bold">Comprobante</small>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block text-uppercase smallest">Monto Abonado</small>
                        <strong class="${colorClass} fs-5">${monto} ${moneda}</strong>
                    </div>
                </div>
            </div>`;
        }
        
        return '';
    }

    
// --- VARIABLES GLOBALES PARA ZOOM Y ARRASTRE DEL CAPTURE ---
    let imgScale = 1, imgPointX = 0, imgPointY = 0, isPanning = false, startX = 0, startY = 0;

    function resetReceiptZoom() {
        const imgPreview = document.getElementById('receiptImage');
        if(imgPreview) {
            imgScale = 1; imgPointX = 0; imgPointY = 0;
            imgPreview.style.transform = `translate(${imgPointX}px, ${imgPointY}px) scale(${imgScale})`;
        }
    }

    // --- ESCUCHADOR GLOBAL DE CLICS (OJITOS PREVIEW) ---
    document.addEventListener('click', function(e) {
        
        // 1. Lógica para Documentos (PDF) -> Abre modalViewPDF
        const btnDoc = e.target.closest('.btn-preview-resume');
        if (btnDoc) {
            const id = btnDoc.dataset.id;
            const input = document.getElementById(`file_${id}`);
            if (input && input.files[0]) {
                const url = URL.createObjectURL(input.files[0]);
                const iframe = document.getElementById('iframePDF');
                if (iframe) {
                    iframe.src = url;
                    const modal = new bootstrap.Modal(document.getElementById('modalViewPDF'));
                    modal.show();
                }
            }
        }

        // 2. Lógica para el Pago (Imagen) -> Abre modalViewReceipt
        const btnPayment = e.target.closest('.btn-preview-payment');
        if (btnPayment) {
            const inputPay = document.querySelector('input[name="pay_screenshot"]');
            
            if (inputPay && inputPay.files[0]) {
                const file = inputPay.files[0];
                if (file.type.startsWith('image/')) {
                    const url = URL.createObjectURL(file);
                    const imgPreview = document.getElementById('receiptImage');
                    if (imgPreview) {
                        imgPreview.src = url;
                        resetReceiptZoom(); // Resetea el zoom al abrir
                        const modal = new bootstrap.Modal(document.getElementById('modalViewReceipt'));
                        modal.show();
                    }
                } else {
                    Swal.fire('Atención', 'El comprobante subido no es una imagen válida.', 'warning');
                }
            } else {
                Swal.fire({ icon: 'info', title: 'Atención', text: 'No hay ningún comprobante adjunto en memoria.' });
            }
        }
    });

    // --- MOTOR DE PAN & ZOOM PARA EL CAPTURE (VANILLA JS) ---
    const receiptImg = document.getElementById('receiptImage');
    const receiptContainer = document.getElementById('receiptContainer');

    if (receiptContainer && receiptImg) {
        // Zoom con la rueda del ratón
        receiptContainer.addEventListener('wheel', (e) => {
            e.preventDefault();
            const delta = Math.sign(e.deltaY) * -1;
            const zoomStep = 0.15;
            
            const rect = receiptContainer.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            const newScale = Math.min(Math.max(1, imgScale + (delta * zoomStep)), 5); // Entre 1x y 5x
            
            if (newScale !== imgScale) {
                imgPointX -= (mouseX - imgPointX) * (newScale / imgScale - 1);
                imgPointY -= (mouseY - imgPointY) * (newScale / imgScale - 1);
                imgScale = newScale;
                
                if (imgScale === 1) { imgPointX = 0; imgPointY = 0; }
                receiptImg.style.transform = `translate(${imgPointX}px, ${imgPointY}px) scale(${imgScale})`;
            }
        }, { passive: false });

        // Iniciar arrastre
        receiptImg.addEventListener('mousedown', (e) => {
            if (imgScale <= 1) return;
            e.preventDefault();
            isPanning = true;
            startX = e.clientX - imgPointX;
            startY = e.clientY - imgPointY;
            receiptImg.style.cursor = 'grabbing';
        });

        // Arrastrando
        window.addEventListener('mousemove', (e) => {
            if (!isPanning) return;
            imgPointX = e.clientX - startX;
            imgPointY = e.clientY - startY;
            receiptImg.style.transform = `translate(${imgPointX}px, ${imgPointY}px) scale(${imgScale})`;
        });

        // Soltar
        window.addEventListener('mouseup', () => {
            isPanning = false;
            receiptImg.style.cursor = 'grab';
        });

        // Limpiar imagen al cerrar
        document.getElementById('modalViewReceipt')?.addEventListener('hidden.bs.modal', function () {
            receiptImg.src = '';
        });
    }


    const btnSubmit = document.getElementById('btnSubmit');
    if (btnSubmit) {
        btnSubmit.addEventListener('click', function() {
            Swal.fire({
                title: '¿Confirmar Inscripción?',
                text: "Su expediente será enviado para revisión administrativa.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
                    processFinalInscription();
                }
            });
        });
    }

    async function processFinalInscription() {
        const form = document.getElementById('formAtomicInscription');
        const formData = new FormData(form);

        formData.append('undergraduate_degree_s2', document.getElementById('undergraduate_degree_s2')?.value);
        formData.append('provenance_s2', document.getElementById('provenance_s2')?.value);

        Swal.fire({
            title: 'Procesando...',
            text: 'Registrando expediente y archivos.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const basePath = window.location.pathname.split('/create')[0];
            const targetUrl = basePath + '/store';
            
            const response = await fetch(targetUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error("Error en el servidor.");
            const result = await response.json();

            if (result.status === 'success' || result.success === true) {
                
                // --- DISPARO DEL CORREO ELECTRÓNICO (Corregido enrollment_id) ---
                if (result.enrollment_id) { // <-- Cambio aquí
                    const mailData = new FormData();
                    mailData.append('enrollment_id', result.enrollment_id); // <-- Cambio aquí
                    
                    const mailUrl = basePath.replace('_s5', '_s6') + '/send-email';

                    fetch(mailUrl, {
                        method: 'POST',
                        body: mailData
                    }).then(res => res.json())
                      .then(mailRes => console.log("Notificación Email:", mailRes.message))
                      .catch(err => console.error("Error en disparo de correo:", err));
                }

                window.onbeforeunload = null; 
                if (typeof window.killWizardAlarms === 'function') {
                    window.killWizardAlarms();
                } else {
                    window.isSubmitting = true; 
                }

                Swal.fire({
                    icon: 'success',
                    title: '¡Registro Exitoso!',
                    text: 'Su solicitud ha sido enviada y recibirá un correo de confirmación.',
                    confirmButtonText: 'Finalizar',
                    confirmButtonColor: '#198754',
                    allowOutsideClick: false
                }).then(() => {
                    window.onbeforeunload = null;
                    window.location.href = '../inscriptions'; 
                });
            } else {
                if(btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = 'Finalizar Inscripción';
                }
                Swal.fire('Atención', result.message || 'Error al procesar.', 'warning');
            }
        } catch (error) {
            if(btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = 'Finalizar Inscripción';
            }
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo completar la solicitud.' });
        }
    }

})();