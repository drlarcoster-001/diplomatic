/**
 * MÓDULO: ADMINISTRATIVO / MATRÍCULAS
 * ARCHIVO: public/assets/js/administrative_matriculations_print.js
 * PROPÓSITO: Control del auto-lanzador de impresión y eventos de los botones.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Auto-lanzar el diálogo de impresión 0.5 segundos después de que cargue la hoja
    setTimeout(function() {
        window.print();
    }, 500);

    // 2. Asignar evento al botón de "Imprimir Formato"
    const btnPrint = document.getElementById('btn-print-action');
    if (btnPrint) {
        btnPrint.addEventListener('click', function() {
            window.print();
        });
    }

    // 3. Asignar evento al botón de "Cerrar Pestaña"
    const btnClose = document.getElementById('btn-close-action');
    if (btnClose) {
        btnClose.addEventListener('click', function() {
            window.close();
        });
    }

});