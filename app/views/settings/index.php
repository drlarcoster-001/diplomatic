<?php
/**
 * MÓDULO: CONFIGURACIÓN GLOBAL
 * Archivo: app/views/settings/index.php
 * Propósito: Panel central con 9 módulos de configuración (8 originales + 1 nuevo de WordPress).
 */
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="/diplomatic/public/assets/css/settings_panel.css">

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

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/settings/eventos" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item">
                    <div class="bg-dark bg-opacity-10 text-dark p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-terminal-fill fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Eventos y Auditoría</h5>
                    <p class="text-muted small">Logs de actividad y trazabilidad del sistema.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/UserSecurity" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item border-security-hover">
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-person-lock fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Seguridad de Usuarios</h5>
                    <p class="text-muted small">Gestión de claves inmediatas y reactivación de cuentas.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="<?= htmlspecialchars($basePath) ?>/settings/wordpress" class="text-decoration-none text-dark">
                <div class="card h-100 border-0 shadow-sm p-4 text-center settings-card-item">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mx-auto mb-3" style="width: fit-content;">
                        <i class="bi bi-wordpress fs-2"></i>
                    </div>
                    <h5 class="fw-bold">WordPress</h5>
                    <p class="text-muted small">Sincronización de profesores y entradas web.</p>
                </div>
            </a>
        </div>

    </div>
</div>