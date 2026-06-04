/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: public/assets/js/administrative_inscriptions_create.js
 * PROPÓSITO: Lógica restrictiva del Wizard, búsqueda visual con avatares y conciliación de pagos.
 * VERSIÓN: 1.5.9 - Restauración de formularios de pago y unificación de confirmación de salida.
 */

console.log("🚀 [SISTEMA] Motor del Wizard Inscriptions v1.5.9 restaurado.");

document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. CONFIGURACIÓN DE RUTAS Y ESTADO ---
    const getBasePath = () => {
        const host = window.location.origin;
        const path = window.location.pathname;
        const root = path.includes('/public/') ? path.split('/public/')[0] : '';
        return host + root;
    };
    
    const BASE_URL = getBasePath();
    const PUBLIC_URL = BASE_URL + '/public';
    const API_BASE = PUBLIC_URL + '/administrative/inscriptions';
    const AVATAR_BASE = PUBLIC_URL + '/assets/img/avatars';

    let currentStep = parseInt(document.getElementById('current_step_val')?.value) || 1;
    let isDirty = false; 

    const btnNext = document.getElementById('btnNext');
    const btnPrev = document.getElementById('btnPrev');
    const btnSubmit = document.getElementById('btnSubmit');
    const btnSaveDraft = document.getElementById('btnSaveDraft');
    const btnCancel = document.getElementById('btnCancel');
    const form = document.getElementById('formAtomicInscription');

    const inputAmount = document.getElementById('amount'); 
    const inputMethodType = document.getElementById('payment_method_type'); 
    const inputMetadata = document.getElementById('payment_metadata');
    
    const modalPayment = new bootstrap.Modal(document.getElementById('modalDigital'));
    const selectorMethod = document.getElementById('digitalMethod');
    const dynamicFields = document.getElementById('dynamicFields');
    const inputFinalMethod = document.getElementById('payment_final_method');

    form.addEventListener('input', () => { isDirty = true; });

    // --- 2. MOTOR DEL WIZARD (NAVEGACIÓN) ---

    function updateWizard() {
        document.querySelectorAll('.wizard-step').forEach(s => s.classList.add('d-none'));
        const activePane = document.getElementById('step' + currentStep);
        if (activePane) activePane.classList.remove('d-none');

        if (btnPrev) btnPrev.classList.toggle('d-none', currentStep === 1);
        if (btnNext) btnNext.classList.toggle('d-none', currentStep === 5);
        if (btnSubmit) btnSubmit.classList.toggle('d-none', currentStep !== 5);

        const progress = document.getElementById('wizardProgress');
        if (progress) progress.style.width = (currentStep * 20) + '%';
        
        const indicator = document.getElementById('stepIndicator');
        if (indicator) indicator.innerText = `Paso ${currentStep} de 5`;
        
        const stepInput = document.getElementById('current_step_val');
        if (stepInput) stepInput.value = currentStep;

        if (currentStep === 5) prepareSummary();
    }

    function validateStep(step) {
        if (step === 1 && !document.getElementById('user_id_val').value) {
            Swal.fire('Atención', 'Debe seleccionar un estudiante.', 'warning');
            return false;
        }
        if (step === 2) {
            const degree = document.getElementById('undergraduate_degree').value.trim();
            if (!degree || degree === 'N/A') {
                Swal.fire('Error', 'Perfil sin carrera de pregrado.', 'error');
                return false;
            }
        }
        if (step === 3) {
            const docId = form.querySelector('input[name="doc_id"]').value;
            const docDegree = form.querySelector('input[name="doc_degree"]').value;
            if (!docId || !docDegree) {
                Swal.fire('Atención', 'Documentos obligatorios faltantes.', 'warning');
                return false;
            }
        }
        if (step === 4 && !inputFinalMethod.value) {
            Swal.fire('Atención', 'Seleccione un método de pago.', 'warning');
            return false;
        }
        return true;
    }

    if (btnNext) btnNext.addEventListener('click', () => { if (validateStep(currentStep)) { currentStep++; updateWizard(); } });
    if (btnPrev) btnPrev.addEventListener('click', () => { if (currentStep > 1) { currentStep--; updateWizard(); } });

    // --- 3. BÚSQUEDA Y SELECCIÓN ---

    const searchInput = document.getElementById('studentSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            const resultsDiv = document.getElementById('searchResults');
            if (query.length < 3) { resultsDiv.innerHTML = ''; return; }
            fetch(`${API_BASE}/search?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(user => {
                            const imgPath = (user.avatar && user.avatar !== 'default_avatar.png') ? `${AVATAR_BASE}/${user.avatar}` : `${AVATAR_BASE}/default_avatar.png`;
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'list-group-item list-group-item-action d-flex align-items-center p-3';
                            item.innerHTML = `<img src="${imgPath}" class="rounded-circle me-3 shadow-sm" style="width:45px; height:45px; object-fit:cover;" onerror="this.onerror=null; this.src='${AVATAR_BASE}/default_avatar.png';">
                                <div class="flex-grow-1 text-start"><div class="fw-bold">${user.first_name} ${user.last_name}</div><small class="text-muted">Cédula: ${user.document_id}</small></div>`;
                            item.onclick = () => selectStudent(user, imgPath);
                            resultsDiv.appendChild(item);
                        });
                    }
                });
        });
    }

    function selectStudent(u, imgPath) {
        document.getElementById('user_id_val').value = u.id;
        document.getElementById('displayName').innerText = `${u.first_name} ${u.last_name}`;
        document.getElementById('displayDoc').innerText = u.document_id;
        document.getElementById('document_id_hidden').value = u.document_id;
        document.getElementById('displayAvatar').src = imgPath;
        document.getElementById('avatar_hidden').value = u.avatar || 'default_avatar.png';
        document.getElementById('display_name_hidden').value = `${u.first_name} ${u.last_name}`;
        document.getElementById('undergraduate_degree').value = u.undergraduate_degree || 'N/A';
        document.getElementById('provenance').value = u.provenance || 'N/A';
        document.getElementById('selectedStudentCard').classList.remove('d-none');
        document.getElementById('searchResults').innerHTML = '';
        document.getElementById('studentSearch').value = '';
        isDirty = true;
    }


// --- 4. CONCILIACIÓN DE PAGOS (CORREGIDO PARA CAPTURAR MONTO) ---

/**
 * SECCIÓN: CONCILIACIÓN DE PAGOS (EFECTIVO / CASH)
 * Esta versión captura montos dinámicos y sincroniza el input 'amount' para el controlador.
 */

// 1. Asegúrate de tener estas referencias al inicio de tu archivo o dentro del DOMContentLoaded
const btnCash          = document.getElementById('btnOptCash');

if (btnCash) {
    btnCash.addEventListener('click', function() {
        
        // --- A. CÁLCULO DINÁMICO DEL MONTO SUGERIDO ---
        // Buscamos en la tabla del plan de pagos que ya cargaste en el Step 4
        const planRows = document.querySelectorAll('#paymentPlanBody tr');
        let montoInscripcionSugerido = "0,00";

        if (planRows.length >= 2) {
            // Sumamos Inscripción (Fila 1) + Cuota 1 (Fila 2)
            // Limpiamos el texto por si tiene '$', puntos o espacios
            const m1 = parseFloat(planRows[0].cells[2].innerText.replace(/[^0-9,]/g, '').replace(',', '.')) || 0;
            const m2 = parseFloat(planRows[1].cells[2].innerText.replace(/[^0-9,]/g, '').replace(',', '.')) || 0;
            
            const totalSugerido = m1 + m2;
            montoInscripcionSugerido = totalSugerido.toLocaleString('de-DE', { minimumFractionDigits: 2 });
        }

        // --- B. LANZAMIENTO DEL POPUP DE CAPTURA ---
        Swal.fire({
            title: '<h5 class="fw-bold text-success mb-0">Recepción de Efectivo</h5>',
            text: 'Confirme o ajuste el monto exacto recibido en taquilla:',
            input: 'text',
            inputValue: montoInscripcionSugerido,
            showCancelButton: true,
            confirmButtonText: 'Registrar Monto',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#198754',
            didOpen: () => {
                const input = Swal.getInput();
                // Aplicamos formato de moneda mientras el usuario escribe
                input.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/\D/g, "");
                    if (value === "") return;
                    value = (value / 100).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    e.target.value = value;
                });
            },
            inputValidator: (value) => {
                if (!value || value === '0,00' || value === '0') {
                    return 'Debe ingresar un monto válido para procesar la inscripción.';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // --- C. PROCESAMIENTO Y ASIGNACIÓN (ELIMINA EL AMOUNT 0) ---
                
                // 1. Limpiamos el formato para MySQL (de 125,50 a 125.50)
                const cleanAmount = result.value.replace(/\./g, '').replace(',', '.');
                
                // 2. Inyectamos los valores en los inputs que viajan al controlador
                if (inputAmount)     inputAmount.value = cleanAmount;
                if (inputMethodType) inputMethodType.value = 'CASH';
                if (inputMetadata) {
                    inputMetadata.value = JSON.stringify({ 
                        nota: "Pago presencial en taquilla",
                        monto_formateado: result.value,
                        fecha_registro: new Date().toLocaleString()
                    });
                }

                // --- D. FEEDBACK VISUAL EN LA TARJETA ---
                
                // Resaltamos la tarjeta seleccionada
                document.querySelectorAll('.payment-option-card').forEach(c => {
                    c.classList.remove('border-primary', 'bg-primary-subtle', 'active-selection');
                });
                btnCash.classList.add('border-primary', 'bg-primary-subtle', 'active-selection');

                // Mostramos el monto capturado en el badge de la tarjeta
                const display = document.getElementById('displayAmountCash');
                const label   = document.getElementById('valAmountCash');
                if (display && label) {
                    label.innerText = result.value;
                    display.classList.remove('d-none');
                }

                // Ocultamos otros resúmenes (como el digital)
                document.getElementById('digitalSummary')?.classList.add('d-none');
                
                // Marcamos el formulario como "con cambios"
                isDirty = true;

                // Confirmación final al usuario
                Swal.fire({
                    icon: 'success',
                    title: 'Monto Vinculado',
                    text: `Se han registrado $${result.value} para esta inscripción.`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    });
}
    const btnDigital = document.getElementById('btnOptDigital');
    if (btnDigital) btnDigital.addEventListener('click', () => { modalPayment.show(); });

    // RESTAURACIÓN: Listener para construir los campos de pago digital
    if (selectorMethod) {
        selectorMethod.addEventListener('change', function() {
            dynamicFields.innerHTML = '';
            dynamicFields.classList.toggle('d-none', !this.value);
            const val = this.value;
            if (val === 'ZELLE') {
                dynamicFields.innerHTML = `<input type="datetime-local" name="z_date" class="form-control mb-2" required>
                    <input type="number" step="0.01" name="z_amount" class="form-control mb-2" placeholder="Monto $" required>
                    <input type="text" name="z_issuer" class="form-control mb-2" placeholder="Titular de la cuenta" required>
                    <input type="text" name="z_ref" class="form-control mb-2" placeholder="Nro de Referencia" required>`;
            } else if (val === 'BINANCE') {
                dynamicFields.innerHTML = `<input type="text" name="b_order" class="form-control mb-2" placeholder="ID de Orden" required>
                    <input type="number" step="0.01" name="b_amount" class="form-control mb-2" placeholder="Monto USDT" required>
                    <input type="text" name="b_uid" class="form-control mb-2" placeholder="UID del Emisor" required>`;
            } else if (val === 'PAGOMOVIL') {
                dynamicFields.innerHTML = `<select name="pm_bank" class="form-select mb-2" required><option value="">Banco Origen...</option><option>Banesco</option><option>Mercantil</option><option>Provincial</option><option>Venezuela</option></select>
                    <input type="text" name="pm_id" class="form-control mb-2" placeholder="Cédula/RIF del titular" required>
                    <input type="text" name="pm_ref" class="form-control mb-2" placeholder="Referencia (Últimos 7)" maxlength="7" required>
                    <input type="number" step="0.01" name="pm_amount" class="form-control mb-2" placeholder="Monto en Bs." required>`;
            }
            if (val) dynamicFields.innerHTML += `<div class="mt-2 small fw-bold">Capture de pantalla (*)</div><input type="file" name="pay_screenshot" class="form-control" accept="image/*" required>`;
        });
    }

    const btnConfirmDigital = document.getElementById('btnConfirmDigital');
    if (btnConfirmDigital) {
        btnConfirmDigital.addEventListener('click', () => {
            const method = selectorMethod.value;
            if (!method) return;
            const inputs = dynamicFields.querySelectorAll('input[required], select[required]');
            let valid = true;
            inputs.forEach(i => { if(!i.value) { i.classList.add('is-invalid'); valid = false; } else { i.classList.remove('is-invalid'); } });
            if (valid) {
                inputFinalMethod.value = method;
                document.getElementById('lblDigital').innerText = method;
                document.getElementById('digitalSummary').classList.remove('d-none');
                document.querySelectorAll('.payment-option-card').forEach(c => c.classList.remove('border-primary', 'bg-primary-subtle'));
                document.getElementById('btnOptDigital').classList.add('border-primary', 'bg-primary-subtle');
                modalPayment.hide();
                isDirty = true;
            }
        });
    }

    // --- 5. CANCELACIÓN UNIFICADA ---

    if (btnCancel) {
        btnCancel.addEventListener('click', () => {
            const title = isDirty ? '¿Cancelar y perder cambios?' : '¿Cancelar operación?';
            const text = isDirty ? 'Se detectaron cambios en el formulario. Si sale ahora, se perderán.' : '¿Está seguro de que desea volver al catálogo?';
            
            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'No, quedarse'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = PUBLIC_URL + '/administrative/inscriptions';
                }
            });
        });
    }

    const saveDraftAction = (redirect = false) => {
        const formData = new FormData(form);
        formData.append('current_step', currentStep);
        fetch(`${API_BASE}/saveDraft`, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    isDirty = false;
                    if (redirect) window.location.href = PUBLIC_URL + '/administrative/inscriptions';
                    else Swal.fire('Éxito', 'Borrador actualizado.', 'success');
                }
            });
    };
    
    if (btnSaveDraft) btnSaveDraft.addEventListener('click', () => saveDraftAction(false));

    function prepareSummary() {
        document.getElementById('summaryName').innerText = document.getElementById('displayName').innerText;
        const m = inputFinalMethod.value;
        document.getElementById('summaryStatus').innerText = (m === 'CASH') ? 'PAGO FÍSICO (POR CONCILIAR)' : `PAGO DIGITAL (${m})`;
    }

    if (btnSubmit) {
        btnSubmit.addEventListener('click', () => {
            Swal.fire({
                title: '¿Confirmar Inscripción?',
                text: "Se generará el expediente y el registro financiero.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                confirmButtonColor: '#198754'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData(form);
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span>...';
                    fetch(`${API_BASE}/store`, { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.ok) Swal.fire('¡Éxito!', data.msg, 'info').then(() => window.location.href = PUBLIC_URL + '/administrative/inscriptions');
                            else throw data;
                        })
                        .catch(err => {
                            Swal.fire('Fallo', err.msg || 'Error', 'error');
                            btnSubmit.disabled = false;
                            btnSubmit.innerHTML = 'Finalizar Inscripción';
                        });
                }
            });
        });
    }

    updateWizard();
});