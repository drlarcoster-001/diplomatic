<?php
/**
 * MÓDULO: PERFIL DE USUARIO
 * Archivo: app/views/users/profile.php
 * Propósito: Interfaz para que el usuario gestione su información profesional.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body text-center py-5">
                    <div class="mx-auto mb-3 bg-primary text-white d-flex align-items-center justify-content-center rounded-circle shadow-sm" style="width: 100px; height: 100px; font-size: 2.5rem; font-weight: bold;">
                        <?= strtoupper(substr($u['first_name'] ?? 'U', 0, 1) . substr($u['last_name'] ?? '', 0, 1)) ?>
                    </div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></h4>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($u['email']) ?></p>
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                        <i class="bi bi-shield-check me-1 text-primary"></i> <?= strtoupper($u['role']) ?>
                    </span>
                </div>
                <div class="card-footer bg-light border-0 p-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Estado de Cuenta:</span>
                        <span class="badge bg-success">Activo</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Perfil Completo:</span>
                        <?php if ($u['profile_complete']): ?>
                            <span class="text-success small fw-bold"><i class="bi bi-check-all"></i> Sí</span>
                        <?php else: ?>
                            <span class="text-danger small fw-bold"><i class="bi bi-exclamation-circle"></i> Pendiente</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 py-4 px-4">
                    <h5 class="fw-bold mb-0">Información Profesional y Académica</h5>
                    <p class="text-muted small mb-0">Complete estos datos para habilitar su proceso de inscripción.</p>
                </div>
                <div class="card-body p-4">
                    <form id="form-profile-update" data-basepath="<?= $basePath ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Teléfono de Contacto</label>
                                <input type="text" name="telefono" class="form-control bg-light border-0 py-2" value="<?= htmlspecialchars($u['telefono'] ?? '') ?>" placeholder="Ej: +58 412 0000000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Grado Académico</label>
                                <select name="grado_academico" class="form-select bg-light border-0 py-2">
                                    <option value="" disabled <?= empty($u['grado_academico']) ? 'selected' : '' ?>>Seleccione su grado...</option>
                                    <option value="Bachiller" <?= ($u['grado_academico'] ?? '') === 'Bachiller' ? 'selected' : '' ?>>Bachiller</option>
                                    <option value="Técnico" <?= ($u['grado_academico'] ?? '') === 'Técnico' ? 'selected' : '' ?>>Técnico</option>
                                    <option value="Licenciado/Ingeniero" <?= ($u['grado_academico'] ?? '') === 'Licenciado/Ingeniero' ? 'selected' : '' ?>>Licenciado/Ingeniero</option>
                                    <option value="Magister" <?= ($u['grado_academico'] ?? '') === 'Magister' ? 'selected' : '' ?>>Magister</option>
                                    <option value="Doctorado" <?= ($u['grado_academico'] ?? '') === 'Doctorado' ? 'selected' : '' ?>>Doctorado</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small">Especialidad / Profesión</label>
                                <input type="text" name="especialidad" class="form-control bg-light border-0 py-2" value="<?= htmlspecialchars($u['especialidad'] ?? '') ?>" placeholder="Ej: Abogado, Médico Cirujano, Policía...">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small">Dirección de Domicilio</label>
                                <textarea name="direccion" class="form-control bg-light border-0 py-2" rows="3" placeholder="Indique su dirección completa..."><?= htmlspecialchars($u['direccion'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="submit" class="btn btn-dark px-4 fw-bold shadow-sm">
                                <i class="bi bi-save me-2"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('form-profile-update').addEventListener('submit', function(e) {
    e.preventDefault();
    const basePath = this.getAttribute('data-basepath');
    const formData = new FormData(this);

    Swal.fire({
        title: '¿Actualizar perfil?',
        text: "Se guardarán sus datos profesionales.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#000',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${basePath}/profile/update`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    Swal.fire('¡Éxito!', data.msg, 'success').then(() => {
                        location.reload(); // Recargamos para actualizar badges y datos
                    });
                } else {
                    Swal.fire('Error', data.msg, 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Fallo técnico: ' + err, 'error'));
        }
    });
});
</script>