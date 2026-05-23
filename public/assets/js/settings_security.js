/**
 * MÓDULO: CONFIGURACIÓN / SEGURIDAD
 * ARCHIVO: public/assets/js/settings_security.js
 * PROPÓSITO: Gestión de pre-usuarios y tokens vencidos.
 * VERSIÓN: 1.0.0
 */

document.addEventListener('DOMContentLoaded', () => {

    loadPreUsers();

    document.getElementById('btn-search').addEventListener('click', loadPreUsers);

    document.getElementById('btn-clear-filters').addEventListener('click', () => {
        document.getElementById('filter-text').value = '';
        document.getElementById('filter-status').value = '';
        loadPreUsers();
    });

    document.getElementById('filter-text').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') loadPreUsers();
    });

    document.getElementById('btn-clean-expired').addEventListener('click', cleanExpiredTokens);
});

async function loadPreUsers() {
    const tbody = document.querySelector('#table-pre-users tbody');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-5"><div class="spinner-border text-warning" role="status"></div></td></tr>';

    const text   = encodeURIComponent(document.getElementById('filter-text').value.trim());
    const status = encodeURIComponent(document.getElementById('filter-status').value);

    try {
        const res = await fetch(`${BASE_PATH}/settings/seguridad/getPreUsers?text=${text}&status=${status}`);
        const r   = await res.json();

        if (!r.ok || !r.data.length) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-5 text-muted">No se encontraron registros.</td></tr>';
            return;
        }

        let html = '';
        r.data.forEach((row, i) => {
            const statusBadge = {
                'PENDING':  '<span class="badge bg-warning text-dark">Pendiente</span>',
                'VERIFIED': '<span class="badge bg-success">Verificado</span>',
                'EXPIRED':  '<span class="badge bg-secondary">Expirado</span>',
                'BLOCKED':  '<span class="badge bg-danger">Bloqueado</span>',
            }[row.status] || row.status;

            const tokenBadge = row.token_used_at
                ? '<span class="badge bg-success">Usado</span>'
                : (parseInt(row.token_expired)
                    ? '<span class="badge bg-danger">Vencido</span>'
                    : '<span class="badge bg-info text-dark">Activo</span>');

            const userBadge = parseInt(row.is_registered_user)
                ? `<span class="badge bg-primary">Usuario #${row.user_id}</span>`
                : '<span class="badge bg-light text-muted border">No registrado</span>';

            const fecha = row.created_at ? row.created_at.substring(0, 10) : '-';

            const btnEliminar = !parseInt(row.is_registered_user)
                ? `<button class="btn btn-sm btn-outline-danger rounded-pill" onclick="deletePreUser(${row.id}, '${row.first_name} ${row.last_name}')">
                       <i class="bi bi-trash3"></i>
                   </button>`
                : '<span class="text-muted small">—</span>';

            html += `
            <tr class="align-middle">
                <td class="ps-4 text-muted small">#${i + 1}</td>
                <td class="fw-bold">${row.first_name} ${row.last_name}</td>
                <td class="small text-muted">${row.email}</td>
                <td class="font-monospace small">${row.document_id || '-'}</td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">${tokenBadge}</td>
                <td class="text-center">${userBadge}</td>
                <td class="small text-muted">${fecha}</td>
                <td class="text-center pe-4">${btnEliminar}</td>
            </tr>`;
        });

        tbody.innerHTML = html;

    } catch(e) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
    }
}

async function deletePreUser(id, nombre) {
    const conf = await Swal.fire({
        title: '¿Eliminar pre-usuario?',
        html: `Se eliminará a <b>${nombre}</b> y sus tokens. Podrá volver a registrarse con el mismo correo.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!conf.isConfirmed) return;

    try {
        const fd = new FormData();
        fd.append('pre_user_id', id);
        const res = await fetch(`${BASE_PATH}/settings/seguridad/deletePreUser`, { method: 'POST', body: fd });
        const r   = await res.json();

        if (r.ok) {
            await Swal.fire({ icon: 'success', title: '¡Eliminado!', text: r.message, timer: 2000, showConfirmButton: false });
            loadPreUsers();
        } else {
            Swal.fire('Error', r.message, 'error');
        }
    } catch(e) {
        Swal.fire('Error', 'Fallo de red.', 'error');
    }
}

async function cleanExpiredTokens() {
    const conf = await Swal.fire({
        title: '¿Limpiar tokens vencidos?',
        html: 'Se eliminarán todos los tokens vencidos no usados y los pre-usuarios <b>PENDING</b> que nunca activaron su cuenta.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
    });

    if (!conf.isConfirmed) return;

    Swal.fire({ title: 'Limpiando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const res = await fetch(`${BASE_PATH}/settings/seguridad/cleanExpiredTokens`, { method: 'POST' });
        const r   = await res.json();

        if (r.ok) {
            await Swal.fire({
                icon: 'success',
                title: '¡Limpieza Completada!',
                html: `<b>${r.data.tokens_eliminados}</b> tokens vencidos eliminados.<br>
                       <b>${r.data.pre_users_eliminados}</b> pre-usuarios sin activar eliminados.`
            });
            location.reload();
        } else {
            Swal.fire('Error', r.message, 'error');
        }
    } catch(e) {
        Swal.fire('Error', 'Fallo de red.', 'error');
    }
}