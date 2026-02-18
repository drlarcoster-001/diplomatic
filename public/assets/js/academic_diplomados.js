/**
 * MÓDULO: GESTIÓN ACADÉMICA
 * Archivo: public/assets/js/academic_diplomados.js
 * Propósito: Gestión de eventos de la tabla, vista previa AJAX y descarga de PDF institucional.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    const basePath = '/diplomatic/public/academic/diplomados';
    const modalElement = document.getElementById('previewModal');

    // --- 1. GESTIÓN DE LA GRID (CLIC EN FILA) ---
    const rows = document.querySelectorAll('.row-preview');
    
    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.btn-action')) return;

            const id = this.getAttribute('data-id');
            if (!id) return;

            fetch(`${basePath}/getDetails?id=${id}`)
                .then(response => {
                    if (!response.ok) throw new Error('Error en la comunicación con el servidor');
                    return response.json();
                })
                .then(data => {
                    if (data.ok) {
                        const d = data.diplomado;
                        
                        // Guardamos el ID para la exportación
                        modalElement.setAttribute('data-current-id', id);

                        // Llenado de contenido del modal
                        document.getElementById('pv_name').innerText = d.name;
                        document.getElementById('pv_code').innerText = d.code;
                        document.getElementById('pv_directed').innerText = d.directed_to || 'Personal profesional.';
                        document.getElementById('pv_description').innerText = d.description || 'Sin descripción académica.';
                        document.getElementById('pv_hours').innerText = d.total_hours || '200';

                        // Renderizado de Requisitos
                        const reqCont = document.getElementById('pv_requirements');
                        reqCont.innerHTML = data.requirements.length 
                            ? data.requirements.map(r => `<li>${r.requirement_text}</li>`).join('') 
                            : '<li>Consultar coordinación académica.</li>';
                        
                        // Renderizado de Condiciones
                        const condCont = document.getElementById('pv_conditions');
                        condCont.innerHTML = data.conditions.length 
                            ? data.conditions.map(c => `<li>${c.condition_text}</li>`).join('') 
                            : '<li>Sujeto a normas institucionales.</li>';

                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
                        modalInstance.show();
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });

    // --- 2. BOTÓN DESCARGAR (PDF GENERADO EN SERVIDOR) ---
    const downloadBtn = document.getElementById('btnDownloadPDF');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            const id = modalElement.getAttribute('data-current-id');
            if (id) {
                // Redirección al controlador de exportación real
                window.location.href = `${basePath}/export?id=${id}`;
            }
        });
    }

    // --- 3. ACCIÓN DE ELIMINACIÓN ---
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const id = this.getAttribute('data-id');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Desea eliminar el diplomado',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'si',
                    cancelButtonText: 'cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `${basePath}/delete`;
                        const inputId = document.createElement('input');
                        inputId.type = 'hidden';
                        inputId.name = 'id';
                        inputId.value = id;
                        form.appendChild(inputId);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        });
    });
});