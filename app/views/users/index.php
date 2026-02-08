<?php
/**
 * MÓDULO: USUARIOS
 * Archivo: app/views/users/index.php
 * Cambio: Oculta campo contraseña al editar.
 */
?>
<style>
    .avatar-initials { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 14px; }
    .table-hover tbody tr:hover { background-color: #f8f9fa; }
</style>

<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Gestión de Usuarios</h3>
            <div class="text-muted small">Administración de personal y participantes.</div>
        </div>
        <button class="btn btn-primary shadow-sm px-4" onclick="openModal()">
            <i class="bi bi-plus-lg me-2"></i>Nuevo Usuario
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="usersTable">
                    <thead class="bg-light border-bottom">
                        <tr class="text-uppercase text-secondary small">
                            <th class="ps-4 py-3">Usuario / Cédula</th>
                            <th>Contacto</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $u): ?>
                            <tr id="row-<?= $u['id'] ?>">
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initials bg-primary text-white me-3">
                                            <?= strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></div>
                                            <div class="text-muted small">CI: <?= htmlspecialchars($u['cedula'] ?? 'S/C') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-dark"><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($u['email']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-whatsapp me-1"></i> <?= htmlspecialchars($u['phone'] ?? '-') ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= $u['role'] ?></span>
                                </td>
                                <td>
                                    <?php if($u['status'] === 'ACTIVE'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-light border me-1" onclick='editUser(<?= json_encode($u) ?>)' title="Editar"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-light border text-danger" onclick="deleteUser(<?= $u['id'] ?>)" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No hay usuarios registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-light border-bottom-0">
        <h5 class="modal-title fw-bold" id="modalTitle">Nuevo Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="userForm" onsubmit="saveUser(event)">
        <div class="modal-body p-4">
            <input type="hidden" id="userId" value="">

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold text-muted">Nombre *</label>
                    <input type="text" id="fname" class="form-control" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold text-muted">Apellido *</label>
                    <input type="text" id="lname" class="form-control" required>
                </div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold text-muted">Cédula *</label>
                    <input type="text" id="cedula" class="form-control" placeholder="V-..." required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold text-muted">Teléfono</label>
                    <input type="text" id="phone" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Correo *</label>
                <input type="email" id="email" class="form-control" required>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold text-muted">Rol *</label>
                    <select id="role" class="form-select" required>
                        <option value="ADMIN">Administrador</option>
                        <option value="ACADEMIC">Académico</option>
                        <option value="FINANCIAL">Financiero</option>
                        <option value="PARTICIPANT">Participante</option>
                    </select>
                </div>
                <div class="col-6" id="passContainer">
                    <label class="form-label small fw-bold text-muted">Contraseña *</label>
                    <input type="password" id="pass" class="form-control" placeholder="••••••">
                </div>
            </div>
            <div id="formError" class="alert alert-danger d-none small"></div>
        </div>
        <div class="modal-footer border-top-0 px-4 pb-4">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" id="btnSave" class="btn btn-primary px-4">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let modal = null;
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('userModal');
    if(el) modal = new bootstrap.Modal(el);
});

function openModal() {
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = ""; 
    document.getElementById('modalTitle').textContent = "Nuevo Usuario";
    
    // MOSTRAR Password al crear
    document.getElementById('passContainer').classList.remove('d-none');
    document.getElementById('pass').required = true;
    
    document.getElementById('formError').classList.add('d-none');
    modal.show();
}

function editUser(u) {
    document.getElementById('userId').value = u.id;
    document.getElementById('fname').value = u.first_name;
    document.getElementById('lname').value = u.last_name;
    document.getElementById('cedula').value = u.cedula || '';
    document.getElementById('phone').value = u.phone || '';
    document.getElementById('email').value = u.email;
    document.getElementById('role').value = u.role;
    
    document.getElementById('modalTitle').textContent = "Editar Usuario";
    
    // OCULTAR Password al editar
    document.getElementById('passContainer').classList.add('d-none');
    document.getElementById('pass').required = false;
    document.getElementById('pass').value = ""; 

    document.getElementById('formError').classList.add('d-none');
    modal.show();
}

async function saveUser(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSave');
    const err = document.getElementById('formError');
    
    const data = {
        id:         document.getElementById('userId').value,
        first_name: document.getElementById('fname').value,
        last_name:  document.getElementById('lname').value,
        cedula:     document.getElementById('cedula').value,
        phone:      document.getElementById('phone').value,
        email:      document.getElementById('email').value,
        role:       document.getElementById('role').value,
        password:   document.getElementById('pass').value
    };

    btn.disabled = true; btn.innerText = "Procesando...";

    try {
        const res = await fetch('/diplomatic/public/users/save', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await res.json();

        if(result.ok) {
            location.reload();
        } else {
            err.textContent = result.msg;
            err.classList.remove('d-none');
        }
    } catch(error) {
        err.textContent = "Error de conexión";
        err.classList.remove('d-none');
    } finally {
        btn.disabled = false; btn.innerText = "Guardar";
    }
}

async function deleteUser(id) {
    if(!confirm('¿Eliminar usuario?')) return;
    try {
        const res = await fetch('/diplomatic/public/users/delete', {
            method: 'POST',
            body: JSON.stringify({id: id})
        });
        const r = await res.json();
        if(r.ok) document.getElementById('row-'+id).remove();
    } catch(e) { alert('Error al eliminar'); }
}
</script>