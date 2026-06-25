/**
 * MÓDULO: GESTIÓN GERENCIAL / ESTADO DE RESULTADOS
 * ARCHIVO: public/assets/js/managerial_estado_resultados.js
 * PROPÓSITO: Lógica del filtro de fechas — validación y envío del formulario.
 * VERSIÓN: 1.0.0 - Creación inicial.
 */

document.addEventListener('DOMContentLoaded', () => {

    const formFiltro = document.getElementById('formFiltro');
    const inputDesde = document.getElementById('inputDesde');
    const inputHasta = document.getElementById('inputHasta');

    if (!formFiltro) return;

    formFiltro.addEventListener('submit', (e) => {
        const desde = inputDesde?.value;
        const hasta = inputHasta?.value;

        if (!desde || !hasta) {
            e.preventDefault();
            alert('Debes seleccionar ambas fechas.');
            return;
        }

        if (desde > hasta) {
            e.preventDefault();
            alert('La fecha "Desde" no puede ser mayor que "Hasta".');
            return;
        }
    });

});