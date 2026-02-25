<?php
/**
 * MÓDULO: CONFIGURACIÓN GLOBAL
 * Archivo: app/views/settings/wordpress.php
 * Propósito: Interfaz de integración con WordPress vía JWT y grids de contenido.
 */
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark"><i class="bi bi-wordpress text-primary me-2"></i> Integración WordPress</h2>
            <p class="text-muted small">Configuración de seguridad vía JWT para sincronización de datos.</p>
        </div>
        <a href="/diplomatic/public/settings" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 pt-3 px-4">
            <ul class="nav nav-tabs fw-bold" id="wpTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-config" type="button"><i class="bi bi-shield-lock-fill me-2"></i> Conexión</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-profesores" type="button"><i class="bi bi-people-fill me-2"></i> Profesores</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-noticias" type="button"><i class="bi bi-newspaper me-2"></i> Noticias</button></li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content">
                
                <div class="tab-pane fade show active" id="tab-config">
                    <form id="formWpConfig" action="/diplomatic/public/settings/wordpress/save" method="POST" class="row g-4">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-uppercase">URL del WordPress</label>
                            <input type="url" name="wp_url" id="wp_url" class="form-control" placeholder="https://tuweb.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase">Usuario Administrador</label>
                            <input type="text" name="wp_user" id="wp_user" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase">Contraseña</label>
                            <input type="password" name="wp_pass" id="wp_pass" class="form-control" required>
                        </div>
                        <div class="col-12 text-end pt-3">
                            <button type="button" id="btnTestConn" class="btn btn-outline-primary rounded-pill px-4 me-2 shadow-sm">
                                <i class="bi bi-lightning-charge me-1"></i> Probar Conexión
                            </button>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 shadow">Guardar Cambios</button>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade" id="tab-profesores">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border rounded">
                            <thead class="bg-light small fw-bold">
                                <tr><th>Nombre</th><th>Tipo</th><th class="text-end">Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($profesores as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['full_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $p['professor_type'] ?></span></td>
                                    <td class="text-end">
                                        <form action="/diplomatic/public/settings/wordpress/sync-prof" method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">Subir</button>
                                        </form>
                                        <form action="/diplomatic/public/settings/wordpress/unsync-prof" method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3 ms-1">Quitar</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-noticias">
                    <div class="text-center py-5">
                        <i class="bi bi-newspaper fs-1 text-muted"></i>
                        <h5 class="fw-bold mt-2">Módulo en Desarrollo</h5>
                        <p class="text-muted small">Próximamente integración con el blog de noticias.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('dev')) {
        Swal.fire({ title: 'Aviso', text: 'Esta función se activará tras validar la conexión JWT.', icon: 'info', confirmButtonColor: '#4e73df' });
    }
    if (urlParams.has('updated')) {
        Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'Parámetros actualizados.', confirmButtonColor: '#4e73df' });
    }

    document.getElementById('btnTestConn').addEventListener('click', function() {
        const btn = this;
        const url = document.getElementById('wp_url').value;
        const user = document.getElementById('wp_user').value;
        const pass = document.getElementById('wp_pass').value;

        if(!url || !user || !pass) {
            Swal.fire('Atención', 'Ingresa usuario y contraseña para la prueba.', 'warning');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Conectando...';

        const data = new FormData();
        data.append('wp_url', url);
        data.append('wp_user', user);
        data.append('wp_pass', pass); 

        fetch('/diplomatic/public/settings/wordpress/test', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if(res.ok) {
                Swal.fire('¡Conexión Exitosa!', 'Token JWT obtenido correctamente.', 'success');
            } else {
                Swal.fire('Error', 'Fallo de autenticación: ' + res.message, 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'Fallo crítico al contactar al servidor.', 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i> Probar Conexión';
        });
    });
});
</script>