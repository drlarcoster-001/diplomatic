/**
 * MÓDULO: USUARIOS
 * Archivo: public/assets/js/users.js
 * VERSIÓN: 2.0.0 - Paginación, búsqueda, WhatsApp y clic en fila.
 */

let _currentPage = 1;
let _searchTerm  = '';

document.addEventListener('DOMContentLoaded', () => {
    loadUsers();

    document.getElementById('btn-search').addEventListener('click', () => {
        _searchTerm  = document.getElementById('search-text').value.trim();
        _currentPage = 1;
        loadUsers();
    });

    document.getElementById('btn-clear').addEventListener('click', () => {
        document.getElementById('search-text').value = '';
        _searchTerm  = '';
        _currentPage = 1;
        loadUsers();
    });

    document.getElementById('search-text').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            _searchTerm  = e.target.value.trim();
            _currentPage = 1;
            loadUsers();
        }
    });

    // Avatar preview en modal
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // Submit del formulario
    const userForm = document.getElementById('userForm');
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(userForm);
            const submitBtn = userForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';

            fetch(userForm.action, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    Swal.fire({ icon: 'success', title: data.msg, timer: 1500, showConfirmButton: false })
                    .then(() => { loadUsers(); bootstrap.Modal.getInstance(document.getElementById('userModal')).hide(); });
                } else {
                    Swal.fire('Error', data.msg, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Guardar Usuario';
                }
            })
            .catch(() => {
                Swal.fire('Error', 'No se pudo procesar la solicitud', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Guardar Usuario';
            });
        });
    }

    // Botón enviar WhatsApp
    document.getElementById('btn-wa-send').addEventListener('click', () => {
        const nombre   = document.getElementById('wa-nombre').textContent;
        const telefono = document.getElementById('wa-telefono').textContent.trim();
        const mensaje  = document.getElementById('wa-mensaje').value.trim();

        if (!mensaje) {
            Swal.fire('Atención', 'Escribe el mensaje personalizado.', 'warning');
            return;
        }

        const telefonoLimpio = telefono.replace(/\D/g, '');
        const textoCompleto = `👋 Buenas, *${nombre}*\n\nNos comunicamos con usted desde la Coordinación de la Plataforma de Diplomados para indicarle que:\n\n_${mensaje}_\n\n✅ Muchas Gracias\n*Coordinación de Diplomados*`;
        const url = `https://web.whatsapp.com/send?phone=${telefonoLimpio}&text=${encodeURIComponent(textoCompleto)}`;

        window.open(url, '_blank');
        bootstrap.Modal.getInstance(document.getElementById('modalWhatsapp')).hide();
    });
});

