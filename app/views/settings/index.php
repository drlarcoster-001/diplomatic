<?php
/**
 * MÓDULO: CONFIGURACIÓN
 * Archivo: app/views/settings/index.php
 * Propósito: Menú principal de ajustes con las 4 opciones originales restauradas.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Panel de Configuración</h2>
        <p class="text-muted small">Administra los parámetros globales del sistema Diplomatic.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <a href="<?= htmlspecialchars($basePath) ?>/settings/correo" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-envelope-check fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Correo / SMTP</h5>
                    <p class="text-muted small">Configura servidores, puertos SSL y plantillas de inscripción.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm p-4 text-center opacity-75">
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                    <i class="bi bi-cpu fs-2"></i>
                </div>
                <h5 class="fw-bold">Sistema</h5>
                <p class="text-muted small">Nombre del sitio, logo, zona horaria y mantenimientos.</p>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm p-4 text-center opacity-75">
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                    <i class="bi bi-shield-lock fs-2"></i>
                </div>
                <h5 class="fw-bold">Seguridad</h5>
                <p class="text-muted small">Logs de acceso, intentos fallidos y políticas de contraseña.</p>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm p-4 text-center opacity-75">
                <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                    <i class="bi bi-database-gear fs-2"></i>
                </div>
                <h5 class="fw-bold">Base de Datos</h5>
                <p class="text-muted small">Backups manuales, optimización de tablas y exportación.</p>
            </div>
        </div>
    </div>
</div>

<style>
.settings-card-item { transition: all 0.3s ease; border: 1px solid transparent !important; }
.settings-card-item:hover { 
    transform: translateY(-8px); 
    border: 1px solid #0d6efd !important; 
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; 
}
</style>