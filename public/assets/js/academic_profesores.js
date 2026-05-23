/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: public/assets/js/academic_profesores.js
 * Propósito: Interacción del directorio de profesores con borrado físico y detección de uso.
 * Version: 1.3.0 - Mensajes genéricos, manejo de dependencias y auditoría.
 */

document.addEventListener('DOMContentLoaded', function() {
    const basePath = '/diplomatic/public/academic/profesores';

    // --- 0. CAPTURA DE ESTADOS DESDE LA URL ---
    const urlParams = new URLSearchParams(window.location.search);
    
    // Si el controlador bloqueó el borrado por dependencias
    if (urlParams.get('error') === 'has_dependencies') {
        Swal.fire({
            icon: 'error',
            title: 'No se puede eliminar',
            text: 'Este registro no se puede eliminar porque ha sido utilizado.',
            confirmButtonColor: '#4e73df'
        });
    }

    // --- 1. ELIMINAR PROFESOR (Borrado Físico Inteligente) ---
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); 
            e.stopPropagation(); 
            
            const id = this.dataset.id;

            Swal.fire({
                title: '¿Eliminar registro?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Log de auditoría antes de proceder
                    fetch(`${basePath}/logAccess?action=DELETE_ATTEMPT&id=${id}`);

                    // Formulario dinámico para ejecución en controlador (Versión 1.3.0)
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

    // --- 2. CLIC EN FILA: ABRIR FICHA RESUMEN (Auditoría integrada) ---
    document.querySelectorAll('.profesor-row').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.btn-group') || e.target.closest('a') || e.target.closest('button')) return;

            const id = this.dataset.id;
            
            // Log de auditoría: Visualización de expediente
            fetch(`${basePath}/logAccess?action=VIEW_DETAILS&id=${id}`);

            fetch(`${basePath}/getDetails?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.ok) {
                        const p = data.profesor;
                        
                        const avatarUrl = p.photo_path ? p.photo_path : `https://ui-avatars.com/api/?name=${encodeURIComponent(p.first_name + ' ' + p.last_name)}&background=4e73df&color=fff&size=150`;
                        document.getElementById('prev_photo').src = avatarUrl;
                        
                        document.getElementById('prev_name').innerText = p.full_name;
                        document.getElementById('prev_type').innerText = p.professor_type;
                        document.getElementById('prev_id').innerText = p.identification;
                        document.getElementById('prev_bio').innerText = p.biography || 'Sin biografía registrada en el sistema.';
                        
                        document.getElementById('prev_email').innerText = (p.contact && p.contact.email) ? p.contact.email : 'No registrado';
                        document.getElementById('prev_phone').innerText = (p.contact && p.contact.phone) ? p.contact.phone : 'No registrado';
                        
                        const specContainer = document.getElementById('prev_specialties');
                        if (p.specialties && p.specialties.length > 0) {
                            specContainer.innerHTML = p.specialties.map(s => 
                                `<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary me-1 mb-1 px-2 py-1">${s.specialty_name}</span>`
                            ).join('');
                        } else {
                            specContainer.innerHTML = '<span class="text-muted small">No hay especialidades registradas.</span>';
                        }

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

                        document.getElementById('btn_full_profile').href = `${basePath}/edit?id=${p.id}`;

                        new bootstrap.Modal(document.getElementById('modalProfesorPreview')).show();
                    }
                })
                .catch(error => console.error("Error cargando ficha:", error));
        });
    });
});