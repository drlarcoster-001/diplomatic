<?php
/**
 * MÓDULO - app/views/settings/index.php
 * Panel central con 6 módulos de configuración.
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="h4 fw-bold mb-0 text-dark">Panel de Configuración</h2>
        <p class="text-muted small">Administra la identidad y parámetros globales de Diplomatic.</p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/settings/empresa" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-building-fill-check fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Datos de Empresa</h5>
                    <p class="text-muted small">Información legal, dirección y contactos.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/settings/whatsapp" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-whatsapp fs-2"></i>
                    </div>
                    <h5 class="fw-bold">WhatsApp</h5>
                    <p class="text-muted small">Configuración de API y mensajes automáticos.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/settings/correo" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item">
                    <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-envelope-at-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Correo / SMTP</h5>
                    <p class="text-muted small">Servidores de envío y plantillas personalizadas.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm p-4 text-center opacity-75 grayscale">
                <div class="bg-secondary bg-opacity-10 text-secondary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                    <i class="bi bi-display fs-2"></i>
                </div>
                <h5 class="fw-bold">Sistema</h5>
                <p class="text-muted small">Zona horaria, mantenimientos y registros.</p>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm p-4 text-center opacity-75 grayscale">
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                    <i class="bi bi-shield-lock-fill fs-2"></i>
                </div>
                <h5 class="fw-bold">Seguridad</h5>
                <p class="text-muted small">Políticas de acceso y auditoría.</p>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm p-4 text-center opacity-75 grayscale">
                <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                    <i class="bi bi-database-fill-gear fs-2"></i>
                </div>
                <h5 class="fw-bold">Base de Datos</h5>
                <p class="text-muted small">Respaldos y optimización.</p>
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
.grayscale { filter: grayscale(1); }
</style>