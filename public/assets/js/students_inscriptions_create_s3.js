/**
 * MÓDULO: EVENTOS / ESTUDIANTES
 * ARCHIVO: public/assets/js/students_inscriptions_create_s3.js
 * PROPÓSITO: Lógica de carga y visualización de documentos en ventana emergente (Modal).
 * VERSIÓN: 1.1.5 - FIX: Implementación de límite de carga de archivos (Máx 2MB) para protección del servidor.
 */

(function() {
    // Instancia del modal (Bootstrap 5)
    let pdfModal;
    
    
    document.addEventListener('click', function(e) {
        
        // 1. BOTÓN SUBIR
        const btnSubir = e.target.closest('.btn-subir');
        if (btnSubir) {
            e.preventDefault();
            document.getElementById(`file_${btnSubir.dataset.id}`).click();
            return;
        }

        // 2. BOTÓN VER (CORREGIDO: AHORA POPUP)
        const btnVer = e.target.closest('.btn-ver');
        if (btnVer) {
            e.preventDefault();
            const id = btnVer.dataset.id;
            const inputReal = document.getElementById(`file_${id}`);
            
            if (inputReal && inputReal.files[0]) {
                const fileURL = URL.createObjectURL(inputReal.files[0]);
                const iframe = document.getElementById('iframePDF');
                
                // Cargamos el PDF en el iframe del modal
                iframe.src = fileURL;
                
                // Mostramos el modal
                if (!pdfModal) pdfModal = new bootstrap.Modal(document.getElementById('modalViewPDF'));
                pdfModal.show();
            }
            return;
        }

        // 3. BOTÓN ELIMINAR
        const btnDel = e.target.closest('.btn-eliminar');
        if (btnDel) {
            e.preventDefault();
            const id = btnDel.dataset.id;
            const row = document.getElementById(`row_${id}`);
            document.getElementById(`file_${id}`).value = '';
            
            row.querySelector('.doc-details').classList.add('d-none');
            row.querySelector('.doc-desc').classList.remove('d-none');
            row.querySelector('.btn-subir').classList.remove('d-none');
            row.querySelector('.btn-group-managed').classList.add('d-none');
            row.querySelector('.btn-group-managed').classList.remove('d-flex');
            return;
        }
    });

    // Limpiar el iframe cuando se cierra el modal para liberar memoria
    document.getElementById('modalViewPDF')?.addEventListener('hidden.bs.modal', function () {
        document.getElementById('iframePDF').src = '';
    });

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('input-file-real')) {
            const input = e.target;
            const id = input.id.replace('file_', '');
            const row = document.getElementById(`row_${id}`);
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // --- VALIDACIÓN 1: TIPO DE ARCHIVO ---
                if (file.type !== 'application/pdf') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Formato Inválido',
                        text: 'Por favor, suba únicamente archivos en formato PDF.'
                    });
                    input.value = '';
                    return;
                }

                // --- VALIDACIÓN 2: LÍMITE DE TAMAÑO (2MB) ---
                const maxSizeInBytes = 2 * 1024 * 1024; // 2MB
                if (file.size > maxSizeInBytes) {
                    const actualSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                    Swal.fire({
                        icon: 'warning',
                        title: 'Archivo muy pesado',
                        html: `El documento pesa <b>${actualSizeMB} MB</b>.<br>El límite máximo permitido es de <b>2 MB</b> por archivo.<br><br><span style="font-size: 0.9em; color: #666;">Le sugerimos usar herramientas web para comprimir su PDF antes de subirlo.</span>`
                    });
                    input.value = ''; // Reseteamos el input para que no se guarde el archivo pesado
                    return;
                }

                // Si pasa las validaciones, mostramos los detalles
                const sizeKB = (file.size / 1024).toFixed(1);
                const details = row.querySelector('.doc-details');
                
                details.innerHTML = `${file.name.toUpperCase().substring(0, 15)}... <span class="text-muted">(${sizeKB} KB)</span>`;
                details.classList.remove('d-none');
                row.querySelector('.doc-desc').classList.add('d-none');
                row.querySelector('.btn-subir').classList.add('d-none');
                
                const group = row.querySelector('.btn-group-managed');
                group.classList.remove('d-none');
                group.classList.add('d-flex');
            }
        }
    });



// --- LÓGICA DE VALIDACIÓN PARA EL WIZARD ---
    let bypassStep3 = false;

    window.validateStep3 = function() {
        // 1. Si ya aceptó el bypass, lo dejamos pasar
        if (bypassStep3) {
            bypassStep3 = false; 
            return true;
        }

        // 2. Verificamos los inputs (usando tus IDs del controlador)
        const idSet = document.getElementById('file_doc_id')?.files.length > 0;
        const degreeSet = document.getElementById('file_doc_degree')?.files.length > 0;

        // 3. Si tiene los dos obligatorios, pasa directo
        if (idSet && degreeSet) return true;

        // 4. Si falta alguno, preguntamos
        Swal.fire({
            title: 'Expediente Incompleto',
            text: '¿Desea continuar sin cargar los documentos ahora? Podrá completarlos después para finalizar su inscripción.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cargar ahora'
        }).then((result) => {
            if (result.isConfirmed) {
                // 1. Activamos el permiso
                bypassStep3 = true; 
                
                // 2. Esperamos 100 milisegundos para que el popup se cierre bien
                setTimeout(() => {
                    // 1. Intentamos por clases conocidas
                    const btnNext = document.querySelector('.btn-next') || 
                                    document.querySelector('[data-wizard-type="next"]') ||
                                    document.querySelector('.sw-btn-next');

                    if (btnNext) {
                        btnNext.click();
                    } else {
                        // 2. PLAN B: Si no hay clase, buscamos por el texto del botón
                        const buttons = Array.from(document.querySelectorAll('button, a'));
                        const btnPorTexto = buttons.find(b => b.innerText.includes('Siguiente'));
                        
                        if (btnPorTexto) {
                            btnPorTexto.click();
                        } else {
                            // 3. SOLO AQUÍ mandamos el error, si fallaron ambos planes
                            console.error("Error crítico: No se pudo avanzar automáticamente. Por favor, haga clic en Siguiente manualmente.");
                        }
                    }
                }, 100);
            }
        });

        return false; // Detiene el avance automático
    };



})();