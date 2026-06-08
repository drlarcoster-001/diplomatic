/**
 * MÓDULO: AUTOGESTIÓN ESTUDIANTIL
 * ARCHIVO: assets/js/students_document_management.js
 * PROPÓSITO: Gestión de carga y eliminación de recaudos con rutas relativas seguras.
 * VERSIÓN: 1.4.6 - Fix: Uso de fetch relativo para evitar errores de JSON por respuestas HTML.
 */

async function handleFileUpload(enrollmentId, docType) {
    const fileInput = document.getElementById(`file_${docType}`);
    if (!fileInput || !fileInput.files[0]) return;

    const file = fileInput.files[0];

    // Validaciones básicas
    if (file.type !== 'application/pdf') {
        Swal.fire('Error', 'Solo se permiten archivos PDF.', 'warning');
        fileInput.value = '';
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        Swal.fire('Atención', 'El archivo no debe exceder los 2MB.', 'warning');
        fileInput.value = '';
        return;
    }

    const formData = new FormData();
    formData.append('enrollment_id', enrollmentId);
    formData.append('doc_type', docType);
    formData.append('file', file);

    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

    try {
        // La ruta absoluta se deduce de la URL actual para evitar errores de subcarpeta
        const response = await fetch('documents/upload', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            Swal.fire({ icon: 'success', title: '¡Cargado!', timer: 1000, showConfirmButton: false })
            .then(() => { location.reload(); });
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error de Red', 'El servidor no devolvió un JSON válido. Verifique la ruta.', 'error');
    }
}

function confirmDelete(enrollmentId, docType, label) {
    Swal.fire({
        title: '¿Eliminar documento?',
        text: `Esta acción borrará tu ${label} permanentemente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Eliminando...', didOpen: () => { Swal.showLoading(); } });
            try {
                const response = await fetch('documents/deleteDocument', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ enrollment_id: enrollmentId, column: docType })
                });
            const raw = await response.text();
            console.log('STATUS:', response.status);
            console.log('RESPUESTA RAW:', raw);

            const res = JSON.parse(raw);
            if (res.status === 'success') {
                location.reload();
            } else {
                Swal.fire('Error', res.message || 'Error desconocido.', 'error');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'No se pudo realizar la operación.', 'error');
        }


        }
    });
}

function saveFinalChanges() {
    Swal.fire({
        icon: 'success',
        title: '¡Cambios Guardados!',
        text: 'Su expediente ha sido actualizado con éxito.',
        confirmButtonColor: '#0d6efd',
        confirmButtonText: 'Finalizar'
    }).then(() => {
        // Redirección dinámica al panel de estudiantes
        window.location.href = window.location.pathname.split('/documents')[0];
    });
}