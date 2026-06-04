/**
 * MÓDULO: CONFIGURACIÓN / MÉTODOS DE PAGO
 * ARCHIVO: public/assets/js/settings_metodos.js
 * PROPÓSITO: Lógica de discriminación de campos por tipo de pago (Zelle, Binance, PM, Efectivo).
 * VERSIÓN: 1.8.0 - Fix: Etiquetas dinámicas y ocultación de campos irrelevantes.
 */

"use strict";

const PaymentMethods = {

    _request: function(endpoint, formData) {
        if (typeof Swal === 'undefined') { alert("Error: SweetAlert2 no cargó."); return; }

        const url = `/diplomatic/public/settings/paymentMethods/${endpoint}`;
        Swal.fire({ title: 'Guardando configuración...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        fetch(url, { 
            method: 'POST', 
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(async res => {
            const text = await res.text();
            if (text.trim().startsWith('<')) { throw new Error("Sesión expirada o error de ruta (HTML recibido)."); }
            try { return JSON.parse(text); } catch (e) { throw new Error("Error en el formato de respuesta."); }
        })
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'Los cambios se aplicaron correctamente.', timer: 1500, showConfirmButton: false })
                .then(() => window.location.reload());
            } else {
                Swal.fire('Atención', data.message || 'No se pudo guardar.', 'warning');
            }
        })
        .catch(err => Swal.fire('Error de Sistema', err.message, 'error'));
    },

    /**
     * Reconfigura el modal según el tipo de método
     */
    editMethod: function(data) {
        const form = document.getElementById('formMetodo');
        if (!form) return;
        form.reset();

        // Datos base
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_name').value = data.method_name;
        document.getElementById('edit_estatus').checked = (parseInt(data.status) === 1);
        document.getElementById('modal_title_type').innerText = data.method_name.toUpperCase();

        // Elementos a manipular
        const divDigital = document.getElementById('fieldsDigital');
        const divCash = document.getElementById('fieldsCash');
        const labelId = document.getElementById('label_identifier');
        const labelEx = document.getElementById('label_extra');
        const rowIden = document.getElementById('row_identification');
        const rowExtra = document.getElementById('row_extra_info');

        // LÓGICA DE DISCRIMINACIÓN SEGÚN method_type
        if (data.method_type === 'efectivo') {
            divDigital.classList.add('d-none');
            divCash.classList.remove('d-none');
            document.getElementById('edit_instrucciones').value = data.description || '';
        } 
        else if (data.method_type === 'pago_movil') {
            divDigital.classList.remove('d-none');
            divCash.classList.add('d-none');
            rowIden.classList.remove('d-none'); // Muestra Cédula
            rowExtra.classList.remove('d-none'); // Muestra Banco
            
            labelId.innerText = "NÚMERO DE TELÉFONO";
            labelEx.innerText = "BANCO DESTINO";
            
            document.getElementById('edit_banco').value = data.extra_info || '';
            document.getElementById('edit_identificador').value = data.identifier || '';
            document.getElementById('edit_titular').value = data.titular || '';
            document.getElementById('edit_documento').value = data.identification || '';
        }
        else if (data.method_type === 'zelle') {
            divDigital.classList.remove('d-none');
            divCash.classList.add('d-none');
            rowIden.classList.add('d-none'); // Zelle NO pide Cédula
            rowExtra.classList.add('d-none'); // Zelle NO pide "Banco" (es Zelle)
            
            labelId.innerText = "CORREO ELECTRÓNICO (ZELLE)";
            
            document.getElementById('edit_identificador').value = data.identifier || '';
            document.getElementById('edit_titular').value = data.titular || '';
        }
        else if (data.method_type === 'binance') {
            divDigital.classList.remove('d-none');
            divCash.classList.add('d-none');
            rowIden.classList.add('d-none'); // Binance NO pide Cédula
            rowExtra.classList.remove('d-none'); // Binance pide RED (TRC20, etc)
            
            labelId.innerText = "ID DE USUARIO / CORREO";
            labelEx.innerText = "RED DE TRANSFERENCIA";
            
            document.getElementById('edit_banco').value = data.extra_info || '';
            document.getElementById('edit_identificador').value = data.identifier || '';
            document.getElementById('edit_titular').value = data.titular || '';
        }

        const modalEl = document.getElementById('modalEditMethod');
        const myModal = new bootstrap.Modal(modalEl);
        myModal.show();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formMetodo');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            PaymentMethods._request('save', new FormData(this));
        });
    }
    window.editMethod = (data) => PaymentMethods.editMethod(data);
});