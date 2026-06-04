/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA / FRONTEND
 * ARCHIVO: public/assets/js/administrative_inscriptions_create_s5.js
 * PROPÓSITO: Resumen final con soporte para Arquitectura de Metadata y encadenamiento asíncrono corregido.
 * VERSIÓN: 3.4.1 - FIX: Sincronización de ruta API (/send-email) con Bootstrap.php.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    const metadataLabels = {
        'identificador': 'ID / Cédula Receptor',
        'cuenta_correo_telf': 'Canal de Origen',
        'nombre_titular': 'Titular de Cuenta',
        'banco_emisor': 'Entidad Bancaria',
        'referencia': 'Nº de Operación',
        'fecha_comprobante': 'Fecha del Pago',
        'monto_nativo': 'Monto Original',
        'moneda_nativa': 'Moneda'
    };

    /**
     * 1. POBLAR RESUMEN (STEP 5)
     */
    window.populateSummary = function() {
        const studentName = document.getElementById('display_name_hidden')?.value || '---';
        const studentDoc = document.getElementById('document_id_hidden')?.value || '---';
        const avatar = document.getElementById('avatar_hidden')?.value || 'default.png';
        const degree = document.getElementById('undergraduate_degree')?.value || '---';
        const prov = document.getElementById('provenance')?.value || '---';
        const payMethod = document.getElementById('payment_method_type')?.value || 'CASH';
        const rawMetadata = document.getElementById('payment_metadata')?.value || '';
        const amountValue = document.getElementById('amount')?.value || '0.00';

        // Datos del Estudiante
        document.getElementById('sumName').innerText = studentName;
        document.getElementById('sumDoc').innerText = `Cédula: ${studentDoc}`;
        document.getElementById('summaryAvatar').src = `${Wizard.avatarBase()}${avatar}`;
        document.getElementById('sumDegree').innerText = degree;
        document.getElementById('sumProv').innerText = prov;
        
        // Estatus Visual del Método (Badge)
        const elPay = document.getElementById('sumPayment');
        if (elPay) {
            elPay.innerText = payMethod === 'CASH' ? 'RECEPCIÓN (EFECTIVO)' : payMethod;
            elPay.className = payMethod === 'CASH' ? 'badge bg-success rounded-pill px-3 py-2' : 'badge bg-dark rounded-pill px-3 py-2';
        }

        // SINCRONIZACIÓN UI
        const elStatus = document.getElementById('sumStatus');
        if (elStatus) {
            elStatus.innerText = (payMethod === 'CASH') ? 'EN REVISIÓN - COMPROMISO PAGO' : 'EN REVISIÓN';
            elStatus.className = 'fw-bold text-primary fs-5';
        }

        const elPayDetails = document.getElementById('paymentDetailsSummary');
        const elGrid = document.getElementById('metadataGrid');
        
        if (elPayDetails && elGrid) {
            elGrid.innerHTML = ''; 
            
            if (rawMetadata) {
                try {
                    const data = JSON.parse(rawMetadata);
                    elPayDetails.classList.remove('d-none');

                    const currency = data.detalles_transaccion?.moneda_nativa || 'USD';
                    const amountFormatted = parseFloat(amountValue).toLocaleString('de-DE', { minimumFractionDigits: 2 });

                    const items = [
                        { label: 'CÉDULA ESTUDIANTE', val: data.detalles_origen?.identificador },
                        { label: metadataLabels.cuenta_correo_telf, val: data.detalles_origen?.cuenta_correo_telf },
                        { label: metadataLabels.banco_emisor, val: data.detalles_origen?.banco_emisor },
                        { label: metadataLabels.referencia, val: data.detalles_transaccion?.referencia },
                        { label: 'MONTO ABONADO', val: `${amountFormatted} ${currency}` }
                    ];

                    const skipTitular = ["NO_SUMINISTRADO", "N/A", "ESTUDIANTE", "BINANCE_PAY"];
                    if (data.detalles_origen?.nombre_titular && !skipTitular.includes(data.detalles_origen.nombre_titular)) {
                        items.splice(2, 0, { label: metadataLabels.nombre_titular, val: data.detalles_origen.nombre_titular });
                    }

                    items.forEach(item => {
                        if (!item.val || item.val === 'N/A') return;
                        const col = document.createElement('div');
                        col.className = 'col-6 col-md-4 mb-3';
                        col.innerHTML = `
                            <small class="text-muted d-block smallest text-uppercase fw-bold" style="font-size: 0.65rem;">${item.label}</small>
                            <span class="small fw-bold text-dark text-truncate d-block">${item.val}</span>
                        `;
                        elGrid.appendChild(col);
                    });

                } catch (e) { 
                    console.error("Error al parsear metadata en Step 5:", e);
                    elPayDetails.classList.add('d-none'); 
                }
            } else { 
                elPayDetails.classList.add('d-none'); 
            }
        }

        updateDocumentStatus();
    };

    /**
     * 2. GESTIÓN DE DOCUMENTOS
     */
    function updateDocumentStatus() {
        const docs = ['doc_id', 'doc_degree', 'doc_cv'];
        docs.forEach(id => {
            const input = document.querySelector(`input[name="${id}"]`);
            const btnPreview = document.querySelector(`.btn-preview-doc[data-doc-target="${id}"]`);
            const iconCheck = document.getElementById(`check_${id}`);
            const hasFile = (input && input.files && input.files.length > 0);

            if (btnPreview) {
                btnPreview.disabled = !hasFile;
                btnPreview.style.opacity = hasFile ? "1" : "0.3";
                btnPreview.style.pointerEvents = hasFile ? "auto" : "none";
            }
            if (iconCheck) {
                iconCheck.className = hasFile ? 'bi bi-check-circle-fill text-success fs-4' : 'bi bi-dash-circle text-muted fs-4 opacity-50';
            }
        });
    }

    /**
     * 3. PREVISUALIZACIÓN
     */
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-preview-doc');
        if (btn && !btn.disabled) {
            e.preventDefault();
            const targetId = btn.getAttribute('data-doc-target');
            const fileInput = document.querySelector(`input[name="${targetId}"]`);
            if (fileInput && fileInput.files[0]) {
                const file = fileInput.files[0];
                const fileURL = URL.createObjectURL(file);
                if (file.type.startsWith('image/')) {
                    Swal.fire({ 
                        title: 'Vista Previa', 
                        imageUrl: fileURL, 
                        confirmButtonText: 'Cerrar',
                        customClass: { image: 'img-fluid rounded border shadow-sm' }
                    });
                } else if (file.type === 'application/pdf') {
                    Swal.fire({
                        title: 'Visor PDF',
                        html: `<iframe src="${fileURL}" width="100%" height="500px" style="border:none; border-radius: 8px;"></iframe>`,
                        width: '80%', 
                        confirmButtonText: 'Cerrar'
                    });
                }
            }
        }
    });

    window.addEventListener('stepChanged', function(e) {
        if (e.detail === 5) populateSummary();
    });

    /**
     * 4. EJECUCIÓN FINAL
     */
    const btnSubmit = document.getElementById('btnSubmit');
    if (btnSubmit) {
        btnSubmit.addEventListener('click', async function() {
            const result = await Swal.fire({
                title: '¿Confirmar Registro?',
                text: 'Se generará el expediente y la orden de cobro.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, registrar ahora',
                cancelButtonText: 'Revisar',
                confirmButtonColor: '#198754'
            });
            if (result.isConfirmed) processFinalInscription();
        });
    }

    async function processFinalInscription() {
        const form = document.getElementById('formAtomicInscription');
        if (!form) return;

        const formData = new FormData(form);
        const sendNotifFlag = document.getElementById('sendNotification')?.checked ? '1' : '0';
        formData.append('send_notification', sendNotifFlag);

        try {
            Swal.fire({ 
                title: 'Guardando registro...', 
                html: 'Este proceso puede tomar unos segundos.',
                allowOutsideClick: false, 
                didOpen: () => Swal.showLoading() 
            });

            // 1. PETICIÓN STEP 5: GUARDAR
            const res = await fetch(`${Wizard.apiBase()}/store`, { method: 'POST', body: formData });
            const contentType = res.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Respuesta inesperada del servidor.");
            }

            const data = await res.json();
            
            if (res.ok && data.success) {
                const enrollId = data.enroll_id;
                let emailSent = false;
                let emailMsg = '';

                // 2. PETICIÓN STEP 6: ENVIAR CORREO (CORREGIDO A /send-email)
                if (sendNotifFlag === '1' && enrollId) {
                    try {
                        Swal.update({
                            title: 'Generando Credenciales...',
                            html: 'Despachando notificación por correo electrónico...'
                        });

                        const emailFormData = new FormData();
                        emailFormData.append('enrollment_id', enrollId);

                        // RUTA CORREGIDA: Agregado el guion para coincidir con Bootstrap.php [cite: 1]
                        const emailRes = await fetch(`${Wizard.apiBase()}/send-email`, { 
                            method: 'POST', 
                            body: emailFormData 
                        });

                        const emailData = await emailRes.json();
                        if (emailData.success) {
                            emailSent = true;
                        } else {
                            emailMsg = emailData.message;
                        }
                    } catch (mailErr) {
                        emailMsg = mailErr.message;
                    }
                }

                // 3. RESULTADO FINAL
                let finalTitle = '¡Proceso Completado!';
                let finalText = data.message;
                let finalIcon = 'success';

                if (sendNotifFlag === '1' && !emailSent) {
                    finalTitle = 'Inscripción Guardada';
                    finalText = `${data.message}<br><br><span class="text-danger fw-bold">Falla en correo: ${emailMsg}</span>`;
                    finalIcon = 'warning';
                }

                Swal.fire({ 
                    icon: finalIcon, 
                    title: finalTitle, 
                    html: finalText 
                }).then(() => {
                    window.location.href = Wizard.apiBase();
                });

            } else {
                Swal.fire('Error al guardar', data.message || 'Error desconocido.', 'error');
            }
        } catch (err) {
            Swal.fire('Fallo en la Inscripción', err.message, 'error');
        }
    }
});