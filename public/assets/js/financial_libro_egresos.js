/**
 * MÓDULO: FINANCIERO / LIBRO DE EGRESOS
 * ARCHIVO: public/assets/js/financial_libro_egresos.js
 * PROPÓSITO: Selector de período que rellena automáticamente las fechas desde/hasta.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', () => {
    const selPeriodo = document.getElementById('selPeriodo');
    const inputDesde = document.querySelector('input[name="desde"]');
    const inputHasta = document.querySelector('input[name="hasta"]');

    selPeriodo?.addEventListener('change', () => {
        const opt = selPeriodo.options[selPeriodo.selectedIndex];
        if (opt.value) {
            inputDesde.value = opt.dataset.inicio;
            inputHasta.value = opt.dataset.fin;
        }
    });
});