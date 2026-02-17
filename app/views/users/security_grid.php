<?php
/**
 * MÓDULO: SEGURIDAD DE USUARIOS
 * Archivo: app/views/users/security_grid.php
 * Propósito: Interfaz de cuadrícula con acciones dinámicas y soporte de iniciales.
 */
?>
<link rel="stylesheet" href="/diplomatic/public/assets/css/user_security.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark"><i class="bi bi-shield-lock-fill me-2"></i>Seguridad de Usuarios</h2>
            <p class="text-muted small">Gestión de credenciales y estados de acceso.</p>
        </div>
        <a href="/diplomatic/public/settings" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 user-security-grid">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Image</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Tipo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): 
                            $fullName = $u['first_name'] . ' ' . $u['last_name'];
                            $initials = strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1));
                        ?>
                        <tr>
                            <td class="ps-4">
                                <?php if (!empty($u['avatar']) && $u['avatar'] !== 'default_avatar.png'): ?>
                                    <img src="/diplomatic/public/assets/img/avatars/<?= $u['avatar'] ?>" width="35" height="35" class="rounded-circle border object-fit-cover">
                                <?php else: ?>
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border shadow-sm text-primary fw-bold" style="width: 35px; height: 35px; font-size: 0.8rem;">
                                        <?= $initials ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?= $fullName ?></td>
                            <td class="text-muted small"><?= $u['email'] ?></td>
                            <td><small><?= $u['user_type'] ?></small></td>
                            <td><span class="badge bg-light text-dark border small"><?= $u['role'] ?></span></td>
                            <td>
                                <span class="badge rounded-pill bg-<?= ($u['status'] === 'ACTIVE') ? 'success' : 'danger' ?> bg-opacity-10 text-<?= ($u['status'] === 'ACTIVE') ? 'success' : 'danger' ?> px-3">
                                    <?= $u['status'] ?>
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group">
                                    <button class="btn btn-white btn-sm border" onclick="UserSecurity.openResetModal(<?= $u['id'] ?>, '<?= $u['email'] ?>')" title="Cambiar Clave">
                                        <i class="bi bi-key text-primary"></i>
                                    </button>
                                    
                                    <?php if($u['status'] === 'ACTIVE'): ?>
                                        <button class="btn btn-white btn-sm border" onclick="UserSecurity.toggleStatus(<?= $u['id'] ?>, '<?= $u['email'] ?>', 'INACTIVE')" title="Inactivar">
                                            <i class="bi bi-person-x-fill text-danger"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-white btn-sm border" onclick="UserSecurity.toggleStatus(<?= $u['id'] ?>, '<?= $u['email'] ?>', 'ACTIVE')" title="Activar">
                                            <i class="bi bi-person-check-fill text-success"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSecurityPass" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title small">ACTUALIZAR CREDENCIALES</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small">Cambiar contraseña para: <br><strong id="security_email_display" class="text-primary"></strong></p>
                <input type="hidden" id="security_uid">
                <input type="hidden" id="security_uemail_hidden">
                <div class="form-group">
                    <label class="small fw-bold">Nueva Clave Inmediata:</label>
                    <input type="password" id="new_password_input" class="form-control" placeholder="****">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-primary btn-sm px-4" onclick="UserSecurity.saveNewPassword()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="/diplomatic/public/assets/js/user_security.js"></script>