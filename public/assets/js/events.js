/**
 * Limpia visualmente el contenido de la consola de auditoría.
 */
function clearConsole() {
    const consoleBody = document.getElementById('event-logs');
    consoleBody.innerHTML = '<div class="text-secondary opacity-50"># Terminal reseteada por el usuario. Esperando nuevos eventos...</div><div class="cursor">_</div>';
}

/**
 * Función para exportar los logs mostrados.
 */
function exportAuditLogs() {
    Swal.fire({
        title: 'Exportar Auditoría',
        text: "¿Deseas descargar el reporte de los eventos filtrados?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Descargar CSV',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Procesando', 'Generando archivo de trazabilidad...', 'info');
            // Aquí irá la redirección a la ruta de descarga del controlador
        }
    });
}