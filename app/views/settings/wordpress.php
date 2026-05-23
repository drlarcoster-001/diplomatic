<?php
/**
 * MÓDULO: CONFIGURACIÓN GLOBAL / INTEGRACIÓN
 * ARCHIVO: app/views/settings/wordpress.php
 * PROPÓSITO: Interfaz para configurar parámetros del Bridge y panel de Sincronización de Profesores.
 * VERSIÓN: 1.5.0 - Incorporación de Grid de Sincronización de Profesores (UI).
 */
declare(strict_types=1);
$basePath = '/diplomatic/public';
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/settings_wordpress.css?v=<?= time() ?>">

<div class="container-fluid py-4 animate__animated animate__fadeIn">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <i class="bi bi-wordpress text-primary me-2"></i> Integración WordPress
            </h2>
            <p class="text-muted small">Configuración de seguridad y sincronización de datos web.</p>
        </div>
        <a href="<?= $basePath ?>/settings" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="mb-4">
                <h5 class="fw-bold"><i class="bi bi-shield-lock-fill text-primary me-2"></i> Parámetros de Conexión</h5>
                <hr class="text-muted opacity-25">
            </div>

            <form id="formWpConfig" action="<?= $basePath ?>/settings/wordpress/save" method="POST" class="row g-4">
                <div class="col-md-12">
                    <label class="form-label small fw-bold text-uppercase text-muted">URL del WordPress</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-link-45deg"></i></span>
                        <input type="url" name="wp_url" id="wp_url" class="form-control border-start-0" 
                               value="<?= htmlspecialchars($config['wp_url'] ?? '') ?>" 
                               placeholder="https://www.plataformadiplomados.com" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-uppercase text-muted">Usuario Administrador</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-fill"></i></span>
                        <input type="text" name="wp_user" id="wp_user" class="form-control border-start-0" 
                               value="<?= htmlspecialchars($config['wp_user'] ?? '') ?>" 
                               placeholder="admin_web" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-uppercase text-muted">Contraseña / Token Seguro</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-key-fill"></i></span>
                        <input type="password" name="wp_pass" id="wp_pass" class="form-control border-start-0" 
                               value="<?= htmlspecialchars($config['wp_pass'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="col-12 text-end pt-3">
                    <button type="button" id="btnTestConn" class="btn btn-outline-primary rounded-pill px-4 me-2 shadow-sm">
                        <i class="bi bi-lightning-charge me-1"></i> Probar Conexión
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow">
                        <i class="bi bi-save me-1"></i> Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-people-fill text-success me-2"></i> Sincronización de Profesores</h5>
                <button type="button" id="btnTestPush" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-sm">
                    <i class="bi bi-send-check me-1"></i> Probar Inyección (Test Push)
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Profesor</th>
                            <th>Especialidad</th>
                            <th>Estado Web</th>
                            <th class="text-center">Sincronización WP</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($profesores)): ?>
                            <?php foreach ($profesores as $p): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="bi bi-person-circle fs-3 text-secondary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($p['web_label'] ?? 'Sin cargo asignado') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['specialty'] ?? 'N/A') ?></span></td>
                                    <td>
                                        <?php if ($p['is_ready']): ?>
                                            <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i> Listo</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning"><i class="bi bi-exclamation-circle me-1"></i> Incompleto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($p['wp_post_id']): ?>
                                            <span class="badge bg-primary"><i class="bi bi-wordpress me-1"></i> ID: <?= $p['wp_post_id'] ?></span>
                                            <div class="small text-muted mt-1" style="font-size: 0.75rem;">Última: <?= date('d/m/Y', strtotime($p['last_sync'])) ?></div>
                                        <?php else: ?>
                                            <span class="badge bg-secondary text-light">No sincronizado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-sync-prof" 
                                                data-id="<?= $p['id'] ?>" 
                                                <?= !$p['is_ready'] ? 'disabled title="Complete el perfil web primero"' : '' ?>>
                                            <i class="bi bi-arrow-repeat"></i> Sincronizar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No hay profesores activos en el sistema.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/settings_wordpress.js?v=<?= time() ?>"></script>