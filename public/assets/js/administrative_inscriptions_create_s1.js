/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: public/assets/js/administrative_inscriptions_create_s1.js
 * PROPÓSITO: Orquestación del Wizard Administrativo y vinculación de estudiantes.
 * VERSIÓN: 2.3.8 - FIX: Sincronización de avatar por defecto y blindaje contra bucles de redirección 404.
 */

// Objeto global para compartir estado entre los archivos del Wizard
window.Wizard = {
    currentStep: 1,
    isDirty: false,
    basePath: window.location.origin + (window.location.pathname.includes('/public/') ? '/diplomatic/public' : ''),
    apiBase: function() { return this.basePath + '/administrative/inscriptions'; },
    avatarBase: function() { return this.basePath + '/assets/img/avatars/'; },
    validators: {} 
};

document.addEventListener('DOMContentLoaded', function() {
    console.log("🚀 Wizard Administrativo Inicializado correctamente.");

    const form = document.getElementById('formAtomicInscription');
    const btnNext = document.getElementById('btnNext');
    const btnPrev = document.getElementById('btnPrev');
    const btnCancel = document.getElementById('btnCancel');
    const btnRemove = document.getElementById('btnRemoveStudent'); 
    const searchInput = document.getElementById('studentSearch');
    const resultsDiv = document.getElementById('searchResults');
    const selectedIndicator = document.getElementById('selectedIndicator');

    const safeSet = (id, value, isInnerText = false) => {
        const el = document.getElementById(id);
        if (el) {
            if (isInnerText) el.innerText = value;
            else el.value = value;
        }
    };

    if (form) form.addEventListener('input', () => Wizard.isDirty = true);

    // --- 1. LÓGICA DE CANCELACIÓN ---
    if (btnCancel) {
        btnCancel.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Cancelar inscripción?',
                text: "Se perderán todos los datos ingresados del estudiante.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'No, continuar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = Wizard.apiBase();
                }
            });
        });
    }

    // --- 2. NAVEGACIÓN GLOBAL ---
    window.updateWizardUI = function() {
        document.querySelectorAll('.wizard-step').forEach(s => s.classList.add('d-none'));
        const currentStepEl = document.getElementById('step' + Wizard.currentStep);
        if (currentStepEl) currentStepEl.classList.remove('d-none');

        if (btnPrev) btnPrev.classList.toggle('d-none', Wizard.currentStep === 1);
        if (btnNext) btnNext.classList.toggle('d-none', Wizard.currentStep === 5);
        
        const btnSubmit = document.getElementById('btnSubmit');
        if (btnSubmit) btnSubmit.classList.toggle('d-none', Wizard.currentStep !== 5);

        const progress = document.getElementById('wizardProgress');
        if (progress) progress.style.width = (Wizard.currentStep * 20) + '%';
        
        const indicator = document.getElementById('stepIndicator');
        if (indicator) indicator.innerText = `Paso ${Wizard.currentStep} de 5`;
        
        safeSet('current_step_val', Wizard.currentStep);
        window.dispatchEvent(new CustomEvent('stepChanged', { detail: Wizard.currentStep }));
    };

    if (btnNext) {
        btnNext.addEventListener('click', () => {
            const isValid = Wizard.validators[Wizard.currentStep] ? Wizard.validators[Wizard.currentStep]() : true;
            if (isValid) { Wizard.currentStep++; updateWizardUI(); }
        });
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            if (Wizard.currentStep > 1) { Wizard.currentStep--; updateWizardUI(); }
        });
    }

    // --- 3. BÚSQUEDA ASÍNCRONA CON AVATARES ---
    if (searchInput) {
        searchInput.addEventListener('input', async function(e) {
            const query = e.target.value.trim();
            if (query.length < 3) { 
                if (resultsDiv) {
                    resultsDiv.innerHTML = '';
                    resultsDiv.classList.add('d-none');
                }
                return; 
            }

            try {
                const res = await fetch(`${Wizard.apiBase()}/search?q=${encodeURIComponent(query)}`);
                const data = await res.json();
                
                if (resultsDiv) {
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        resultsDiv.classList.remove('d-none');
                        data.forEach(user => {
                            // FIX: Sincronización con default_avatar.png
                            const imgPath = (user.avatar && user.avatar !== 'default_avatar.png') 
                                ? `${Wizard.avatarBase()}${user.avatar}` 
                                : `${Wizard.avatarBase()}default_avatar.png`;

                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action border-0 d-flex align-items-center py-2';
                            
                            // FIX: Añadido onerror="this.onerror=null; this.src='...'" para romper bucles de redirección
                            btn.innerHTML = `
                                <img src="${imgPath}" 
                                     class="rounded-circle me-3 border shadow-sm" 
                                     style="width:40px; height:40px; object-fit:cover;"
                                     onerror="this.onerror=null; this.src='${Wizard.avatarBase()}default_avatar.png';">
                                <div class="text-start">
                                    <span class="fw-bold text-dark d-block">${user.first_name} ${user.last_name}</span>
                                    <small class="text-muted">Cédula: ${user.document_id}</small>
                                </div>`;
                            btn.onclick = () => validateDuplicityAndSelect(user, imgPath);
                            resultsDiv.appendChild(btn);
                        });
                    } else {
                        resultsDiv.classList.add('d-none');
                    }
                }
            } catch (err) { console.error("Error S1 Search:", err); }
        });
    }

    // --- 4. SELECCIÓN Y VINCULACIÓN ---
    async function validateDuplicityAndSelect(user, imgPath) {
        const offIdEl = document.querySelector('input[name="offering_id"]');
        if (!offIdEl || !offIdEl.value) {
            Swal.fire('Error de Contexto', 'No se detectó el ID de la oferta académica. Recargue la página.', 'error');
            return;
        }

        try {
            const res = await fetch(`${Wizard.apiBase()}/checkExisting?user_id=${user.id}&offering_id=${offIdEl.value}`);
            const data = await res.json();

            if (data.exists) {
                Swal.fire('Atención', 'Este estudiante ya tiene un expediente registrado para esta oferta.', 'warning');
                if (resultsDiv) resultsDiv.innerHTML = '';
            } else {
                safeSet('user_id_val', user.id);
                safeSet('document_id_hidden', user.document_id);
                safeSet('display_name_hidden', `${user.first_name} ${user.last_name}`);
                
                // FIX: Sincronización de valor por defecto
                safeSet('avatar_hidden', user.avatar || 'default_avatar.png');
                
                safeSet('undergraduate_degree', user.undergraduate_degree || 'No especificado');
                safeSet('provenance', user.provenance || 'No especificada');

                safeSet('displayNameDisplay', `${user.first_name} ${user.last_name}`, true);
                safeSet('displayDocDisplay', user.document_id, true);
                
                const displayImg = document.getElementById('displayAvatar');
                if (displayImg) {
                    displayImg.src = imgPath;
                    // FIX: Candado adicional para la tarjeta de selección
                    displayImg.onerror = function() {
                        this.onerror = null;
                        this.src = Wizard.avatarBase() + 'default_avatar.png';
                    };
                }

                if (selectedIndicator) selectedIndicator.classList.remove('d-none');
                
                if (searchInput) {
                    searchInput.value = `${user.first_name} ${user.last_name}`;
                    searchInput.disabled = true;
                }

                if (resultsDiv) {
                    resultsDiv.innerHTML = '';
                    resultsDiv.classList.add('d-none');
                }

                Wizard.isDirty = true;
                const Toast = Swal.mixin({
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true
                });
                Toast.fire({ icon: 'success', title: 'Estudiante vinculado' });
            }
        } catch (err) { console.error("Error S1 Select:", err); }
    }

    // --- 5. LÓGICA DE LIMPIEZA (X EN LA TARJETA) ---
    if (btnRemove) {
        btnRemove.addEventListener('click', function() {
            const fields = ['user_id_val', 'document_id_hidden', 'display_name_hidden', 'undergraduate_degree', 'provenance'];
            fields.forEach(id => safeSet(id, ''));
            
            // FIX: Reset a default_avatar.png
            safeSet('avatar_hidden', 'default_avatar.png');

            if (selectedIndicator) selectedIndicator.classList.add('d-none');
            
            if (searchInput) {
                searchInput.value = '';
                searchInput.disabled = false;
                searchInput.focus();
            }

            if (resultsDiv) resultsDiv.innerHTML = '';
            Wizard.isDirty = true;
        });
    }

    // --- 6. VALIDADOR ---
    Wizard.validators[1] = () => {
        if (!document.getElementById('user_id_val')?.value) {
            Swal.fire('Atención', 'Debe seleccionar un estudiante para proceder con la inscripción.', 'info');
            return false;
        }
        return true;
    };

    updateWizardUI();
});