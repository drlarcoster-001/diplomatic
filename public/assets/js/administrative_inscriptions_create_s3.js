/**
 * MÓDULO: GESTIÓN ADMINISTRATIVA
 * ARCHIVO: public/assets/js/administrative_inscriptions_create_s3.js
 * PROPÓSITO: Lógica de carga, popup de previsualización y eliminación de archivos.
 * VERSIÓN: 2.3.0
 */

document.addEventListener('DOMContentLoaded', function() {
    
    const fileURLs = {}; // Memoria temporal para las previsualizaciones

    // 1. DISPARADOR DE CARGA
    document.querySelectorAll('.action-upload').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById(`input_${this.dataset.target}`).click();
        });
    });

    // 2. MANEJO DE SELECCIÓN Y VALIDACIÓN
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const target = this.name;
            const file = this.files[0];
            const statusLabel = document.getElementById(`status_${target}`);
            const btnUpload = document.querySelector(`.action-upload[data-target="${target}"]`);
            const btnView = document.querySelector(`.action-view[data-target="${target}"]`);
            const btnDelete = document.querySelector(`.action-delete[data-target="${target}"]`);

            if (!file) return;

            // Validación estricta PDF
            if (file.type !== 'application/pdf') {
                Swal.fire('Error', 'Solo se permiten archivos PDF.', 'error');
                this.value = '';
                return;
            }

            // Actualizar Interfaz
            statusLabel.innerText = `${file.name.substring(0, 20)}... (${(file.size/1024).toFixed(1)} KB)`;
            statusLabel.className = 'text-success fw-bold small';

            btnUpload.classList.add('d-none');
            btnView.classList.remove('d-none');
            btnDelete.classList.remove('d-none');

            // Crear URL para el popup
            if (fileURLs[target]) URL.revokeObjectURL(fileURLs[target]);
            fileURLs[target] = URL.createObjectURL(file);
        });
    });

    // 3. POPUP DE PREVISUALIZACIÓN (BONITO)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.action-view');
        if (btn) {
            const target = btn.dataset.target;
            const url = fileURLs[target];
            
            Swal.fire({
                title: 'Vista Previa del Documento',
                html: `<iframe src="${url}" style="width:100%; height:450px; border-radius:10px;" frameborder="0"></iframe>`,
                width: '60%', // Tamaño elegante, no pantalla completa
                showCloseButton: true,
                showConfirmButton: false,
                focusConfirm: false,
                customClass: { popup: 'rounded-4' }
            });
        }
    });

    // 4. LÓGICA DE ELIMINACIÓN
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.action-delete');
        if (btn) {
            const target = btn.dataset.target;
            const input = document.getElementById(`input_${target}`);
            const statusLabel = document.getElementById(`status_${target}`);
            const btnUpload = document.querySelector(`.action-upload[data-target="${target}"]`);
            const btnView = document.querySelector(`.action-view[data-target="${target}"]`);
            const btnDelete = document.querySelector(`.action-delete[data-target="${target}"]`);

            // Resetear todo
            input.value = '';
            statusLabel.innerText = 'Pendiente por cargar';
            statusLabel.className = 'text-muted small';
            
            btnUpload.classList.remove('d-none');
            btnView.classList.add('d-none');
            btnDelete.classList.add('d-none');

            if (fileURLs[target]) {
                URL.revokeObjectURL(fileURLs[target]);
                delete fileURLs[target];
            }
        }
    });

    // 5. VALIDADOR DEL PASO (Cédula y Título obligatorios)
   let bypassStep3 = false; // Variable de control para permitir el salto

Wizard.validators[3] = function() {
    const idSet = document.getElementById('input_doc_id').files.length > 0;
    const degreeSet = document.getElementById('input_doc_degree').files.length > 0;

    // 1. Si los documentos están cargados, pasa sin preguntar
    if (idSet && degreeSet) return true;

    // 2. Si el usuario ya confirmó el salto en el SweetAlert, pasa
    if (bypassStep3) {
        bypassStep3 = false; // Reset para la próxima vez
        return true;
    }

    // 3. Si faltan documentos, lanza la pregunta
    Swal.fire({
        title: '¿Desea continuar sin documento de identidad y título?',
        text: 'Luego serán necesarios para finalizar el diplomado.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cargar ahora'
    }).then((result) => {
        if (result.isConfirmed) {
            bypassStep3 = true; // Activamos el permiso de salto
            // Ejecutamos el clic en el botón siguiente del Wizard
            document.getElementById('btnNext').click(); 
        }
    });

    return false; // Bloquea el avance automático para esperar la respuesta del SweetAlert
};
});