<?php
/**
 * MÓDULO: USUARIOS
 * Archivo: app/views/users/index.php
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$roles = $roles ?? [];
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

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-7">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="search-text" class="form-control border-start-0" placeholder="Buscar por nombre, correo, cédula o teléfono...">
                </div>
            </div>
            <div class="col-md-5 text-end">
                <button class="btn btn-outline-secondary btn-sm me-2 fw-bold" id="btn-clear">
                    <i class="bi bi-eraser me-1"></i>Limpiar
                </button>
                <button class="btn btn-dark btn-sm px-4 fw-bold" id="btn-search">
                    <i class="bi bi-funnel me-1"></i>Buscar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-users">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Usuario</th>
                        <th>ID / Teléfono</th>
                        <th>Perfil Académico</th>
                        <th>Rol</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-top py-2 d-flex justify-content-between align-items-center" id="pagination-container"></div>
</div>

<!-- Modal WhatsApp -->
<div class="modal fade" id="modalWhatsapp" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-whatsapp me-2"></i>Enviar WhatsApp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-1">Para: <strong id="wa-nombre"></strong></p>
                <p class="small text-muted mb-3">Teléfono: <span id="wa-telefono" class="font-monospace"></span></p>
                <label class="form-label small fw-bold">Mensaje personalizado</label>
                <textarea id="wa-mensaje" class="form-control" rows="4" placeholder="Escribe el mensaje aquí..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-sm px-4 fw-bold" id="btn-wa-send">
                    <i class="bi bi-whatsapp me-1"></i> Abrir WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Usuario -->
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