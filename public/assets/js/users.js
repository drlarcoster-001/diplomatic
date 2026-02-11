/**
 * MÓDULO: USUARIOS
 * Archivo: public/assets/js/users.js
 * Propósito: Gestión de lógica de cliente y UI dinámica.
 * Soporta la lógica de iniciales automáticas y previsualización de Avatar.
 */

document.addEventListener('DOMContentLoaded', function() {
    const userForm = document.getElementById('userForm');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const modalElement = document.getElementById('userModal');
    
    let bModal = null;
    if (modalElement) {
        bModal = bootstrap.Modal.getOrCreateInstance(modalElement);
    }

    // Previsualización de imagen al cargar archivo
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                    avatarPreview.classList.remove('d-none');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // Envío del formulario vía Fetch
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(userForm);
            const submitBtn = userForm.querySelector('button[type="submit"]');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';

            fetch(userForm.action, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    Swal.fire({ icon: 'success', title: data.msg, timer: 1500, showConfirmButton: false })
                    .then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.msg, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Guardar Usuario';
                }
            })
            .catch(err => {
                console.error("Error:", err);
                Swal.fire('Error', 'No se pudo procesar la solicitud', 'error');
                submitBtn.disabled = false;
            });
        });
    }
});

/**
 * Limpia el formulario y resetea la vista previa del avatar.
 */
function resetForm() {
    const form = document.getElementById('userForm');
    if (form) form.reset();

    document.getElementById('userId').value = '';
    document.getElementById('currentAvatar').value = 'default_avatar.png';
    document.getElementById('modalTitle').innerText = 'Nuevo Usuario';
    
    const preview = document.getElementById('avatarPreview');
    if (preview) {
        preview.src = BASE_PATH + '/assets/img/avatars/default_avatar.png';
    }
    
    if (document.getElementById('passContainer')) {
        document.getElementById('passContainer').style.display = 'block';
        document.getElementById('password').required = true;
    }
}

/**
 * Carga los datos del usuario en el modal, incluyendo los campos nuevos.
 */
function editUser(u) {
    resetForm();
    
    document.getElementById('modalTitle').innerText = 'Editar Usuario';
    document.getElementById('userId').value = u.id;
    document.getElementById('firstName').value = u.first_name || '';
    document.getElementById('lastName').value = u.last_name || '';
    document.getElementById('email').value = u.email || '';
    document.getElementById('documentId').value = u.document_id || '';
    document.getElementById('phone').value = u.phone || '';
    document.getElementById('userType').value = u.user_type || 'INTERNAL';
    document.getElementById('role').value = u.role || 'PARTICIPANT';
    document.getElementById('status').value = u.status || 'ACTIVE';
    document.getElementById('provenance').value = u.provenance || '';
    document.getElementById('undergraduateDegree').value = u.undergraduate_degree || '';
    document.getElementById('address').value = u.address || '';
    document.getElementById('currentAvatar').value = u.avatar || 'default_avatar.png';

    // Lógica para la previsualización del avatar en el modal
    const preview = document.getElementById('avatarPreview');
    if (preview) {
        const imgPath = (u.avatar && u.avatar !== 'default_avatar.png') 
                        ? '/assets/img/avatars/' + u.avatar 
                        : '/assets/img/avatars/default_avatar.png';
        preview.src = BASE_PATH + imgPath;
    }

    // Ocultar contraseña en edición
    if (document.getElementById('passContainer')) {
        document.getElementById('passContainer').style.display = 'none';
        document.getElementById('password').required = false;
    }

    const modalEl = document.getElementById('userModal');
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

/**
 * Confirmación de eliminación.
 */
function deleteUser(id) {
    Swal.fire({
        title: '¿Confirmar eliminación?',
        text: "El usuario será marcado como INACTIVO.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', id);
            fetch(BASE_PATH + '/users/delete', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.ok) window.location.reload();
                else Swal.fire('Error', data.msg, 'error');
            });
        }
    });
}