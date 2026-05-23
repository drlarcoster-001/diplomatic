/**
 * MÓDULO: GESTIÓN FINANCIERA / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/financial_payment_registration_s1.js
 * PROPÓSITO: Lógica de búsqueda reactiva de participantes y gestión de selección en el Step 1.
 * VERSIÓN: 2.1.0 - FIX: Sincronización de default_avatar.png y limpieza profunda de variables de sesión.
 */

window.FinancialS1 = {
    searchTimeout: null,

    init: () => {
        const studentSearch = document.getElementById('studentSearch');
        const btnRemoveStudent = document.getElementById('btnRemoveStudent');

        if (studentSearch) {
            studentSearch.addEventListener('input', (e) => {
                window.FinancialS1.handleSearchDebounce(e.target.value);
            });
        }

        if (btnRemoveStudent) {
            btnRemoveStudent.addEventListener('click', window.FinancialS1.resetSelection);
        }

        document.addEventListener('click', (e) => {
            const item = e.target.closest('.student-result-item');
            if (item) {
                window.FinancialS1.selectStudent(item.dataset);
            }
        });
    },

    handleSearchDebounce: (query) => {
        clearTimeout(window.FinancialS1.searchTimeout);
        const resultsContainer = document.getElementById('searchResults');
        
        if (!resultsContainer) return;

        if (query.length < 3) {
            resultsContainer.classList.add('d-none');
            return;
        }

        window.FinancialS1.searchTimeout = setTimeout(() => {
            window.FinancialS1.executeSearch(query);
        }, 300);
    },

    executeSearch: async (query) => {
        const resultsContainer = document.getElementById('searchResults');
        if (!resultsContainer) return;
        
        try {
            const response = await fetch(`${BASE_URL}/financial/payment_registration/searchStudents?q=${encodeURIComponent(query)}`);
            const res = await response.json();

            if (res.status === 'success' && res.data && res.data.length > 0) {
                window.FinancialS1.renderResults(res.data);
            } else {
                resultsContainer.innerHTML = '<div class="list-group-item text-muted small">No se encontraron resultados</div>';
                resultsContainer.classList.remove('d-none');
            }
        } catch (error) {
            console.error("Error en búsqueda:", error);
            resultsContainer.innerHTML = '<div class="list-group-item text-danger small">Error de conexión</div>';
            resultsContainer.classList.remove('d-none');
        }
    },

    renderResults: (students) => {
        const resultsContainer = document.getElementById('searchResults');
        if (!resultsContainer) return;
        
        let html = '';

        students.forEach(s => {
            // FIX: Uso estricto de default_avatar.png
            const avatarFile = (s.avatar && s.avatar !== 'N/A' && s.avatar.trim() !== '') ? s.avatar : 'default_avatar.png';
            const avatarFullUrl = `${BASE_URL}/assets/img/avatars/${avatarFile}`;

            html += `
                <button type="button" class="list-group-item list-group-item-action student-result-item border-bottom p-3" 
                    data-id="${s.id}" 
                    data-code="${s.student_code || 'S/E'}" 
                    data-fullname="${s.first_name} ${s.last_name}" 
                    data-doc="${s.cedula}"
                    data-avatar="${avatarFullUrl}">
                    <div class="d-flex align-items-center">
                        <img src="${avatarFullUrl}" 
                             class="rounded-circle me-3" 
                             style="width: 45px; height: 45px; object-fit: cover; border: 1px solid #ddd;"
                             onerror="this.src='${BASE_URL}/assets/img/avatars/default_avatar.png'">
                        <div>
                            <div class="fw-bold text-dark small text-uppercase">${s.first_name} ${s.last_name}</div>
                            <div class="text-muted smallest">${s.cedula} | <span class="text-primary fw-bold">${s.student_code || 'SIN CÓDIGO'}</span></div>
                        </div>
                    </div>
                </button>`;
        });

        resultsContainer.innerHTML = html;
        resultsContainer.classList.remove('d-none');
    },

    selectStudent: (data) => {
        // Llenado de inputs ocultos
        const userIdVal = document.getElementById('user_id_val');
        const studentCodeHidden = document.getElementById('student_code_hidden');
        const fullNameHidden = document.getElementById('full_name_hidden');
        
        if (userIdVal) userIdVal.value = data.id;
        if (studentCodeHidden) studentCodeHidden.value = data.code;
        if (fullNameHidden) fullNameHidden.value = data.fullname;
        
        // ESTA ES LA LÍNEA QUE FALTABA: Guarda la cédula para que el modal la vea
        const docIdHidden = document.getElementById('document_id_hidden');
        if (docIdHidden) docIdHidden.value = data.doc;

        // Actualización de UI visual
        const displayNavName = document.getElementById('displayNameDisplay');
        const displayNavDoc = document.getElementById('displayDocDisplay');
        const displayAvatar = document.getElementById('displayAvatar');

        if (displayNavName) displayNavName.innerText = data.fullname;
        if (displayNavDoc) displayNavDoc.innerText = data.doc;
        if (displayAvatar) displayAvatar.src = data.avatar;
        
        // Transición visual
        document.getElementById('searchWrapper')?.classList.add('d-none');
        document.getElementById('searchResults')?.classList.add('d-none');
        document.getElementById('selectedIndicator')?.classList.remove('d-none');

        // Habilitar navegación
        const btnNext = document.getElementById('btnNext');
        if (btnNext) btnNext.disabled = false;

        // MEMORIA GLOBAL DE EMERGENCIA
        window.STUDENT_CI_FLAG = data.doc;
    },

    resetSelection: () => {
        // 1. Limpiar Paso 1
        const userIdVal = document.getElementById('user_id_val');
        const studentCodeHidden = document.getElementById('student_code_hidden');
        const fullNameHidden = document.getElementById('full_name_hidden');
        const studentSearch = document.getElementById('studentSearch');

        if (userIdVal) userIdVal.value = '';
        if (studentCodeHidden) studentCodeHidden.value = '';
        if (fullNameHidden) fullNameHidden.value = '';
        
        // Limpiamos también la cédula
        const docIdHidden = document.getElementById('document_id_hidden');
        if (docIdHidden) docIdHidden.value = '';



        if (studentSearch) {
            studentSearch.value = '';
            studentSearch.focus();
        }

        // 2. Limpiar Paso 2 (CRÍTICO: Evita cobrar deudas cruzadas si se cambia de alumno)
        const offeringIdVal = document.getElementById('offering_id_val');
        if (offeringIdVal) offeringIdVal.value = '';

        // 3. Restaurar UI visual
        document.getElementById('searchWrapper')?.classList.remove('d-none');
        document.getElementById('selectedIndicator')?.classList.add('d-none');
        
        const btnNext = document.getElementById('btnNext');
        if (btnNext) btnNext.disabled = true;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    window.FinancialS1.init();
});