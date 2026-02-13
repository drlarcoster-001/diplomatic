<?php
/**
 * MÓDULO - app/views/settings/eventos.php
 * Vista de auditoría y monitoreo de eventos de usuario.
 * Presenta la consola de registros con controles de descarga, limpieza y filtrado por tipo de acción.
 */

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/events.css?v=<?= time() ?>">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0 text-dark">
                <i class="bi bi-terminal-fill me-2 text-secondary"></i>Auditoría de Eventos
            </h2>
            <p class="text-muted small mb-0">Trazabilidad completa: Acceso, Creación, Modificación y Eliminación.</p>
        </div>
        <a href="<?= $basePath ?>/settings" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver al Panel
        </a>
    </div>

    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
        <form id="filter-events" class="row g-3 align-items-end" data-basepath="<?= $basePath ?>">
            <div class="col-lg-4 col-md-12">
                <label class="form-label small fw-bold text-secondary">Buscador General</label>
                <input type="text" name="search" class="form-control bg-light" placeholder="Usuario, acción o ID de registro...">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-bold text-secondary">Desde</label>
                <input type="date" name="date_from" class="form-control bg-light">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label small fw-bold text-secondary">Hasta</label>
                <input type="date" name="date_to" class="form-control bg-light">
            </div>
            <div class="col-lg-2 col-md-12">
                <button type="button" class="btn btn-dark w-100 fw-bold shadow-sm" onclick="filterLogs()">
                    <i class="bi bi-funnel me-2"></i>Filtrar
                </button>
            </div>
        </form>
    </div>

    <div class="console-wrapper shadow-lg rounded-4 overflow-hidden border border-dark">
        <div class="console-header d-flex justify-content-between align-items-center px-3 py-2">
            <div class="d-flex gap-2">
                <span class="console-dot dot-red"></span>
                <span class="console-dot dot-yellow"></span>
                <span class="console-dot dot-green"></span>
            </div>
            <div class="console-title fw-mono small text-uppercase opacity-50">Diplomatic_Audit_Log_Monitor</div>
            <div><i class="bi bi-shield-check text-success"></i></div>
        </div>

        <div class="console-body p-4" id="event-logs">
            <?php if (!empty($logs)): ?>
                <?php foreach($logs as $log): 
                    // Lógica de colores por tipo de evento
                    $color = 'success'; // Default: Acceso/Crear
                    if(in_array($log['accion'], ['UPDATE', 'MODIFICAR'])) $color = 'warning';
                    if(in_array($log['accion'], ['DELETE', 'ELIMINAR', 'ERROR'])) $color = 'danger';
                    if($log['accion'] === 'LOGIN') $color = 'info';
                ?>
                    <div class="log-line mb-2">
                        <span class="log-time">[<?= date('H:i:s', strtotime($log['fecha_hora'])) ?>]</span>
                        <span class="log-user text-info">@<?= htmlspecialchars($log['usuario_id'] ?? 'SISTEMA') ?></span>
                        <span class="log-action text-<?= $color ?> fw-bold mx-2">
                            :: <?= strtoupper($log['accion']) ?>
                        </span>
                        <span class="log-desc text-light opacity-75"><?= htmlspecialchars($log['descripcion']) ?></span>
                        <span class="log-module text-secondary small ms-2">{<?= $log['modulo'] ?>}</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-secondary opacity-50"># Terminal lista. Esperando eventos...</div>
            <?php endif; ?>
            <div class="cursor">_</div>
        </div>

        <div class="console-footer d-flex justify-content-end gap-2 p-2 bg-dark border-top border-secondary border-opacity-25">
            <button class="btn btn-outline-light btn-xs fw-mono" onclick="exportAuditLogs()">
                <i class="bi bi-download me-1"></i> DESCARGAR
            </button>
            <button class="btn btn-outline-danger btn-xs fw-mono" onclick="clearConsole()">
                <i class="bi bi-trash3 me-1"></i> LIMPIAR
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= $basePath ?>/assets/js/events.js?v=<?= time() ?>"></script>