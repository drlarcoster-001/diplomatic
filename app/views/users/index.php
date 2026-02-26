<?php
/**
 * MÓDULO: USUARIOS
 * Archivo: app/views/users/index.php
 * Propósito: Interfaz de gestión simplificada. Se elimina la columna 'Estado' del grid para mayor claridad visual.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>
<style>
    .avatar-circle { width: 40px; height: 40px; background: #e9ecef; color: #0d6efd; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; border: 1px solid #dee2e6; text-transform: uppercase; flex-shrink: 0; }
    .bg-light-custom { background-color: #f8f9fa; border: 1px solid #e9ecef; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 fw-bold mb-0">Gestión de Usuarios</h2>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
        <i class="bi bi-person-plus-fill me-2"></i>Nuevo Usuario
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Usuario</th>
                        <th>ID / Teléfono</th>
                        <th>Perfil Académico</th>
                        <th>Rol</th> <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <?php if (!empty($u['avatar']) && $u['avatar'] !== 'default_avatar.png'): ?>
                                    <img src="<?= $basePath ?>/assets/img/avatars/<?= htmlspecialchars($u['avatar']) ?>" class="rounded-circle border" width="40" height="40" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="avatar-circle"><?= strtoupper(substr($u['first_name'] ?? 'U', 0, 1) . substr($u['last_name'] ?? 'N', 0, 1)) ?></div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($u['email']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($u['document_id'] ?? 'S/D') ?></div>
                            <small class="text-muted"><?= htmlspecialchars($u['phone'] ?? '') ?></small>
                        </td>
                        <td>
                            <div class="small fw-bold text-primary"><?= htmlspecialchars($u['undergraduate_degree'] ?? 'N/A') ?></div>
                            <small class="text-muted"><?= htmlspecialchars($u['provenance'] ?? '') ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($u['role']) ?></span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-white border" title="Editar" onclick='editUser(<?= json_encode($u) ?>)'>
                                    <i class="bi bi-pencil text-primary"></i>
                                </button>
                                <button class="btn btn-sm btn-white border" title="Desactivar" onclick="deleteUser(<?= $u['id'] ?>)">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="userForm" action="<?= $basePath ?>/users/save" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <div class="modal-header bg-white border-bottom-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <input type="hidden" name="id" id="userId">
                <input type="hidden" name="current_avatar" id="currentAvatar">
                
                <div class="row g-3">
                    <div class="col-12 text-center mb-3">
                        <div class="position-relative d-inline-block">
                            <img id="avatarPreview" src="<?= $basePath ?>/assets/img/avatars/default_avatar.png" class="rounded-circle border shadow-sm" width="100" height="100" style="object-fit: cover;">
                            <label for="avatarInput" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle shadow">
                                <i class="bi bi-camera-fill"></i>
                            </label>
                        </div>
                        <input type="file" name="avatar" id="avatarInput" class="d-none" accept="image/*">
                    </div>

                    <div class="col-md-4"><label class="form-label fw-semibold small">Nombres</label><input type="text" name="first_name" id="firstName" class="form-control bg-light-custom" required></div>
                    <div class="col-md-4"><label class="form-label fw-semibold small">Apellidos</label><input type="text" name="last_name" id="lastName" class="form-control bg-light-custom" required></div>
                    <div class="col-md-4"><label class="form-label fw-semibold small">Cédula / ID</label><input type="text" name="document_id" id="documentId" class="form-control bg-light-custom" required></div>

                    <div class="col-md-6"><label class="form-label fw-semibold small">Email</label><input type="email" name="email" id="email" class="form-control bg-light-custom" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Teléfono</label><input type="text" name="phone" id="phone" class="form-control bg-light-custom"></div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Tipo</label>
                        <select name="user_type" id="userType" class="form-select bg-light-custom">
                            <option value="INTERNAL">INTERNO</option>
                            <option value="PARTICIPANT">PARTICIPANTE</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Rol</label>
                        <select name="role" id="role" class="form-select bg-light-custom" required>
                            <option value="" disabled selected>Seleccionar...</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= htmlspecialchars($r['role_key']) ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Estado</label>
                        <select name="status" id="status" class="form-select bg-light-custom">
                            <option value="ACTIVE">ACTIVO</option>
                            <option value="SUSPENDED">SUSPENDIDO</option>
                            <option value="INACTIVE" disabled>INACTIVO (Solo eliminación)</option>
                        </select>
                    </div>

                    <div class="col-12" id="passContainer"><label class="form-label fw-semibold small">Contraseña</label><input type="password" name="password" id="password" class="form-control bg-light-custom"></div>
                    
                    <div class="col-md-6"><label class="form-label fw-semibold small">Procedencia</label><input type="text" name="provenance" id="provenance" class="form-control bg-light-custom"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Carrera de Pregrado</label><input type="text" name="undergraduate_degree" id="undergraduateDegree" class="form-control bg-light-custom"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Dirección Detallada</label><textarea name="address" id="address" class="form-control bg-light-custom" rows="2"></textarea></div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary px-4 shadow-sm">Guardar Usuario</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script> const BASE_PATH = '<?= $basePath ?>'; </script>
<script src="<?= $basePath ?>/assets/js/users.js?v=<?= time() ?>"></script>