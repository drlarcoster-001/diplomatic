/**
 * MÓDULO: SEGURIDAD DE USUARIOS
 * Archivo: public/assets/js/user_security.js
 */

const UserSecurity = {
    // Abre el modal de password usando Bootstrap nativo o jQuery
    openResetModal: function(id, email) {
        document.getElementById('security_uid').value = id;
        document.getElementById('security_uemail_hidden').value = email;
        document.getElementById('security_email_display').innerText = email;
        document.getElementById('new_password_input').value = '';
        
        // Forzar apertura de modal
        var myModal = new bootstrap.Modal(document.getElementById('modalSecurityPass'));
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

        Swal.fire({
            title: '¿Confirmar cambio?',
            text: `Se modificará el acceso para ${email}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('user_id', id);
                formData.append('user_email', email);
                formData.append('password', pass);

                fetch('/diplomatic/public/UserSecurityController/updatePassword', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire('¡Éxito!', 'Contraseña actualizada.', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('modalSecurityPass')).hide();
                    }
                });
            }
        });
    },

    toggleStatus: function(id, email, newStatus) {
        const action = (newStatus === 'ACTIVE') ? 'Reactivar' : 'Inactivar';
        const color = (newStatus === 'ACTIVE') ? '#28a745' : '#d33';

        Swal.fire({
            title: `¿${action} usuario?`,
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

                fetch('/diplomatic/public/UserSecurityController/updateStatus', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire({
                            title: '¡Actualizado!',
                            text: data.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                });
            }
        });
    }
};