async function loadUsers() {
    const tbody = document.querySelector('#table-users tbody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>';

    try {
        const url = `${BASE_PATH}/users/getUsers?text=${encodeURIComponent(_searchTerm)}&page=${_currentPage}`;
        const res = await fetch(url);
        const r   = await res.json();

        if (!r.ok || !r.data.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No se encontraron usuarios.</td></tr>';
            document.getElementById('pagination-container').innerHTML = '';
            return;
        }

        const offset = (_currentPage - 1) * 25;
        let html = '';

        r.data.forEach((u, i) => {
            const fullName = `${u.first_name} ${u.last_name}`;
            const initials = ((u.first_name || 'U')[0] + (u.last_name || 'N')[0]).toUpperCase();
            const avatar   = (u.avatar && u.avatar !== 'default_avatar.png')
                ? `<img src="${BASE_PATH}/assets/img/avatars/${u.avatar}" class="rounded-circle border" width="40" height="40" style="object-fit:cover;">`
                : `<div class="avatar-circle">${initials}</div>`;

            const telefono = u.phone || '';
            const waBtn    = telefono
                ? `<button class="btn btn-sm btn-white border" title="WhatsApp" onclick="openWhatsapp(event, '${fullName}', '${telefono}')">
                       <i class="bi bi-whatsapp text-success"></i>
                   </button>`
                : '';

            html += `
            <tr style="cursor:pointer" onclick="editUser(${JSON.stringify(u).replace(/"/g, '&quot;')})">
                <td class="ps-4 text-muted small">#${offset + i + 1}</td>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        ${avatar}
                        <div>
                            <div class="fw-bold">${fullName}</div>
                            <small class="text-muted">${u.email}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div>${u.document_id || 'S/D'}</div>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">${telefono}</small>
                        ${waBtn}
                    </div>
                </td>
                <td>
                    <div class="small fw-bold text-primary">${u.undergraduate_degree || 'N/A'}</div>
                    <small class="text-muted">${u.provenance || ''}</small>
                </td>
                <td><span class="badge bg-light text-dark border">${u.role}</span></td>
                <td class="text-end pe-4" onclick="event.stopPropagation()">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-white border" title="Editar" onclick="editUser(${JSON.stringify(u).replace(/"/g, '&quot;')})">
                            <i class="bi bi-pencil text-primary"></i>
                        </button>
                        <button class="btn btn-sm btn-white border" title="Desactivar" onclick="deleteUser(${u.id})">
                            <i class="bi bi-trash text-danger"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        });

        tbody.innerHTML = html;
        renderPagination(r.pagination);

    } catch(e) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
    }
}

function renderPagination(p) {
    const container = document.getElementById('pagination-container');
    if (!container) return;

    const totalText = `<span class="text-muted small">Total: <b>${p.total_records}</b> usuarios</span>`;

    if (p.total_pages <= 1) {
        container.innerHTML = totalText;
        return;
    }

    let html = `${totalText}<nav><ul class="pagination pagination-sm mb-0">`;
    html += `<li class="page-item ${p.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${p.current_page - 1}">Ant</a>
             </li>`;
    for (let i = 1; i <= p.total_pages; i++) {
        html += `<li class="page-item ${i === p.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                 </li>`;
    }
    html += `<li class="page-item ${p.current_page === p.total_pages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${p.current_page + 1}">Sig</a>
             </li></ul></nav>`;

    container.innerHTML = html;
    container.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const pg = parseInt(e.target.getAttribute('data-page'));
            if (pg && pg !== _currentPage) {
                _currentPage = pg;
                loadUsers();
            }
        });
    });
}

function openWhatsapp(event, nombre, telefono) {
    event.stopPropagation();
    document.getElementById('wa-nombre').textContent    = nombre;
    document.getElementById('wa-telefono').textContent  = telefono;
    document.getElementById('wa-mensaje').value         = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalWhatsapp')).show();
}

function resetForm() {
    const form = document.getElementById('userForm');
    if (form) form.reset();
    document.getElementById('userId').value        = '';
    document.getElementById('currentAvatar').value = 'default_avatar.png';
    document.getElementById('modalTitle').innerText = 'Nuevo Usuario';
    document.getElementById('avatarPreview').src    = BASE_PATH + '/assets/img/avatars/default_avatar.png';
    document.getElementById('passContainer').style.display = 'block';
    document.getElementById('password').required = true;
}

function editUser(u) {
    if (typeof u === 'string') u = JSON.parse(u);
    resetForm();
    document.getElementById('modalTitle').innerText          = 'Editar Usuario';
    document.getElementById('userId').value                  = u.id;
    document.getElementById('firstName').value               = u.first_name || '';
    document.getElementById('lastName').value                = u.last_name || '';
    document.getElementById('email').value                   = u.email || '';
    document.getElementById('documentId').value              = u.document_id || '';
    document.getElementById('phone').value                   = u.phone || '';
    document.getElementById('userType').value                = u.user_type || 'INTERNAL';
    document.getElementById('role').value                    = u.role || 'PARTICIPANT';
    document.getElementById('status').value                  = u.status || 'ACTIVE';
    document.getElementById('provenance').value              = u.provenance || '';
    document.getElementById('undergraduateDegree').value     = u.undergraduate_degree || '';
    document.getElementById('address').value                 = u.address || '';
    document.getElementById('currentAvatar').value           = u.avatar || 'default_avatar.png';
    document.getElementById('avatarPreview').src             = BASE_PATH + '/assets/img/avatars/' + (u.avatar || 'default_avatar.png');
    document.getElementById('passContainer').style.display   = 'none';
    document.getElementById('password').required             = false;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal')).show();
}

function deleteUser(id) {
    Swal.fire({
        title: '¿Confirmar eliminación?',
        text: 'El usuario será marcado como INACTIVO.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);
            fetch(BASE_PATH + '/users/delete', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.ok) loadUsers();
                else Swal.fire('Error', data.msg, 'error');
            });
        }
    });
}