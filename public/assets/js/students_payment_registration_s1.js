/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL / REGISTRO DE PAGOS
 * ARCHIVO: public/assets/js/students_payment_registration_s1.js
 * PROPÓSITO: Inicialización automática de la identidad del estudiante logueado (Paso 1).
 * VERSIÓN: 1.0.1 - FIX: Sincronización robusta de datos y validación de sesión.
 */

window.StudentsS1 = {
    
    /**
     * Inicializa el módulo ocultando elementos de búsqueda administrativa 
     * y disparando la carga automática por sesión.
     */
    init: () => {
        // En autogestión, no buscamos; cargamos el perfil de la sesión inmediatamente
        window.StudentsS1.loadLoggedStudent();

        // Limpieza de UI: Ocultamos elementos del "clon" administrativo que no aplican al alumno
        const idsToHide = ['btnRemoveStudent', 'searchWrapper', 'searchResults'];
        idsToHide.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
    },

    /**
     * Consulta al servidor los datos del alumno logueado.
     */
    loadLoggedStudent: async () => {
        const resultsContainer = document.getElementById('searchResults');
        
        try {
            // Llamada al endpoint blindado del controlador
            const response = await fetch(`${BASE_URL}/students/payment_registration/getStudentData`);
            
            if (!response.ok) throw new Error("Fallo en la comunicación con el servidor.");
            
            const res = await response.json();

            if (res.status === 'success' && res.data) {
                const d = res.data;

                // Mapeo Inteligente: Detectamos si el modelo envía nombres separados o ya unidos
                const studentData = {
                    id: d.id,
                    code: d.student_code || d.codigo_estudiante || 'S/E',
                    fullname: d.full_name || `${d.first_name || ''} ${d.last_name || ''}`.trim(),
                    doc: d.cedula || d.documento || 'N/A',
                    avatar: d.avatar 
                        ? `${BASE_URL}/assets/img/avatars/${d.avatar}` 
                        : `${BASE_URL}/assets/img/avatars/default_avatar.png`
                };

                // Inyectamos al DOM
                window.StudentsS1.selectStudent(studentData);
            } else {
                // Error controlado desde el PHP (Ej: Sesión expirada)
                Swal.fire({
                    icon: 'warning',
                    title: 'Error de Identidad',
                    text: res.message || 'No pudimos verificar su perfil. Reingrese al sistema.',
                    confirmButtonColor: '#0d6efd'
                });
            }
        } catch (error) {
            console.error("Error crítico S1:", error);
            if (resultsContainer) {
                resultsContainer.innerHTML = '<div class="alert alert-danger smallest">Error técnico al verificar identidad.</div>';
                resultsContainer.classList.remove('d-none');
            }
        }
    },

    /**
     * Inyecta los datos en el DOM, habilita el botón Siguiente y persiste el estado.
     */
    selectStudent: (data) => {
        // 1. Seteo de inputs ocultos del formulario maestro para el submit final
        const mapping = {
            'user_id_val': data.id,
            'student_code_hidden': data.code,
            'full_name_hidden': data.fullname
        };

        Object.keys(mapping).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = mapping[id];
        });

        // 2. Actualización de elementos visuales (Perfil de Demetrio)
        const uiElements = {
            'displayNameDisplay': data.fullname,
            'displayDocDisplay': data.doc
        };

        Object.keys(uiElements).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerText = uiElements[id];
        });

        const avatarImg = document.getElementById('displayAvatar');
        if (avatarImg) avatarImg.src = data.avatar;
        
        // 3. Transición de UI
        document.getElementById('selectedIndicator')?.classList.remove('d-none');

        // 4. Habilitar navegación al Paso 2
        const btnNext = document.getElementById('btnNext');
        if (btnNext) {
            btnNext.disabled = false;
        }

        // 5. Llamada al orquestador para actualizar el estado de los botones
        if (typeof window.validarBotonesPersistentes === 'function') {
            window.validarBotonesPersistentes();
        }
    }
};

// Disparo automático al estar listo el documento
document.addEventListener('DOMContentLoaded', () => {
    window.StudentsS1.init();
});