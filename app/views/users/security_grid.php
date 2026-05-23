<?php
/**
 * MÓDULO: SEGURIDAD DE USUARIOS
 * Archivo: app/views/users/security_grid.php
 */
$users = $users ?? [];
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
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

    <!-- Filtro de búsqueda -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Buscar por nombre, correo o cédula</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="search-text" placeholder="Ej: Juan, juan@email.com, 12345678...">
                    </div>
                </div>
                <div class="col-md-6 text-end">
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

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 user-security-grid" id="table-users">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Correo</th>
                            <th>Tipo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top py-2 d-flex justify-content-between align-items-center" id="pagination-container">
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
                <p class="small">Establecer nueva contraseña para: <br><strong id="security_email_display" class="text-primary"></strong></p>
                <input type="hidden" id="security_uid">
                <input type="hidden" id="security_uemail_hidden">
                <div class="form-group">
                    <label class="small fw-bold">Nueva Contraseña:</label>
                    <input type="password" id="new_password_input" class="form-control" placeholder="Ingrese la nueva clave">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm px-4" onclick="UserSecurity.saveNewPassword()">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<script src="/diplomatic/public/assets/js/user_security.js"></script>