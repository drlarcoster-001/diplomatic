/**
 * MÓDULO: SEGURIDAD DE USUARIOS
 * Archivo: public/assets/js/user_security.js
 * Propósito: Gestión de credenciales, control de estados y eliminación definitiva.
 */

"use strict";

const UserSecurity = {

    /**
     * Helper para centralizar peticiones y capturar errores técnicos
     */
    _request: function(endpoint, formData, successMsg) {
        // Ajustamos la ruta para que sea relativa al origen y evitar problemas de carpetas
        const url = `/diplomatic/public/UserSecurity/${endpoint}`;
        
        console.log(`%c[DEBUG] Enviando a: ${url}`, 'color: #007bff; font-weight: bold;');

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(async res => {
            const text = await res.text(); // Leemos como texto primero para ver errores de PHP
            console.log(`%c[DEBUG] Respuesta del servidor:`, 'color: #28a745; font-weight: bold;', text);
            
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('%c[ERROR] El servidor no envió un JSON válido.', 'color: #dc3545; font-weight: bold;');
                throw new Error("El servidor devolvió una respuesta inesperada (posible error 500).");
            }
        })
        .then(data => {
            if (data.status === 'success') {
                Swal.fire('¡Éxito!', successMsg, 'success').then(() => {
                    if (endpoint !== 'updatePassword') window.location.reload();
                });
                if (endpoint === 'updatePassword') {
                    bootstrap.Modal.getInstance(document.getElementById('modalSecurityPass')).hide();
                }
            } else {
                Swal.fire('Atención', data.message || 'Error en la operación', 'error');
            }
        })
        .catch(err => {
            console.error('%c[FATAL] Error de comunicación:', 'color: #dc3545;', err);
            Swal.fire('Error', 'Error de comunicación con el servidor. Revisa la consola (F12).', 'error');
        });
    },

    /**
     * ACCIÓN: ACTUALIZAR CREDENCIALES
     */
    openResetModal: function(id, email) {
        document.getElementById('security_uid').value = id;
        document.getElementById('security_uemail_hidden').value = email;
        document.getElementById('security_email_display').innerText = email;
        document.getElementById('new_password_input').value = '';
        
        const modalEl = document.getElementById('modalSecurityPass');
        const myModal = new bootstrap.Modal(modalEl);
        myModal.show();
    },

    saveNewPassword: function() {
        const id = document.getElementById('security_uid').value;
        const email = document.getElementById('security_uemail_hidden').value;
        const pass = document.getElementById('new_password_input').value;

        if (pass.length < 4) {
            Swal.fire('Error', 'La clave debe tener al menos 4 caracteres.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('user_id', id);
        formData.append('user_email', email);
        formData.append('password', pass);

        this._request('updatePassword', formData, 'Credenciales actualizadas correctamente.');
    },

    /**
     * ACCIÓN: ACTIVAR / INACTIVAR
     */
    toggleStatus: function(id, email, newStatus) {
        const action = (newStatus === 'ACTIVE') ? 'Activar' : 'Inactivar';
        const color = (newStatus === 'ACTIVE') ? '#28a745' : '#f39c12';

        Swal.fire({
            title: `¿${action} acceso?`,
            text: `El usuario ${email} cambiará su estado a ${newStatus}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: color,
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Sí, ${action}`,
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('user_id', id);
                formData.append('user_email', email);
                formData.append('status', newStatus);

                this._request('updateStatus', formData, 'Estado de acceso actualizado.');
            }
        });
    },

    /**
     * ACCIÓN: ELIMINAR (FÍSICO)
     */
    deletePhysical: function(id, email) {
        Swal.fire({
            title: '¿Desea Eliminar este usuario?', // Título simplificado solicitado
            text: 'Esta es la eliminacion completa de este usuario esta accion es irreversible', // Texto exacto solicitado
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, Eliminar permanentemente',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('user_id', id);

                this._request('deletePhysical', formData, 'El usuario ha sido removido de la base de datos.');
            }
        });
    }
};