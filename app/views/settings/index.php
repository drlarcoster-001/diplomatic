<?php
/**
 * MÓDULO: CONFIGURACIÓN
 * Archivo: app/views/settings/index.php
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/settings.css?v=<?= time() ?>">

<div class="mb-4">
    <h2 class="h4 fw-bold mb-0">Configuración del Sistema</h2>
    <p class="text-muted small">Panel administrativo institucional.</p>
</div>

<div class="row g-4 settings-grid-container">
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 settings-card shadow-sm p-4 text-center">
            <div class="settings-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-envelope-at fs-2"></i>
            </div>
            <h5>Correo</h5>
            <p class="small text-muted mb-0">SMTP y notificaciones.</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 settings-card shadow-sm p-4 text-center">
            <div class="settings-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="bi bi-whatsapp fs-2"></i>
            </div>
            <h5>WhatsApp</h5>
            <p class="small text-muted mb-0">API y mensajes.</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100 settings-card shadow-sm p-4 text-center">
            <div class="settings-icon-wrapper bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-grid-3x3-gap fs-2"></i>
            </div>
            <h5>Módulos</h5>
            <p class="small text-muted mb-0">Gestión de componentes.</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3" style="cursor:pointer;">
        <div class="card h-100 settings-card shadow-sm p-4 text-center">
            <div class="settings-icon-wrapper bg-info bg-opacity-10 text-info">
                <i class="bi bi-database-down fs-2"></i>
            </div>
            <h5>Respaldo</h5>
            <p class="small text-muted mb-0">Exportar Base de Datos SQL.</p>
        </div>
    </div>
</div>