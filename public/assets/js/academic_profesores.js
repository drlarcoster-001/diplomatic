/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: public/assets/js/academic_profesores.js
 * Propósito: Interacción de la vista principal de Profesores y apertura de Ficha Resumen.
 */

document.addEventListener('DOMContentLoaded', function() {
    const basePath = '/diplomatic/public/academic/profesores';

    // 1. ELIMINAR PROFESOR (Borrado Lógico)
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); 
            e.stopPropagation(); // Evita que al darle a eliminar se abra el popup de resumen
            
            const id = this.dataset.id;
            const name = this.dataset.name;

            // Log de auditoría: Intento de eliminación
            fetch(`${basePath}/logAccess?action=DELETE_ATTEMPT&id=${id}`);

            Swal.fire({
                title: '¿Eliminar Docente?',
                html: `Se dará de baja el expediente de:<br><strong>${name}</strong>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const f = document.createElement('form');
                    f.method = 'POST'; 
                    f.action = `${basePath}/delete`;
                    
                    const i = document.createElement('input');
                    i.type = 'hidden'; 
                    i.name = 'id'; 
                    i.value = id;
                    
                    f.appendChild(i); 
                    document.body.appendChild(f); 
                    f.submit();
                }
            });
        });
    });

    // 2. CLIC EN FILA: ABRIR FICHA RESUMEN (POPUP)
    document.querySelectorAll('.profesor-row').forEach(row => {
        row.addEventListener('click', function(e) {
            // Prevenir si se hizo clic explícitamente en los botones de acción para no abrir el modal
            if (e.target.closest('.btn-group') || e.target.closest('a') || e.target.closest('button')) return;

            const id = this.dataset.id;
            
            // Log de auditoría: Visualización de expediente
            fetch(`${basePath}/logAccess?action=VIEW_DETAILS&id=${id}`);

            // Cargar datos de la Ficha Resumen desde la base de datos
            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        const p = data.profesor;
                        
                        // IMAGEN: Avatar Dinámico (Si no hay foto, generamos iniciales desde la API UI-Avatars)
                        const avatarUrl = p.photo_path ? p.photo_path : `https://ui-avatars.com/api/?name=${encodeURIComponent(p.first_name + ' ' + p.last_name)}&background=4e73df&color=fff&size=150`;
                        document.getElementById('prev_photo').src = avatarUrl;
                        
                        // Datos Principales
                        document.getElementById('prev_name').innerText = p.full_name;
                        document.getElementById('prev_type').innerText = p.professor_type;
                        document.getElementById('prev_id').innerText = p.identification;
                        document.getElementById('prev_bio').innerText = p.biography || 'Sin biografía registrada en el sistema.';
                        
                        // Datos de Contacto (Si existen en la tabla de relaciones)
                        document.getElementById('prev_email').innerText = (p.contact && p.contact.email) ? p.contact.email : 'No registrado';
                        document.getElementById('prev_phone').innerText = (p.contact && p.contact.phone) ? p.contact.phone : 'No registrado';
                        
                        // Especialidades (Ciclo para generar badges)
                        const specContainer = document.getElementById('prev_specialties');
                        if (p.specialties && p.specialties.length > 0) {
                            specContainer.innerHTML = p.specialties.map(s => 
                                `<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary me-1 mb-1 px-2 py-1">${s.specialty_name}</span>`
                            ).join('');
                        } else {
                            specContainer.innerHTML = '<span class="text-muted small">No hay especialidades registradas.</span>';
                        }

                        // Formación destacada (Mostramos solo la principal/reciente en el resumen rápido)
                        const formContainer = document.getElementById('prev_formation');
                        if (p.formations && p.formations.length > 0) {
                            const mainForm = p.formations[0];
                            formContainer.innerHTML = `
                                <strong>${mainForm.degree_title}</strong><br>
                                <span class="text-muted small">${mainForm.institution} (${mainForm.year_obtained || 'N/A'})</span>
                            `;
                        } else {
                            formContainer.innerHTML = '<span class="text-muted small">No hay formación académica registrada.</span>';
                        }

                        // Configurar botón para ver el expediente completo (Edición/Detalle con pestañas)
                        document.getElementById('btn_full_profile').href = `${basePath}/edit?id=${p.id}`;

                        // Levantar Modal nativo de Bootstrap
                        new bootstrap.Modal(document.getElementById('modalProfesorPreview')).show();
                    }
                })
                .catch(error => console.error("Error cargando ficha:", error));
        });
    });
});