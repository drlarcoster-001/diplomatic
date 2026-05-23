/**
 * MÓDULO: SEGURIDAD DE USUARIOS
 * Archivo: public/assets/js/user_security.js
 * Propósito: Gestión de credenciales, control de estados y eliminación definitiva.
 */

"use strict";

// --- ESTADO Y PAGINACIÓN ---
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
});

async function loadUsers() {
    const tbody = document.querySelector('#table-users tbody');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></td></tr>';

    try {
        const url = `/diplomatic/public/UserSecurity/getUsers?text=${encodeURIComponent(_searchTerm)}&page=${_currentPage}`;
        const res = await fetch(url);
        const r   = await res.json();

        if (!r.ok || !r.data.length) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-5 text-muted">No se encontraron usuarios.</td></tr>';
            document.getElementById('pagination-container').innerHTML = '';
            return;
        }

        const offset = (_currentPage - 1) * 25;
        let html = '';
        r.data.forEach((u, i) => {
            const fullName = `${u.first_name} ${u.last_name}`;
            const initials = (u.first_name[0] + u.last_name[0]).toUpperCase();
            const avatar   = (u.avatar && u.avatar !== 'default_avatar.png')
                ? `<img src="/diplomatic/public/assets/img/avatars/${u.avatar}" width="35" height="35" class="rounded-circle border object-fit-cover">`
                : `<div class="rounded-circle bg-light d-flex align-items-center justify-content-center border shadow-sm text-primary fw-bold" style="width:35px;height:35px;font-size:0.8rem;">${initials}</div>`;

            const statusBadge = u.status === 'ACTIVE'
                ? '<span class="badge rounded-pill bg-success bg-opacity-10 text-success px-3">ACTIVE</span>'
                : '<span class="badge rounded-pill bg-danger bg-opacity-10 text-danger px-3">INACTIVE</span>';

            const toggleBtn = u.status === 'ACTIVE'
                ? `<button class="btn btn-white btn-sm border" onclick="UserSecurity.toggleStatus(${u.id}, '${u.email}', 'INACTIVE')" title="Inactivar"><i class="bi bi-person-x text-warning"></i></button>`
                : `<button class="btn btn-white btn-sm border" onclick="UserSecurity.toggleStatus(${u.id}, '${u.email}', 'ACTIVE')" title="Activar"><i class="bi bi-person-check text-success"></i></button>`;

            html += `
            <tr>
                <td class="ps-4 text-muted small">#${offset + i + 1}</td>
                <td>${avatar}</td>
                <td class="fw-bold">${fullName}</td>
                <td class="font-monospace small">${u.document_id || '-'}</td>
                <td class="text-muted small">${u.email}</td>
                <td><small>${u.user_type}</small></td>
                <td><span class="badge bg-light text-dark border small">${u.role}</span></td>
                <td>${statusBadge}</td>
                <td class="text-center pe-4">
                    <div class="btn-group">
                        <button class="btn btn-white btn-sm border" onclick="UserSecurity.openResetModal(${u.id}, '${u.email}')" title="Credenciales">
                            <i class="bi bi-shield-lock text-primary"></i>
                        </button>
                        ${toggleBtn}
                        <button class="btn btn-white btn-sm border" onclick="UserSecurity.deletePhysical(${u.id}, '${u.email}')" title="Eliminar">
                            <i class="bi bi-trash text-danger"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        });
        tbody.innerHTML = html;

        renderPagination(r.pagination);

    } catch(e) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
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
             </li>`;
    html += `</ul></nav>`;

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