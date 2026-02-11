/**
 * MÓDULO: USUARIOS
 * Archivo: public/assets/js/users.js
 * Propósito: Gestión de lógica de cliente para CRUD de usuarios, carga de archivos y UI.
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. SELECTORES PRINCIPALES
    const userForm = document.getElementById('userForm');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const modalElement = document.getElementById('userModal');
    
    // Inicializamos la instancia de Bootstrap Modal para controlarla mediante JS
    let bModal = null;
    if (modalElement) {
        bModal = bootstrap.Modal.getOrCreateInstance(modalElement);
    }

    // 2. VISTA PREVIA DEL AVATAR (EN TIEMPO REAL)
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                    // Al cargar una nueva imagen, nos aseguramos de que sea visible
                    avatarPreview.classList.remove('d-none');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // 3. ENVÍO DEL FORMULARIO (GUARDAR / ACTUALIZAR)
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Usamos FormData para empaquetar archivos y texto
            const formData = new FormData(userForm);
            const submitBtn = userForm.querySelector('button[type="submit"]');

            // Feedback visual: Bloqueamos el botón
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

            fetch(userForm.action, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Operación Exitosa!',
                        text: data.msg,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        if (bModal) bModal.hide();
                        window.location.reload(); // Recarga para ver cambios y nuevas iniciales/fotos
                    });
                } else {
                    Swal.fire('Error', data.msg, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error("Error en la petición:", err);
                Swal.fire('Error Técnico', 'No se pudo procesar la solicitud en el servidor.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});

/**
 * Prepara el formulario para un nuevo registro.
 */
function resetForm() {
    const form = document.getElementById('userForm');
    if (form) form.reset();

    document.getElementById('userId').value = '';
    document.getElementById('currentAvatar').value = '';
    document.getElementById('modalTitle').innerText = 'Nuevo Usuario';
    
    // Restauramos imagen por defecto
    const preview = document.getElementById('avatarPreview');
    if (preview) preview.src = BASE_PATH + '/assets/img/avatars/default_avatar.png';
    
    // Mostramos campo de contraseña para nuevos usuarios
    const passContainer = document.getElementById('passContainer');
    if (passContainer) {
        passContainer.style.display = 'block';
        document.getElementById('password').setAttribute('required', 'required');
    }
}

/**
 * Carga los datos de un usuario en el modal para edición.
 * Versión Segura (Evita errores de 'null')
 */
function editUser(u) {
    resetForm();
    
    // 1. Elementos Obligatorios (Deben existir en index.php)
    document.getElementById('modalTitle').innerText = 'Editar Usuario';
    document.getElementById('userId').value = u.id;
    document.getElementById('firstName').value = u.first_name || '';
    document.getElementById('lastName').value = u.last_name || '';
    document.getElementById('email').value = u.email || '';
    
    // 2. Elementos Flexibles (Si no existen, el código no se rompe)
    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val || '';
    };

    setVal('documentId', u.cedula || u.document_id);
    setVal('phone', u.phone);
    setVal('provenance', u.provenance);
    setVal('undergraduateDegree', u.undergraduate_degree);
    setVal('address', u.address);
    setVal('role', u.role);
    setVal('currentAvatar', u.avatar);

    // 3. Gestión de la Imagen Preview
    const preview = document.getElementById('avatarPreview');
    if (preview) {
        const imgName = (u.avatar && u.avatar !== 'default_avatar.png') ? u.avatar : 'default_avatar.png';
        preview.src = BASE_PATH + '/assets/img/avatars/' + imgName;
    }

    // 4. Ocultar contraseña en edición
    const passContainer = document.getElementById('passContainer');
    if (passContainer) passContainer.style.display = 'none';
    
    const passInput = document.getElementById('password');
    if (passInput) passInput.required = false;

    // 5. Mostrar Modal
    const modalEl = document.getElementById('userModal');
    if (modalEl) {
        const instance = bootstrap.Modal.getOrCreateInstance(modalEl);
        instance.show();
    }
}

/**
 * Ejecuta la eliminación lógica (Soft Delete) del usuario.
 * @param {number} id - ID del usuario a desactivar.
 */
function deleteUser(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "El usuario será desactivado y no podrá acceder al sistema.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', id);

            fetch(BASE_PATH + '/users/delete', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    Swal.fire('¡Eliminado!', 'El usuario ha sido dado de baja.', 'success')
                    .then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.msg || 'No se pudo completar la acción.', 'error');
                }
            })
            .catch(err => {
                console.error("Error:", err);
                Swal.fire('Error', 'Fallo de conexión con el servidor.', 'error');
            });
        }
    });
}